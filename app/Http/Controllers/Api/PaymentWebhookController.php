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
     * Handle incoming webhooks from Stripe or Razorpay.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('Webhook received: ', $payload);

        // Simulated logic for Stripe `invoice.payment_failed`
        // In reality, you'd check $request->type == 'invoice.payment_failed'
        $type = $payload['type'] ?? 'invoice.payment_failed';

        if ($type === 'invoice.payment_failed') {
            // Find user by customer_email or customer_id
            // For this demo, we'll assume the payload sends the email
            $email = $payload['data']['object']['customer_email'] ?? $request->input('email');

            if ($email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $subscription = current($user->active_subscription) ? current($user->active_subscription) : (object)['plan_name' => 'Premium Plan'];
                    
                    // Extract decline reason (Stripe format)
                    $declineCode = $payload['data']['object']['last_payment_error']['decline_code'] 
                                   ?? $payload['data']['object']['last_payment_error']['code'] 
                                   ?? $request->input('decline_code') 
                                   ?? 'unknown_reason';

                    // Mark as day1 of dunning
                    $user->dunning_status = 'day1';
                    $user->dunning_started_at = now();
                    $user->last_payment_failure_reason = $declineCode;
                    $user->save();

                    // Fire the event (which sends email + push)
                    event(new PaymentFailed($user, $subscription, $declineCode));

                    Log::info("PaymentFailed event fired for user {$user->id} with reason: {$declineCode}");
                    return response()->json(['status' => 'success', 'message' => 'Dunning initiated'], 200);
                }
            }
        }

        return response()->json(['status' => 'ignored'], 200);
    }
}
