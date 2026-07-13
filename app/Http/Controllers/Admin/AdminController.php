<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGeneration;
use App\Models\ApiLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\ProductImport;
use App\Models\PromptTemplate;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // Admin access is enforced via the 'admin' route middleware (see routes/web.php)

    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_listings' => ProductImport::count(),
            'total_generations' => AiGeneration::count(),
            'completed_generations' => AiGeneration::where('status', 'completed')->count(),
            'total_revenue' => Payment::where('status', 'success')->sum('amount'),
            'monthly_revenue' => Payment::where('status', 'success')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'total_api_calls' => ApiLog::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'total_tokens_used' => AiGeneration::sum('total_tokens'),
            'total_ai_cost' => AiGeneration::sum('ai_cost'),
        ];

        // Chart data - last 30 days registrations
        $userGrowth = User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $listingGrowth = AiGeneration::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $recentUsers = User::with('plan')->latest()->take(5)->get();
        $recentPayments = Payment::with(['user', 'plan'])->latest()->take(5)->get();

        $planDistribution = User::with('plan')
            ->select('plan_id', DB::raw('COUNT(*) as count'))
            ->groupBy('plan_id')
            ->get();

        return view('admin.dashboard', compact(
            'stats', 'userGrowth', 'listingGrowth',
            'recentUsers', 'recentPayments', 'planDistribution'
        ));
    }

    // Users Management
    public function users(Request $request)
    {
        $query = User::with('plan');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('company_name', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->status) $query->where('status', $request->status);
        if ($request->plan_id) $query->where('plan_id', $request->plan_id);
        if ($request->role) $query->where('role', $request->role);

        $users = $query->latest()->paginate(20)->withQueryString();
        $plans = Plan::where('is_active', true)->get();

        return view('admin.users.index', compact('users', 'plans'));
    }

    public function showUser(User $user)
    {
        $user->load(['plan', 'productImports', 'aiGenerations', 'subscriptions', 'payments']);
        return view('admin.users.show', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'status' => 'required|in:active,suspended,pending',
            'role' => 'required|in:admin,user',
            'plan_id' => 'nullable|exists:plans,id',
            'notes' => 'nullable|string',
        ]);

        $user->update($validated);
        return back()->with('success', 'User updated successfully.');
    }

    public function suspendUser(User $user)
    {
        if ($user->isAdmin()) return back()->with('error', 'Cannot suspend admin users.');
        $user->update(['status' => 'suspended']);
        return back()->with('success', 'User suspended.');
    }

    public function activateUser(User $user)
    {
        $user->update(['status' => 'active']);
        return back()->with('success', 'User activated.');
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) return back()->with('error', 'Cannot delete yourself.');
        $user->delete();
        return redirect()->route('admin.users')->with('success', 'User deleted.');
    }

    // Plans Management
    public function plans()
    {
        $plans = Plan::orderBy('sort_order')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function createPlan()
    {
        return view('admin.plans.create');
    }

    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|unique:plans|max:50',
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'listings_limit' => 'required|integer|min:-1',
            'ai_generations_limit' => 'required|integer|min:-1',
            'exports_limit' => 'required|integer|min:-1',
        ]);

        $plan = Plan::create(array_merge($validated, [
            'amazon_publish' => $request->boolean('amazon_publish'),
            'bulk_import' => $request->boolean('bulk_import'),
            'team_accounts' => $request->boolean('team_accounts'),
            'priority_support' => $request->boolean('priority_support'),
            'api_access' => $request->boolean('api_access'),
            'is_featured' => $request->boolean('is_featured'),
        ]));

        return redirect()->route('admin.plans')->with('success', 'Plan created successfully.');
    }

    public function editPlan(Plan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function updatePlan(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'listings_limit' => 'required|integer|min:-1',
        ]);

        $plan->update(array_merge($validated, [
            'amazon_publish' => $request->boolean('amazon_publish'),
            'bulk_import' => $request->boolean('bulk_import'),
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
        ]));

        return back()->with('success', 'Plan updated.');
    }

    // AI Settings
    public function aiSettings()
    {
        $templates = PromptTemplate::all();
        return view('admin.ai-settings', compact('templates'));
    }

    public function updateAiSettings(Request $request)
    {
        $validated = $request->validate([
            'openai_model' => 'required|string',
            'max_tokens' => 'required|integer|min:500|max:8000',
            'temperature' => 'required|numeric|min:0|max:2',
        ]);

        // Update .env or settings table
        // For now, store in cache/settings
        cache()->forever('ai_settings', $validated);

        return back()->with('success', 'AI settings updated.');
    }

    // Analytics
    public function analytics()
    {
        $apiLogs = ApiLog::select('service', DB::raw('COUNT(*) as count'), DB::raw('AVG(response_time_ms) as avg_time'))
            ->groupBy('service')
            ->get();

        $topUsers = User::withCount(['aiGenerations' => fn($q) => $q->where('status', 'completed')])
            ->orderByDesc('ai_generations_count')
            ->take(10)
            ->get();

        $revenueByMonth = Payment::where('status', 'success')
            ->select(DB::raw('YEAR(created_at) as year'), DB::raw('MONTH(created_at) as month'), DB::raw('SUM(amount) as total'))
            ->groupBy('year', 'month')
            ->orderBy('year')->orderBy('month')
            ->take(12)
            ->get();

        return view('admin.analytics', compact('apiLogs', 'topUsers', 'revenueByMonth'));
    }

    public function apiLogs(Request $request)
    {
        $query = ApiLog::with('user')->latest();
        if ($request->service) $query->where('service', $request->service);
        if ($request->success !== null) $query->where('success', $request->success === '1');
        $logs = $query->paginate(30)->withQueryString();
        $services = ApiLog::distinct()->pluck('service');
        return view('admin.logs.api', compact('logs', 'services'));
    }

    public function auditLogs(Request $request)
    {
        $query = \App\Models\AuditLog::with('user')->latest();
        if ($request->user_id) $query->where('user_id', $request->user_id);
        if ($request->action)  $query->where('action', 'like', '%'.$request->action.'%');
        $logs = $query->paginate(30)->withQueryString();
        return view('admin.logs.audit', compact('logs'));
    }

    public function payments(Request $request)
    {
        $query = Payment::with(['user', 'plan'])->latest();
        if ($request->status)  $query->where('status', $request->status);
        if ($request->gateway) $query->where('gateway', $request->gateway);
        $payments = $query->paginate(25)->withQueryString();
        $totalRevenue = Payment::where('status', 'success')->sum('amount');
        $monthRevenue = Payment::where('status', 'success')->whereMonth('created_at', now()->month)->sum('amount');
        return view('admin.payments', compact('payments', 'totalRevenue', 'monthRevenue'));
    }

    public function promptTemplates()
    {
        $templates = \App\Models\PromptTemplate::orderByDesc('is_default')->orderBy('name')->get();
        return view('admin.prompts.index', compact('templates'));
    }

    public function createPromptTemplate()
    {
        return view('admin.prompts.create');
    }

    public function storePromptTemplate(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|unique:prompt_templates|max:60',
            'description' => 'nullable|string|max:500',
            'system_prompt' => 'required|string',
            'user_prompt_template' => 'nullable|string',
            'ai_model' => 'required|string|max:60',
            'max_tokens' => 'required|integer|min:100|max:8000',
            'temperature' => 'required|numeric|min:0|max:2',
        ]);
        $v['is_active']  = $request->boolean('is_active');
        $v['is_default'] = $request->boolean('is_default');
        if ($v['is_default']) \App\Models\PromptTemplate::where('is_default', true)->update(['is_default' => false]);
        \App\Models\PromptTemplate::create($v);
        return redirect()->route('admin.prompts')->with('success', 'Template created.');
    }

    public function editPromptTemplate(\App\Models\PromptTemplate $template)
    {
        return view('admin.prompts.edit', compact('template'));
    }

    public function updatePromptTemplate(Request $request, \App\Models\PromptTemplate $template)
    {
        $v = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'system_prompt' => 'required|string',
            'user_prompt_template' => 'nullable|string',
            'ai_model' => 'required|string|max:60',
            'max_tokens' => 'required|integer|min:100|max:8000',
            'temperature' => 'required|numeric|min:0|max:2',
        ]);
        $v['is_active']  = $request->boolean('is_active');
        $v['is_default'] = $request->boolean('is_default');
        if ($v['is_default']) \App\Models\PromptTemplate::where('id','!=',$template->id)->update(['is_default'=>false]);
        $template->update($v);
        return redirect()->route('admin.prompts')->with('success', 'Template updated.');
    }

    public function destroyPromptTemplate(\App\Models\PromptTemplate $template)
    {
        if ($template->is_default) return back()->with('error', 'Cannot delete default template.');
        $template->delete();
        return redirect()->route('admin.prompts')->with('success', 'Template deleted.');
    }

}
