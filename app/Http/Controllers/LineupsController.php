<?php

namespace App\Http\Controllers;

use App\Http\Requests\LineupStoreRequest;
use App\Http\Requests\LineupUpdateRequest;
class LineupsController extends Controller
{

    public function index()
    {

    }

    public function show($id)
    {

    }

    public function store(LineupStoreRequest $request)
    {
        $request->validated();
    }

    public function update(LineupUpdateRequest $request, $id)
    {

    }

    public function destroy($id)
    {

    }
}
