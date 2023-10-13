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
use Illuminate\Support\Facades\Schema;

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

    public function cekDB($tabel = 'backup_lokal'): bool
    {
        $cek_tabel = Schema::hasTable($tabel);
        return $cek_tabel;
    }

    public function installDB(): void
    {
        $cek_tabel = self::cekDB();
        if ($cek_tabel == false) {
            /*tabel belum ada buat dulu*/
            Schema::create('backup_lokal', function ($table) {
                $table->increments('id');
                $table->string('upload', 10)->default(0);
                $table->string('filemtime', 255)->nullable();
                $table->string('hash_file', 255)->nullable();
                $table->string('getpathname', 255)->unique()->nullable();
                $table->string('getfilename', 255)->nullable();
                $table->string('getrelativePath', 255)->nullable();
                $table->string('getrelativePathname', 255)->nullable();
                $table->string('getextension', 10)->nullable();
                $table->string('jenis', 1)->nullable();
                $table->string('scan', 1)->nullable()->default(0);
                $table->string('url_storage', 255)->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function saveDB($list = [])
    {
        self::installDB();

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

    public function saveScanDB($list = null, $get_list = true)
    {
        if ($list) {
            // foreach ($list as $key => $val) {
            //     $data[$key]['hash_file'] = @$val['hash_file'];
            //     $data[$key]['getpathname'] = @$val['getpathname'];
            //     $data[$key]['getfilename'] = @$val['getfilename'];
            //     $data[$key]['getrelativePath'] = @$val['getrelativePath'];
            //     $data[$key]['getrelativePathname'] = @$val['getrelativePathname'];
            //     $data[$key]['getextension'] = @$val['getextension'];
            //     $data[$key]['jenis'] = @$val['jenis'];
            //     $data[$key]['scan'] = @$val['scan'] ?? 0;
            //     $data[$key]['filemtime'] = @$val['filemtime'] ?? 0;
            // }
            if (!is_array($list)) {
                $data = $list->toArray();
            }
            // dd($data);
            BackupLokal::upsert($data, ['getpathname', 'jenis'], ['getpathname']);
        }

        if ($get_list) {
            $storage_list['file'] = BackupLokal::where('jenis', '=', 'F')->get();
            $storage_list['dir'] = BackupLokal::where('jenis', '=', 'D')->where('scan', '=', '0')->get();
            return $storage_list;
        }
    }

    public function scanDirReset(Request $request, $folder = null, $scan = false, $backup = false)
    {
        // $storage_list['dir'] = BackupLokal::where('jenis', '=', 'D')->where('scan', '=', '1')->get();
        $scanDirReset = BackupLokal::where('jenis', 'D')
            ->where('scan', '1')
            ->update(['scan' => '0']);
        echo 'reset scan folder ' . $scanDirReset;
    }

    public function scanDir(Request $request, $folder = null, $scan = null)
    {
        self::log('user: sys url: ' . url()->current() . ' message: run schedule scanDir');
        self::installDB();

        $list_scan_db_data = BackupLokal::where('jenis', '=', 'D')->where('scan', '=', '0')->limit(1);
        $list_scan_db['dir'] = $list_scan_db_data->get();
        $list_scan_db_dir =  $list_scan_db_data->count();

        $folder_cek =  $folder ? $folder : ($request->folder ? $request->folder : '');
        $scan_cek =  $scan ? $scan : ($request->scan ? $request->scan : false);

        /*ambil satu folder dari DB yang akan di scan*/
        if ($list_scan_db_dir > 0 && $scan_cek == false) {
            // dd(1);
            // dd('scan folder '.$list_scan_db['dir'][0]['getrelativePath']);
            $folder =  $list_scan_db['dir'][0]['getrelativePath'];
            self::scanDir($request, $folder, true);
        } else {
            /*scan data di folder langsung*/
            if ($folder_cek) {
                BackupLokal::where('getrelativePath', '=', $folder_cek)->where('jenis', '=', 'D')->update(['scan' => 1]);
            }

            $path = DIRECTORY_SEPARATOR  . $folder_cek;

            $folder = $folder_cek;

            $dir = public_path('storage' . $path);

            if (is_dir($dir)) {
                $files = scandir($dir);
                $files_n = count($files) - 1;
            } else {
                $files = 0;
                $files_n = -1;
            };
            $i = 0;
            while ($i <= $files_n) {
                // dd(pathinfo($files[0], PATHINFO_EXTENSION), $files, $files_n);
                // $temp = explode('.', $files[$i]);
                // $extension = end($temp);
                $getfilename = $files[$i];
                $extension = pathinfo($getfilename, PATHINFO_EXTENSION);


                if ($getfilename != '.' && $getfilename != '..') {

                    if ($getfilename) {
                        $MyFileType[$i]['getfilename'] = null;
                        $MyFileType[$i]['getpathname'] = null;
                        $MyFileType[$i]['hash_file'] = null;
                        $MyFileType[$i]['getrelativePathname'] = null;
                        $MyFileType[$i]['getrelativePath'] = null;
                        $MyFileType[$i]['getextension'] = null;
                        $MyFileType[$i]['jenis'] = null;
                        $MyFileType[$i]['filemtime'] = null;
                        $MyFileType[$i]['scan'] = 0;
                    }

                    $MyFileType[$i]['getfilename'] = $getfilename;
                    $MyFileType[$i]['getpathname'] = $dir . DIRECTORY_SEPARATOR . $MyFileType[$i]['getfilename'];
                    $MyFileType[$i]['getpathname'] = str_replace('\\\\', '\\', $MyFileType[$i]['getpathname']); /*ubah doble backslash ke backslash*/
                    $MyFileType[$i]['getpathname'] = str_replace('/', DIRECTORY_SEPARATOR, $MyFileType[$i]['getpathname']); /*ubah slash ke format slash sistem os*/

                    /*cek folder bukan*/
                    if (is_dir($MyFileType[$i]['getpathname'])) {
                        $MyFileType[$i]['getrelativePath'] = $folder . DIRECTORY_SEPARATOR . $MyFileType[$i]['getfilename']; // D for Directory
                        $MyFileType[$i]['jenis'] = "D"; // D for Directory
                        $cek_filemtime = filemtime($MyFileType[$i]['getpathname']);
                        $MyFileType[$i]['filemtime'] = $cek_filemtime; // cek lasmodif
                        // $MyFileType[$i]['scan'] = 0; // F for File
                        /*cek perubahan folder*/
                        // echo $MyFileType[$i]['getpathname'] . ' - ' . $cek_filemtime;
                        $cek_filemtime_db = BackupLokal::where('getpathname', '=', $MyFileType[$i]['getpathname'])->where('jenis', '=', 'D')->first(); //->update(['filemtime' => $cek_filemtime_db]);
                        // dd(@$cek_filemtime_db->filemtime);
                        if (@$cek_filemtime_db->filemtime != $cek_filemtime && isset($cek_filemtime_db->filemtime)) {
                            $cek_filemtime_db->scan = 0;
                            $cek_filemtime_db->filemtime = $cek_filemtime;
                            $cek_filemtime_db->save();
                            // echo 'beda cek cek_filemtime ' . $cek_filemtime;
                        }
                    } else {
                        $hash_file = hash_file('md5', $MyFileType[$i]['getpathname']);
                        $MyFileType[$i]['hash_file'] = $hash_file;
                        $MyFileType[$i]['getrelativePathname'] =  $folder . DIRECTORY_SEPARATOR . $MyFileType[$i]['getfilename']; // D for Directory
                        $MyFileType[$i]['getrelativePath'] =  $folder; // D for Directory
                        $MyFileType[$i]['getextension'] = $extension;
                        $MyFileType[$i]['jenis'] = "F"; // F for File
                        $cek_filemtime = filemtime($MyFileType[$i]['getpathname']);
                        $MyFileType[$i]['filemtime'] = $cek_filemtime; // cek lasmodif
                        $MyFileType[$i]['scan'] = 1; // F for File
                    }
                    // dd(count($list_scan_db['dir']) > 0 && $scan_cek == true);
                    // dd($list_scan_db['dir']->toArray());
                    // $cek_count = count($list_scan_db['dir']);

                    // $cek_count =  BackupLokal::where('jenis', '=', 'D')->where('scan', '=', '0')->limit(1)->count();
                    // dd($list_scan_db_dir);
                    if ($scan_cek == true) {
                        // echo 'Scan::' . $folder_cek . ' ' . $MyFileType[$i]['getrelativePath'] . '<br>';
                        echo $i . '. [' . $MyFileType[$i]['jenis'] . '] ' . $MyFileType[$i]['getfilename'] . '<br>';
                    }

                    // $list_scan_db = self::saveScanDB($MyFileType, false);
                }
                // print itemNo, itemType(D/F) and itemname
                $i++;
                // $MyFileType = null;
            }

            if (isset($MyFileType)) {

                $insert_data = collect($MyFileType);
                $chunks = $insert_data->chunk(config('StorageConfig.main.CHUNKS_SCANDB', 10));
                /*save ke DB berdasar chunks*/
                foreach ($chunks as $chunk) {
                    // dd($chunks, $insert_data, $MyFileType);
                    self::saveScanDB($chunk, false);
                }
                // $list_scan_db = self::saveScanDB([], true);
                // dd(1);
                // $list_scan_db = self::saveScanDB($MyFileType);
                // if (count($list_scan_db['dir']) > 0) {
                //     // dd('scan folder '.$list_scan_db['dir'][0]['getrelativePath']);
                //     $folder =  $list_scan_db['dir'][0]['getrelativePath'];
                //     self::scanDir($request, $folder);
                // }
                if ($list_scan_db_dir > 0) {
                    echo ' <br> scanDB [' . $files_n . ']::' . $folder_cek . ' ' . $scan_cek . 'init found ' . count($list_scan_db['dir']) . ' Folder <br>';
                } else {
                    echo ' <br> scanDirect [' . $files_n . ']:: ' . $folder_cek . ' '  . ' finish <br>';
                }
            } else {
                echo 'scan[' . $files_n . ']::' . $folder_cek . ' ' . $scan_cek . count($list_scan_db['dir']) . '<br>';
                echo  'index::' . $i . '<br>';
            }
        }

        // $dir1 = public_path('storage/bup1');
        // echo 'C:\laragon\www\clay\public\storage/bup1 - 1697009800 <br>';
        // echo $dir1 . ' - ' . filemtime($dir1);

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


    public function uploadSyncDB(Request $request, $backup = null, $path = null, $folder = null, $upload = false)
    {
        $list_WL = explode(",", config('StorageConfig.main.BACKUP_FILE_WL'));
        $list_BL = explode(",", config('StorageConfig.main.BACKUP_FILE_BL'));

        $backup = $backup ?? $request->backup;
        // dd($backup, $request->backup);
        $files = BackupLokal::where('upload', '=', '0')
            ->where('jenis', 'F')
            ->whereIn('getextension', $list_WL)
            ->whereNotIn('getextension', $list_BL)
            ->limit(config('StorageConfig.main.UPLOAD_BATCH', 10))
            ->get();

        echo 'run backup:: ' . ($backup ? 'TRUE' : 'FALSE    ') . ' total::' . count($files) . '<br>';
        // dd($files->toArray());
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
                    $app_code = config('StorageConfig.main.APP_CODE');
                    if (!empty($getpath->getrelativePath) || $folder) {
                        // $getpath_file = $folder;
                        $getpath_file = empty($getpath->getrelativePath) ? $folder : $folder . DIRECTORY_SEPARATOR . $getpath->getrelativePath;

                        $getpath_file = str_replace('\\\\', '\\', $getpath_file);
                        $getpath_file = str_replace('/', DIRECTORY_SEPARATOR, $getpath_file);

                        $param = [
                            'path_file' => $getpath_file,
                            'hash_file' => $hash_file,
                            'app_code' => $app_code,
                        ];
                    } else {
                        $getpath_file = '';
                        $param = [
                            'hash_file' => $hash_file,
                            'app_code' => $app_code,
                        ];
                    };

                    $arrayPools[] = $pool->as($getpath->id)->timeout(config('StorageConfig.curl.TIMEOUT', 3600))->withOptions([
                        'verify' => config('StorageConfig.curl.VERIFY', false),
                    ])->attach(
                        'file',
                        $photo,
                        $file_name
                    )->post(config('StorageConfig.main.URL', 'http://localhost:8080/api/upload') . '?token=' . config('StorageConfig.main.TOKEN', 'demo123'), $param);
                    echo $index . ') uploadSyncDB: ' . $file_name . "<br>";
                    self::log('user: sys url: ' . url()->current() . ' message: ' . $index . ') uploadSyncDB: ' . $getpath_file . DIRECTORY_SEPARATOR . $file_name);
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

        if ($backup == TRUE) {
            foreach ($sync as $key => $respond) {
                try {
                    if (method_exists($respond, 'gethandlerContext')) {
                        self::log('user: sys url: ' . url()->current() . ' message: ' . $respond->gethandlerContext()["error"]);
                        BackupLokal::where('id', $key)
                            ->update(['description' => $respond->gethandlerContext()["error"]]);
                    } else {
                        if ($respond->successful()) {
                            $storage_sukses[$key][] = $respond->object()->data->url;
                            $file_csv = $path . "/storage_sukses.csv";
                            self::writeOutput($file_csv, $storage_sukses);
                            self::log('user: sys url: ' . url()->current() . ' message: ' . $respond->object()->message);

                            $upload_status = 1;
                            $url_storage = @$respond->object()->data ? $respond->object()->data->url : null;
                            BackupLokal::where('id', $key)
                                ->update(['upload' => $upload_status, 'url_storage' => $url_storage, 'description' => $respond->body()]);
                        } else {
                            // dd($respond);
                            $storage_error[$key][] = $respond->object()->message;
                            $file_csv = $path . "/storage_error.csv";
                            self::writeOutput($file_csv, $storage_error);
                            self::log('user: sys url: ' . url()->current() . ' message: ' . $respond->object()->message);

                            $upload_status = $respond->object()->code == 200 ? 1 : 0;
                            $url_storage = @$respond->object()->data ? $respond->object()->data->url : null;
                            BackupLokal::where('id', $key)
                                ->update(['upload' => $upload_status, 'url_storage' => $url_storage, 'description' => $respond->body()]);
                        };
                    };
                    // $files = File::allFiles($path);
                } catch (\Exception $e) {
                    // $respond->throw();
                    self::log('Exception ' . $e->getMessage());
                }
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
                    $app_code = config('StorageConfig.main.APP_CODE');
                    /*cek file di dalam folder*/
                    if (!empty($getpath->getrelativePath()) || $folder) {
                        // $getpath_file = $folder;
                        $getpath_file = empty($getpath->getrelativePath()) ? $folder : $folder . '\\' . $getpath->getrelativePath();
                        $param = [
                            'path_file' => $getpath_file,
                            'hash_file' => $hash_file,
                            'app_code' => $app_code,
                        ];
                    } else {
                        $getpath_file = '';
                        $param = [
                            'hash_file' => $hash_file,
                            'app_code' => $app_code,
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
