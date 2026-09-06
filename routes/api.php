<?php

use App\Http\Controllers\Api\WordPressController;
use Illuminate\Support\Facades\Route;

// Called by the seo-connector plugin on the customer's WordPress site.
Route::post('wp/handshake', [WordPressController::class, 'handshake'])->name('api.wp.handshake');
