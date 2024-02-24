<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\Category;

class CategoryController extends Controller
{

    public function index()
    {
        return response()->json(Category::select('id','name')->get());
    }

    public function show($id)
    {

    }

    public function store(CategoryStoreRequest $request)
    {
        $data = $request->validated();
        $category = Category::create($data);
        return response()->json($category, 201);
    }

    public function update(CategoryUpdateRequest $request, $id)
    {
        $request->only('name','gender_id');
    }

    public function destroy($id)
    {

    }
}
