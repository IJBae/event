<?php
class Auth
{
    public $is_auth = false;
    public function __construct()
    {
        $bearer = Auth::getBearerToken();
        if ($bearer == 'a2d6a3468e8299effeed07f04070ffc3') { //주식회사 케어랩스 Admin 키
            $this->is_auth = true;
        }
    }

    /** 
     * Get header Authorization
     * */
    static private function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * get access token from header
     * */
    static public function getBearerToken()
    {
        $headers = Auth::getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/KakaoAK\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    //암호화 함수
    private function aes_encrypt($data, $key = '++!CHAINSAW!++')
    {
        $key = substr(hex2bin(openssl_digest($key, 'sha512')), 0, 16);
        $enc = openssl_encrypt($data, "AES-128-ECB", $key, true);
        return strtoupper(bin2hex($enc));
    }

    //복호화 함수
    public function aes_decrypt($data, $key = '++!CHAINSAW!++')
    {
        $data = @hex2bin($data);
        $key = substr(hex2bin(openssl_digest($key, 'sha512')), 0, 16);
        $dec = openssl_decrypt($data, "AES-128-ECB", $key, true);
        return $dec;
    }
}
