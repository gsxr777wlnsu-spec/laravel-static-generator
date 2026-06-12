<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\AiAgentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/ai-agent', [AiAgentController::class, 'edit'])->name('ai-agent.edit');
    
    Route::prefix('sites')->name('sites.')->group(function () {
        Route::get('/', [SiteController::class, 'index'])->name('index');
        Route::get('/create', [SiteController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [SiteController::class, 'edit'])->name('edit');
        Route::get('/{id}/creation-log', [SiteController::class, 'creationLog'])->name('creation-log');
    });
    
    Route::prefix('sites/{siteId}/pages')->name('pages.')->group(function () {
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('/create', [PageController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [PageController::class, 'edit'])->name('edit');
    });

    Route::prefix('sites/{siteId}/media')->name('media.')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->name('index');
        Route::get('/serve/{path}', [MediaController::class, 'serve'])->name('serve')->where('path', '.*');
    });
});
