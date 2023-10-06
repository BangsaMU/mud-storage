<?php

namespace Bangsamu\Storage\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Validator;
// use Response;
use Illuminate\Support\Str;
use App\Models\Telegram;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\RequestException;

class StorageController extends Controller
{

    function __construct()
    {
    }

    public function getListLokal(Request $request, $folder = null, $backup = false)
    {
        $backup = $request->backup;
        $allMedia = null;
        $path = $folder ? '\\' . $folder : ($request->folder ? '\\' . $request->folder : '');
        $folder = $path;
        // dd($folder);

        try {
            $path = public_path('storage' . $path);
            // dd($path);
            $files = File::allFiles($path);
        } catch (\Exception $e) {
            $files = [];
        }


        self::uploadSync($files, $backup, $path, $folder);



        return $backup;
    }

    public function writeOutput($file_csv, $data)
    {
        if (isset($data)) {
            $fp = fopen($file_csv, 'w');
            foreach ($data as $fields) {
                fputcsv($fp, $fields);
            }
            fclose($fp);
        }
    }



    private function uploadSync($files, $backup, $path, $folder)
    {
        $sync = Http::pool(function (Pool $pool) use ($files, $backup, $path, $folder) {
            $index = 1;

            foreach ($files as $key => $getpath) {
                // dd($getpath->getextension()); //jpg
                $file = pathinfo($getpath);
                $a = $getpath->getrelativePathname();
                $allMedia[$getpath->getrelativePath()]['path'] = $getpath->getrelativePath();
                $allMedia[$getpath->getrelativePath()]['file'][] = $getpath->getrelativePathname();
                // $list_media[$key]['path'] = $getpath->getrelativePath();
                $list_media[$key]['file'] = $getpath->getrelativePathname();

                if ($backup == FALSE) {
                    echo $getpath->getrelativePathname() . "<br>";
                }

                // if ($backup == TRUE && $getpath->getextension() == 'jpg') { //limit file upload
                if ($backup == TRUE && $getpath->getextension() != 'zip') { //limit file upload

                    $file_kirim = $getpath->getpathname();
                    // $photo = fopen($file_kirim, 'r');
                    $photo = file_get_contents($file_kirim);
                    $hash_file = hash_file('md5', $file_kirim);


                    $file_name = $getpath->getfilename();
                    if (!empty($getpath->getrelativePath()) || $folder) {
                        // $getpath_file = $folder;
                        $getpath_file = empty($getpath->getrelativePath()) ? $folder : $folder . '\\' . $getpath->getrelativePath();
                        // echo $getpath_file.'<<--<br>';
                        $param = [
                            'path_file' => $getpath_file,
                            'hash_file' => $hash_file,
                        ];
                    } else {
                        $param = [
                            'hash_file' => $hash_file,
                        ];
                    };

                    $arrayPools[] = $pool->as($key . '-' . $getpath->getrelativePathname())->timeout(config('StorageConfig.curl.TIMEOUT', 3600))->withOptions([
                        'verify' => config('StorageConfig.curl.VERIFY', false),
                    ])->attach(
                        'file',
                        $photo,
                        $file_name
                    )->post(config('StorageConfig.main.URL', 'http://localhost:8080/api/upload') . '?token=' . config('StorageConfig.main.TOKEN', 'demo123'), $param);
                    echo $index . ') Upload: ' . $file_name . "<br>";
                }
                $index++;
                // return $arrayPools;
            }


            $file_csv = $path . "/storage.csv";
            if (isset($list_media)) {
                $fp = fopen($file_csv, 'w');
                foreach ($list_media as $fields) {
                    fputcsv($fp, $fields);
                }

                fclose($fp);
            }
        });


        foreach ($sync as $key => $respond) {
            try {
                if ($respond->successful()) {
                    // dd($respond->object());
                    $storage_sukses[$key][] = $respond->object()->file->path;
                    $file_csv = $path . "/storage_sukses.csv";
                    self::writeOutput($file_csv, $storage_sukses);
                } else {
                    $storage_error[$key][] = $respond->object()->message;
                    $file_csv = $path . "/storage_error.csv";
                    self::writeOutput($file_csv, $storage_error);
                };
                // $files = File::allFiles($path);
            } catch (\Exception $e) {
                $respond->throw();
                dd($e->getMessage());
                // $files = [];
            }
        }
    }

    private function generateRandomString($n)
    {

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $randomString = '';
        for ($i = 0; $i < $n; $i++) {

            $index = rand(0, strlen($characters) - 1);

            $randomString .= $characters[$index];
        }

        return $randomString;
    }
}
