<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeagueStoreRequest;
use App\Http\Resources\LeagueResource;
use App\Http\Resources\TournamentByLeagueCollection;
use App\Models\FootballType;
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
		$data = $request->validated();

		if ($request->hasFile('logo')) {
			$path = $request->file('logo')->store('images', 'public');
			$request->logo = Storage::disk('public')->url($path);
		}
		if ($request->hasFile('banner')) {
			$path = $request->file('banner')->store('images', 'public');
			$request->banner = Storage::disk('public')->url($path);
		}

		$league = League::create([
			'name' => $request->name,
			'status' => $request->status ?? self::DEFAULT_STATUS,
		]);

		auth()->user()->league_id = $league->id;
		auth()->user()->save();

		return new LeagueResource($league);
	}

	public function getTournaments($leagueId): TournamentByLeagueCollection
	{

//        $tournament = League::find($leagueId)->tournaments();
//        return response()->json($tournament);
		$tournament = League::find($leagueId)->tournaments()->withCount('teams')->get();

		return new TournamentByLeagueCollection($tournament);
	}

	public function getFootballTypes(): JsonResponse
	{
		$footBallTypes = FootballType::query()
			->select('id', 'name', 'description')
			->get();

		return response()->json($footBallTypes);
	}
}
