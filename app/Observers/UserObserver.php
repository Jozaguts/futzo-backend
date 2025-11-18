<?php

namespace App\Observers;

use App\Jobs\SendMetaCapiEventJob;
use App\Models\User;
use App\Notifications\NewUserRegisteredNotification;
use App\Notifications\SendOTPViaTwilioVerifyNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Random\RandomException;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     * @throws RandomException
     */
    public function creating(User $user): void
    {
        if (empty($user->verification_token)) {
            $user->verification_token = random_int(1000, 9999);
        }

        if (empty($user->image)) {
            $user->image = 'https://ui-avatars.com/api/?name=' . $user->name . '&color=9155fd&background=F9FAFB';
        }

        if (empty($user->status)) {
            $user->status = User::PENDING_ONBOARDING_STATUS;
        }

        if (is_null($user->trial_ends_at)) {
            $days = (int) config('billing.trial_days', 14);
            if ($days > 0) {
                $user->trial_ends_at = now()->addDays($days);
            }
        }

        if (empty($user->plan)) {
            $user->plan = config('billing.default_plan', User::PLAN_FREE);
        }

        if (is_null($user->plan_started_at)) {
            $user->plan_started_at = now();
        }

        if (is_null($user->tournaments_used)) {
            $user->tournaments_used = 0;
        }

        if (is_null($user->tournaments_quota)) {
            $definition = $user->planDefinition($user->plan);
            $user->tournaments_quota = $definition['tournaments_quota'] ?? null;
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $request = $this->request();

        $this->applyMarketingContext($user, $request);

        if (!$user->hasRole('predeterminado')) {
            $user->assignRole('predeterminado');
        }

        $this->dispatchMetaEvent($user, $request);

        event(new Registered($user));

        if (!is_null($user->phone) && is_null($user->email)) {
            $phoneNumber = $user->phone;
            $token = $user->verification_token;
            DB::table('phone_password_resets')->updateOrInsert(
                ['phone' => $phoneNumber],
                ['token' => $token, 'created_at' => now()]
            );
            $user->notify(new SendOTPViaTwilioVerifyNotification($phoneNumber, $token));
        }

        Notification::route('mail', 'sagit@futzo.io')
            ->notify(new NewUserRegisteredNotification($user));
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }

    protected function request(): ?Request
    {
        if (!app()->bound('request')) {
            return null;
        }

        $request = request();

        return $request instanceof Request ? $request : null;
    }

    protected function applyMarketingContext(User $user, ?Request $request): void
    {
        if (!$request) {
            return;
        }

        $updated = false;
        foreach (['fbp', 'fbc', 'fbclid'] as $key) {
            $value = $request->input($key);
            if ($value) {
                $user->{$key} = $value;
                $updated = true;
            }
        }

        if ($request->has('consent')) {
            $user->capi_consent = $request->boolean('consent');
            $updated = true;
        }

        if ($updated) {
            $user->save();
        }
    }

    protected function dispatchMetaEvent(User $user, ?Request $request): void
    {
        if (!$request || !app()->environment(['production', 'local'])) {
            return;
        }

        $eventId = $request->input('event_id', (string) Str::uuid());
        $hasFbAttribution = !empty($user->fbp) || !empty($user->fbc) || !empty($user->fbclid);

        if (!$hasFbAttribution) {
            return;
        }

        $userCtx = [
            'email'       => $user->email,
            'external_id' => (string) $user->id,
            'ip'          => $request->ip(),
            'ua'          => $request->userAgent(),
            'fbp'         => $user->fbp,
            'fbc'         => $user->fbc,
            'fbclid'      => $user->fbclid,
        ];

        SendMetaCapiEventJob::dispatch(
            eventName: 'StartTrial',
            eventId: $eventId,
            userCtx: $userCtx,
            custom: ['trial_days' => (int) config('billing.trial_days', 7), 'value' => 0, 'currency' => 'MXN'],
            eventSourceUrl: config('app.url') . '/login',
            testCode: $request->input('test_event_code'),
            actionSource: 'website',
            consent: $request->boolean('consent', true)
        );
    }
}
