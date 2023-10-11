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

use Bangsamu\Storage\Models\BackupLokal;

class StorageController extends Controller
{

    function __construct()
    {
    }

    public function log($message = 'STORAGE logs')
    {
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/storage.log'),
        ])->info($message);

        // Log::info(json_encode($request->all()) . $folder . '-' . $backup . ' user: sys url: ' . url()->current() . ' message:BEAT STORAGE ' . app()->environment());
    }

    public function installDB()
    {
        if (!\Schema::hasTable('backup_lokal')) {
            /*tabel belum ada buat dulu*/
            \Schema::create('backup_lokal', function ($table) {
                $table->increments('id');
                $table->string('upload', 10)->default(0);
                $table->string('hash_file', 255)->nullable();
                $table->string('getpathname', 255)->unique()->nullable();
                $table->string('getfilename', 255)->nullable();
                $table->string('getrelativePath', 255)->nullable();
                $table->string('getrelativePathname', 255)->nullable();
                $table->string('getextension', 10)->nullable();
                $table->string('jenis', 1)->nullable();
                $table->string('scan', 1)->nullable()->default(0);
                $table->timestamps();
            });
        }
        // $cek_tabel = \Schema::hasTable('backup_lokal');
        // return $cek_tabel;
    }

    public function saveDB($list = [])
    {
        // dd($list);
        self::installDB();
        // $users = DB::connection('sqlite')->select(...);
        // $products = \DB::connection('sqlite')->table("table1")->get();

        foreach ($list as $key => $val) {
            $hash_file = hash_file('md5', $val->getpathname());
            $data[$key]['hash_file'] = $hash_file;
            $data[$key]['getpathname'] = $val->getpathname();
            $data[$key]['getfilename'] = $val->getfilename();
            $data[$key]['getrelativePath'] = $val->getrelativePath();
            $data[$key]['getrelativePathname'] = $val->getrelativePathname();
            $data[$key]['getextension'] = $val->getextension();
            $data[$key]['jenis'] = $val->getextension();
        }
        // dd($data);

        // $data = [
        //     ['name' => 'Coder 1', 'status' => '4096', 'path'=>'/'],
        //     ['name' => 'Coder 4', 'status' => '4096', 'path'=>'/'],
        //     ['name' => 'Coder 3', 'status' => '4096', 'path'=>'/'],
        //     ['name' => 'Coder 2', 'status' => '4096', 'path'=>'/'],
        //     //...
        // ];

        // BackupLokal::insert($data);
        BackupLokal::upsert($data, ['getpathname', 'hash_file'], ['getpathname']);
        // BackupLokal::upsert([
        //     ['departure' => 'Oakland', 'destination' => 'San Diego', 'price' => 99],
        //     ['departure' => 'Chicago', 'destination' => 'New York', 'price' => 150]
        // ], ['departure', 'destination'], ['price']);
        // dd($a, 99);
        $products = BackupLokal::all();
        // dd(3, $products);
        return $products;
    }

    public function saveScanDB($list = [])
    {
        // dd($list);
        self::installDB();
        // $users = DB::connection('sqlite')->select(...);
        // $products = \DB::connection('sqlite')->table("table1")->get();

        foreach ($list as $key => $val) {
            $data[$key]['hash_file'] = @$val['hash_file'];
            $data[$key]['getpathname'] = @$val['getpathname'];
            $data[$key]['getfilename'] = @$val['getfilename'];
            $data[$key]['getrelativePath'] = @$val['getrelativePath'];
            $data[$key]['getrelativePathname'] = @$val['getrelativePathname'];
            $data[$key]['getextension'] = @$val['getextension'];
            $data[$key]['jenis'] = @$val['jenis'];
            $data[$key]['scan'] = @$val['scan'] ?? 0;
        }

        BackupLokal::upsert($data, ['getpathname', 'hash_file'], ['getpathname']);
        $storage_list['file'] = BackupLokal::where('jenis', '=', 'F')->get();
        $storage_list['dir'] = BackupLokal::where('jenis', '=', 'D')->where('scan', '=', '0')->get();
        // dd($data,$list,3, $storage_list);
        return $storage_list;
    }

    public function scanDir(Request $request, $folder = null, $scan = false, $backup = false)
    {
        self::installDB();
        $list_scan_db['dir'] = BackupLokal::where('jenis', '=', 'D')->where('scan', '=', '0')->limit(1)->get();
        // dd($list_scan_db);
        if (count($list_scan_db['dir']) > 0 && $scan == false) {
            // dd(1);
            // dd('scan folder '.$list_scan_db['dir'][0]['getrelativePath']);
            $folder =  $list_scan_db['dir'][0]['getrelativePath'];
            self::scanDir($request, $folder, true);
            // echo 1;
        } else {
            // echo 2;

            $backup = $backup ?? $request->backup;
            $allMedia = null;
            $folder_cek =  $folder ? $folder : ($request->folder ? $request->folder : '');
            if ($folder_cek) {
                BackupLokal::where('getrelativePath', '=', $folder_cek)->where('jenis', '=', 'D')->update(['scan' => 1]);
            }

            $path = DIRECTORY_SEPARATOR  . $folder_cek;

            $folder = $folder_cek;

            $dir = public_path('storage' . $path);

            $files = scandir($dir);
            $files_n = count($files) - 1;
            $i = 0;
            // dd($files,$files_n);
            while ($i <= $files_n) {
                // "is_dir" only works from top directory, so append the $dir before the file
                $temp = explode('.', $files[$i]);
                $extension = end($temp);
                if ($files[$i] != '.' && $files[$i] != '..') {
                    $MyFileType[$i]['getfilename'] = $files[$i]; // D for Directory
                    $MyFileType[$i]['getpathname'] = $dir . DIRECTORY_SEPARATOR . $MyFileType[$i]['getfilename']; // D for Directory

                    // $MyFileType[$i]['getpathname'] = str_replace('//','/',$MyFileType[$i]['getpathname']);
                    // $MyFileType[$i]['getpathname'] = str_replace('\\','\\',$MyFileType[$i]['getpathname']);
                    $MyFileType[$i]['getpathname'] = str_replace('\\\\', '\\', $MyFileType[$i]['getpathname']);
                    $MyFileType[$i]['getpathname'] = str_replace('/', DIRECTORY_SEPARATOR, $MyFileType[$i]['getpathname']);

                    if (is_dir($MyFileType[$i]['getpathname'])) {
                        $MyFileType[$i]['getrelativePath'] = $folder . DIRECTORY_SEPARATOR . $MyFileType[$i]['getfilename']; // D for Directory
                        $MyFileType[$i]['jenis'] = "D"; // D for Directory
                        // $MyFileType[$i]['scan'] = 0; // F for File
                    } else {
                        $hash_file = hash_file('md5', $MyFileType[$i]['getpathname']);
                        $MyFileType[$i]['hash_file'] = $hash_file;
                        $MyFileType[$i]['getrelativePathname'] =  $folder . DIRECTORY_SEPARATOR . $MyFileType[$i]['getfilename']; // D for Directory
                        $MyFileType[$i]['getrelativePath'] =  $folder; // D for Directory
                        $MyFileType[$i]['getextension'] = $extension;
                        $MyFileType[$i]['jenis'] = "F"; // F for File
                        $MyFileType[$i]['scan'] = 1; // F for File
                    }
                    if (count($list_scan_db['dir']) > 0 && $scan == true) {

                        echo 'Scan::' . $MyFileType[$i]['getrelativePath'] . '<br>';
                        echo $i . '. ' . $MyFileType[$i]['jenis'] . '. ' . $MyFileType[$i]['getfilename'];
                    }
                }
                // print itemNo, itemType(D/F) and itemname
                $i++;
            }

            if (isset($MyFileType)) {
                $list_scan_db = self::saveScanDB($MyFileType);
                // if (count($list_scan_db['dir']) > 0) {
                //     // dd('scan folder '.$list_scan_db['dir'][0]['getrelativePath']);
                //     $folder =  $list_scan_db['dir'][0]['getrelativePath'];
                //     self::scanDir($request, $folder);
                // }
                if (count($list_scan_db['dir']) > 0) {
                    echo ' <br> scan::' . $scan . 'init found ' . count($list_scan_db['dir']) . ' Folder <br>';
                } else {
                    echo ' <br> scan::'  . ' finish <br>';
                }
            } else {
                echo 'scan::' . $scan . count($list_scan_db['dir']) . '<br>' . $i . '. ' . $MyFileType[$i]['jenis'] . '. ' . $MyFileType[$i]['getfilename'];
            }
        }




        // dd($MyFileType, $list_scan_db, count($list_scan_db['dir']));
    }

    public function getListLokal(Request $request, $folder = null, $backup = false)
    {
        // $users = DB::connection('sqlite')->select(...);
        // $products = \DB::connection('sqlite')->table("table1")->get();
        // $products = BackupLokal::get();
        // dd(1, $products);
        $backup = $backup ?? $request->backup;
        $allMedia = null;
        $path = $folder ? '\\' . $folder : ($request->folder ? '\\' . $request->folder : '');
        $folder = $path;

        try {
            $path = public_path('storage' . $path);
            $files = File::allFiles($path);
            $list_file =  self::saveDB($files);
        } catch (\Exception $e) {
            $files = [];
        }
        // dd(1,$list_file->toArray());

        // self::uploadSync($files, $backup, $path, $folder);
        if ($backup) {
            $file_bup = self::uploadSyncDB($backup, $path, $folder);
        }

        return dd($list_file->toArray());
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


    private function uploadSyncDB($backup, $path, $folder)
    {

        $list_WL = explode(",", config('StorageConfig.main.BACKUP_FILE_WL'));
        $list_BL = explode(",", config('StorageConfig.main.BACKUP_FILE_BL'));
        $backup = true;
        $files = BackupLokal::where('upload', '=', '0')
            ->whereIn('getextension', $list_WL)
            ->whereNotIn('getextension', $list_BL)
            ->limit(10)->get();
        // dd($files);
        $sync = Http::pool(function (Pool $pool) use ($files, $backup, $path, $folder, $list_WL, $list_BL) {
            $index = 1;

            foreach ($files as $key => $getpath) {
                // dd($getpath->getextension); //jpg
                // $file = pathinfo($getpath);
                $a = $getpath->getrelativePathname;
                $allMedia[$getpath->getrelativePath]['path'] = $getpath->getrelativePath;
                $allMedia[$getpath->getrelativePath]['file'][] = $getpath->getrelativePathname;
                // $list_media[$key]['path'] = $getpath->getrelativePath;
                $list_media[$key]['file'] = $getpath->getrelativePathname;

                if ($backup == FALSE) {
                    echo $getpath->getrelativePathname . "<br>";
                }

                if ($backup == TRUE && in_array($getpath->getextension, $list_WL) && !in_array($getpath->getextension, $list_BL)) { //limit file upload

                    $file_kirim = $getpath->getpathname;
                    if (config('StorageConfig.main.ATTACH_METHOD') == 'fopen') {
                        $photo = fopen($file_kirim, 'r');
                    } else {
                        $photo = file_get_contents($file_kirim);
                    }

                    /*validasi file untuk upload jika berbeda hasil upload di service storage akan di hapus*/
                    $hash_file = hash_file('md5', $file_kirim);

                    $file_name = $getpath->getfilename;
                    if (!empty($getpath->getrelativePath) || $folder) {
                        // $getpath_file = $folder;
                        $getpath_file = empty($getpath->getrelativePath) ? $folder : $folder . '\\' . $getpath->getrelativePath;
                        $param = [
                            'path_file' => $getpath_file,
                            'hash_file' => $hash_file,
                        ];
                    } else {
                        $getpath_file = '';
                        $param = [
                            'hash_file' => $hash_file,
                        ];
                    };

                    $arrayPools[] = $pool->as($getpath->id)->timeout(config('StorageConfig.curl.TIMEOUT', 3600))->withOptions([
                        'verify' => config('StorageConfig.curl.VERIFY', false),
                    ])->attach(
                        'file',
                        $photo,
                        $file_name
                    )->post(config('StorageConfig.main.URL', 'http://localhost:8080/api/upload') . '?token=' . config('StorageConfig.main.TOKEN', 'demo123'), $param);
                    echo $index . ') Upload: ' . $file_name . "<br>";
                    self::log('user: sys url: ' . url()->current() . ' message: ' . $index . ') Upload: ' . $getpath_file . '\\' . $file_name);
                    // self::log('respond ' . $index .  ': '.$pool);
                    // dd($pool->successful());
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
                if (method_exists($respond, 'gethandlerContext')) {
                    self::log('user: sys url: ' . url()->current() . ' message: ' . $respond->gethandlerContext()["error"]);
                } else {
                    if ($respond->successful()) {
                        $storage_sukses[$key][] = $respond->object()->file->path;
                        $file_csv = $path . "/storage_sukses.csv";
                        self::writeOutput($file_csv, $storage_sukses);
                        self::log('user: sys url: ' . url()->current() . ' message: ' . $respond->object()->file->path);
                        // BackupLokal::find($key)->limit(10)->get();
                        BackupLokal::where('id', $key)
                            ->update(['upload' => 1]);
                    } else {
                        // dd($respond);
                        self::log('user: sys url: ' . url()->current() . ' message: ' . $respond->object()->message);
                        $storage_error[$key][] = $respond->object()->message;
                        $file_csv = $path . "/storage_error.csv";
                        self::writeOutput($file_csv, $storage_error);
                        // BackupLokal::where('id', $key)
                        //     ->update(['upload' => 1]);
                    };
                };
                // $files = File::allFiles($path);
            } catch (\Exception $e) {
                $respond->throw();
                self::log('Exception ' . $e->getMessage());
            }
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

                $list_WL = explode(",", config('StorageConfig.main.BACKUP_FILE_WL'));
                $list_BL = explode(",", config('StorageConfig.main.BACKUP_FILE_BL'));

                if ($backup == TRUE && in_array($getpath->getextension(), $list_WL) && !in_array($getpath->getextension(), $list_BL)) { //limit file upload

                    $file_kirim = $getpath->getpathname();
                    if (config('StorageConfig.main.ATTACH_METHOD') == 'fopen') {
                        $photo = fopen($file_kirim, 'r');
                    } else {
                        $photo = file_get_contents($file_kirim);
                    }

                    /*validasi file untuk upload jika berbeda hasil upload di service storage akan di hapus*/
                    $hash_file = hash_file('md5', $file_kirim);

                    $file_name = $getpath->getfilename();
                    if (!empty($getpath->getrelativePath()) || $folder) {
                        // $getpath_file = $folder;
                        $getpath_file = empty($getpath->getrelativePath()) ? $folder : $folder . '\\' . $getpath->getrelativePath();
                        $param = [
                            'path_file' => $getpath_file,
                            'hash_file' => $hash_file,
                        ];
                    } else {
                        $getpath_file = '';
                        $param = [
                            'hash_file' => $hash_file,
                        ];
                    };

                    $arrayPools[] = $pool->as($index)->timeout(config('StorageConfig.curl.TIMEOUT', 3600))->withOptions([
                        'verify' => config('StorageConfig.curl.VERIFY', false),
                    ])->attach(
                        'file',
                        $photo,
                        $file_name
                    )->post(config('StorageConfig.main.URL', 'http://localhost:8080/api/upload') . '?token=' . config('StorageConfig.main.TOKEN', 'demo123'), $param);
                    echo $index . ') Upload: ' . $file_name . "<br>";
                    self::log('user: sys url: ' . url()->current() . ' message: ' . $index . ') Upload: ' . $getpath_file . '\\' . $file_name);
                    // self::log('respond ' . $index .  ': '.$pool);
                    // dd($pool->successful());
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
                if (method_exists($respond, 'gethandlerContext')) {
                    self::log('user: sys url: ' . url()->current() . ' message: ' . $respond->gethandlerContext()["error"]);
                } else {
                    if ($respond->successful()) {
                        $storage_sukses[$key][] = $respond->object()->file->path;
                        $file_csv = $path . "/storage_sukses.csv";
                        self::writeOutput($file_csv, $storage_sukses);
                        self::log('user: sys url: ' . url()->current() . ' message: ' . $respond->object()->file->path);
                    } else {
                        self::log('user: sys url: ' . url()->current() . ' message: ' . $respond->object()->message);
                        $storage_error[$key][] = $respond->object()->message;
                        $file_csv = $path . "/storage_error.csv";
                        self::writeOutput($file_csv, $storage_error);
                    };
                };
                // $files = File::allFiles($path);
            } catch (\Exception $e) {
                $respond->throw();
                self::log('Exception ' . $e->getMessage());
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
