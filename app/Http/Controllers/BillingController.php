<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    public function plans()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return view('billing.plans', compact('plans'));
    }

    public function subscription()
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription;
        $payments = $user->payments()->with('plan')->latest()->paginate(10);
        return view('billing.subscription', compact('user', 'subscription', 'payments'));
    }

    public function subscribe(Request $request, Plan $plan)
    {
        $request->validate([
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $user = Auth::user();

        if ($plan->isFree()) {
            // Assign free plan
            $user->update(['plan_id' => $plan->id, 'listings_used' => 0]);
            return redirect()->route('billing.plans')->with('success', 'You are now on the Free plan.');
        }

        // For paid plans, redirect to payment gateway
        // In production: integrate Razorpay/Stripe checkout
        // For now: simulate subscription creation
        $amount = $request->billing_cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        // Create pending subscription record
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'billing_cycle' => $request->billing_cycle,
            'amount' => $amount,
            'currency' => 'USD',
        ]);

        // Redirect to payment page (Razorpay/Stripe)
        return redirect()->route('billing.plans')
            ->with('info', "Ready to subscribe to {$plan->name}! Payment gateway integration is configured via RAZORPAY_KEY_ID / STRIPE_KEY in .env");
    }

    public function cancel(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription;

        if ($subscription) {
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $request->reason,
            ]);

            // Revert to free plan
            $freePlan = Plan::where('slug', 'free')->first();
            $user->update(['plan_id' => $freePlan?->id]);
        }

        return redirect()->route('billing.plans')->with('success', 'Subscription cancelled. You have been moved to the Free plan.');
    }
}
