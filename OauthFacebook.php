<?php

/**
 * OauthFacebook
 */
class OauthFacebook implements OauthInterface
{
    const PROVIDER = 'Facebook';
    private $version = 'v2.10';
    var     $params  = array(
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
        $url = 'https://www.facebook.com/' . $this->version . '/dialog/oauth';
        $prms = array(
            'client_id' => $this->params['client_id'],
            'redirect_uri' => $this->params['redirect_uri'],
            'scope' => implode(', ', $this->params['scope']),
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
        $params = array(
            'client_id' => $this->params['client_id'],
            'redirect_uri' => $this->params['redirect_uri'],
            'client_secret' => $this->params['client_secret'],
            'code' => $_GET['code'],
        );
        $url = 'https://graph.facebook.com/' . $this->version . '/oauth/access_token?' . http_build_query($params) . '#_=_';
        $tokenInfo = json_decode(file_get_contents($url));
        if (isset($tokenInfo->access_token)) {
            $params = array(
                'fields' => $this->params['fields'],
                'access_token' => $tokenInfo->access_token,
                'appsecret_proof' => hash_hmac('sha256', $tokenInfo->access_token, $this->params['client_secret']),
                'locale' => $this->params['locale'],
            );
            $url = 'https://graph.facebook.com/me';
            if ($userInfo = file_get_contents($url . '?' . http_build_query($params))) {
                $this->userInfo = json_decode($userInfo);
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
            'id' => $this->userInfo->id,
            'name' => $this->userInfo->name,
            'email' => $this->userInfo->email,
            'birthday' => isset($this->userInfo->birthday) ? date('Y-m-d', strtotime($this->userInfo->birthday)) : '',
            'gender' => ($this->userInfo->gender == 'male') ? 'm' : 'f', // male | female
            'timezone' => $this->userInfo->timezone,
            'photo' => $this->userInfo->picture->data->url, // small profile photo 50x50
        );
    }
    
}