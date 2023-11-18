<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerStoreRequest;
use App\Http\Requests\PlayerUpdateRequest;
use Illuminate\Http\Request;
class PlayersController extends Controller
{

    public function index(Request $request)
    {

    }

    public function show($id)
    {

    }

    public function store(PlayerStoreRequest $request)
    {
        $request->all();
    }

    public function update(PlayerUpdateRequest $request, $id)
    {
        $request->except('_method');
    }

    public function destroy($id)
    {

    }


}
