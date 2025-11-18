<?php

return [
    // Days for platform trial when user signs up without paying
    'trial_days' => env('BILLING_TRIAL_DAYS', 7),

    // Default plan slug assigned to new users
    'default_plan' => env('BILLING_DEFAULT_PLAN', 'free'),

    // Plans catalog used to derive quotas and labels
    'plans' => [
        'free' => [
            'name' => 'Free',
            'tournaments_quota' => (int) env('PLAN_FREE_TOURNAMENTS_QUOTA', 1),
            'description' => 'Hasta 1 torneo activo.',
        ],
        'kickoff' => [
            'name' => 'Kickoff',
            'tournaments_quota' => env('PLAN_KICKOFF_TOURNAMENTS_QUOTA'),
            'description' => 'Plan Kickoff (pago).',
        ],
        'pro_play' => [
            'name' => 'Pro Play',
            'tournaments_quota' => env('PLAN_PRO_PLAY_TOURNAMENTS_QUOTA'),
            'description' => 'Plan Pro Play (pago).',
        ],
        'elite_league' => [
            'name' => 'Elite League',
            'tournaments_quota' => env('PLAN_ELITE_LEAGUE_TOURNAMENTS_QUOTA'),
            'description' => 'Plan Elite League (pago).',
        ],
    ],
];
