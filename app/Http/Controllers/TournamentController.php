<?php

namespace App\Http\Controllers;

use App\Http\Requests\TournamentStoreRequest;
use App\Models\Tournament;
use Illuminate\Support\Facades\Storage;

class TournamentController extends Controller
{
   public function store(TournamentStoreRequest $request)
   {
        $request->validated();

        // save logo and banner if exists in the request and storage with public visibility in laravel storage Storage::disk('local')
        if($request->hasFile('logo')){
            $path = $request->file('logo')->store('images', 'public');
            $request->logo = Storage::disk('public')->url($path);
        }
        if($request->hasFile('banner')) {
            $path = $request->file('banner')->store('images', 'public');
            $request->banner = Storage::disk('public')->url($path);
        }

         $tournament = Tournament::create([
                'name' => $request->name,
                'location' => $request->location,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'prize' => $request->prize,
                'winner' => $request->winner,
                'description' => $request->description,
                'logo' => $request->logo,
                'banner' => $request->banner,
                'status' => $request->status,
        ]);

         return response()->json($tournament);
   }
}
