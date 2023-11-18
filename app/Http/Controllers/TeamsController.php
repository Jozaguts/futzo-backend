<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamStoreRequest;
use App\Http\Requests\TeamUpdateRequest;
use App\Http\Resources\TeamCollection;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\Request;
class TeamsController extends Controller
{

    public function index(Team $team, Request $request)
    {
      return  new TeamCollection($team::paginate(20));
    }

    public function show($id)
    {
        return new TeamResource(Team::findOrFail($id));
    }

    public function store(TeamStoreRequest $request)
    {
        return $request->validated();
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
