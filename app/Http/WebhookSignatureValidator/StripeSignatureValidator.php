<?php

namespace App\Http\WebhookSignatureValidator;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $sig_header = $request->header($config->signatureHeaderName);
        $endpoint_secret = $config->signingSecret;
        $payload = @file_get_contents('php://input');
        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (UnexpectedValueException $e) {
            // Invalid payload
            return false;
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            return false;
        }

        return true;
    }
}