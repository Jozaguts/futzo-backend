<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationStoreRequest;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        return response()->json(Location::all());
    }
    public function store (LocationStoreRequest $request)
    {
        $validated = $request->validated();

        Location::create($validated);

        return response()->json(['message' => 'Location created successfully'], 201);
    }
}
