<?php

namespace App\Http\Controllers;

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
}
