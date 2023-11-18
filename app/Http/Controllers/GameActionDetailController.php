<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameActionDetailStoreRequest;
use App\Http\Requests\GameActionDetailUpdateRequest;
use Illuminate\Http\Request;
class GameActionDetailController extends Controller
{

    public function index(Request $request)
    {

    }

    public function store(GameActionDetailStoreRequest $request)
    {
        $request->validated();
    }

    public function show($id)
    {

    }

    public function update(GameActionDetailUpdateRequest $request, $id)
    {
        $request->validated();
    }

    public function destroy($id)
    {

    }
}
