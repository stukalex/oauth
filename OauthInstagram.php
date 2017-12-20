<?php

/**
 * OauthInstagram
 */
class OauthInstagram implements OauthInterface
{
    const PROVIDER = 'Instagram';
    var $params = array(
        'redirect_uri' => '',
        'client_id' => '',
        'client_secret' => '',
        'scope' => ['basic'], // 'basic', 'likes', 'comments', 'relationships'
    );
    
    private $userInfo = array();
    
    /**
     * OauthInstagram constructor.
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
        $url = 'https://api.instagram.com/oauth/authorize';
        $prms = array(
            'client_id' => $this->params['client_id'],
            'redirect_uri' => $this->params['redirect_uri'],
            'scope' => implode('+', $this->params['scope']),
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
            'grant_type' => 'authorization_code',
            'client_id' => $this->params['client_id'],
            'client_secret' => $this->params['client_secret'],
            'redirect_uri' => $this->params['redirect_uri'],
            'code' => $_GET['code'],
        ];
        $url = 'https://api.instagram.com/oauth/access_token?' . http_build_query($params) . '#_=_';
        // curl post query
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $jsonData = curl_exec($ch);
        curl_close($ch);
        // -- curl --
        $tokenInfo = json_decode($jsonData);
        
        if (isset($tokenInfo->access_token)) {
            $this->userInfo = $tokenInfo->user;
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
        print_r($this->userInfo);
        die();
        return array(
            'id' => $this->userInfo->id,
            'name' => $this->userInfo->username,
            'email' => $this->userInfo->email ?? '',
            'birthday' => isset($this->userInfo->birthday) ? date('Y-m-d', strtotime($this->userInfo->birthday)) : '',
            'gender' => ($this->userInfo->gender == 'male') ? 'm' : 'f', // male | female
            'timezone' => $this->userInfo->timezone,
            'photo' => $this->userInfo->picture->data->url, // small profile photo 50x50
        );
    }
    
}
