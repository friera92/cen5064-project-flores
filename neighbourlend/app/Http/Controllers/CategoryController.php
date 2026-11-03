<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categories) {}

    public function index(): JsonResponse
    {
        return response()->json($this->categories->listCategories());
    }
}
