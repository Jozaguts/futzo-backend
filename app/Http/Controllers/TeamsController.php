<?php

namespace App\Http\Controllers;

use App\Events\RegisteredTeamPresident;
use App\Http\Requests\TeamStoreRequest;
use App\Http\Requests\TeamUpdateRequest;
use App\Http\Resources\TeamCollection;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeamsController extends Controller
{

    public function index(Team $team, Request $request)
    {
        $team = $team->with('league')->get();
        return  new TeamCollection($team);
    }

    public function show($id)
    {
        return new TeamResource(Team::findOrFail($id));
    }

    public function store(TeamStoreRequest $request): TeamResource
    {

        $data = $request->validated();

        if ($request->hasFile('team.image')) {
            $path = $request->file('team.image')->store('images', 'public');
            $data['team']['image'] = Storage::disk('public')->url($path);
        }
        $president = collect($data['president']);
        $coach = collect($data['coach']);

        if (!empty($president)) {
            if ($request->hasFile('president.image')) {
                $path = $request->file('president.image')->store('images', 'public');
                $president->put('image',Storage::disk('public')->url($path));
            }
          $president = User::updateOrCreate(
              ['email' => $president->get('email')],
              $president->except('email')->toArray()
          );
            $president->assignRole('dueño de equipo');
            logger('president', [$president]);
            RegisteredTeamPresident::dispatch($president);
        }
        if (!empty($coach)) {
            if ($request->hasFile('coach.image')) {
                $path = $request->file('coach.image')->store('images', 'public');
                $coach->put('image',Storage::disk('public')->url($path));
            }
            $coach =  User::updateOrCreate(['email' => $coach['email']],
                $coach->except('email')->toArray());
            $coach->assignRole('entrenador');
        }

        $team = Team::create([
            'name' => $data['team']['name'],
            'president_id' => $president->id,
            'coach_id' => $coach->id,
            'phone' => $data['team']['phone'],
            'email' => $data['team']['email'],
            'address' => $data['team']['address'],
            'image' => $data['team']['image'],
            'colors' =>$data['team']['colors'],
        ]);

        $team->leagues()->attach(auth()->user()->league_id);
        return new TeamResource($team);
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
