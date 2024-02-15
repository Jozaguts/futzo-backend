<?php

namespace App\Http\Controllers;

use App\Http\Requests\TournamentStoreRequest;
use App\Http\Resources\TournamentCollection;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

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

        if($request->hasFile('logo')){
            $path = $request->file('logo')->store('images', 'public');
            $request->logo = Storage::disk('public')->url($path);
        }
        if($request->hasFile('banner')) {
            $path = $request->file('banner')->store('images', 'public');
            $request->banner = Storage::disk('public')->url($path);
        }

        $tournament = Tournament::create([
            'name' => $request->name,
            'location' => $request->location,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'prize' => $request->prize,
            'winner' => $request->winner,
            'description' => $request->description,
            'logo' => $request->logo,
            'banner' => $request->banner,
            'status' => $request->status,
        ]);

        return response()->json($tournament);
    }
}
