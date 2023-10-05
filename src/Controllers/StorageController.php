<?php

namespace Bangsamu\Storage\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Validator;
use Response;
use Illuminate\Support\Str;
use App\Models\Telegram;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Client\Pool;


class StorageController extends Controller
{
    public $CHAT_ID;
    public $param;

    function __construct($CHAT_ID = null, $param = null)
    {
        $this->CHAT_ID = $CHAT_ID ?? config('SsoConfig.main.CHAT_ID', '-1001983435070');
        $this->param = $param;
    }

    public function getListLokal(Request $request, $path = null, $backup = false)
    {
        $allMedia = null;
        $path = $path ? '\\' . $path : ($request->path ? '\\' . $request->path : '');
        // dd($path);
        try {
            $path = storage_path('app\public' . $path);
            // dd($path);
            $files = File::allFiles($path);
        } catch (\Exception $e) {
            $files = [];
        }



        $sync = Http::pool(function (Pool $pool) use ($files, $backup) {
            $index = 1;



            foreach ($files as $key => $getpath) {
                echo $key;
                $file = pathinfo($getpath);
                $a = $getpath->getrelativePathname();
                $allMedia[$getpath->getrelativePath()]['path'] = $getpath->getrelativePath();
                $allMedia[$getpath->getrelativePath()]['file'][] = $getpath->getrelativePathname();

                $file_kirim = $getpath->getpathname();
                $photo = fopen($file_kirim, 'r');
                $file_name = $getpath->getfilename();
                $getpath_file = $getpath->getrelativePath();
                if (!empty($getpath->getrelativePath())) {
                    $param = [
                        'path_file' => $getpath_file,
                    ];
                } else {
                    $param = [];
                };

                if ($backup == TRUE) {
                    $response = Http::attach(
                        'file',
                        $photo,
                        $file_name
                    )->post('http://localhost:8080/api/upload?token=meindo12345', $param);
                }

                $arrayPools[] = $pool->as($key . $index)->timeout(config('SsoConfig.curl.TIMEOUT', 30))->withOptions([
                    'verify' => config('SsoConfig.curl.VERIFY', false),
                ])->attach(
                    'file',
                    $photo,
                    $file_name
                )->post('http://localhost:8080/api/upload?token=meindo12345', $param);

                $index++;
                // return $arrayPools;

            }



        });

        dd($allMedia, $request->path,  $files);
        return 1; // it will return the server IP if the client IP is not found using this method.
    }

    /**
     * Fungsi untuk setup ke url api telgram
     *
     * @param action string berisi parammeter fungsi api dari telegram
     * @param token string berisi token untuk akses auth, jika kosong akan di ambil dari TOKEN di config
     *
     * @return string berisi return full url untuk akses ke api telegram
     */
    function loginSso($action, $param = null, $token = null)
    {
        $param_segment = isset($param) ? $param . '/' : '';
        $api_token = $token ?? config('SsoConfig.main.TOKEN');
        $uRL = config('SsoConfig.main.URL', url('/')) . $param_segment . $action;
        // $uRL = config('SsoConfig.main.URL', url('/')) . $param_segment . $api_token . '/' . $action;
        return $uRL;
    }


    /**
     * Fungsi awal sebelum melakukan request ke api telgram, mengunakan bawan http call dungsi curl dari laravel
     *
     * @return mix berisi object untuk request http laravel
     */
    public function init()
    {
        $ssoSend = Http::timeout(config('SsoConfig.curl.TIMEOUT', 30))->withOptions([
            'verify' => config('SsoConfig.curl.VERIFY', false),
        ]);
        return $ssoSend;
    }

    /**
     * Fungsi untuk kirim validasi format error
     *
     * @param error array berisi list data yang error
     *
     * @return json berisi return dari format function setOutput
     */
    function validateError($error = null)
    {
        $data['status'] =  'gagal';
        $data['code'] = '400';
        $data['data'] = $error;

        return self::setOutput($data);
    }

    /**
     * Fungsi untuk standart return output respond
     *
     * @param respond mix data bisa json maupun object
     * @param type jenis dari respond yang di harapkan [json,body,object]
     *
     * @return mix respond data dari param type defaultnya json
     */
    function setOutput($respon = null, $type = 'json')
    {
        // dd(9,$type,$respon);
        if ($type == 'json') {
            // $return = $respon->{$type}();

            $status = @$respon['status'] ?? 'sukses';
            $code = @$respon['code'] ?? '200';
            $data = @$respon['data'] ?? $respon->object();
            $return['status'] = $status;
            $return['code'] = $code;
            $return['data'] = $data;
            // dd($return);
        } else {
            $return = $respon->{$type}();
        }
        return $return;
    }

    /**
     * Fungsi untuk validasi param request
     * jika tidak ada param document maka akan upload activity kemarin
     *
     * @param  \Illuminate\Http\Request  $request
     * @param rules array berisi list data rule yang di harapkan
     *
     * @return mix akan return boolean true jika sukses jika gagal akan respod json untuk data errornya
     */
    public function validator($request_all, $rules)
    {
        $validator = Validator::make($request_all, $rules);
        if ($validator->fails()) {
            $error = $validator->errors();
            $return['status'] = 'gagal';
            $return['code'] = 204;
            $return['data'] = $error->getMessages();
            return  self::setOutput($return);
            // Response::make(self::validateError($error))->send();
            // exit();
        }
        return true;
    }

    /**
     * Fungsi untuk validasi telgram respond
     * jika gagal makan akan dikirim detail respond erro dari telegram
     *
     * @param ssoSend retun data object dari http cal ke api telegram
     *
     * @return json respond data dengan format standart json
     */
    public function ssoRespond($ssoSend)
    {
        // dd($ssoSend->object());
        if ($ssoSend->failed()) {
            $respond['status'] = 'gagal';
            $respond['code'] = '204';
            $respond['data'] = $ssoSend->object();

            $data = $respond;
        } else {
            $data = $ssoSend;
            // self::saveDB($data);
        }

        // Log::info('user: sys url: ' . url()->current() . ' message: done backup to TELEGRAM log respond :' . json_encode($data));

        return self::setOutput($data);
    }

    public function saveDB($data)
    {

        if (isset($data->ok)) {
            $result = $data->result;

            if (isset($result->message_id)) {
                $created['message_id'] = @$result->message_id;
            }
            if (isset($result->from)) {
                $created['from_id'] = @$result->from->id;
                $created['from_is_bot'] = @$result->from->is_bot;
                $created['from_first_name'] = @$result->from->first_name;
                $created['from_username'] = @$result->from->username;
            }
            if (isset($result->chat)) {
                $created['chat_id'] = @$result->chat->id;
                $created['chat_first_name'] = @$result->chat->first_name;
                $created['chat_username'] = @$result->chat->username;
                $created['chat_type'] = @$result->chat->type;
            }
            if (isset($result->date)) {
                $created['date'] = @$result->date;
            }
            if (isset($result->caption)) {
                $created['caption'] = @$result->caption;
            }
            if (isset($result->text)) {
                $created['text'] = @$result->text;
            }
            if (isset($result->document)) {
                $created['document'] = json_encode(@$result->document);
            }
            if (isset($result->entities)) {
                $created['entities'] = json_encode(@$result->entities);
            }
            if (isset($result->photo)) {
                $created['photo'] = json_encode(@$result->photo);
            }
            $created['raw'] = json_encode($data);
            $telegram_db = Telegram::create($created);
        } else {
            $telegram_db = false;
        };
        // dd($telegram_db, isset($data->ok), $data);
        return $telegram_db;
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
