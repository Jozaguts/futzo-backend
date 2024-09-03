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
use Illuminate\Support\Facades\Storage;

class TeamsController extends Controller
{

    public function index(Team $team, Request $request)
    {
        $team = $team->with('leagues')->get();
        return  new TeamCollection($team);
    }

    public function show($id)
    {
        return new TeamResource(Team::findOrFail($id));
    }

    public function store(TeamStoreRequest $request): TeamResource | JsonResponse
    {

        $data = $request->validated();

        if ($request->hasFile('team.image')) {
            $path = $request->file('team.image')->store('images', 'public');
            $data['team']['image'] = Storage::disk('public')->url($path);
        }
        $president = collect($data['president']);
        $coach = collect($data['coach']);
        $temporaryPassword = str()->random(8);
        $president->put('password', $temporaryPassword);
        $president->put('email_verified_at', now());
        $coach->put('password', $temporaryPassword);
        $coach->put('email_verified_at', now());

        try {
            DB::beginTransaction();
            if (!empty($president)) {
                if ($request->hasFile('president.image')) {
                    $path = $request->file('president.image')->store('images', 'public');
                    $president->put('image',Storage::disk('public')->url($path));
                }

                $president = User::updateOrCreate(
                    ['email' => $president->get('email')],
                    $president->except('email')->toArray()
                );

                $president->league()->associate(auth()->user()->league);
                $president->save();
                $president->assignRole('dueño de equipo');
                event(new RegisteredTeamPresident($president, $temporaryPassword));
            }
            if (!empty($coach)) {
                if ($request->hasFile('coach.image')) {
                    $path = $request->file('coach.image')->store('images', 'public');
                    $coach->put('image',Storage::disk('public')->url($path));
                }
                $coach =  User::updateOrCreate(
                    ['email' => $coach['email']],
                    $coach->except('email')->toArray()
                );
                $coach->league()->associate(auth()->user()->league);
                $coach->save();
                $coach->assignRole('entrenador');
                event(new RegisteredTeamCoach($coach, $temporaryPassword));
            }

            $team = Team::create([
                'name' => $data['team']['name'],
                'president_id' => $president->id,
                'coach_id' => $coach->id,
                'phone' => $data['team']['phone'],
                'email' => $data['team']['email'],
                'address' => json_decode($data['team']['address']),
                'image' => $data['team']['image'] ?? null,
                'colors' => json_decode($data['team']['colors']),
            ]);

            $team->leagues()->attach(auth()->user()->league_id);
            DB::commit();
            return new TeamResource($team);
        }catch (\Exception $e) {
            DB::rollBack();
            logger('data',[
                'team' => $data['team'],
                'president' => $president,
                'coach' => $coach
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }

    }

    public function update(TeamUpdateRequest $request, $id)
    {
        $request->only('name', 'group', 'category_id', 'won', 'draw', 'lost', 'goals_against', 'goals_for',
            'goals_difference', 'points','gender_id');
    }

    public function destroy($id)
    {

    }
}
