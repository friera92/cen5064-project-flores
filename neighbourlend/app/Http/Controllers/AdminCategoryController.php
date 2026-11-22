<?php

namespace App\Http\Controllers;

use App\Domain\Models\Category;
use App\Domain\Models\Tool;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Category::query()
                ->withCount(['tools' => fn ($query) => $query->withTrashed()])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'data' => $category->loadCount(['tools' => fn ($query) => $query->withTrashed()]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $category = Category::create([
            'name' => trim($data['name']),
            'description' => trim($data['description']),
        ]);

        return response()->json(['data' => $category], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
        ]);

        if (array_key_exists('name', $data)) {
            $data['name'] = trim($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $data['description'] = trim($data['description']);
        }

        $category->update($data);

        return response()->json(['data' => $category->refresh()]);
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            return DB::transaction(function () use ($category): JsonResponse {
                // FK RESTRICT also protects against concurrent tool insertions.
                $category = Category::query()->lockForUpdate()->findOrFail($category->id);

                if (Tool::withTrashed()->where('category_id', $category->id)->exists()) {
                    return response()->json([
                        'message' => 'This category cannot be deleted because it is used by equipment, including historical listings.',
                    ], 409);
                }

                $category->delete();
                return response()->json(null, 204);
            });
        } catch (QueryException $e) {
            // Handle PostgreSQL foreign key violation outside a failed transaction.
            if ($e->getCode() === '23503') {
                return response()->json([
                    'message' => 'This category cannot be deleted because it is used by equipment.',
                ], 409);
            }
            throw $e;
        }
    }
}
