<?php

namespace App\Jobs;

use App\Services\MetaCapiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMetaCapiEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $eventName,
        public string $eventId,
        public array $userCtx,      // email, external_id, ip, ua, fbp, fbc, fbclid
        public array $custom = [],
        public ?string $eventSourceUrl = null,
        public ?string $testCode = null,
        public string $actionSource = 'website',
        public ?bool $consent = null,
    ) {}

    public function handle(MetaCapiService $svc): void
    {
        if ($this->consent === false) {
            return;
        }

        $dedupeKey = 'capi_dedupe:' . $this->eventName . ':' . $this->eventId;
        if (!cache()->add($dedupeKey, 1, now()->addHours(48))) {
            return; // ya enviado
        }

        $event = [
            'event_name'       => $this->eventName,
            'event_time'       => time(),
            'event_id'         => $this->eventId,
            'action_source'    => $this->actionSource,
            'event_source_url' => $this->eventSourceUrl,
            'user_data'        => $svc->buildUserData($this->userCtx),
            'custom_data'      => $this->custom,
        ];
        $effectiveTestCode = $this->testCode ?: (app()->environment(['local','development','testing']) ? config('services.meta.test_event_code') : null);
        $svc->postEvent($event, $effectiveTestCode);
    }
}
