<?php

// namespace App\Http\Controllers;

// use App\Services\ToolService;
// use App\Http\Requests\ToolRequest;
// use App\Domain\Models\Tool;
// use Illuminate\Http\JsonResponse;
// use Symfony\Component\HttpFoundation\Response;

// class ToolController extends Controller
// {
//     public function __construct(
//         private ToolService $toolService
//     ) {}

//     public function show(ToolRequest $toolRequest): JsonResponse
//     {
//         return response()->json($toolRequest->tool(), Response::HTTP_OK);
//     }

//     public function add(ToolRequest $request): JsonResponse
//     {
//         // 1. Get validated input from ToolRequest
//         $data = $request->validated();

//         // 2. Attach the authenticated user's ID as the owner
//         $data['owner_id'] = $request->user()->id;
//         $tool = $this->toolService->createTool($data);
//         return response()->json([
//             "message" => "Tool created successfully",
//             "tool" => $tool
//         ], Response::HTTP_OK);
//     }

//     public function update(ToolRequest $request, Tool $tool)
//     {
//         $this->authorize('update', $tool);

//         $updatedTool = $this->toolService->updateTool(
//             $tool,
//             $request->validated()
//         );
//         return response()->json([
//             "message" => "The tool was updated successfully",
//             "tool" => $updatedTool
//         ], Response::HTTP_OK);
//     }

//     public function delete(ToolRequest $request, Tool $tool)
//     {
//         $this->authorize('update', $tool);
//         $this->toolService->deleteTool($request->tool());

//         return response()->json([
//             'message' => 'Tool deleted successfully.',
//         ], Response::HTTP_NO_CONTENT);
//     }
// }

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
        // Reuse the project's existing ToolPolicy::update authorization.
        $this->authorize('update', $tool);
        try {
            $updatedTool = $this->toolService->updateTool($tool, $request->validated());
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
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
