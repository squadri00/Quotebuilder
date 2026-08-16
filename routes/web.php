<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InternalQuoteController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicQuoteController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\QuoteHubController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\RuleController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TrainingArtifactController;
use App\Http\Controllers\Stripe\WebhookController as StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\PaymentController as CashierPaymentController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'business.active'])->name('dashboard');

// Temporary — a visual reference for the Prompt 4A style direction.
// Safe to remove once the real admin screens exist (Prompt 4+).
Route::get('/design-preview', function () {
    return view('design-preview');
})->middleware('auth')->name('design-preview');

// No account exists yet at this point (see PendingRegistration) — these
// are deliberately outside the auth-gated group, guarded by the
// unguessable token instead of a session.
Route::get('/checkout/{token}', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout/{token}', [CheckoutController::class, 'confirm'])->name('checkout.confirm');
Route::get('/checkout/{token}/success', [CheckoutController::class, 'success'])->name('checkout.success');

// Also no account yet — a team invitation only becomes a real User row
// once the invitee sets their password here (see AcceptInvitationController).
Route::get('/team/accept/{token}', [AcceptInvitationController::class, 'show'])->name('team.accept');
Route::post('/team/accept/{token}', [AcceptInvitationController::class, 'store'])->name('team.accept.store');

Route::middleware(['auth', 'business.active'])->group(function () {
    Route::post('/theme', [ThemeController::class, 'update'])->name('theme.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/business/qrcode', [QrCodeController::class, 'business'])->name('business.qrcode');

    Route::middleware('permission:business_settings')->group(function () {
        Route::patch('/business', [BusinessSettingsController::class, 'updateDetails'])->name('business.update');
        Route::post('/business/branding', [BusinessSettingsController::class, 'updateBranding'])->name('business.branding.update');
        Route::post('/business/public-quote-settings', [BusinessSettingsController::class, 'updatePublicQuoteSettings'])->name('business.public-quote-settings.update');

        Route::get('/get-started', [OnboardingController::class, 'create'])->name('onboarding.create');
        Route::post('/get-started', [OnboardingController::class, 'store'])->name('onboarding.store');
        Route::post('/get-started/dismiss', [OnboardingController::class, 'dismiss'])->name('onboarding.dismiss');
    });

    Route::resource('tax-rates', TaxRateController::class)->except('show')->middleware('permission:tax_rates');

    // Registered before the products resource route below — otherwise
    // PATCH /products/{product} (from the resource route) matches first
    // and tries to bind "quote-hub" as a product ID, 404ing before this
    // route is ever reached.
    Route::patch('/products/quote-hub', [QuoteHubController::class, 'update'])->name('quote-hub.update');
    Route::resource('products', ProductController::class)->except('show');
    Route::post('/products/{product}/publish', [ProductController::class, 'publish'])->name('products.publish');
    Route::get('/products/{product}/qrcode', [QrCodeController::class, 'show'])->name('products.qrcode');
    Route::post('/products/{product}/images', [ProductController::class, 'storeImages'])->name('products.images.store');
    Route::delete('/products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
    Route::resource('products.questions', QuestionController::class)->shallow()->except('show');
    Route::resource('questions.options', OptionController::class)->shallow()->except('show');
    Route::resource('rules', RuleController::class)->except('show');

    // Self-service "add a template product to my account" — available any
    // time, on every plan, unlike the one-time onboarding flow above.
    // Businesses can't change their own Industry here; only Super Admin
    // can (see SuperAdmin\BusinessController) — this only ever shows
    // templates matching whatever Industry is already assigned.
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::post('/templates/{product}', [TemplateController::class, 'store'])->name('templates.store');

    // Read-only library of the build sheets installed alongside this
    // business's own products — see BusinessTrainingArtifact's docblock.
    Route::get('/training', [TrainingArtifactController::class, 'index'])->name('training.index');
    Route::get('/training/{artifact}', [TrainingArtifactController::class, 'show'])->name('training.show');
    Route::get('/training/{artifact}/raw', [TrainingArtifactController::class, 'raw'])->name('training.raw');

    Route::get('/quotes', [QuoteController::class, 'index'])->name('quotes.index');
    Route::patch('/quotes/{quote}/status', [QuoteController::class, 'updateStatus'])->name('quotes.update-status');
    Route::post('/quotes/{quote}/email', [InternalQuoteController::class, 'emailExisting'])->name('quotes.email');

    // The internal, staff-facing quote builder — see InternalQuoteController's
    // docblock. Named quotes.create.* to sit next to quotes.index rather than
    // under a literal /admin prefix, matching this app's existing convention
    // of no /admin segment for business-authenticated routes.
    Route::get('/quotes/create', [InternalQuoteController::class, 'create'])->name('quotes.create');
    Route::get('/quotes/create/{product}', [InternalQuoteController::class, 'show'])->name('quotes.create.show');
    Route::post('/quotes/create/{product}/price', [InternalQuoteController::class, 'livePrice'])->name('quotes.create.price');
    Route::post('/quotes/create/{product}', [InternalQuoteController::class, 'review'])->name('quotes.create.review');
    Route::post('/quotes/create/{product}/save', [InternalQuoteController::class, 'save'])->name('quotes.create.save');
    Route::post('/quotes/create/{product}/email', [InternalQuoteController::class, 'email'])->name('quotes.create.email');
    Route::post('/quotes/create/{product}/pdf', [InternalQuoteController::class, 'pdf'])->name('quotes.create.pdf');
    Route::get('/quotes/{quote}/result', [InternalQuoteController::class, 'result'])->name('quotes.create.result');

    // Must come after every /quotes/create... route above — a literal
    // "create" segment collides with {quote} otherwise (Laravel matches
    // routes in registration order, and this one would otherwise swallow
    // /quotes/create as if "create" were a quote ID).
    Route::get('/quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');

    Route::resource('customers', CustomerController::class);
    Route::post('/customers/{customer}/block', [CustomerController::class, 'block'])->name('customers.block');
    Route::post('/customers/{customer}/unblock', [CustomerController::class, 'unblock'])->name('customers.unblock');

    Route::middleware('permission:announcements')->group(function () {
        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements/{announcement}/dismiss', [AnnouncementController::class, 'dismiss'])->name('announcements.dismiss');
    });

    Route::middleware('permission:support')->group(function () {
        Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
        Route::get('/support/create', [SupportTicketController::class, 'create'])->name('support.create');
        Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store');
        Route::get('/support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
        Route::post('/support/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.reply');
    });

    Route::middleware('team.manage')->group(function () {
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::get('/team/invite', [TeamController::class, 'create'])->name('team.create');
        Route::post('/team/invite', [TeamController::class, 'store'])->name('team.store');
        Route::post('/team/invitations/{invitation}/resend', [TeamController::class, 'resend'])->name('team.invitations.resend');
        Route::delete('/team/invitations/{invitation}', [TeamController::class, 'revoke'])->name('team.invitations.revoke');
        Route::get('/team/{member}/edit', [TeamController::class, 'edit'])->name('team.edit');
        Route::patch('/team/{member}', [TeamController::class, 'update'])->name('team.update');
        Route::patch('/team/{member}/toggle-active', [TeamController::class, 'toggleActive'])->name('team.toggle-active');
    });

    Route::middleware('billing.access')->group(function () {
        Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('/billing/portal', [BillingController::class, 'billingPortal'])->name('billing.portal');
        Route::post('/billing/subscribe/{plan}', [BillingController::class, 'subscribe'])->name('billing.subscribe');
        Route::post('/billing/swap/{plan}', [BillingController::class, 'swap'])->name('billing.swap');
        Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
        Route::post('/billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
        Route::post('/billing/support/subscribe', [BillingController::class, 'subscribeSupport'])->name('billing.support.subscribe');
        Route::post('/billing/support/cancel', [BillingController::class, 'cancelSupport'])->name('billing.support.cancel');
        Route::post('/billing/support/resume', [BillingController::class, 'resumeSupport'])->name('billing.support.resume');
        Route::post('/billing/implementation/{tier}', [BillingController::class, 'purchaseImplementation'])->name('billing.implementation.purchase');
    });
});

Route::get('/stripe/payment/{id}', [CashierPaymentController::class, 'show'])->name('cashier.payment');
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// Public, no-login quote builder — a business's customers use this.
// Rate-limited with the 'public' limiter set up in Prompt 0.
Route::middleware('throttle:public')->group(function () {
    // These two more-specific patterns must be registered before the
    // generic {business:slug}/{product:slug} route below, or Laravel
    // matches that one first — e.g. /quote/8/pdf would be parsed as
    // business slug "8", product slug "pdf" and 404 on the business
    // lookup instead of ever reaching downloadPdf().
    Route::get('/quote/view/{uuid}', [PublicQuoteController::class, 'viewByUuid'])->name('quote.view');
    Route::get('/quote/{quote}/pdf', [PublicQuoteController::class, 'downloadPdf'])->name('quote.pdf');

    // Single-segment — safe from the two-segment collision warned about
    // above, since Laravel only matches routes with the same segment count.
    Route::get('/quote/{business:slug}', [PublicQuoteController::class, 'picker'])->name('quote.picker');

    Route::get('/quote/{business:slug}/{product:slug}', [PublicQuoteController::class, 'show'])->name('quote.show');
    Route::post('/quote/{business:slug}/{product:slug}', [PublicQuoteController::class, 'store'])->name('quote.store');
});

require __DIR__.'/auth.php';
require __DIR__.'/superadmin.php';
