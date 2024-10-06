<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserImageUpdateRequest;
use App\Http\Requests\UserPasswordUpdateRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        $response = $user->update($validated);

        return response()->json(['success' => $response, 'message' => 'User updated successfully']);
    }

    public function updateAvatar(UserImageUpdateRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();


        $url = $user
            ->addMedia($validated['image'])
            ->toMediaCollection('image', 's3')
            ->getUrl();

        $response = $user->update(['image' => $url]);


        return response()->json(['success' => $response, 'message' => 'User image updated successfully']);
    }

    public function updatePassword(UserPasswordUpdateRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        $response = $user->update(['password' => bcrypt($validated['new_password'])]);

        return response()->json(['success' => $response, 'message' => 'User password updated successfully']);
    }
}
