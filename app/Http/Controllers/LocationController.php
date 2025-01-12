<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationStoreRequest;
use App\Http\Resources\LocationCollection;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function index(Request $request): LocationCollection
    {
        return new LocationCollection (
            auth()->user()
                ->league->locations()
                ->with('tags')
                ->paginate($request->get('per_page', 8), ['*'], 'page', $request->get('page', 1))
        );
    }

    public function store(LocationStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();
            $location = Location::create($validated);

            $league = auth()->user()->league;

            if ($league) {
                $league->locations()->attach($location->id);
            }

            if ($request->has('tags')) {
                $location->attachTags($validated['tags']);
            }
            DB::commit();
            return response()->json(['message' => 'Location created successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(LocationStoreRequest $request, Location $location): JsonResponse
    {
        try {
            DB::beginTransaction();
            $validated = $request->safe()->except('tags');

            $location->update($validated);
            if ($request->has('tags')) {
                $tags = $request->validated()['tags'] ?? [];
                $location->syncTags($tags);
            }
            DB::commit();
            return response()->json(['message' => 'Location created successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Location $location): JsonResponse
    {
        try {
            $location->delete();
            return response()->json(['message' => 'Location deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
