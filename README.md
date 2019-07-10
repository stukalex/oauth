Пример использования:
```php
$settings = [
    'locale' => 'ru_RU',
    'redirect_uri' => 'https://example.ru/login?provider=facebook',
    'facebook' => [
        'client_id' => '',
        'client_secret' => ''
    ],
    'twitter' => [...],
];
$provider = $_GET['provider']; // facebook, twitter, etc.
$Oauth = new \Oauth($provider, $settings);

if (!$user->isGuest())
    redirect('/');

if ($Oauth->authenticate()) {
    // получение токена, информации, авторизация для фронтенда!
    $userInfo = $Oauth->getUserInfo();
    ... авторизация/регистрация пользователя
    redirect('main');
} else {
    redirect($Oauth->getAuthLink());
}
```
