<?php

namespace App\Http\Controllers;

use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;

class OnboardingController extends Controller
{
    public function __construct(private readonly OnboardingService $service)
    {
    }

    public function index(): JsonResponse
    {
        $user = auth()->user();
        $data = $this->service->stepsFor($user);
        return response()->json($data);
    }
}

