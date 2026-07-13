<?php

use App\Http\Controllers\AiGenerationController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Webhooks\RazorpayWebhookController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// ─── Public ───────────────────────────────────────────────────────────────────
Route::get('/', fn () => view('welcome'))->name('home');

// ─── Webhooks (CSRF-exempt via bootstrap/app.php) ─────────────────────────────
Route::post('/webhooks/razorpay', [RazorpayWebhookController::class, 'handle'])->name('webhooks.razorpay');
Route::post('/webhooks/stripe',   [StripeWebhookController::class, 'handle'])->name('webhooks.stripe');

// ─── Guest Only ───────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',    [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login',   [LoginController::class, 'login'])->name('login.post')->middleware('throttle:auth');

    Route::get('/register',  [RegisterController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

    Route::get('/forgot-password',  [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

    Route::get('/reset-password/{token}',  [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password',         [ResetPasswordController::class, 'reset'])->name('password.update');
});

// ─── Logout ───────────────────────────────────────────────────────────────────
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Authenticated ────────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Listings / Product Imports
    Route::prefix('listings')->name('listings.')->group(function () {
        Route::get('/',         [ProductImportController::class, 'index'])->name('index');
        Route::get('/new',      [ProductImportController::class, 'create'])->name('create');
        Route::post('/',        [ProductImportController::class, 'store'])->name('store')->middleware('throttle:scraper');
        Route::get('/{import}', [ProductImportController::class, 'show'])->name('show');
        Route::delete('/{import}', [ProductImportController::class, 'destroy'])->name('destroy');
    });

    // AI Generations
    Route::prefix('generations')->name('generations.')->group(function () {
        Route::post('/generate/{import}',    [AiGenerationController::class, 'generate'])->name('generate')->middleware('throttle:ai-generation');
        Route::get('/manual/{import}',       [AiGenerationController::class, 'createManual'])->name('manual.create');
        Route::post('/manual/{import}',      [AiGenerationController::class, 'storeManual'])->name('manual.store');
        Route::get('/{generation}',          [AiGenerationController::class, 'show'])->name('view');
        Route::get('/{generation}/edit',     [AiGenerationController::class, 'edit'])->name('edit');
        Route::put('/{generation}',          [AiGenerationController::class, 'update'])->name('update');
        Route::post('/{generation}/publish',  [AiGenerationController::class, 'publish'])->name('publish');
        Route::post('/{generation}/favorite',[AiGenerationController::class, 'toggleFavorite'])->name('favorite');
        Route::delete('/{generation}',       [AiGenerationController::class, 'destroy'])->name('destroy');
    });

    // Exports
    Route::post('/export/{generation}', [ExportController::class, 'export'])->name('export');
    Route::get('/export/{generation}/images', [ExportController::class, 'downloadImagesZip'])->name('export.images.zip');
    Route::get('/export/{generation}/images/{index}', [ExportController::class, 'downloadSingleImage'])->name('export.images.single');

    // Image gallery page
    Route::get('/generations/{generation}/images', [ExportController::class, 'imagesGallery'])->name('generations.images');

    // Billing
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/plans',               [BillingController::class, 'plans'])->name('plans');
        Route::get('/subscription',        [BillingController::class, 'subscription'])->name('subscription');
        Route::get('/checkout/{plan}',     [BillingController::class, 'checkout'])->name('checkout');
        Route::post('/razorpay/verify',    [BillingController::class, 'verifyRazorpay'])->name('razorpay.verify');
        Route::post('/stripe/intent',      [BillingController::class, 'createStripeIntent'])->name('stripe.intent');
        Route::post('/stripe/verify',      [BillingController::class, 'verifyStripe'])->name('stripe.verify');
        Route::post('/cancel',             [BillingController::class, 'cancel'])->name('cancel');
        Route::post('/subscribe/{plan}',   [BillingController::class, 'checkout'])->name('subscribe');
    });

    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/',          [ProfileController::class, 'index'])->name('index');
        Route::put('/',          [ProfileController::class, 'update'])->name('update');
        Route::put('/password',  [ProfileController::class, 'updatePassword'])->name('password');
    });
});

// ─── Admin ────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard',        [AdminController::class, 'dashboard'])->name('dashboard');

    // Users
    Route::get('/users',                    [AdminController::class, 'users'])->name('users');
    Route::get('/users/{user}',             [AdminController::class, 'showUser'])->name('users.show');
    Route::put('/users/{user}',             [AdminController::class, 'updateUser'])->name('users.update');
    Route::post('/users/{user}/suspend',    [AdminController::class, 'suspendUser'])->name('users.suspend');
    Route::post('/users/{user}/activate',   [AdminController::class, 'activateUser'])->name('users.activate');
    Route::delete('/users/{user}',          [AdminController::class, 'deleteUser'])->name('users.delete');

    // Plans
    Route::get('/plans',                    [AdminController::class, 'plans'])->name('plans');
    Route::get('/plans/create',             [AdminController::class, 'createPlan'])->name('plans.create');
    Route::post('/plans',                   [AdminController::class, 'storePlan'])->name('plans.store');
    Route::get('/plans/{plan}/edit',        [AdminController::class, 'editPlan'])->name('plans.edit');
    Route::put('/plans/{plan}',             [AdminController::class, 'updatePlan'])->name('plans.update');

    // AI Settings
    Route::get('/ai-settings',              [AdminController::class, 'aiSettings'])->name('ai-settings');
    Route::post('/ai-settings',             [AdminController::class, 'updateAiSettings'])->name('ai-settings.update');

    // Analytics
    Route::get('/analytics',                [AdminController::class, 'analytics'])->name('analytics');
    Route::get('/logs/api',                 [AdminController::class, 'apiLogs'])->name('logs.api');
    Route::get('/logs/audit',               [AdminController::class, 'auditLogs'])->name('logs.audit');
    Route::get('/payments',                 [AdminController::class, 'payments'])->name('payments');
    Route::get('/prompts',                  [AdminController::class, 'promptTemplates'])->name('prompts');
    Route::get('/prompts/create',           [AdminController::class, 'createPromptTemplate'])->name('prompts.create');
    Route::post('/prompts',                 [AdminController::class, 'storePromptTemplate'])->name('prompts.store');
    Route::get('/prompts/{template}/edit',  [AdminController::class, 'editPromptTemplate'])->name('prompts.edit');
    Route::put('/prompts/{template}',       [AdminController::class, 'updatePromptTemplate'])->name('prompts.update');
    Route::delete('/prompts/{template}',    [AdminController::class, 'destroyPromptTemplate'])->name('prompts.destroy');
});
