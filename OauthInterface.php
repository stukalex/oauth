<?php

/**
 * Interface OauthInterface
 */
interface OauthInterface
{
    
    public function getAuthLink();
    
    public function authenticate();
    
    public function getUserInfo();
    
}
