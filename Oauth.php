<?php

/**
 * Oauth
 */
class Oauth
{
    /* @var OauthInterface */
    private $_provider;
    
    /**
     * Oauth constructor.
     *
     * @param $provider
     * @param $settings
     */
    function __construct($provider, $settings)
    {
        $provider = ucfirst($provider);
        $class = 'Oauth' . $provider;
        $params = $settings[$provider];
        $params['redirect_uri'] = $settings['redirect_uri'];
        $params['locale'] = $settings['locale'];
        $this->_provider = new $class($params);
    }
    
    /**
     * @return mixed
     */
    public function getAuthLink()
    {
        return $this->_provider->getAuthLink();
    }
    
    /**
     * @return mixed
     */
    public function authenticate()
    {
        return $this->_provider->authenticate();
    }
    
    /**
     * @return mixed
     */
    public function getUserInfo()
    {
        return $this->_provider->getUserInfo();
    }
    
    /**
     * @param $settings
     *
     * @return array
     */
    public static function getLoginUrls($settings)
    {
        $urls = array();
        foreach ($settings as $provider => $options) {
            $urls[] = call_user_func('Oauth' . $provider, 'getAuthLink', $options);
        }
        return $urls;
    }
    
    public static function getLoginButtons()
    {
        
    }
}
