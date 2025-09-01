<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeagueStoreRequest;
use App\Http\Resources\LeagueLocationCollection;
use App\Http\Resources\LeagueResource;
use App\Http\Resources\TournamentByLeagueCollection;
use App\Models\FootballType;
use App\Models\League;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class LeaguesController extends Controller
{
    public const string DEFAULT_STATUS = 'draft';

    public function index(): AnonymousResourceCollection
    {
        $league = League::withCount('tournaments')->get();

        return LeagueResource::collection($league);
    }

    public function store(LeagueStoreRequest $request): LeagueResource
    {
        $league = League::create([
            'name' => $request->name,
            'status' => $request->status ?? self::DEFAULT_STATUS,
            'owner_id' => auth()->id(),
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')?->store('images', 'public');
            $league->logo = Storage::disk('public')->url($path);
        }
        if ($request->hasFile('banner')) {
            $path = $request->file('banner')?->store('images', 'public');
            $league->banner = Storage::disk('public')?->url($path);
        }

        $user = auth()->user();
        $user->league_id = $league->id;
        // todo esto no se tendria que resolver de esta manera pero por ahora es lo mas rapido
        if (is_null($user->verified_at)){
            $user->verified_at = now();
        }
        $user->save();

        // Si no tiene suscripción y no está en trial, inicia trial
        if (!$user->hasActiveSubscription() && !$user->onTrial()) {
            $days = (int) config('billing.trial_days', 14);
            if ($days > 0) {
                $user->trial_ends_at = now()->addDays($days);
                $user->save();
            }
        }

        // Sincroniza estado de liga en función de la suscripción del owner
        app(\App\Services\LeagueStatusSyncService::class)->syncForOwner($user);
        $league->refresh();

        return new LeagueResource($league);
    }

    public function getTournaments($leagueId): TournamentByLeagueCollection
    {
        $tournament = League::find($leagueId)?->tournaments()->withCount('teams')->get();

        return new TournamentByLeagueCollection($tournament);
    }

    public function getFootballTypes(): JsonResponse
    {
        $footBallTypes = FootballType::query()
            ->select('id', 'name', 'description')
            ->get();

        return response()->json($footBallTypes);
    }

    public function leagueLocations(): LeagueLocationCollection
    {
        return new LeagueLocationCollection(Location::query()
            ->whereHas('leagues', fn($query) => $query->where('league_id', auth()->user()->league_id))
            ->with([
                'fields' => fn($query) => $query->whereHas('leaguesFields', fn($query) => $query->where('league_id', auth()->user()->league_id))
            ])
            ->get());
    }
}
