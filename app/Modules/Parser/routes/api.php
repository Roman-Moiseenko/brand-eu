<?php

use App\Modules\Parser\Presentation\Http\Controllers\Api\ScraperAvailableController;
use App\Modules\Parser\Presentation\Http\Controllers\Api\ScraperCategoryController;
use App\Modules\Parser\Presentation\Http\Controllers\Api\ScraperProductController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api/scraper-categories',
    'as' => 'api.scraper-categories.',
    'middleware' => ['scraper.api.key'],
], function () {
    Route::post('/next', [ScraperCategoryController::class, 'next'])->name('next');
    Route::post('/result', [ScraperCategoryController::class, 'result'])->name('result');
});

Route::group([
    'prefix' => 'api/scraper-products',
    'as' => 'api.scraper-products.',
    'middleware' => ['scraper.api.key'],
], function () {
    Route::post('/next', [ScraperProductController::class, 'next'])->name('next');
    Route::post('/result', [ScraperProductController::class, 'result'])->name('result');
});

Route::group([
    'prefix' => 'api/scraper-available',
    'as' => 'api.scraper-available.',
    'middleware' => ['scraper.api.key'],
], function () {
    Route::post('/next', [ScraperAvailableController::class, 'next'])->name('next');
    Route::post('/result', [ScraperAvailableController::class, 'result'])->name('result');
});
