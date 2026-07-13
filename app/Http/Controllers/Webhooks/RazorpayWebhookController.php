<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $secret = config('services.razorpay.webhook_secret');
        $signature = $request->header('X-Razorpay-Signature');
        $payload = $request->getContent();

        // Verify webhook signature
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expectedSignature, $signature ?? '')) {
            Log::warning('Razorpay webhook signature mismatch');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = $request->input('event');
        $data  = $request->input('payload');

        Log::info("Razorpay webhook received: {$event}");

        try {
            match($event) {
                'payment.captured' => $this->handlePaymentCaptured($data['payment']['entity']),
                'payment.failed'   => $this->handlePaymentFailed($data['payment']['entity']),
                'subscription.activated' => $this->handleSubscriptionActivated($data['subscription']['entity']),
                'subscription.cancelled' => $this->handleSubscriptionCancelled($data['subscription']['entity']),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error("Razorpay webhook error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok']);
    }

    private function handlePaymentCaptured(array $payment): void
    {
        $notes = $payment['notes'] ?? [];
        $userId = $notes['user_id'] ?? null;
        $planId = $notes['plan_id'] ?? null;

        if (!$userId || !$planId) {
            Log::warning('Razorpay payment.captured missing user_id/plan_id in notes', $payment);
            return;
        }

        $user = User::find($userId);
        $plan = Plan::find($planId);

        if (!$user || !$plan) return;

        // Record payment
        Payment::create([
            'user_id'          => $user->id,
            'plan_id'          => $plan->id,
            'gateway_payment_id' => $payment['id'],
            'gateway'          => 'razorpay',
            'amount'           => $payment['amount'] / 100, // paise to rupees/dollars
            'currency'         => strtoupper($payment['currency']),
            'status'           => 'success',
            'gateway_response' => $payment,
            'invoice_number'   => 'RZP-' . strtoupper(substr($payment['id'], -8)),
        ]);

        // Activate subscription
        $subscription = Subscription::updateOrCreate(
            ['user_id' => $user->id, 'status' => 'pending'],
            [
                'plan_id'              => $plan->id,
                'status'               => 'active',
                'payment_gateway'      => 'razorpay',
                'gateway_payment_id'   => $payment['id'],
                'amount'               => $payment['amount'] / 100,
                'currency'             => strtoupper($payment['currency']),
                'current_period_start' => now(),
                'current_period_end'   => now()->addMonth(),
            ]
        );

        $user->update(['plan_id' => $plan->id]);

        Log::info("Razorpay payment captured for user {$user->id}, plan: {$plan->name}");
    }

    private function handlePaymentFailed(array $payment): void
    {
        Log::warning("Razorpay payment failed: " . $payment['id']);

        Payment::create([
            'user_id'          => $payment['notes']['user_id'] ?? null,
            'plan_id'          => $payment['notes']['plan_id'] ?? null,
            'gateway_payment_id' => $payment['id'],
            'gateway'          => 'razorpay',
            'amount'           => $payment['amount'] / 100,
            'currency'         => strtoupper($payment['currency'] ?? 'INR'),
            'status'           => 'failed',
            'gateway_response' => $payment,
        ]);
    }

    private function handleSubscriptionActivated(array $subscription): void
    {
        Log::info("Razorpay subscription activated: " . $subscription['id']);
    }

    private function handleSubscriptionCancelled(array $subscription): void
    {
        $sub = Subscription::where('gateway_subscription_id', $subscription['id'])->first();
        if ($sub) {
            $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            $freePlan = Plan::where('slug', 'free')->first();
            $sub->user?->update(['plan_id' => $freePlan?->id]);
        }
    }
}
