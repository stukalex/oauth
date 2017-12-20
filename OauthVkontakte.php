<?php

/**
 * Vkontakte
 */
class OauthVkontakte implements OauthInterface
{
    
    const PROVIDER = 'Vkontakte';
    private $token  = array();
    private $params = array(
        'redirect_uri' => '',
        'client_id' => '',
        'client_secret' => '',
        'scope' => 'photos,email', // разрешения на доступ для приложения.
        'fields' => 'uid,email,first_name,last_name,timezone,sex,bdate,photo_big', // запрашиваемые поля пользователя.
    );
    
    private $userInfo = array();
    
    /**
     * OauthVkontakte constructor.
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
        $url = 'https://oauth.vk.com/authorize';
        $prms = array(
            'client_id' => $this->params['client_id'],
            'redirect_uri' => $this->params['redirect_uri'],
            'scope' => $this->params['scope'],
            'response_type' => 'code',
            'display' => 'page',
            'v' => '5.37',
        );
        return $url . '?' . urldecode(http_build_query($prms));
    }
    
    public function authenticate()
    {
        $result = false;
        $params = array(
            'client_id' => $this->params['client_id'],
            'client_secret' => $this->params['client_secret'],
            'code' => $_GET['code'],
            'redirect_uri' => $this->params['redirect_uri'],
        );
        $url = 'https://oauth.vk.com/access_token';
        
        $token = json_decode(file_get_contents($url . '?' . http_build_query($params)), true);
        if (isset($token['access_token'])) {
            $params = array(
                'uids' => $token['user_id'],
                'fields' => $this->params['fields'],
                'access_token' => $token['access_token'],
            );
            $url = 'https://api.vk.com/method/users.get';
            if ($userInfo = file_get_contents($url . '?' . http_build_query($params))) {
                $userInfo = json_decode($userInfo);
                $this->userInfo = $userInfo->response[0];
                $this->token = $token;
                $result = true;
            }
        }
        return $result;
    }
    
    /**
     * @return array
     */
    public function getUserInfo()
    {
        // форматирование пола пользователя
        if ($this->userInfo->sex == '1') {
            $sex = 'f';
        } elseif ($this->userInfo->sex == '2') {
            $sex = 'm';
        } else {
            $sex = '';
        }
        return array(
            'id' => $this->userInfo->uid,
            'name' => $this->userInfo->first_name . ' ' . $this->userInfo->last_name,
            'email' => $this->token['email'],
            'birthday' => isset($this->userInfo->bdate) ? date('Y-m-d', strtotime($this->userInfo->bdate)) : '',
            'gender' => $sex,
            'timezone' => $this->userInfo->timezone,
            'photo' => $this->userInfo->photo_big, // small profile photo 50x50
        );
    }
    
}