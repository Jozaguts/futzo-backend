<?php

namespace App\Http\Controllers;

use App\DTO\TournamentDTO;
use App\Events\TournamentCreatedEvent;
use App\Http\Requests\CreateTournamentScheduleRequest;
use App\Http\Requests\TournamentStoreRequest;
use App\Http\Requests\TournamentUpdateRequest;
use App\Http\Requests\UpdateTournamentStatusRequest;
use App\Http\Resources\MatchScheduleResource;
use App\Http\Resources\ScheduleSettingsResource;
use App\Http\Resources\TournamentCollection;
use App\Http\Resources\TournamentResource;
use App\Models\Location;
use App\Models\MatchSchedule;
use App\Models\Tournament;
use App\Models\TournamentConfiguration;
use App\Models\TournamentFormat;
use App\Models\TournamentTiebreaker;
use App\Services\ScheduleGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class TournamentController extends Controller
{
    public function index(Request $request): TournamentCollection
    {

        $tournaments = Tournament::withCount(['teams', 'players', 'games'])
            ->with([
                'format' => function ($query) {
                    $query->select('id', 'name');
                },
                'locations'
            ])
            ->paginate($request->get('per_page', 10), ['*'], 'page', $request->get('page', 1));

        return new TournamentCollection($tournaments);
    }

    /**
     * @throws \Throwable
     * @throws \JsonException
     */
    public function store(TournamentStoreRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $tourneyDto = (new TournamentDTO($request->validated()));
            $tournament = Tournament::create($tourneyDto->basicFields());

            if ($tourneyDto->hasLocation) {
                $tournament->locations()->attach($tourneyDto->locationFields());
            }

            if ($request->hasFile('basic.image')) {
                $media = $tournament
                    ->addMedia($tourneyDto->getImage())
                    ->toMediaCollection('tournament');

                $tournament->update([
                    'image' => $media->getUrl('default'),
                    'thumbnail' => $media->getUrl('thumbnail')
                ]);
            }
            TournamentCreatedEvent::dispatch($tournament, $tourneyDto->basicFields());
            DB::commit();
        } catch (FileIsTooBig|FileDoesNotExist $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return response()->json($tournament, 201);
    }

    public function show(Tournament $tournament): TournamentResource
    {
        return new TournamentResource($tournament);
    }

    public function update(TournamentUpdateRequest $request, Tournament $tournament): TournamentResource
    {
        $data = $request->safe()->collect();
        $location = null;

        if ($data->has('location')) {
            $requestLocation = json_decode($data->get('location'), true);

            $location = Location::updateOrCreate([
                'autocomplete_prediction->place_id' => $requestLocation['place_id']
            ], [
                'name' => $requestLocation['structured_formatting']['main_text'],
                'address' => $requestLocation['description'],
                'city' => $requestLocation['terms'][2]['value'],
                'autocomplete_prediction' => $requestLocation
            ]);
        }
        $tournament->update($data->except('location')->toArray());

        if ($data->has('image')) {
            $media = $tournament
                ->addMedia($data->get('image'))
                ->toMediaCollection('tournament');

            $tournament->update([
                'image' => $media->getUrl('default'),
                'thumbnail' => $media->getUrl('thumbnail')
            ]);
        }

        if (!is_null($location)) {
            $tournament->locations()->sync([$location->id]);
        }
        return new TournamentResource($tournament);
    }

    public function getTournamentTypes(): JsonResponse
    {
        $tournamentTypes = TournamentFormat::query()->select('id', 'name')->get();

        return response()->json($tournamentTypes);
    }

    public function getTournamentFormats(): JsonResponse
    {
        $tournamentFormats = TournamentFormat::query()->select('id', 'name', 'description')->get();

        return response()->json($tournamentFormats);
    }

    public function updateStatus(UpdateTournamentStatusRequest $request, Tournament $tournament): JsonResponse
    {

        $data = $request->safe()->collect();

        $tournament->update($data->only('status')->toArray());

        return response()->json($tournament);
    }

    public function scheduleSettings(int $tournamentId): ScheduleSettingsResource
    {
        $tournament = Tournament::with(['configuration', 'format', 'footballType', 'locations'])
            ->findOrFail($tournamentId);
        return new ScheduleSettingsResource($tournament);
    }

    public function getTournamentLocations(int $tournamentId): JsonResponse
    {
        $tournament = Tournament::with('locations.tags')->where('id', $tournamentId)->firstOrFail();
        return response()->json($tournament->locations);
    }

    public function storeTournamentLocations(Request $request, Tournament $tournament): JsonResponse
    {
        $requestLocation = $request->location['location'];
        $location = $tournament->locations()->save(Location::updateOrCreate([
            'autocomplete_prediction->place_id' => $requestLocation['place_id']
        ], [
            'name' => $requestLocation['structured_formatting']['main_text'],
            'address' => $requestLocation['description'],
            'city' => $requestLocation['terms'][2]['value'],
            'autocomplete_prediction' => $requestLocation
        ]));
        $location->syncTags($request->tags);
        return response()->json($location);
    }

    public function getTournamentSchedule(Request $request, int $tournamentId): MatchScheduleResource
    {
        $status = $request->get('status', 'scheduled');
        $schedule = MatchSchedule::where(['tournament_id' => $tournamentId, 'status' => $status])->first();
        return new MatchScheduleResource($schedule);
    }

    public function schedule(CreateTournamentScheduleRequest $request, ScheduleGeneratorService $scheduleGeneratorService, int $tournamentId): JsonResponse
    {
        $data = $request->validated();
        $tournamentData = $data['general'];
        $tournament = Tournament::where('id', $tournamentId)
            ->where('league_id', auth()->user()->league->id)
            ->firstOrFail();
        $startDate = Carbon::parse($tournamentData['start_date']);
        if ($startDate->isPast()) {
            return response()->json(['error' => 'La fecha de inicio no puede ser en el pasado.'], 400);
        }
        $locations = collect($tournamentData['locations'])->pluck('id');

        $availableLocations = Location::whereIn('id', $locations)->get();

        if ($availableLocations->isEmpty()) {
            return response()->json(['error' => 'No hay locaciones disponibles para este torneo.'], 400);
        }
        $tournament->configuration()->save(
            TournamentConfiguration::updateOrCreate(
                ['tournament_id' => $tournamentId],
                $request->tournamentConfigurationData()
            )
        );
        $tiebreakersData = $request->tiebrakersData($tournamentId);

        foreach ($tiebreakersData as $tiebreaker) {
            $tournament->configuration->tiebreakers()->save(
                TournamentTiebreaker::updateOrCreate(
                    ['tournament_configuration_id' => $tournament->configuration->id, 'rule' => $tiebreaker['rule']],
                    $tiebreaker
                )
            );
        }

//        $scheduleGeneratorService->generateFor($tournament);
        return response()->json(['message' => 'El calendario se está creando, cuando esté preparado se le notificará.', $tiebreakersData], 201);
    }
}
