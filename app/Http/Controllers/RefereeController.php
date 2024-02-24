<?php

namespace App\Http\Controllers;

use App\Http\Requests\RefereeStoreRequest;
use App\Http\Requests\RefereeUpdateRequest;
use Illuminate\Support\Str;

class RefereeController extends Controller
{

    public function index()
    {

    }

    public function show($id)
    {

    }

    public function store(RefereeStoreRequest $request)
    {
        $data = array_merge($request->validated(),['password' => Str::random(8)]);
    }

    public function update(RefereeUpdateRequest $request,$id)
    {

    }

    public function destroy($id)
    {

    }
}
