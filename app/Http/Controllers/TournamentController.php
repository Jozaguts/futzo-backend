<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
   public function store(Request $request)
   {
        $request->validate([
            'name' => 'required',
            'location' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'prize' => 'required',
            'winner' => 'required',
            'description' => 'required',
            'logo' => 'required',
            'banner' => 'required',
            'status' => 'required'
        ]);

         $tournament = Tournament::create($request->all());

         return response()->json($tournament);
   }
}
