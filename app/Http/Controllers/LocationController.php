<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationStoreRequest;
use App\Http\Resources\LocationCollection;
use App\Http\Resources\LocationResource;
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
                ->when($request->has('search'), function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->get('search') . '%');
                })
                ->with('tags')
                ->paginate($request->get('per_page', 8), ['*'], 'page', $request->get('page', 1))
        );
    }

    public function store(LocationStoreRequest $request): LocationResource
    {
        $validated = $request->safe();
        $locationData = $validated->except('availability');
        $availabilityData = $validated->only('availability');


        try {
            DB::beginTransaction();
            $location = Location::create($locationData);

            $league = auth()->user()->league;

            if ($league) {
                $league->locations()->attach($location->id);
            }

            if ($request->has('tags')) {
                $location->attachTags($locationData['tags']);
            }

            DB::commit();
            return new LocationResource($location);
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
            auth()->user()->league->locations()->detach($location->id);
            return response()->json(['message' => 'Location deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getLeagueLocation()
    {
        return new LocationCollection(auth()->user()->league->locations);
    }
}
