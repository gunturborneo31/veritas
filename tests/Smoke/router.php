<?php

// Router untuk `php -S`: file statis di public/ dilayani langsung, sisanya ke Laravel.
$public = getenv('VERITAS_ROOT').'/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

if ($uri !== '/' && is_file($public.$uri)) {
    return false;
}

chdir($public);
require $public.'/index.php';
