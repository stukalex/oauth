<?php

/**
 * OauthMailru
 */
class OauthMailru implements OauthInterface
{
    const PROVIDER = 'Mailru';
    var $params = array(
        'redirect_uri' => '',
        'client_id' => '',
        'client_secret' => '',
        'scope' => ['email', 'user_birthday', 'user_photos'], // разрешения на доступ для приложения.
        'fields' => 'id,email,name,gender,birthday,timezone,picture', // запрашиваемые поля пользователя.
        'locale' => 'ru_RU',
    );
    
    private $userInfo = array();
    
    /**
     * OauthFacebook constructor.
     *
     * @param $params
     */
    function __construct($params)
    {
        $this->params = array_merge($this->params, $params);
    }
    
    /**
     * @return string
     */
    public function getAuthLink()
    {
        $url = 'https://connect.mail.ru/oauth/authorize';
        $prms = array(
            'client_id' => $this->params['client_id'],
            'redirect_uri' => $this->params['redirect_uri'],
            // 'scope' => implode(', ', $this->params['scope']), ???
            'response_type' => 'code',
        );
        return $url . '?' . urldecode(http_build_query($prms));
    }
    
    /**
     * @return bool
     */
    public function authenticate()
    {
        if (empty($_GET['code'])) {
            return false;
        }
        $params = [
            'client_id' => $this->params['client_id'],
            'client_secret' => $this->params['client_secret'],
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->params['redirect_uri'],
            'code' => $_GET['code'],
        ];
        $url = 'https://connect.mail.ru/oauth/token';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        curl_close($curl);
        $tokenInfo = json_decode($result);
        if (isset($tokenInfo->access_token)) {
            $sign = md5(http_build_str([
                'app_id' => $this->params['client_id'],
                'method' => 'users.getInfo',
                'secure' => '1',
                'session_key' => $tokenInfo->access_token . $this->params['client_secret'],
            ], '', ''));
            $params = [
                'method' => 'users.getInfo',
                'secure' => '1',
                'app_id' => $this->params['client_id'],
                'session_key' => $tokenInfo->access_token,
                'sig' => $sign,
            ];
            $url = 'http://www.appsmail.ru/platform/api?' . urldecode(http_build_query($params));
            if ($userInfo = file_get_contents($url)) {
                $this->userInfo = array_shift(json_decode($userInfo, true));
                return true;
            }
        } else {
            print_r($tokenInfo);
            die();
        }
        return false;
    }
    
    /**
     * @return array
     */
    public function getUserInfo()
    {
        return array(
            'id' => $this->userInfo['uid'],
            'name' => $this->userInfo['nick'],
            'email' => $this->userInfo['email'] ?? '',
            'birthday' => $this->userInfo['birthday'] ? date('Y-m-d', strtotime($this->userInfo['birthday'])) : '',
            'gender' => ($this->userInfo['sex'] == 0) ? 'm' : 'f', // male | female
            'timezone' => '', //$this->userInfo['timezone'],
            'photo' => $this->userInfo['pic_50'],
        );
    }
    
}