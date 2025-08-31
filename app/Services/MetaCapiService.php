<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class MetaCapiService
{
    private string $graph = 'https://graph.facebook.com/v21.0';
    private string $pixelId;
    private string $accessToken;

    public function __construct()
    {
        $this->pixelId = config('services.meta.pixel_id');
        $this->accessToken = config('services.meta.access_token');
    }
    public static function sha256Lower(?string $v): ?string
    {
        if (!$v) {
            return null;
        }
        return hash('sha256', strtolower(trim($v)));
    }
    public static function normalizeFbc(?string $fbc, ?string $fbclid): ?string
    {
        if ($fbc) {
            return $fbc;
        }
        if ($fbclid) {
            return 'fb.1.' . time() . '.' . $fbclid;
        }
        return null;
    }
    public function buildUserData(array $ctx): array
    {
        $ud = array_filter([
            'client_ip_address' => $ctx['ip'] ?? null,
            'client_user_agent' => $ctx['ua'] ?? null,
            'fbc' => self::normalizeFbc($ctx['fbc'] ?? null, $ctx['fbclid'] ?? null),
            'fbp' => $ctx['fbp'] ?? null,
        ], fn($v) => $v !== null && $v !== '');

        if (!empty($ctx['email'])) {
            $ud['em'] = [self::sha256Lower($ctx['email'])];
        }
        if (!empty($ctx['external_id'])) {
            $ud['external_id'] = self::sha256Lower($ctx['external_id']);
        }
        return $ud;
    }

    /**
     * @throws ConnectionException
     */
    public function postEvent(array $event, ?string $testCode = null): array
    {
        \Log::info('env', [app()->environment()]);
        $payload = ['data' => [$event]];
        if ($testCode) {
            $payload['test_event_code'] = $testCode;
        }


        $url = "{$this->graph}/{$this->pixelId}/events?access_token={$this->accessToken}";
        $resp = Http::asJson()->retry(3, 800)->post($url, $payload);

        if ($resp->failed()) {
            throw new \RuntimeException('Meta CAPI error: ' . $resp->body());
        }
        return $resp->json();
    }
}
