<?php

namespace App\Http\Resources;

use App\Models\ProductPrice;
use App\Services\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $onboarding = app(OnboardingService::class)->stepsFor($this->resource);
        $planDefinition = $this->resource->planDefinition();
        $contactMethod = $this->resource->contact_method;
        if(is_null($contactMethod)) {
            if(!is_null($this->resource->email)) {
                $contactMethod = 'email';
            } elseif(!is_null($this->resource->phone)) {
                $contactMethod = 'phone';
            }
        }
        $currentPlan = [
            'slug' => $this->resource->planSlug(),
            'label' => $this->resource->planLabel(),
            'tournaments_quota' => $this->resource->tournamentsQuota(),
            'tournaments_used' => (int) $this->resource->tournaments_used,
            'can_create_more_tournaments' => $this->resource->canCreateTournament(),
            'started_at' => $this->resource->plan_started_at,
            'expires_at' => $this->resource->plan_expires_at,
            'description' => $planDefinition['description'] ?? null,
        ];

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'contact_method' => $contactMethod,
            'opened_tickets_count' => $this->resource->openedTicketsCount(),
            'roles' => $this->resource->roles()->pluck('name'),
            'league' => new LeagueResource($this->whenLoaded('league')),
            'has_league' => (bool)$this->resource->league,
            'verified' => (bool)$this->resource->verified_at,
            'phone' => $this->resource->phone,
            'image' => $this->resource->image,
            'status' => $this->resource->status,
            'is_operational' => (bool) $this->resource->isOperationalForBilling(),
            'onboarding' => $onboarding,
            'subscribed' => $this->resource->hasActiveSubscription(),
            'current_plan' => $currentPlan,
            'plan' => ProductPrice::where('stripe_price_id', $this->resource?->subscription()?->stripe_price)
                ->select(['id','billing_period','price','product_id'])
                ->with('product:id,name,sku')
                ->first()
            ?->setAttribute('current_period_end',Carbon::createFromTimestamp( $this->resource?->subscription()?->asStripeSubscription()?->current_period_end)->translatedFormat('l d M Y'))

        ];
    }
}
