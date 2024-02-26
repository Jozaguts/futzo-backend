<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamStoreRequest;
use App\Http\Requests\TeamUpdateRequest;
use App\Http\Resources\TeamCollection;
use App\Http\Resources\TeamResource;
use App\Models\Category;
use App\Models\Team;
use App\Models\TeamDetail;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeamsController extends Controller
{

    public function index(Team $team, Request $request)
    {
        $categories = Category::select('id','name')->get();
        $tournaments = Tournament::select('id','name')->get();
        return  new TeamCollection($team::all(),$categories, $tournaments);
    }

    public function show($id)
    {
        return new TeamResource(Team::findOrFail($id));
    }

    public function store(TeamStoreRequest $request)
    {


        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $request->image = Storage::disk('public')->url($path);
        }

        $team = Team::create([
            'name' => $request->name,
            'tournament_id' => $request->tournament_id,
            'category_id' => $request->category_id,
            'president_name' => $request->president_name,
            'coach_name' => $request->coach_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'image' => $request->image,
        ]);
        if ($request->has('colors')) {
            $team->colors()->create([
                'colors' => $request->colors
            ]);
        }

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
