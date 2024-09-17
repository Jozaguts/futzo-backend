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

    public function index(Request $request)
    {
        $paginate = $request->get('paginate');

        if (!!$paginate){
            $teams = Team::query()->where('league_id', auth()->user()->league_id)
                ->paginate($request->get('per_page', 10), ['*'], 'page', $request->get('page', 2));

        }else{
            $teams = Team::query()->where('league_id', auth()->user()->league_id)->get();
        }
        return  new TeamCollection($teams);
    }

    public function show($id)
    {
        return new TeamResource(Team::findOrFail($id));
    }

    public function store(TeamStoreRequest $request): TeamResource | JsonResponse
    {

        $data = $request->validated();

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

                $president = User::updateOrCreate(
                    ['email' => $president->get('email')],
                    $president->except('email')->toArray()
                );
                if ($request->hasFile('president.avatar')) {
                    $media =  $president
                        ->addMedia($request->file('president.avatar'))
                        ->toMediaCollection('avatar');

                    $president->update([
                        'avatar' => $media->getUrl(),
                    ]);
                }

                $president->league()->associate(auth()->user()->league); // user belongs to league
                $president->save();
                $president->assignRole('dueño de equipo');
                event(new RegisteredTeamPresident($president, $temporaryPassword));
            }
            if (!empty($coach)) {
                $coach =  User::updateOrCreate(
                    ['email' => $coach['email']],
                    $coach->except('email')->toArray()
                );
                if ($request->hasFile('coach.avatar')) {
                    $media =  $coach
                        ->addMedia($request->file('coach.avatar'))
                        ->toMediaCollection('avatar');

                    $coach->update([
                        'avatar' => $media->getUrl(),
                    ]);
                }
                $coach->league()->associate(auth()->user()->league); // user belongs to league
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
                'colors' => json_decode($data['team']['colors']),
            ]);
            if ($request->hasFile('team.image')) {
                $media =  $team
                    ->addMedia($request->file('team.image'))
                    ->toMediaCollection('team');
                $team->update([
                    'image' => $media->getUrl('default'),
                ]);
            }

            $team->leagues()->attach(auth()->user()->league_id); // team belongs to league
            $team->categories()->attach($data['team']['category_id']); // team belongs to category
            $team->tournaments()->attach($data['team']['tournament_id']);  // team belongs to tournament
            DB::commit();
            return new TeamResource($team);
        }catch (\Exception $e) {
            DB::rollBack();
            logger('error',[
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
            if (!empty($president)){
                $team->president->update($president->only('name')->toArray());
                if ($request->hasFile('president.avatar') ) {

                    $media =  $team->president
                        ->addMedia($request->file('president.avatar'))
                        ->toMediaCollection('avatar', 's3');
                    logger('media',[
                        ' president url' => $media->getUrl(),
                    ]);
                    $team->president->update([
                        'avatar' => $media->getUrl(),
                    ]);
                }
            }
            if (!empty($coach)){
                $team->coach->update($coach->only('name')->toArray() );
                if ($request->hasFile('coach.avatar') ) {

                    $media = $team->coach
                        ->addMedia($request->file('coach.avatar'))
                        ->toMediaCollection('avatar','s3');
                    logger('media',[
                        'coach url' => $media->getUrl(),
                    ]);
                    $team->coach->update([
                        'avatar' => $media->getUrl(),
                    ]);
                }
            }
            $team->update([
                'name' => $data['team']['name'],
                'address' => json_decode($data['team']['address']),
                'colors' => json_decode($data['team']['colors']),
            ]);
            if ($request->hasFile('team.image') ) {

                $media =  $team
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
        }catch (\Exception $e) {
            DB::rollBack();
            logger('error',[
                'message' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }

    public function destroy($id)
    {

    }
}
