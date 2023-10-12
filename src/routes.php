<?php

use Illuminate\Support\Facades\Route;

Route::get('mud-storage', function () {
    $value = config('StorageConfig.main.APP_CODE');
    echo 'Hello from the storage package!' . json_encode($value);
});


Route::get('storage-list-offline/{path?}',  [Bangsamu\Storage\Controllers\StorageController::class, 'getListLokal'])
    ->name('storage-list-offline');

Route::get('storage-scan/{path?}',  [Bangsamu\Storage\Controllers\StorageController::class, 'scanDir'])
    ->name('storage-scan');

Route::get('storage-scan-reset',  [Bangsamu\Storage\Controllers\StorageController::class, 'scanDirReset'])
    ->name('storage-scan-reset');

Route::get('storage-upload-db',  [Bangsamu\Storage\Controllers\StorageController::class, 'uploadSyncDB'])
    ->name('storage-upload-db');
