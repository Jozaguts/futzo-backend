<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameTimeDetailsStoreRequest;
use App\Http\Requests\GameTimeDetailsUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
class GameTimeDetailsController extends Controller
{

    public function index(Request $request)
    {

    }

    public function store(GameTimeDetailsStoreRequest $request)
    {
        $request->validated();
    }

    public function show(int $id)
    {

    }

    public function update(GameTimeDetailsUpdateRequest $request, $id)
    {

    }

    public function destroy(int $id)
    {

    }
}
