<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameStoreRequest;
use App\Http\Requests\GameUpdateRequest;
use Illuminate\Http\Request;
class GameController extends Controller
{

    public function index(Request $request)
    {

    }

    public function show($id)
    {

    }

    public function store(GameStoreRequest $request)
    {
        return $request->validated();
    }


    public function update(GameUpdateRequest $request, $id)
    {
        return $request->validated();
    }


    public function destroy($id)
    {

    }
}
