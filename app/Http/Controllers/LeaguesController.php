<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeagueStoreRequest;
use App\Http\Resources\LeagueResource;
use App\Models\League;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class LeaguesController extends Controller
{
    const DEFAULT_STATUS = 'active';

    public function index()
    {
        $league = League::withCount('tournaments')->get();

        return LeagueResource::collection($league);
    }

    public function store(LeagueStoreRequest $request)
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

        $league  = League::create([
            'name' => $request->name,
            'location' => $request->location,
            'description' => $request->description,
            'creation_date' => $request->creation_date,
            'logo' => $request->logo,
            'banner' => $request->banner,
            'status' => $request->status ?? self::DEFAULT_STATUS,
        ]);

        auth()->user()->league_id = $league->id;
        auth()->user()->save();

        return new LeagueResource($league);
    }

    public function getTournaments($leagueId): JsonResponse
    {
        $tournament = League::find($leagueId)->tournaments()->withCount('teams')->get();

        return response()->json($tournament);
    }
}
