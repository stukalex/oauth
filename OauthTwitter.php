<?php
/**
 * twitter
 */
class OauthTwitter implements OauthInterface
{
    const PROVIDER = 'twitter';
    var     $params      = array(
        'redirect_uri' => '', // callback_url
        'client_id' => '', // consumer_key
        'client_secret' => '', // consumer_secret
    );
    private $userInfo    = array();
    private $oauth_token;
    private $oauth_token_secret;
    private $screen_name = '';
    private $user_id     = '';
    
    /**
     * Oauthtwitter constructor.
     *
     * @param $params
     */
    function __construct($params)
    {
        $this->params = array_merge($this->params, $params);
        $this->oauth_token = $_SESSION['twitter_oauth_token'] ?? '';
        $this->oauth_token_secret = $_SESSION['twitter_oauth_token_secret'] ?? '';
    }
    
    /**
     * @return bool|string
     */
    public function getAuthLink()
    {
        $response = $this->getRequest(
            'https://api.twitter.com/oauth/request_token',
            $this->params['client_secret'] . '&',
            [
                'oauth_callback' => $this->params['redirect_uri'],
            ]
        );
        dump('twitter_oauth.log', 'GET request_token ' . $response . "\r\n");
        parse_str($response, $result);
        if (empty($result['oauth_token_secret'])) {
            return false;
        }
        $_SESSION['twitter_oauth_token_secret'] = $result['oauth_token_secret'];
        $_SESSION['twitter_oauth_token'] = $result['oauth_token'];
        return 'https://api.twitter.com/oauth/authorize' . '?oauth_token=' . $result['oauth_token'];
    }
    
    /**
     * @return bool
     */
    public function authenticate()
    {
        if (empty($_GET['oauth_token']) || empty($_GET['oauth_verifier'])) {
            return false;
        }
        $response = $this->getRequest(
            'https://api.twitter.com/oauth/access_token',
            $this->params['client_secret'] . '&' . $this->oauth_token_secret,
            [
                'oauth_token' => $_GET['oauth_token'],
                'oauth_verifier' => $_GET['oauth_verifier'],
            ]
        );
        dump('twitter_oauth.log', 'GET access_token ' . $response . "\r\n");
        parse_str($response, $result);
        if (empty($result['oauth_token']) || empty($result['user_id'])) {
            return false;
        }
        $this->oauth_token = $result['oauth_token'];
        $this->oauth_token_secret = $result['oauth_token_secret'];
        $this->user_id = $result['user_id'];
        $this->screen_name = $result['screen_name'];
        return true;
    }
    
    /**
     * @return array|bool
     */
    public function getUserInfo()
    {
        if ($this->getAccount()) {
            return array(
                'id' => $this->userInfo['id'],
                'name' => $this->userInfo['name'],
                'login' => $this->userInfo['screen_name'],
                'photo' => $this->userInfo['profile_image_url'],
                'email' => $this->userInfo['email'] ?? '',
                'timezone' => $this->userInfo['utc_offset'] ? $this->userInfo['utc_offset'] / 3600 : '',
            );
        } else {
            return false;
        }
    }
    
    /**
     * @return bool
     */
    private function getAccount()
    {
        $response = $this->getRequest(
            'https://api.twitter.com/1.1/users/show.json',
            $this->params['client_secret'] . '&' . $this->oauth_token_secret,
            [
                'oauth_consumer_key' => $this->params['client_id'],
                'oauth_token' => urlencode($this->oauth_token),
                'screen_name' => $this->screen_name,
            ]
        );
        dump('twitter_oauth.log', 'GET account ' . $response . "\r\n");
        $this->userInfo = json_decode($response, true);
        return count($this->userInfo) ? true : false;
    }
    
    /**
     * @param $url
     * @param $key
     * @param $data
     *
     * @return string
     */
    private function getRequest($url, $key, $data)
    {
        $params = array_merge([
            'oauth_consumer_key' => $this->params['client_id'],
            'oauth_nonce' => md5(uniqid(rand(), true)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        ], $data);
        ksort($params);
        $oauth_base_text = 'GET&' . urlencode($url) . '&' . urlencode(http_build_query($params));
        $params['oauth_signature'] = base64_encode(hash_hmac("sha1", $oauth_base_text, $key, true));
        ksort($params);
        $response = @file_get_contents($url . '?' . http_build_query($params));
        return $response ?: '';
    }
    
}