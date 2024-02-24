<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamStoreRequest;
use App\Http\Requests\TeamUpdateRequest;
use App\Http\Resources\TeamCollection;
use App\Http\Resources\TeamResource;
use App\Models\Category;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\Request;
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
        return $request->all();
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
