<?php
require_once 'Router.php';
$router = new Router('config.ini');
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);