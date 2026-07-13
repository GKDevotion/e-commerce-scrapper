<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $webhookSecret = config('services.stripe.webhook_secret');
        $payload       = $request->getContent();
        $sigHeader     = $request->header('Stripe-Signature');

        try {
            // Verify Stripe signature
            if ($webhookSecret) {
                $parts     = explode(',', $sigHeader ?? '');
                $timestamp = null;
                $signature = null;
                foreach ($parts as $part) {
                    if (str_starts_with($part, 't=')) $timestamp = substr($part, 2);
                    if (str_starts_with($part, 'v1=')) $signature = substr($part, 3);
                }
                $signedPayload    = "{$timestamp}.{$payload}";
                $expectedSig      = hash_hmac('sha256', $signedPayload, $webhookSecret);
                if (!hash_equals($expectedSig, $signature ?? '')) {
                    Log::warning('Stripe webhook signature mismatch');
                    return response()->json(['error' => 'Invalid signature'], 400);
                }
            }

            $event = json_decode($payload, true);
            $type  = $event['type'] ?? '';

            Log::info("Stripe webhook: {$type}");

            match($type) {
                'checkout.session.completed'    => $this->handleCheckoutCompleted($event['data']['object']),
                'invoice.payment_succeeded'     => $this->handleInvoiceSucceeded($event['data']['object']),
                'invoice.payment_failed'        => $this->handleInvoiceFailed($event['data']['object']),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event['data']['object']),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event['data']['object']),
                default => null,
            };

        } catch (\Exception $e) {
            Log::error("Stripe webhook error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleCheckoutCompleted(array $session): void
    {
        $metadata  = $session['metadata'] ?? [];
        $userId    = $metadata['user_id'] ?? null;
        $planId    = $metadata['plan_id'] ?? null;

        if (!$userId || !$planId) return;

        $user = User::find($userId);
        $plan = Plan::find($planId);
        if (!$user || !$plan) return;

        // Create payment record
        Payment::create([
            'user_id'          => $user->id,
            'plan_id'          => $plan->id,
            'gateway_payment_id' => $session['payment_intent'] ?? $session['id'],
            'gateway'          => 'stripe',
            'amount'           => ($session['amount_total'] ?? 0) / 100,
            'currency'         => strtoupper($session['currency'] ?? 'usd'),
            'status'           => 'success',
            'gateway_response' => $session,
            'invoice_number'   => 'STR-' . strtoupper(substr($session['id'], -8)),
        ]);

        // Activate subscription
        Subscription::updateOrCreate(
            ['user_id' => $user->id, 'status' => ['pending', 'cancelled']],
            [
                'plan_id'                  => $plan->id,
                'status'                   => 'active',
                'payment_gateway'          => 'stripe',
                'gateway_subscription_id'  => $session['subscription'] ?? null,
                'gateway_customer_id'      => $session['customer'] ?? null,
                'amount'                   => ($session['amount_total'] ?? 0) / 100,
                'currency'                 => strtoupper($session['currency'] ?? 'USD'),
                'current_period_start'     => now(),
                'current_period_end'       => now()->addMonth(),
            ]
        );

        $user->update(['plan_id' => $plan->id]);
        Log::info("Stripe checkout completed for user {$user->id}");
    }

    private function handleInvoiceSucceeded(array $invoice): void
    {
        Log::info("Stripe invoice paid: " . $invoice['id']);
        $sub = Subscription::where('gateway_subscription_id', $invoice['subscription'] ?? '')->first();
        if ($sub) {
            $sub->update([
                'status'             => 'active',
                'current_period_end' => now()->addMonth(),
            ]);
        }
    }

    private function handleInvoiceFailed(array $invoice): void
    {
        Log::warning("Stripe invoice payment failed: " . $invoice['id']);
        $sub = Subscription::where('gateway_subscription_id', $invoice['subscription'] ?? '')->first();
        if ($sub) {
            $sub->update(['status' => 'past_due']);
        }
    }

    private function handleSubscriptionDeleted(array $subscription): void
    {
        $sub = Subscription::where('gateway_subscription_id', $subscription['id'])->first();
        if ($sub) {
            $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            $freePlan = Plan::where('slug', 'free')->first();
            $sub->user?->update(['plan_id' => $freePlan?->id]);
        }
    }

    private function handleSubscriptionUpdated(array $subscription): void
    {
        Log::info("Stripe subscription updated: " . $subscription['id']);
    }
}
