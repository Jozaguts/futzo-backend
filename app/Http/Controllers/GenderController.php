<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenderRequest;
use Illuminate\Http\Response;
class GenderController extends Controller
{

    public function index()
    {

    }

    public function store(GenderRequest $request)
    {

      $request->validated();
    }

    public function show($id)
    {
        //
    }

    public function update(GenderRequest $request, int $id)
    {
        $request->validated();
    }

    public function destroy(int $id)
    {

    }
}
