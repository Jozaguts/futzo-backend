<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReSendVerificationCodeRequest;
use App\Http\Requests\UserImageUpdateRequest;
use App\Http\Requests\UserPasswordUpdateRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Random\RandomException;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class UserController extends Controller
{
	public function update(UserUpdateRequest $request, User $user): JsonResponse
	{
		$validated = $request->validated();

		$response = $user->update($validated);

		return response()->json(['success' => $response, 'message' => 'User updated successfully']);
	}

	/**
	 * @throws FileIsTooBig
	 * @throws FileDoesNotExist
	 */
	public function updateImage(UserImageUpdateRequest $request, User $user): JsonResponse
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

	/**
	 * @throws RandomException
	 */
	public function resendVerifyCode(ReSendVerificationCodeRequest $request)
	{
		$validated = $request->validated();
		$isPhone = isset($validated['phone']);
		$user = User::where($isPhone ? 'phone' : 'email', $validated[$isPhone ? 'phone' : 'email'])->firstOrFail();
		$user->update([
            'verification_token' => random_int(1000, 9999),
            'verified_at' => null,
        ]);

		$user->sendEmailVerificationNotification();

		return response()->noContent();
	}
}
