<?php

namespace App\Http\Controllers;

use App\Mail\PlanUpgradedEmail;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

    public function checkout(Plan $plan, Request $request)
    {
        $request->validate(['billing_cycle' => 'required|in:monthly,yearly']);
        $user = Auth::user();

        if ($plan->isFree()) {
            $user->update(['plan_id' => $plan->id]);
            return redirect()->route('billing.plans')->with('success', 'Switched to Free plan.');
        }

        $amount = $request->billing_cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $currency = 'INR';

        $subscription = Subscription::updateOrCreate(
            ['user_id' => $user->id, 'status' => 'pending', 'plan_id' => $plan->id],
            ['billing_cycle' => $request->billing_cycle, 'amount' => $amount, 'currency' => $currency]
        );

        $gateway = $this->detectGateway();
        if ($gateway === 'razorpay') return $this->razorpayView($plan, $subscription, $amount, $currency, $request->billing_cycle);
        if ($gateway === 'stripe')   return $this->stripeView($plan, $subscription, $amount, $request->billing_cycle);
        return view('billing.checkout-pending', compact('plan', 'subscription', 'amount', 'currency'));
    }

    private function razorpayView($plan, $subscription, $amount, $currency, $billingCycle)
    {
        $user = Auth::user();
        $keyId = config('services.razorpay.key_id');
        $amountPaise = (int)($amount * 100);
        return view('billing.checkout-razorpay', compact('plan','subscription','amount','amountPaise','currency','billingCycle','keyId','user'));
    }

    private function stripeView($plan, $subscription, $amount, $billingCycle)
    {
        $user = Auth::user();
        $publishableKey = config('services.stripe.key');
        $amountCents = (int)($amount * 100);
        return view('billing.checkout-stripe', compact('plan','subscription','amount','amountCents','billingCycle','publishableKey','user'));
    }

    public function verifyRazorpay(Request $request)
    {
        $request->validate(['razorpay_payment_id'=>'required','subscription_id'=>'required|integer','plan_id'=>'required|integer']);
        $user = Auth::user();
        $plan = Plan::findOrFail($request->plan_id);
        $subscription = Subscription::findOrFail($request->subscription_id);
        if ($subscription->user_id !== $user->id) abort(403);

        if ($request->razorpay_order_id && $request->razorpay_signature) {
            $sig = hash_hmac('sha256', $request->razorpay_order_id.'|'.$request->razorpay_payment_id, config('services.razorpay.key_secret'));
            if (!hash_equals($sig, $request->razorpay_signature))
                return redirect()->route('billing.plans')->with('error', 'Payment verification failed.');
        }

        $subscription->update(['status'=>'active','payment_gateway'=>'razorpay','gateway_payment_id'=>$request->razorpay_payment_id,
            'current_period_start'=>now(),'current_period_end'=>$subscription->billing_cycle==='yearly'?now()->addYear():now()->addMonth()]);
        Payment::create(['user_id'=>$user->id,'subscription_id'=>$subscription->id,'plan_id'=>$plan->id,
            'gateway_payment_id'=>$request->razorpay_payment_id,'gateway'=>'razorpay',
            'amount'=>$subscription->amount,'currency'=>$subscription->currency,'status'=>'success',
            'invoice_number'=>'RZP-'.strtoupper(substr($request->razorpay_payment_id,-8))]);
        $user->update(['plan_id'=>$plan->id]);

        try { Mail::to($user->email)->queue(new PlanUpgradedEmail($user,$plan,$subscription->billing_cycle,(float)$subscription->amount)); } catch(\Exception $e) {}
        return redirect()->route('dashboard')->with('success',"🎉 Payment successful! You are now on the {$plan->name} plan.");
    }

    public function createStripeIntent(Request $request)
    {
        $request->validate(['subscription_id'=>'required|integer','amount_cents'=>'required|integer|min:50']);
        $subscription = Subscription::findOrFail($request->subscription_id);
        if ($subscription->user_id !== Auth::id()) return response()->json(['error'=>'Unauthorized'],403);
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            $intent = \Stripe\PaymentIntent::create(['amount'=>$request->amount_cents,'currency'=>'usd',
                'metadata'=>['user_id'=>Auth::id(),'plan_id'=>$subscription->plan_id,'subscription_id'=>$subscription->id]]);
            return response()->json(['client_secret'=>$intent->client_secret]);
        } catch(\Exception $e) { return response()->json(['error'=>$e->getMessage()],500); }
    }

    public function verifyStripe(Request $request)
    {
        $request->validate(['payment_intent_id'=>'required','subscription_id'=>'required|integer','plan_id'=>'required|integer']);
        $user = Auth::user();
        $plan = Plan::findOrFail($request->plan_id);
        $subscription = Subscription::findOrFail($request->subscription_id);
        if ($subscription->user_id !== $user->id) abort(403);
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            $pi = \Stripe\PaymentIntent::retrieve($request->payment_intent_id);
            if ($pi->status !== 'succeeded') return redirect()->route('billing.plans')->with('error','Payment not completed.');
            $subscription->update(['status'=>'active','payment_gateway'=>'stripe','gateway_payment_id'=>$request->payment_intent_id,
                'current_period_start'=>now(),'current_period_end'=>$subscription->billing_cycle==='yearly'?now()->addYear():now()->addMonth()]);
            Payment::create(['user_id'=>$user->id,'subscription_id'=>$subscription->id,'plan_id'=>$plan->id,
                'gateway_payment_id'=>$request->payment_intent_id,'gateway'=>'stripe',
                'amount'=>$subscription->amount,'currency'=>'USD','status'=>'success',
                'invoice_number'=>'STR-'.strtoupper(substr($request->payment_intent_id,-8))]);
            $user->update(['plan_id'=>$plan->id]);
            try { Mail::to($user->email)->queue(new PlanUpgradedEmail($user,$plan,$subscription->billing_cycle,(float)$subscription->amount)); } catch(\Exception $e) {}
            return redirect()->route('dashboard')->with('success',"🎉 Payment successful! You are now on the {$plan->name} plan.");
        } catch(\Exception $e) { return redirect()->route('billing.plans')->with('error',$e->getMessage()); }
    }

    public function cancel(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription;
        if ($subscription) {
            $subscription->update(['status'=>'cancelled','cancelled_at'=>now(),'cancellation_reason'=>$request->reason]);
            $user->update(['plan_id'=>Plan::where('slug','free')->first()?->id]);
        }
        return redirect()->route('billing.plans')->with('success','Subscription cancelled. Moved to Free plan.');
    }

    private function detectGateway(): string
    {
        if (config('services.razorpay.key_id') && config('services.razorpay.key_secret')) return 'razorpay';
        if (config('services.stripe.key') && config('services.stripe.secret')) return 'stripe';
        return 'none';
    }
}
