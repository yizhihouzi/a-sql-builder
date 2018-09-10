<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2018/8/13
 * Time: 14:20
 */
require __DIR__ . "/../vendor/autoload.php";
define('CONN_PARAMS',
    [
        'url'           => 'sqlite:///' . __DIR__ . '/test.sqlite',
        'driverOptions' => [PDO::MYSQL_ATTR_FOUND_ROWS => true]
    ]);