<?php

use App\Http\Controllers\Sync\EndpointController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Environment Sync endpoints
|--------------------------------------------------------------------------
|
| Registered from bootstrap/app.php OUTSIDE the web middleware group.
| Auth is the X-Sync-Token header (config('sync.shared_secret') /
| apply_secret), not a login session.
|
|   GET  /sync/endpoint   -> this environment's fingerprint (5-min cache)
|   POST /sync/apply       -> back up the DB, then `php artisan migrate --force`
|
*/

Route::get('/sync/endpoint', [EndpointController::class, 'endpoint']);
Route::post('/sync/apply', [EndpointController::class, 'apply']);
