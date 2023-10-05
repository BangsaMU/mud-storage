<?php

namespace Bangsamu\Sso\Controllers;

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Validator;
use Response;
use Illuminate\Support\Str;
use App\Models\Telegram;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use App\Models\Session;

use Illuminate\Encryption\Encrypter;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Cookie\CookieJar;


class SsoCrulController
{
    public $CHAT_ID;
    public $param;

    function __construct($CHAT_ID = null, $param = null)
    {
        $this->CHAT_ID = $CHAT_ID ?? config('SsoConfig.main.CHAT_ID', '-1001983435070');
        $this->param = $param;
    }

    function ssoCrul($action, $param = null, $token = null)
    {
        $base_uri = 'https://cms.meindo.com';

        // $client = new Client([
        //     'verify' => false,
        //     // Base URI is used with relative requests
        //     // 'base_uri' => $base_uri,
        //     // You can set any number of default request options.
        //     'timeout'  => 2.0,
        // ]);
        // $headers = [
        //     // 'Cookie' => 'INACTSESSID17db9fa0c5ba1cb5979b54663da2df35=gita.samudra%40meindo.com%2390A4REPlskfjsPdsklkdsf1fa659e9c4f8acc24d9ebecd4f82671; PHPSESSID=62ntlebaocv2sqf20gle05b7v1'
        // ];
        // $options = [
        //     'multipart' => [
        //         [
        //             'name' => 'username',
        //             'contents' => 'gita.samudra@meindo.com'
        //         ],
        //         [
        //             'name' => 'password',
        //             'contents' => 'Meindo12345'
        //         ],
        //         [
        //             'name' => 'page',
        //             'contents' => 'member'
        //         ],
        //         [
        //             'name' => 'cmd',
        //             'contents' => 'login'
        //         ],
        //         [
        //             'name' => 'act',
        //             'contents' => 'login'
        //         ]
        //     ]
        // ];
        // $request = new Request('POST', 'https://cms.meindo.com/main.php' );
        // $res = $client->sendAsync($request, $options)->wait();
        // echo $res->getBody();

        // dd(99);


        // $this->client = new GuzzleClient(['defaults' => [
        //     'verify' => false
        // ]]);
        $client = new Client([
            'verify' => false,
            // Base URI is used with relative requests
            'base_uri' => $base_uri,
            // You can set any number of default request options.
            'timeout'  => 2.0,
        ]);

        $cookieJar = CookieJar::fromArray([
            'INACTSESSID17db9fa0c5ba1cb5979b54663da2df35' => 'gita.samudra%40meindo.com%2390A4REPlskfjsPdsklkdsf1fa659e9c4f8acc24d9ebecd4f82671'
        ], 'cms.meindo.com');
        $cookie_name='INACTSESSID17db9fa0c5ba1cb5979b54663da2df35';
        $cookie_value='gita.samudra%40meindo.com%2390A4REPlskfjsPdsklkdsf1fa659e9c4f8acc24d9ebecd4f82671';
        $cookie_domain='cms.meindo.com';
        // $cookie_expiers= time() + (86400 * 30);
        $cookie_expiers= [];
        // setrawcookie($cookie_name, rawurlencode($cookie_value), 0,'/');
        // setcookie($cookie_name, $cookie_value, 0, '/','cms.meindo.com',1,1); // 86400 = 1 day
        // $client->request('GET', '/get', ['cookies' => $cookieJar]);

        // $cookieJar = new \GuzzleHttp\Cookie\CookieJar;
        $response = $client->request('POST', 'https://cms.meindo.com/main.php', [
            'cookies' => $cookieJar,
            'allow_redirects' => true,
            'form_params' => [
                'username' => 'gita.samudra@meindo.com',
                'password' => 'Meindo12345',
                'page' => 'member',
                'cmd' => 'login',
                'act' => 'login'
            ]

        ]);

        // Send a request to https://foo.com/api/test
        // $response = $client->request('GET', 'main.php');

        // $request = $client->post('http://httpbin.org/post');

        $body = $response->getBody();
        echo $body->getContents();
        dd(9);
    }
    function ssoCrulOLD($action, $param = null, $token = null)
    {

        $attr = [
            'username' => 'gita.samudra@meindo.com',
            'password' => 'Meindo12345',
            'page' => 'member',
            'cmd' => 'login',
            'act' => 'login'
        ];
        define("DOC_ROOT", "c:");
        //username and password of account
        // $username = trim($values["email"]);
        // $password = trim($values["password"]);
        extract($attr);
        //login form action url
        $url = "https://cms.meindo.com/main.php";
        $postinfo = "username=" . $username . "&password=" . $password . "&page=" . $page . "&cmd=" . $cmd . "&act=" . $act;


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/cookieFileNameSamu");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);

        ob_start();      // prevent any output
        curl_exec($ch); // execute the curl command
        ob_end_clean();  // stop preventing output

        curl_close($ch);
        dd($ch);
        unset($ch);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, "/tmp/cookieFileNameSamu");
        curl_setopt($ch, CURLOPT_URL, "https://cms.meindo.com/main.php?page=member");

        $buf2 = curl_exec($ch);

        curl_close($ch);

        echo "<PRE>" . htmlentities($buf2);
        dd(9);

        //set the directory for the cookie using defined document root var
        $path = DOC_ROOT . "/tmp";
        //build a unique path with every request to store. the info per user with custom func. I used this function to build unique paths based on member ID, that was for my use case. It can be a regular dir.
        //$path = build_unique_path($path); // this was for my use case

        $cookie_file_path = $path . "/cookies/cookiesamu.txt";
        // dd($path,$cookie_file_path);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
        //set the cookie the site has for certain features, this is optional
        curl_setopt($ch, CURLOPT_COOKIE, "cookiename=0");
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7"
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['REQUEST_URI']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $attr);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
        curl_exec($ch);

        //page with the content I want to grab
        curl_setopt($ch, CURLOPT_URL, "https://cms.meindo.com/main.php?page=member");
        //do stuff with the info with DomDocument() etc
        $html = curl_exec($ch);
        curl_close($ch);
        echo $html;
        dd(2, $ch);

        // 'username' => 'gita.samudra@meindo.com',
        // 'password' => 'Meindo12345',
        // 'page' => 'member',
        // 'cmd' => 'login',
        // 'act' => 'login'
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://cms.meindo.com/main.php',
            //  CURLOPT_USERAGENT => 'login',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $attr
        ]);
        $resp = curl_exec($curl);
        curl_close($curl);
        echo ($resp);
        dd($resp);
        exit();
        $response = Http::timeout(config('SsoConfig.curl.TIMEOUT', 30))->withOptions([
            'verify' => config('SsoConfig.curl.VERIFY', true),
            // 'debug' => true,
        ])
            ->post('http://ams-meindo.test/login', [
                'email' => 'gita.samudra@meindo.com',
                'password' => 'Meindo12345',
            ]);
        // $response = Http::timeout(config('SsoConfig.curl.TIMEOUT', 30))->withOptions([
        //     'verify' => config('SsoConfig.curl.VERIFY', false),
        // ])->post(config('SsoConfig.main.URL', url('/')) . '/auth_login', $data);

        dd($response->object());

        // Send user/password to the login page so that we get new cookies.
        // https://cms.meindo.com/main.php?page=member&cmd=logout&act=logout
        $curl = curl_init('https://cms.meindo.com/main.php');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_COOKIEJAR, '/tmp/cookies'); // cookies get stored in this file
        curl_setopt($curl, CURLOPT_POSTFIELDS, [
            'username' => 'gita.samudra@meindo.com',
            'password' => 'Meindo12345',
            'page' => 'member',
            'cmd' => 'login',
            'act' => 'login'
        ]);
        // curl_setopt(...);
        curl_exec($curl);
        curl_close($curl);

        // Send the cookies we just saved to the data page you want
        $curl = curl_init('https://cms.meindo.com/main.php?page=member');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_COOKIEFILE, '/tmp/cookies'); // cookies in this file get sent
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt(...);
        $page = curl_exec($curl);
        dd($page);
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

    public function getIp()
    {
        // foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
        //     if (array_key_exists($key, $_SERVER) === true) {
        //         foreach (explode(',', $_SERVER[$key]) as $ip) {
        //             $ip = trim($ip); // just to be safe
        //             if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
        //                 return $ip;
        //             }
        //         }
        //     }
        // }

        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = $request->ip();

        return $ipaddress; // it will return the server IP if the client IP is not found using this method.
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

    public function token($request, $credentials = [])
    {
        $key = base64_decode(config('SsoConfig.main.KEY'));
        $fromKey = $key;
        $cipher = "AES-256-CBC"; //or AES-128-CBC if you prefer

        try {
            //Create two encrypters using different keys for each
            $encrypterFrom = new Encrypter($fromKey, $cipher);
            $token = $request->token ?? $encrypterFrom->encryptString($request->string ?? $credentials['id']);

            $decryptedFromString = $encrypterFrom->decryptString($token);

            $return['token'] = $token;
            $return['decryptedFromString'] = $decryptedFromString;
            $return['encrypterFrom'] = $encrypterFrom;
        } catch (\Exception $e) {

            $return = false;
        }
        return $return;
    }

    public function forgot(Request $request)
    {

        $data = $request->all();
        $data['app_url'] = url('/');
        // dd($data);

        $response = Http::timeout(config('SsoConfig.curl.TIMEOUT', 30))->withOptions([
            'verify' => config('SsoConfig.curl.VERIFY', false),
        ])->post(config('SsoConfig.main.URL', url('/')) . 'forgot_password', $data);


        Log::info('user: sys url: ' . url()->current() . ' message: SSO forgot request :' . json_encode($data));

        if ($response->ok()) {
            $data = $response->object();
            $respond =  self::ssoRespond($response);
        } else {
            $data = $response->object();
            $respond = false;
        }

        Log::info('user: sys url: ' . url()->current() . ' message: SSO forgot respond :' . json_encode($data));

        return $respond;
    }

    public function reset(Request $request)
    {
        $data = $request->all();
        // dd($data, config('SsoConfig.main.URL', url('/')) . 'forgot_password');
        $response = Http::timeout(config('SsoConfig.curl.TIMEOUT', 30))->withOptions([
            'verify' => config('SsoConfig.curl.VERIFY', false),
        ])->post(config('SsoConfig.main.URL', url('/')) . 'update_password', $data);

        Log::info('user: sys url: ' . url()->current() . ' message: SSO login request :' . json_encode($data));

        if ($response->ok()) {
            $data = $response->object();
            $respond =  self::ssoRespond($response);
        } else {
            $data = $response->object();
            $respond = false;
        }

        Log::info('user: sys url: ' . url()->current() . ' message: SSO login respond :' . json_encode($data));

        // dd( $response->body());
        return $respond;
    }

    // public function auth($email, $password)
    public function auth(Request $request, $credentials = [])
    {
        /*internal cek DB user by token user id ada bug kalo user_status tidak sync ke sso*/
        $token = $request->token ? self::sessionSet($request->token) : false;
        // dd($token);
        // $this->auth($request,['email'=>'2','password'=>2]);
        // dd($credentials);
        if (empty($token)) {

            $credentials = !empty($credentials) ? $credentials : $request->all();
            extract($credentials);
            $rules = array(
                'email' => "required|min:5",
                'password'  => "required|min:3|max:30",
            );

            // $request_all = $request->all();
            $request_all['email'] = @$email;
            $request_all['password'] = @$password;

            // self::validator($request_all, $rules);


            $data['app_code'] = config('SsoConfig.main.APP_CODE', 'APP01');
            $data['email'] = $email;
            $data['password'] = $password;
            if ($token) {
                $data['token'] = $token;
            }
            $response = Http::timeout(config('SsoConfig.curl.TIMEOUT', 30))->withOptions([
                'verify' => config('SsoConfig.curl.VERIFY', false),
            ])->post(config('SsoConfig.main.URL', url('/')) . 'auth_login', $data);

            $data['password'] = '******';
            Log::info('user: sys url: ' . url()->current() . ' message: SSO login request :' . json_encode($data));

            if ($response->ok()) {
                $data = $response->object();
                $respond =  self::ssoRespond($response);
            } else {
                $data = $response->object();
                $respond = false;
            }

            Log::info('user: sys url: ' . url()->current() . ' message: SSO login respond :' . json_encode($data));

            // dd( $response->body());
            return $respond;
        } else {
            return $token;
        }
    }


    public function cobaCurl()
    {
        $data['email'] = 'bagas.setyonugroho@meindo.com';
        $data['password'] = 'bagas.setyonugroho@meindo.com';
        $response = Http::timeout(config('SsoConfig.curl.TIMEOUT', 30))->withOptions([
            'verify' => config('SsoConfig.curl.VERIFY', false),
        ])->post(config('SsoConfig.main.URL', url('/')) . '/auth_login', $data);

        if ($response->ok()) {
            $data = $response->body();
        }
        Log::info("cobaCurl::" . $data);
        return $data;
    }



    public function sessionSet($token)
    {
        dd('ada bug karena ganti auto login pakek email, solve cari id by email untuk dapat id');
        try {
            $user_id =  $token ? Crypt::decryptString($token) : null;
        } catch (\Exception $e) {
            $data['status'] =   'gagal';
            $data['code'] =   101;
            $data['data'] =   $e->getMessage();
            return self::setOutput($data);
        }


        $route = 'home';
        if ($user_id) {
            // Manually Logging a user (Here is successfully recieve the user id)
            $loggedInUser = \Auth::loginUsingId($user_id);

            if (!$loggedInUser || $loggedInUser->is_active == 0) {
                // If User not logged in, then Throw exception
                // throw new Exception('Single SignOn: User Cannot be Signed In');
                // dd('Single SignOn: User Cannot be Signed In');

                $data['status'] =   'gagal';
                $data['code'] =   101;
                $data['data'] =   'Single SignOn: User Cannot be Signed In';
                return self::setOutput($data);
                // return false;
            }
            $redirectTo = '/' . $route;
            // dd($redirectTo,$loggedInUser->toArray(),1,$id);
            return redirect($redirectTo);
            // return $redirectTo;
        } else {
            return 'gagal';
        }
    }

    public function sessionCek(Request $request)
    {
        $user = \Auth::check();
        if ($user) {
            $data['data'] =   \Auth::user()->toArray();
        } else {
            $data['data'] = [];
        }
        return self::setOutput($data);
    }

    public function sessionUnset($token)
    {
        dd('ada bug karena ganti auto login pakek email, solve cari id by email untuk dapat id');
        try {

            $request = new Request();
            $request['token'] = $token;
            $token = self::token($request);
            // dd($request->all(), $token);
            $user_id = $token['decryptedFromString'];
            // $user_id =  $token ? Crypt::decryptString($token) : null;
        } catch (\Exception $e) {
            $data['status'] =   'gagal';
            $data['code'] =   101;
            $data['data'] =   $e->getMessage();
            return self::setOutput($data);
        }
        // dd($user_id);

        if (\Auth::id() == $user_id) {
            $route = 'login';
            $session = session::where('user_id', $user_id)->delete();
            $redirectTo = '/' . $route;
        } else {
            $redirectTo = '/';
        }
        return redirect($redirectTo);
    }
}
