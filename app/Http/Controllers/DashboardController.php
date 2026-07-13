<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $stats = [
            'total_imports'      => $user->productImports()->count(),
            'total_generations'  => $user->aiGenerations()->count(),
            'completed_listings' => $user->aiGenerations()->where('status', 'completed')->count(),
            'total_exports'      => $user->exports()->where('status', 'completed')->count(),
            'listings_remaining' => $user->getRemainingListings(),
            'usage_percentage'   => $user->getUsagePercentage(),
        ];

        $recentGenerations = $user->aiGenerations()
            ->with('productImport')
            ->latest()
            ->take(6)
            ->get();

        $recentImports = $user->productImports()
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.index', compact('user', 'stats', 'recentGenerations', 'recentImports'));
    }
}
