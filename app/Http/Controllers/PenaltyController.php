<?php

namespace App\Http\Controllers;

use App\Http\Requests\PenaltyStoreRequest;
use App\Http\Requests\PenaltyUpdateRequest;
class PenaltyController extends Controller
{

    public function index()
    {
    }

    public function show($id)
    {

    }

    public function store(PenaltyStoreRequest $request)
    {
        $request->validated();
    }

    public function update(PenaltyUpdateRequest $request, $id)
    {
        $request->validated();
    }

    public function destroy($id)
    {

    }
}
