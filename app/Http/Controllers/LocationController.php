<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationStoreRequest;
use App\Http\Requests\LocationUpdateRequest;
use App\Http\Resources\LocationCollection;
use App\Http\Resources\LocationFieldCollection;
use App\Http\Resources\LocationResource;
use App\Models\Field;
use App\Models\LeagueField;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function index(Request $request): LocationCollection
    {
        $data = Location::with(['tags', 'fields.leaguesFields', 'fields.tournamentsFields'])
            ->whereHas('leagues', function ($query) use($request) {
                $query->where('league_id', $request->headers->get('X-League-Id'));
            })
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where('locations.name', 'like', '%' . $request->get('search') . '%');
            })
            ->paginate($request->get('per_page', 8), ['*'], 'page', $request->get('page', 1));
        return new LocationCollection ($data);
    }

    /**
     * @throws \Throwable
     */
    public function store(LocationStoreRequest $request): LocationResource
    {
        $validated = $request->safe();
        $locationData = $validated->except('availability');
        $availabilityData = $validated->only('availability');

        $placeId = $locationData['autocomplete_prediction']['place_id'] ?? null;

        $location = null;
        $league = auth()->user()->league;
        try {
            DB::beginTransaction();
            $location = Location::whereJsonContains('autocomplete_prediction->place_id', $placeId)->first();

            if (!$location) {
                $location = Location::create($locationData);

                if ($request->has('tags')) {
                    $location->attachTags($locationData['tags']);
                }
            }
            // Asociar la Location con la liga (si no está ya asociada)
            if ($league && !$league->locations()->where('locations.id', $location->id)->exists()) {
                $league->locations()->attach($location->id, ['updated_at' => now(), 'created_at' => now()]);
            }
            // Crear campos de juego asociados a la liga
            if (!empty($availabilityData)) {
                foreach ($availabilityData['availability'] as $fieldData) {
                    $field = Field::create([
                        'location_id' => $location->id,
                        'name' => $fieldData['name'],
                        'type' => Field::defaultType,
                        'dimensions' => Field::defaultDimensions,
                    ]);
                    $league->fields()->attach($field->id, [
                        'availability' => json_encode([
                            'monday' => $fieldData['monday'] ?? [],
                            'tuesday' => $fieldData['tuesday'] ?? [],
                            'wednesday' => $fieldData['wednesday'] ?? [],
                            'thursday' => $fieldData['thursday'] ?? [],
                            'friday' => $fieldData['friday'] ?? [],
                            'saturday' => $fieldData['saturday'] ?? [],
                            'sunday' => $fieldData['sunday'] ?? [],
                        ], JSON_THROW_ON_ERROR),
                        'updated_at' => now(),
                        'created_at' => now()
                    ]);
                }
            }

            if ($request->has('tags')) {
                $location->attachTags($locationData['tags']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            logger('Location creation failed', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
        return new LocationResource($location);
    }

    public function update(LocationUpdateRequest $request, Location $location): JsonResponse
    {
        try {
            DB::beginTransaction();
            $data = $request->safe()->except('tags');
            $availability = $data['availability'] ?? [];
            $league = auth()->user()->league;
            $location->update([
                    'name' => $data['name'],
                    'city' => $data['city'],
                    'address' => $data['address'],
                    'autocomplete_prediction' => $data['address'],
                    'position' => $data['position']
                ]
            );
            if (!empty($availability)) {
                foreach ($availability as $fieldData) {
                    Field::where([
                        'location_id' => $location->id,
                        'id' => $fieldData ['id']
                    ])->update([
                        'name' => $fieldData['name'],
                        'type' => Field::defaultType,
                        'dimensions' => Field::defaultDimensions
                    ]);
                    LeagueField::where([
                        'league_id' => $league->id,
                        'field_id' => $fieldData['id']
                    ])->update([
                        'availability' => json_encode([
                            'monday' => $fieldData['monday'] ?? [],
                            'tuesday' => $fieldData['tuesday'] ?? [],
                            'wednesday' => $fieldData['wednesday'] ?? [],
                            'thursday' => $fieldData['thursday'] ?? [],
                            'friday' => $fieldData['friday'] ?? [],
                            'saturday' => $fieldData['saturday'] ?? [],
                            'sunday' => $fieldData['sunday'] ?? [],
                        ], JSON_THROW_ON_ERROR),
                    ]);
                }
            }
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

    public function fields(Request $request): LocationFieldCollection
    {
        $locationIds = explode(',', $request->query('location_ids'));
        return new LocationFieldCollection(Field::whereIn('location_id', $locationIds)->get());
    }

}
