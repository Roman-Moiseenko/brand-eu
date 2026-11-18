<?php

use App\Modules\Chat\Presentation\Http\Controllers\Web\ChatController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'role:admin|staff',
    'prefix' => 'admin/chat',
    'as' => 'admin.chat.',
],function () {
    Route::get('/', [ChatController::class, 'index'])->name('index');
});
