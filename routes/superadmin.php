<?php

use App\Http\Controllers\SuperAdmin\AnnouncementController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\SuperAdmin\BusinessController;
use App\Http\Controllers\SuperAdmin\CountryController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\DemoCalculatorController;
use App\Http\Controllers\SuperAdmin\ExportController;
use App\Http\Controllers\SuperAdmin\FeatureController;
use App\Http\Controllers\SuperAdmin\FeaturesPageController;
use App\Http\Controllers\SuperAdmin\FinancialController;
use App\Http\Controllers\SuperAdmin\HomePageController;
use App\Http\Controllers\SuperAdmin\ImplementationOrderController;
use App\Http\Controllers\SuperAdmin\ImplementationTierController;
use App\Http\Controllers\SuperAdmin\IndustryController;
use App\Http\Controllers\SuperAdmin\InternalQuoteController;
use App\Http\Controllers\SuperAdmin\PasswordController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\OptionController;
use App\Http\Controllers\SuperAdmin\PlatformSettingController;
use App\Http\Controllers\SuperAdmin\PlatformTaxRateController;
use App\Http\Controllers\SuperAdmin\ProductController;
use App\Http\Controllers\SuperAdmin\QuestionController;
use App\Http\Controllers\SuperAdmin\QuoteHubController;
use App\Http\Controllers\SuperAdmin\RuleController;
use App\Http\Controllers\SuperAdmin\SitePageController;
use App\Http\Controllers\SuperAdmin\SocialLinkController;
use App\Http\Controllers\SuperAdmin\SupportAddonController;
use App\Http\Controllers\SuperAdmin\SupportTicketController;
use App\Http\Controllers\SuperAdmin\SystemInfoController;
use App\Http\Controllers\SuperAdmin\TemplateController;
use App\Http\Controllers\SuperAdmin\ThemeController;
use App\Http\Controllers\SuperAdmin\TrainingArtifactController;
use App\Http\Controllers\SuperAdmin\UserController;
use Illuminate\Support\Facades\Route;

/**
 * Every route here is either guest-only on the "admin" guard (the login
 * screen) or auth:admin-protected — completely separate from the "web"
 * guard business routes in routes/web.php. A business user, even if
 * fully logged in, is NOT authenticated on the "admin" guard, so
 * auth:admin sends them to /superadmin/login same as anyone else.
 */
Route::prefix('superadmin')->name('superadmin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::post('/theme', [ThemeController::class, 'update'])->name('theme.update');

        Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
        Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

        Route::get('/system-info', [SystemInfoController::class, 'index'])->name('system-info.index');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/businesses', [BusinessController::class, 'index'])->name('businesses.index');
        Route::get('/businesses/{business}', [BusinessController::class, 'show'])->name('businesses.show');
        Route::patch('/businesses/{business}/toggle-active', [BusinessController::class, 'toggleActive'])->name('businesses.toggle-active');
        Route::patch('/businesses/{business}/plan', [BusinessController::class, 'assignPlan'])->name('businesses.assign-plan');
        Route::patch('/businesses/{business}/support-access', [BusinessController::class, 'toggleSupportAccess'])->name('businesses.toggle-support-access');
        Route::post('/businesses/{business}/copy-template', [BusinessController::class, 'copyTemplate'])->name('businesses.copy-template');
        Route::delete('/businesses/{business}', [BusinessController::class, 'destroy'])->name('businesses.destroy');
        Route::post('/businesses/{business}/impersonate', [BusinessController::class, 'impersonate'])->name('businesses.impersonate');
        Route::post('/impersonate/exit', [BusinessController::class, 'exitImpersonation'])->name('impersonate.exit');

        Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
        Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
        Route::get('/templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
        Route::patch('/templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
        Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');

        /**
         * The native Super Admin quote builder — mirrors the business-side
         * products/questions/options/rules routes in routes/web.php, but
         * every product/question/option/rule is reached through an
         * explicit {business} (works for templates and real customer
         * businesses alike), never through Auth::user()->business. See
         * app/Http/Controllers/SuperAdmin/ProductController's docblock.
         */
        Route::get('/businesses/{business}/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/businesses/{business}/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/businesses/{business}/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/businesses/{business}/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/businesses/{business}/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/businesses/{business}/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/businesses/{business}/products/{product}/publish', [ProductController::class, 'publish'])->name('products.publish');
        Route::get('/businesses/{business}/products/{product}/preview', [ProductController::class, 'preview'])->name('products.preview');
        Route::patch('/businesses/{business}/quote-hub', [QuoteHubController::class, 'update'])->name('quote-hub.update');

        /*
         * Super Admin's demo-quote tool — every product reachable here
         * belongs to a Template, never a real business, so it's
         * deliberately NOT nested under /businesses/{business}. See
         * App\Http\Controllers\SuperAdmin\InternalQuoteController's
         * docblock. Nothing is saved until Save/Email/PDF is picked on
         * the review screen.
         */
        Route::get('/quotes/demo', [InternalQuoteController::class, 'create'])->name('quotes.create');
        Route::get('/quotes/demo/templates/{template}', [InternalQuoteController::class, 'products'])->name('quotes.create.products');
        Route::get('/quotes/demo/products/{product}', [InternalQuoteController::class, 'show'])->name('quotes.create.show');
        Route::post('/quotes/demo/products/{product}/price', [InternalQuoteController::class, 'livePrice'])->name('quotes.create.price');
        Route::post('/quotes/demo/products/{product}', [InternalQuoteController::class, 'review'])->name('quotes.create.review');
        Route::post('/quotes/demo/products/{product}/save', [InternalQuoteController::class, 'save'])->name('quotes.create.save');
        Route::post('/quotes/demo/products/{product}/email', [InternalQuoteController::class, 'email'])->name('quotes.create.email');
        Route::post('/quotes/demo/products/{product}/pdf', [InternalQuoteController::class, 'pdf'])->name('quotes.create.pdf');
        Route::get('/quotes/{quote}/result', [InternalQuoteController::class, 'result'])->name('quotes.result');
        Route::post('/quotes/{quote}/email', [InternalQuoteController::class, 'emailExisting'])->name('quotes.email');

        Route::get('/businesses/{business}/products/{product}/questions', [QuestionController::class, 'index'])->name('products.questions.index');
        Route::get('/businesses/{business}/products/{product}/questions/create', [QuestionController::class, 'create'])->name('products.questions.create');
        Route::post('/businesses/{business}/products/{product}/questions', [QuestionController::class, 'store'])->name('products.questions.store');
        Route::get('/questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
        Route::put('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');

        Route::get('/questions/{question}/options', [OptionController::class, 'index'])->name('questions.options.index');
        Route::get('/questions/{question}/options/create', [OptionController::class, 'create'])->name('questions.options.create');
        Route::post('/questions/{question}/options', [OptionController::class, 'store'])->name('questions.options.store');
        Route::get('/options/{option}/edit', [OptionController::class, 'edit'])->name('options.edit');
        Route::put('/options/{option}', [OptionController::class, 'update'])->name('options.update');
        Route::delete('/options/{option}', [OptionController::class, 'destroy'])->name('options.destroy');

        Route::get('/businesses/{business}/rules', [RuleController::class, 'index'])->name('rules.index');
        Route::get('/businesses/{business}/rules/create', [RuleController::class, 'create'])->name('rules.create');
        Route::post('/businesses/{business}/rules', [RuleController::class, 'store'])->name('rules.store');
        Route::get('/rules/{rule}/edit', [RuleController::class, 'edit'])->name('rules.edit');
        Route::put('/rules/{rule}', [RuleController::class, 'update'])->name('rules.update');
        Route::delete('/rules/{rule}', [RuleController::class, 'destroy'])->name('rules.destroy');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

        Route::get('/exports/businesses.csv', [ExportController::class, 'businesses'])->name('exports.businesses');
        Route::get('/exports/quotes.csv', [ExportController::class, 'quotes'])->name('exports.quotes');

        Route::resource('features', FeatureController::class)->except('show');
        Route::resource('industries', IndustryController::class)->except('show');

        Route::resource('countries', CountryController::class)->except('show');
        Route::patch('/countries/{country}/toggle-active', [CountryController::class, 'toggleActive'])->name('countries.toggle-active');

        Route::resource('demo-calculators', DemoCalculatorController::class)->except('show');
        Route::patch('/demo-calculators/{demo_calculator}/toggle-active', [DemoCalculatorController::class, 'toggleActive'])->name('demo-calculators.toggle-active');

        Route::resource('site-pages', SitePageController::class)->except('show');

        Route::get('/features-page', [FeaturesPageController::class, 'index'])->name('features-page.index');
        Route::put('/features-page/hero', [FeaturesPageController::class, 'updateHero'])->name('features-page.hero.update');
        Route::get('/features-page/cards/create', [FeaturesPageController::class, 'createCard'])->name('features-page.cards.create');
        Route::post('/features-page/cards', [FeaturesPageController::class, 'storeCard'])->name('features-page.cards.store');
        Route::get('/features-page/cards/{card}/edit', [FeaturesPageController::class, 'editCard'])->name('features-page.cards.edit');
        Route::put('/features-page/cards/{card}', [FeaturesPageController::class, 'updateCard'])->name('features-page.cards.update');
        Route::delete('/features-page/cards/{card}', [FeaturesPageController::class, 'destroyCard'])->name('features-page.cards.destroy');
        Route::patch('/features-page/cards/{card}/toggle-active', [FeaturesPageController::class, 'toggleCardActive'])->name('features-page.cards.toggle-active');

        Route::get('/home-page', [HomePageController::class, 'index'])->name('home-page.index');
        Route::put('/home-page/hero', [HomePageController::class, 'updateHero'])->name('home-page.hero.update');

        Route::resource('social-links', SocialLinkController::class)->except('show');
        Route::patch('/social-links/{social_link}/toggle-active', [SocialLinkController::class, 'toggleActive'])->name('social-links.toggle-active');

        Route::resource('announcements', AnnouncementController::class)->except('show');
        Route::patch('/announcements/{announcement}/toggle-active', [AnnouncementController::class, 'toggleActive'])->name('announcements.toggle-active');

        Route::resource('plans', PlanController::class)->except('show');
        Route::patch('/plans/{plan}/toggle-active', [PlanController::class, 'toggleActive'])->name('plans.toggle-active');

        Route::resource('platform-tax-rates', PlatformTaxRateController::class)->except('show');

        Route::resource('training', TrainingArtifactController::class)->parameters(['training' => 'artifact']);
        Route::get('/training/{artifact}/raw', [TrainingArtifactController::class, 'raw'])->name('training.raw');

        Route::get('/support-addon', [SupportAddonController::class, 'edit'])->name('support-addon.edit');
        Route::patch('/support-addon', [SupportAddonController::class, 'update'])->name('support-addon.update');

        Route::get('/settings', [PlatformSettingController::class, 'edit'])->name('settings.edit');
        Route::patch('/settings', [PlatformSettingController::class, 'update'])->name('settings.update');

        Route::resource('implementation-tiers', ImplementationTierController::class)->except('show');
        Route::patch('/implementation-tiers/{implementation_tier}/toggle-active', [ImplementationTierController::class, 'toggleActive'])->name('implementation-tiers.toggle-active');

        Route::get('/implementation-orders', [ImplementationOrderController::class, 'index'])->name('implementation-orders.index');
        Route::patch('/implementation-orders/{implementation_order}/status', [ImplementationOrderController::class, 'updateStatus'])->name('implementation-orders.update-status');
        Route::get('/financial', [FinancialController::class, 'index'])->name('financial.index');

        Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
        Route::get('/support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
        Route::post('/support/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.reply');
        Route::patch('/support/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->name('support.update-status');
    });
});
