<?php

use App\Http\Controllers\Api\AiContentController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\TemplateSetController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'throttle:api'])->group(function () {

    Route::get('/page-templates', [PageController::class, 'pageTemplates']);

    Route::prefix('sites')->group(function () {
        Route::get('/', [SiteController::class, 'index']);
        Route::post('/', [SiteController::class, 'store']);
        Route::get('/{id}', [SiteController::class, 'show']);
        Route::put('/{id}', [SiteController::class, 'update']);
        Route::delete('/{id}', [SiteController::class, 'destroy']);
        Route::post('/{id}/clone', [SiteController::class, 'clone']);
        Route::post('/{id}/generate', [SiteController::class, 'generate']);
        Route::post('/{id}/deploy', [SiteController::class, 'deploy']);
        Route::post('/{id}/import-and-deploy', [SiteController::class, 'importAndDeploy']);
        Route::post('/{id}/test-sftp', [SiteController::class, 'testSftp']);
    });

    Route::prefix('pages')->group(function () {
        Route::get('/', [PageController::class, 'index']);
        Route::post('/', [PageController::class, 'store']);
        Route::get('/{id}', [PageController::class, 'show']);
        Route::put('/{id}', [PageController::class, 'update']);
        Route::delete('/{id}', [PageController::class, 'destroy']);
        Route::get('/{id}/preview', [PageController::class, 'preview']);
        Route::post('/{id}/preview-token', [PageController::class, 'generatePreviewToken']);
        Route::post('/{id}/sections/bootstrap', [PageController::class, 'bootstrapSections']);
    });

    Route::prefix('preview')->group(function () {
        Route::get('/{token}/{path?}', [PageController::class, 'servePreview'])->where('path', '.*');
    });

    Route::prefix('sections')->group(function () {
        Route::get('/', [SectionController::class, 'index']);
        Route::post('/', [SectionController::class, 'store']);
        Route::get('/{id}', [SectionController::class, 'show']);
        Route::put('/{id}', [SectionController::class, 'update']);
        Route::delete('/{id}', [SectionController::class, 'destroy']);
        Route::post('/reorder', [SectionController::class, 'reorder']);
    });

    Route::prefix('media')->group(function () {
        Route::get('/', [MediaController::class, 'index']);
        Route::post('/', [MediaController::class, 'store']);
        Route::get('/{id}', [MediaController::class, 'show']);
        Route::put('/{id}', [MediaController::class, 'update']);
        Route::delete('/{id}', [MediaController::class, 'destroy']);
        Route::post('/{id}/resize', [MediaController::class, 'resize']);
    });

    Route::prefix('ai')->group(function () {
        Route::post('/process-markdown', [AiContentController::class, 'processMarkdown']);
        Route::post('/generate', [AiContentController::class, 'generate']);
    });

    Route::prefix('templates')->group(function () {
        Route::get('/', [TemplateSetController::class, 'index']);
        Route::post('/', [TemplateSetController::class, 'store']);
        Route::get('/built-in', [TemplateSetController::class, 'builtIn']);
        Route::get('/{id}', [TemplateSetController::class, 'show']);
        Route::put('/{id}', [TemplateSetController::class, 'update']);
        Route::delete('/{id}', [TemplateSetController::class, 'destroy']);
        Route::post('/{id}/clone', [TemplateSetController::class, 'clone']);
        Route::get('/{id}/validate', [TemplateSetController::class, 'validate']);
    });

    Route::prefix('import')->group(function () {
        Route::post('/', [ImportController::class, 'import']);
        Route::post('/deploy', [ImportController::class, 'importAndDeploy']);
        Route::get('/templates', [ImportController::class, 'templates']);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
    });

});
