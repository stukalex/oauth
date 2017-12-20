Пример использования:
```php
$settings = [
    'locale' => 'ru_RU',
    'redirect_uri' => 'https://example.ru/login?provider=facebook',
    'Facebook' => [
        'client_id' => '',
        'client_secret' => ''
    ],
    'Twitter' => [...],
];
$Oauth = new \Oauth($_GET['provider'], $settings);

if ($Oauth->authenticate()) {

    // получение токена, информации, авторизация для фронтенда!
    $userInfo = $Oauth->getUserInfo();
    ... авторизация/регистрация пользователя
    redirect('main');

} else {

    if (!$user->isGuest()) {
        // уже авторизованны, редирект на главную страницу
        redirect('main');
    } else {
        // редирект в соц.сеть
        redirect($Oauth->getAuthLink());
    }

}
```
