<?php

namespace App\Http\Controllers;

use App\Http\Requests\TournamentStoreRequest;
use App\Http\Requests\TournamentUpdateRequest;
use App\Http\Requests\UpdateTournamentStatusRequest;
use App\Http\Resources\TournamentCollection;
use App\Http\Resources\TournamentResource;
use App\Models\Location;
use App\Models\Tournament;
use App\Models\TournamentFormat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function store(TournamentStoreRequest $request): JsonResponse
    {

        $data = $request->validated();

        $requestLocation = json_decode($data['details']['location'], true);

        $location = Location::updateOrCreate([
            'autocomplete_prediction->place_id' => $requestLocation['place_id']
        ], [
            'name' => $requestLocation['structured_formatting']['main_text'] ?? null,
            'address' => $requestLocation['description'] ?? null,
            'city' => $requestLocation['terms'][2]['value'] ?? null,
            'autocomplete_prediction' => $requestLocation ?? null
        ]);

        $tournament = Tournament::create([
            'name' => $data['basic']['name'],
            'tournament_format_id' => $data['basic']['tournament_format_id'],
            'start_date' => $data['details']['start_date'] ?? null,
            'end_date' => $data['details']['end_date'] ?? null,
            'prize' => $data['details']['prize'] ?? null,
            'winner' => $data['details']['winner'] ?? null,
            'description' => $data['details']['description'] ?? null,
            'category_id' => $data['basic']['category_id'],
        ]);
        $tournament->locations()->attach($location->id);
        if ($request->hasFile('basic.image')) {
            $media = $tournament
                ->addMedia($data['basic']['image'])
                ->toMediaCollection('tournament');

            $tournament->update([
                'image' => $media->getUrl('default'),
                'thumbnail' => $media->getUrl('thumbnail')
            ]);
        }
        $tournament->refresh();
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
}
