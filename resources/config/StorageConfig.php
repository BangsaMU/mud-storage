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
        'TOKEN' => '', /*auth untuk masuk ke sytem api storage*/
        'URL' => env('STORAGE_URL', 'http://localhost:8080/api/upload'), /*harus diakhiri dengan / (slash) url untuk upload storage*/
        'CALL_BACK' => '',
    ),
];
