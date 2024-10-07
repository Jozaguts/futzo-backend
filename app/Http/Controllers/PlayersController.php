<?php

namespace App\Http\Controllers;

use App\Events\RegisteredPlayer;
use App\Http\Requests\PlayerStoreRequest;
use App\Http\Requests\PlayerUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PlayersController extends Controller
{

    public function index(Request $request)
    {

    }

    public function show($id)
    {

    }

    public function store(PlayerStoreRequest $request)
    {
        try {

            DB::beginTransaction();
            $userData = $request->userFormData();
            $temporaryPassword = str()->random(8);
            $userData['password'] = $temporaryPassword;
            $playerData = $request->playerFormData();
            $user = User::create($userData);
            $user->assignRole('jugador');
            $user->league()->associate(auth()->user()->league);
            $user->save();
            $user->players()->create($playerData);
            event(new RegisteredPlayer($user, $userData['password']));
            if ($userData['image'] instanceof UploadedFile) {
                $image = $user->addMediaFromRequest('basic.image')->toMediaCollection('image');
                $user->update(['image' => $image->getUrl()]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    public function update(PlayerUpdateRequest $request, $id)
    {
        $request->except('_method');
    }

    public function destroy($id)
    {

    }


}
