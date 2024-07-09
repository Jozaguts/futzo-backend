<?php

namespace App\Http\Controllers;

use App\Http\Requests\TournamentStoreRequest;
use App\Http\Requests\TournamentUpdateRequest;
use App\Http\Resources\TournamentCollection;
use App\Http\Resources\TournamentResource;
use App\Models\Location;
use App\Models\Tournament;
use App\Models\TournamentFormat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class TournamentController extends Controller
{
    public function index(): TournamentCollection
    {
        // todo paginate the response
        $tournaments = Tournament::withCount(['teams', 'players', 'games'])
            ->with('format',function ($query){
                $query->select('id','name');
            })
            ->with('locations')
            ->get();

        return new TournamentCollection($tournaments);
    }

    public function store(TournamentStoreRequest $request): JsonResponse
    {

        $data = $request->safe()->collect();

        $requestLocation = json_decode($data->get('location'), true);

        $location = Location::updateOrCreate([
            'autocomplete_prediction->place_id' => $requestLocation['place_id']
        ],[
            'name' => $requestLocation['structured_formatting']['main_text'],
            'address' => $requestLocation['description'],
            'city' => $requestLocation['terms'][2]['value'],
            'autocomplete_prediction' => $requestLocation
        ]);
        $tournament = Tournament::create([
            'name' => $data->get('name'),
            'tournament_format_id' => $data->get('tournament_format_id'),
            'start_date' => $data->get('start_date'),
            'end_date' => $data->get('end_date'),
            'prize' => $data->get('prize'),
            'winner' => $data->get('winner'),
            'description' => $data->get('description'),
            'category_id' => $data->get('category_id'),
        ]);
        $tournament->locations()->attach($location->id);
        if ($data->has('image')) {
          $media =  $tournament
               ->addMedia($data->get('image'))
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

        if($data->has('location')) {
            $requestLocation = json_decode($data->get('location'), true);

            $location = Location::updateOrCreate([
                'autocomplete_prediction->place_id' => $requestLocation['place_id']
            ],[
                'name' => $requestLocation['structured_formatting']['main_text'],
                'address' => $requestLocation['description'],
                'city' => $requestLocation['terms'][2]['value'],
                'autocomplete_prediction' => $requestLocation
            ]);
        }
        $tournament->update($data->except('location')->toArray());

        if ($data->has('image')) {
            $media =  $tournament
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
        $tournamentTypes = TournamentFormat::query()->select('id','name')->get();

        return response()->json($tournamentTypes);
    }
    public function getTournamentFormats(): JsonResponse
    {
        $tournamentFormats = TournamentFormat::query()->select('id','name','description')->get();

        return response()->json($tournamentFormats);
    }

    public function updateStatus(Request $request, Tournament $tournament): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:creado,en curso,completado,cancelado'
        ]);
        $tournament->update([
            'status' => 'creado'
        ]);

        return response()->json($tournament);
    }
}
