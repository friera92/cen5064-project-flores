<?php

namespace App\Http\Controllers;

use App\Services\ToolService;
use App\Http\Requests\ToolRequest;
use App\Domain\Models\Tool;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ToolController extends Controller
{
    public function __construct(
        private ToolService $toolService
    ) {}

    public function show(ToolRequest $toolRequest): JsonResponse
    {
        return response()->json($toolRequest->tool(), Response::HTTP_OK);
    }

    public function add(ToolRequest $request): JsonResponse
    {
        // 1. Get validated input from ToolRequest
        $data = $request->validated();

        // 2. Attach the authenticated user's ID as the owner
        $data['owner_id'] = $request->user()->id;
        $tool = $this->toolService->createTool($data);
        return response()->json([
            "message" => "Tool created successfully",
            "tool" => $tool
        ], Response::HTTP_OK);
    }

    public function update(ToolRequest $request, Tool $tool)
    {
        $this->authorize('update', $tool);

        $updatedTool = $this->toolService->updateTool(
            $tool,
            $request->validated()
        );
        return response()->json([
            "message" => "The tool was updated successfully",
            "tool" => $updatedTool
        ], Response::HTTP_OK);
    }

    public function delete(ToolRequest $request, Tool $tool)
    {
        $this->authorize('update', $tool);
        $this->toolService->deleteTool($request->tool());

        return response()->json([
            'message' => 'Tool deleted successfully.',
        ], Response::HTTP_NO_CONTENT);
    }
}
