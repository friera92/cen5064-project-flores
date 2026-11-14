<?php

namespace App\Http\Controllers;

use App\Domain\Models\Tool;
use DomainException;
use App\Domain\States\ToolCondition;
use App\Domain\States\ToolStatus;
use App\Services\ToolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\ToolRequest;
use Illuminate\Support\Facades\Storage;

class ToolController extends Controller
{
    public function __construct(private ToolService $toolService) {}

    public function index(ToolRequest $request): JsonResponse
    {
        return response()->json($this->toolService->searchTools($this->filters($request)));
    }

    public function show(Tool $tool): JsonResponse
    {
        return response()->json($tool->load(['category:id,name', 'owner:id,name,address']));
    }

    public function mine(ToolRequest $request): JsonResponse
    {
        $filters = $this->filters($request);
        // Do not trust owner_id supplied in request. Always derive it from token.
        $filters['owner_id'] = $request->user()->id;
        return response()->json($this->toolService->searchTools($filters));
    }

    public function store(ToolRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['owner_id'] = $request->user()->id;

        if ($request->hasFile('picture')) {
            $data['picture'] = $request
                ->file('picture')
                ->store('tools', 'public');
        }

        $tool = $this->toolService->createTool($data);

        return response()->json([
            'message' => 'Tool created successfully.',
            'tool' => $tool->load('category'),
        ], Response::HTTP_CREATED);
    }

    public function update(ToolRequest $request, Tool $tool): JsonResponse
    {
        $this->authorize('update', $tool);

        $data = $request->validated();

        $oldPicture = $tool->picture;
        $newPicturePath = null;

        if ($request->hasFile('picture')) {
            $newPicturePath = $request
                ->file('picture')
                ->store('tools', 'public');

            $data['picture'] = $newPicturePath;
        } else {
            // Keep the existing image
            unset($data['picture']);
        }

        try {
            $updatedTool = $this->toolService->updateTool($tool, $data);
        } catch (\Throwable $e) {
            // Avoid leaving orphaned files after a failed update
            if ($newPicturePath !== null) {
                Storage::disk('public')->delete($newPicturePath);
            }

            if ($e instanceof DomainException) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 409);
            }

            throw $e;
        }

        // Remove the previous locally stored image after a successful update
        if (
            $newPicturePath !== null &&
            is_string($oldPicture) &&
            str_starts_with($oldPicture, 'tools/') &&
            $oldPicture !== $newPicturePath
        ) {
            Storage::disk('public')->delete($oldPicture);
        }

        return response()->json([
            'message' => 'Tool updated successfully.',
            'tool' => $updatedTool->load('category'),
        ]);
    }

    public function destroy(ToolRequest $request, Tool $tool): Response|JsonResponse
    {
        // Reuse 'update' ability for backward compatibility with existing ToolPolicy.
        // When ToolPolicy::delete exists, replace 'update' with 'delete'.
        $this->authorize('update', $tool);
        try {
            $this->toolService->deleteTool($tool);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
        return response()->noContent();
    }

    private function filters(ToolRequest $request): array
    {
        return $request->validate([
            'q' => ['sometimes', 'string', 'max:100'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'condition' => ['sometimes', Rule::enum(ToolCondition::class)],
            'availability_status' => ['sometimes', Rule::enum(ToolStatus::class)],
            'min_rate' => ['sometimes', 'numeric', 'min:0'],
            'max_rate' => ['sometimes', 'numeric', 'min:0', 'gte:min_rate'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
    }
}
