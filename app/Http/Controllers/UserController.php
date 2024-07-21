<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAvatarUpdateRequest;
use App\Http\Requests\UserPasswordUpdateRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        $response =  $user->update($validated);

        return response()->json(['success' => $response, 'message' => 'User updated successfully']);
    }

    public function updateAvatar(UserAvatarUpdateRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();


        $url = $user
            ->addMedia($validated['avatar'])
            ->toMediaCollection('avatar', 's3')
            ->getUrl();
        logger($url);
       $response =  $user->update(['avatar' => $url]);


        return response()->json(['success' => $response, 'message' => 'User avatar updated successfully']);
    }

    public function updatePassword(UserPasswordUpdateRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        $response =  $user->update(['password' => bcrypt($validated['new_password'])]);

        return response()->json(['success' => $response, 'message' => 'User password updated successfully']);
    }
}
