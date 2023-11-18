<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
class CategoryController extends Controller
{

    public function index()
    {

    }

    public function show($id)
    {

    }

    public function store(CategoryStoreRequest $request)
    {
        $request->only('name','gender_id');
    }

    public function update(CategoryUpdateRequest $request, $id)
    {
        $request->only('name','gender_id');
    }

    public function destroy($id)
    {

    }
}
