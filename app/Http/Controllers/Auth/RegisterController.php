<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegisterForm()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return view('auth.register', compact('plans'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'      => ['required', 'confirmed', Password::min(8)],
            'company_name'  => ['nullable', 'string', 'max:255'],
            'terms'         => ['required', 'accepted'],
        ]);

        $freePlan = Plan::where('slug', 'free')->first();

        $user = User::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'password'     => Hash::make($request->password),
            'company_name' => $request->company_name,
            'plan_id'      => $freePlan?->id,
        ]);

        Auth::login($user);

        $this->logAudit($user, 'user_registered');

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to Amazon Listing Builder! Your account is ready.');
    }

    private function logAudit(User $user, string $action): void
    {
        try {
            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => $action,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Non-critical — don't break registration if audit logging fails
        }
    }
}
