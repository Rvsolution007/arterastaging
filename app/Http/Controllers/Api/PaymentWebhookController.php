<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Events\PaymentFailed;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from payment providers.
     *
     * SECURITY: This endpoint is publicly accessible (no auth middleware).
     * It MUST verify the webhook signature to prevent spoofed requests.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        // =====================================================
        // SECURITY: Verify webhook signature
        // =====================================================
        // For Stripe: verify Stripe-Signature header
        $signature = $request->header('Stripe-Signature') 
                   ?? $request->header('X-Razorpay-Signature')
                   ?? $request->header('X-Verify');

        if (!$signature) {
            Log::warning('Webhook rejected: missing signature header', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Missing signature'], 403);
        }

        // Verify the webhook secret
        $webhookSecret = config('services.stripe.webhook_secret') 
                       ?? env('PAYMENT_WEBHOOK_SECRET');

        if ($webhookSecret) {
            // Stripe signature verification
            if ($request->header('Stripe-Signature')) {
                try {
                    $computedSig = hash_hmac('sha256', $request->getContent(), $webhookSecret);
                    // Parse Stripe's "t=...,v1=..." format
                    $sigParts = [];
                    foreach (explode(',', $signature) as $part) {
                        [$key, $val] = explode('=', $part, 2);
                        $sigParts[$key] = $val;
                    }
                    $expectedSig = hash_hmac(
                        'sha256',
                        ($sigParts['t'] ?? '') . '.' . $request->getContent(),
                        $webhookSecret
                    );
                    if (!hash_equals($expectedSig, $sigParts['v1'] ?? '')) {
                        Log::warning('Webhook rejected: invalid Stripe signature', [
                            'ip' => $request->ip(),
                        ]);
                        return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
                    }
                } catch (\Exception $e) {
                    Log::warning('Webhook rejected: signature verification failed', [
                        'ip' => $request->ip(),
                        'error' => $e->getMessage(),
                    ]);
                    return response()->json(['status' => 'error', 'message' => 'Signature verification failed'], 403);
                }
            } else {
                // Generic HMAC verification for other providers
                $computedSig = hash_hmac('sha256', $request->getContent(), $webhookSecret);
                if (!hash_equals($computedSig, $signature)) {
                    Log::warning('Webhook rejected: invalid signature', [
                        'ip' => $request->ip(),
                    ]);
                    return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 403);
                }
            }
        } else {
            // SECURITY: If no webhook secret is configured, reject all webhooks
            // Configure PAYMENT_WEBHOOK_SECRET in .env to enable webhook processing
            Log::error('Webhook rejected: PAYMENT_WEBHOOK_SECRET not configured');
            return response()->json(['status' => 'error', 'message' => 'Webhook not configured'], 503);
        }

        Log::info('Webhook verified and processing: ', ['type' => $payload['type'] ?? 'unknown']);

        $type = $payload['type'] ?? null;

        if ($type === 'invoice.payment_failed') {
            $email = $payload['data']['object']['customer_email'] ?? null;

            if ($email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $subscription = $user->active_subscription ?? (object)['plan_name' => 'Premium Plan'];
                    
                    $declineCode = $payload['data']['object']['last_payment_error']['decline_code'] 
                                   ?? $payload['data']['object']['last_payment_error']['code'] 
                                   ?? 'unknown_reason';

                    $user->dunning_status = 'day1';
                    $user->dunning_started_at = now();
                    $user->last_payment_failure_reason = $declineCode;
                    $user->save();

                    event(new PaymentFailed($user, $subscription, $declineCode));

                    Log::info("PaymentFailed event fired for user {$user->id} with reason: {$declineCode}");
                    return response()->json(['status' => 'success', 'message' => 'Dunning initiated'], 200);
                }
            }
        }

        return response()->json(['status' => 'ignored'], 200);
    }
}
