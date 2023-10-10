<?php
return [
    'curl' => array(
        'TIMEOUT' => 30,
        'VERIFY' => false,
        'LIST_NETWORK' => env('APP_LIST_NETWORK'),
    ),
    'main' => array(
        'APP_CODE' => '', /*10 digit max char dari master app */
        'KEY' => '', /*32 digit  char random untuk hasing token harus sama antara server an client untuk decode token dari server */
        'ACTIVE' => env('STORAGE_ACTIVE', false), /*jika akan mengunakan auto upload set ke true [true,false], tambahkan di env STORAGE_ACTIVE untuk config di lokal development*/
        'TOKEN' => env('STORAGE_TOKEN', 'demo123'), /*auth untuk masuk ke sytem api storage*/
        'FOLDER' => env('STORAGE_FOLDER'), /*auth untuk masuk ke sytem api storage*/
        'BACKUP_FILE_WL' => 'jpg,png,pdf',/*list extension yang di ijinkan*/
        'BACKUP_FILE_BL' => 'zip,rar',/*list extension yang tidak di ijinkan*/
        'ATTACH_METHOD' => 'fopen',/*[fopen,file_get_contents] dianjurkan file_get_contents default*/
        'URL' => env('STORAGE_URL', 'http://localhost:8080/api/upload'), /*harus diakhiri dengan / (slash) url untuk upload storage*/
        'CALL_BACK' => '',
    ),
];
