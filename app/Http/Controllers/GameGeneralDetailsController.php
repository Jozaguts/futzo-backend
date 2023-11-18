<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameGeneralDetailsStoreRequest;
use App\Http\Requests\GameGeneralDetailsUpdateRequest;
use Illuminate\Http\Request;
class GameGeneralDetailsController extends Controller
{

    public function index(Request $request)
    {

    }

    public function store(GameGeneralDetailsStoreRequest $request)
    {
        return $request->validated();
    }

    public function show(int $id)
    {

    }

    public function update(GameGeneralDetailsUpdateRequest $request, int $id)
    {

    }

    public function destroy(int $id)
    {


    }
}
