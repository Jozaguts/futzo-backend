<?php

namespace App\Http\Controllers;

use App\Http\Requests\TournamentStoreRequest;
use App\Http\Resources\TournamentCollection;
use App\Models\Tournament;
use App\Models\TournamentFormat;
use Illuminate\Http\JsonResponse;
class TournamentController extends Controller
{
    public function index(): TournamentCollection
    {
        // todo paginate the response
        $tournaments = Tournament::withCount(['teams', 'players', 'games'])->get();

        return new TournamentCollection($tournaments);
    }

    public function store(TournamentStoreRequest $request): JsonResponse
    {

        $request->validated();

        $tournament = Tournament::create([
            'name' => $request->name,
            'tournament_format_id' => $request->tournament_format_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'prize' => $request->prize,
            'winner' => $request->winner,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'status' => 'active',
        ]);
        if ($request->hasFile('image')) {
          $media =  $tournament
               ->addMedia($request->file('image'))
               ->toMediaCollection('tournament');

            $tournament->update([
                'image' => $media->getUrl('default'),
                'thumbnail' => $media->getUrl('thumbnail')
            ]);
        }

        return response()->json($tournament, 201);
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
}
