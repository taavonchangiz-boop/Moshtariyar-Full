<?php

use Illuminate\Support\Facades\Route;
use Modules\Automation\Http\Controllers\WorkflowController;
use Modules\Automation\Http\Controllers\CampaignController;

Route::middleware(['web', 'auth'])->prefix('app')->group(function () {
    Route::get('/workflows', [WorkflowController::class, 'index'])->middleware('can.do:automation.view');
    Route::post('/workflows', [WorkflowController::class, 'store'])->middleware('can.do:automation.manage');
    Route::post('/workflows/{workflow}/toggle', [WorkflowController::class, 'toggle'])->middleware('can.do:automation.manage');
    Route::delete('/workflows/{workflow}', [WorkflowController::class, 'destroy'])->middleware('can.do:automation.manage');

    Route::get('/campaigns', [CampaignController::class, 'index'])->middleware('can.do:automation.view');
    Route::post('/campaigns', [CampaignController::class, 'store'])->middleware('can.do:automation.manage');
    Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])->middleware('can.do:automation.manage');
    Route::get('/campaigns/{campaign}/report', [CampaignController::class, 'show'])->middleware('can.do:automation.view');
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->middleware('can.do:automation.manage');
});
