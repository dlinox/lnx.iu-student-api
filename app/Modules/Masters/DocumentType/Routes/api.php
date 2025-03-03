<?php

use App\Modules\Masters\DocumentType\Http\Controllers\DocumentTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/document-types')->group(function () {
    
    Route::get('/items/for-select', [DocumentTypeController::class, 'getItemsForSelect']);
});
