<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingPortalController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = $request->user();

        $target = $authUser;
        $targetId = (int) $request->query('user_id', 0);
        if ($targetId && $targetId !== $authUser->id) {
            // Permitir ver el portal de otro usuario solo a super administradores
            if (method_exists($authUser, 'hasRole') && !$authUser->hasRole('super administrador')) {
                abort(403, 'No autorizado');
            }
            $target = User::findOrFail($targetId);
        }

        // Asegurar y sincronizar el cliente de Stripe con datos locales
        $target->createOrGetStripeCustomer();
        try {
            $target->updateStripeCustomer([
                'email' => (string) $target->email,
                'name'  => trim(($target->name ?? '') . ' ' . ($target->last_name ?? '')),
                'phone' => $target->phone,
            ]);
        } catch (\Throwable $e) {
            // continuar incluso si la actualización falla
        }

        $returnUrl = config('app.frontend_url') . '/configuracion';
        $session = $target->stripe()->billingPortal->sessions->create([
            'customer' => $target->stripe_id,
            'return_url' => $returnUrl,
        ]);

        return response()->json([
            'url' => $session->url,
        ]);
    }
}

