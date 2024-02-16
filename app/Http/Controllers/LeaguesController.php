<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LeaguesController extends Controller
{
    public function store(Request $request)
    {
        $league = new League();
        $league->name = $request->name;
        $league->description = $request->description;
        $league->creation_date = $request->creation_date;
        $league->logo = $request->logo;
        $league->status = $request->status;
        $league->save();
        return response()->json($league);
    }
}
