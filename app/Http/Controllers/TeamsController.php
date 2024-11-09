<?php

namespace App\Http\Controllers;

use App\Events\RegisteredTeamCoach;
use App\Events\RegisteredTeamPresident;
use App\Http\Requests\TeamStoreRequest;
use App\Http\Requests\TeamUpdateRequest;
use App\Http\Resources\TeamCollection;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamsController extends Controller
{

    public function index(Request $request): TeamCollection
    {
        $teams = Team::query()
            ->where('league_id', auth()->user()->league_id)
            ->paginate(
                $request->get('per_page', 10),
                ['*'],
                'page',
                $request->get('page', 1)
            );
        return new TeamCollection($teams);
    }

    public function list(): TeamCollection
    {
        $teams = Team::query()->where('league_id', auth()->user()->league_id)->get();
        return new TeamCollection($teams);
    }

    public function show($id)
    {
        return new TeamResource(Team::findOrFail($id));
    }

    public function store(TeamStoreRequest $request): TeamResource|JsonResponse
    {

        $data = $request->validated();
        try {
            DB::beginTransaction();

            $president = $this->createOrUpdateUser($data['president'] ?? null, $request, 'president', 'dueño de equipo', RegisteredTeamPresident::class);
            $coach = $this->createOrUpdateUser($data['coach'] ?? null, $request, 'coach', 'entrenador', RegisteredTeamCoach::class);

            $team = Team::create([
                'name' => $data['team']['name'],
                'president_id' => $president?->id ?? null,
                'coach_id' => $coach?->id ?? null,
                'phone' => $data['team']['phone'] ?? null,
                'email' => $data['team']['email'] ?? null,
                'address' => json_decode($data['team']['address'] ?? null),
                'colors' => json_decode($data['team']['colors'] ?? '[]'),
            ]);
            if ($request->hasFile('team.image')) {
                $media = $team
                    ->addMedia($request->file('team.image'))
                    ->toMediaCollection('team');
                $team->update([
                    'image' => $media->getUrl('default'),
                ]);
            }

            $team->leagues()->attach(auth()->user()->league_id);
            $team->categories()->attach($data['team']['category_id']);
            $team->tournaments()->attach($data['team']['tournament_id']);
            DB::commit();
            return new TeamResource($team);
        } catch (\Exception $e) {
            DB::rollBack();
            logger('error', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }

    }

    public function update(TeamUpdateRequest $request, $id)
    {

        try {
            $data = $request->validated();
            $president = collect($data['president']);
            $coach = collect($data['coach']);
            DB::beginTransaction();
            $team = Team::findOrFail($id);
            if (!empty($president)) {
                $team->president->update($president->only('name')->toArray());
                if ($request->hasFile('president.image')) {

                    $media = $team->president
                        ->addMedia($request->file('president.image'))
                        ->toMediaCollection('image', 's3');
                    logger('media', [
                        ' president url' => $media->getUrl(),
                    ]);
                    $team->president->update([
                        'image' => $media->getUrl(),
                    ]);
                }
            }
            if (!empty($coach)) {
                $team->coach->update($coach->only('name')->toArray());
                if ($request->hasFile('coach.image')) {

                    $media = $team->coach
                        ->addMedia($request->file('coach.image'))
                        ->toMediaCollection('image', 's3');
                    logger('media', [
                        'coach url' => $media->getUrl(),
                    ]);
                    $team->coach->update([
                        'image' => $media->getUrl(),
                    ]);
                }
            }
            $team->update([
                'name' => $data['team']['name'],
                'address' => json_decode($data['team']['address']),
                'colors' => json_decode($data['team']['colors']),
            ]);
            if ($request->hasFile('team.image')) {

                $media = $team
                    ->addMedia($request->file('team.image'))
                    ->toMediaCollection('team');
                $team->update([
                    'image' => $media->getUrl('default'),
                ]);
            }
            $team->categories()->attach($data['team']['category_id']);
            $team->tournaments()->attach($data['team']['tournament_id']);
            $team->refresh();
            DB::commit();
            return new TeamResource($team);
        } catch (\Exception $e) {
            DB::rollBack();
            logger('error', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }

    public function destroy($id)
    {

    }

    private function createOrUpdateUser($userData, $request, $role, $roleName, $eventClass)
    {
        if (!$userData) return null;

        $user = collect($userData);
        $temporaryPassword = str()->random(8);
        $user->put('password', $temporaryPassword);
        $user->put('email_verified_at', now());

        $user = User::updateOrCreate(['email' => $user->get('email')], $user->except('email')->toArray());

        if ($request->hasFile("$role.image")) {
            $media = $user->addMedia($request->file("$role.image"))->toMediaCollection('image');
            $user->update(['image' => $media->getUrl()]);
        }

        $user->league()->associate(auth()->user()->league);
        $user->save();
        $user->assignRole($roleName);
        event(new $eventClass($user, $temporaryPassword));

        return $user;
    }
}
