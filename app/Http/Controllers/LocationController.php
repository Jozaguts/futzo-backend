<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationStoreRequest;
use App\Http\Requests\LocationUpdateRequest;
use App\Http\Resources\LocationCollection;
use App\Http\Resources\LocationFieldCollection;
use App\Http\Resources\LocationResource;
use App\Models\Field;
use App\Models\LeagueField;
use App\Models\FieldWindow;
use App\Models\LeagueFieldWindow;
use App\Models\Location;
use App\Models\Game;
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
        $locationData = $validated->except('fields');
        $fieldsPayload = $validated->only('fields')['fields'];

        $place_id= $locationData['place_id'] ?? null;
        $league = auth()->user()->league;
        try {
            DB::beginTransaction();
            $location = Location::where('place_id', $place_id)->first();

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
            // Crear campos de juego asociados a la liga + ventanas
            foreach ($fieldsPayload as $fieldData) {
                $field = Field::create([
                    'location_id' => $location->id,
                    'name' => $fieldData['name'],
                    'type' => Field::defaultType,
                    'dimensions' => Field::defaultDimensions,
                ]);

                // Ventanas base 24/7 si no existen
                if (!FieldWindow::where('field_id', $field->id)->exists()) {
                    for ($dow = 0; $dow <= 6; $dow++) {
                        FieldWindow::create([
                            'field_id' => $field->id,
                            'day_of_week' => $dow,
                            'start_minute' => 0,
                            'end_minute' => 1440,
                            'enabled' => true,
                        ]);
                    }
                }

                // Pivot league_field
                $leagueField = LeagueField::create([
                    'league_id' => $league->id,
                    'field_id' => $field->id,
                ]);

                // Ventanas por liga-campo
                $windows = $fieldData['windows'] ?? [];
                if (!empty($windows)) {
                    $this->upsertLeagueFieldWindows($leagueField->id, $windows);
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
            $fields = $request->input('fields', []);
            $league = auth()->user()->league;
            $location->update([
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'position' => $data['position']
                ]
            );
            foreach ($fields as $fieldData) {
                $field = Field::where([
                    'location_id' => $location->id,
                    'id' => $fieldData['id'] ?? 0,
                ])->first();

                if ($field) {
                    $field->update([
                        'name' => $fieldData['name'],
                        'type' => Field::defaultType,
                        'dimensions' => Field::defaultDimensions,
                    ]);

                    $leagueField = LeagueField::where([
                        'league_id' => $league->id,
                        'field_id' => $field->id,
                    ])->first();
                    if ($leagueField && isset($fieldData['windows'])) {
                        // Reemplazar ventanas
                        LeagueFieldWindow::where('league_field_id', $leagueField->id)->delete();
                        $this->upsertLeagueFieldWindows($leagueField->id, $fieldData['windows']);
                    }
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

    private function upsertLeagueFieldWindows(int $leagueFieldId, array $windows): void
    {
        $map = [
            'sun' => 0, 'sunday' => 0,
            'mon' => 1, 'monday' => 1,
            'tue' => 2, 'tuesday' => 2,
            'wed' => 3, 'wednesday' => 3,
            'thu' => 4, 'thursday' => 4,
            'fri' => 5, 'friday' => 5,
            'sat' => 6, 'saturday' => 6,
            'all' => 'all',
        ];
        foreach ($windows as $dayKey => $ranges) {
            $dk = strtolower($dayKey);
            if (!array_key_exists($dk, $map)) {
                continue;
            }
            if ($map[$dk] === 'all') {
                // apply to all days
                for ($dow = 0; $dow <= 6; $dow++) {
                    foreach ($ranges as $r) {
                        LeagueFieldWindow::create([
                            'league_field_id' => $leagueFieldId,
                            'day_of_week' => $dow,
                            'start_minute' => $this->toMinutes($r['start'] ?? '00:00'),
                            'end_minute' => $this->toMinutes($r['end'] ?? '24:00'),
                            'enabled' => true,
                        ]);
                    }
                }
                continue;
            }
            $dow = $map[$dk];
            foreach ($ranges as $r) {
                LeagueFieldWindow::create([
                    'league_field_id' => $leagueFieldId,
                    'day_of_week' => $dow,
                    'start_minute' => $this->toMinutes($r['start'] ?? '00:00'),
                    'end_minute' => $this->toMinutes($r['end'] ?? '24:00'),
                    'enabled' => true,
                ]);
            }
        }
    }

    private function toMinutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        return $h * 60 + $m;
    }

    public function destroy(Location $location): JsonResponse
    {
        try {
            // Validar que no existan partidos programados/en progreso/aplazados
            $blockingStatuses = [
                Game::STATUS_SCHEDULED,
                Game::STATUS_IN_PROGRESS,
                Game::STATUS_POSTPONED,
            ];

            $fieldIds = $location->fields()->pluck('id');

            $hasBlockingGames = Game::query()
                ->whereIn('status', $blockingStatuses)
                ->where(function ($q) use ($location, $fieldIds) {
                    $q->where('location_id', $location->id)
                      ->orWhereIn('field_id', $fieldIds);
                })
                ->exists();

            if ($hasBlockingGames) {
                return response()->json([
                    'message' => 'No puedes eliminar esta locación porque tiene partidos programados o en progreso.',
                ], 422);
            }

            // Desasociar de la liga del usuario actual (si aplica)
            optional(auth()->user()->league)->locations()->detach($location->id);

            // Soft delete de la locación para preservar relaciones históricas
            $location->delete();

            return response()->json(['message' => 'Locación eliminada correctamente'], 200);
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
