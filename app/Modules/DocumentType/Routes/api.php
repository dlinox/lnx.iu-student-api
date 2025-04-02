<?php

use Illuminate\Support\Facades\Route;
use App\Modules\DocumentType\Http\Controllers\DocumentTypeController;

Route::prefix('api/document-type')->middleware('auth:sanctum')
    ->group(function () {
        Route::get('items/for-select', [DocumentTypeController::class, 'getItemsForSelect']);
    });
