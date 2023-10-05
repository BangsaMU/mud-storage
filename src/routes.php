<?php

use Illuminate\Support\Facades\Route;

Route::get('mud-storage', function () {
    $value = config('StorageConfig.main.APP_CODE');
    echo 'Hello from the storage package!' . json_encode($value);
});


Route::get('storage-list-offline/{path?}',  [Bangsamu\Storage\Controllers\StorageController::class, 'getListLokal'])
    ->name('storage-list-offline');
