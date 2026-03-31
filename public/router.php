<?php
// PHP 内置服务器路由文件：php think run 时使用
// +----------------------------------------------------------------------
namespace think;

require __DIR__ . '/../vendor/autoload.php';

$http = (new App())->http;

$response = $http->run();

$response->send();

$http->end($response);
