<?php

use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\Storefront\RobotsTxtController;
use App\Http\Controllers\Storefront\SitemapController;
use App\Http\Controllers\Storefront\WebManifestController;
use App\Http\Middleware\LoadBusinessSettings;
use App\Livewire\Cafe\HomePage;
use App\Livewire\Cafe\MenuPage;
use Illuminate\Support\Facades\Route;

Route::get('/healthz', HealthCheckController::class)
    ->middleware('throttle:60,1')
    ->name('healthz');

require __DIR__.'/admin.php';

Route::middleware([LoadBusinessSettings::class])->group(function (): void {
    Route::get('/robots.txt', RobotsTxtController::class)->name('storefront.robots');
    Route::get('/sitemap.xml', SitemapController::class)->name('storefront.sitemap');
    Route::get('/site.webmanifest', WebManifestController::class)->name('storefront.manifest');
    Route::get('/', HomePage::class)->name('cafe.home');
    Route::get('/menu', MenuPage::class)->name('cafe.menu');
    Route::redirect('/contact', '/#contact')->name('cafe.contact');
});

Route::post('/telemetry/livewire-client-failure', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::warning('livewire.client_failure', [
        'status' => $request->integer('status'),
        'snippet' => \Illuminate\Support\Str::limit((string) $request->input('snippet'), 500),
        'url' => (string) $request->input('url'),
        'staff_user_id' => auth('tenant')->id(),
    ]);

    return response()->noContent();
})->middleware('web');
