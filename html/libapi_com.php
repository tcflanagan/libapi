<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../data/utils.php');

use \Laminas\Config\Config;

$config = new Config(include($_SERVER['DOCUMENT_ROOT'] . '/../data/libapi_conf.php'));

function connectDb() {
    global $config;
    $mysqli = mysqli_connect(
        $config->connections->libapi->host,
        $config->connections->libapi->username,
        $config->connections->libapi->password,
        $config->connections->libapi->dbname);
                         
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }
    return $mysqli;
}
