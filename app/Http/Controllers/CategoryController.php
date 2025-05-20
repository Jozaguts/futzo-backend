<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{

    public function index(): JsonResponse
    {
        return response()->json(Category::select('id', 'name')->get());
    }

    public function store(CategoryStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $category = Category::create($data);
        return response()->json($category, 201);
    }

    public function update(CategoryUpdateRequest $request, $id): void
    {
        $request->only('name', 'gender_id');
    }
}
