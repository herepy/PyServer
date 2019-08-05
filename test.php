<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 17:40
 */

require_once "vendor/autoload.php";

use PyServer\Worker\PyServer;

$worker=new PyServer("http://0.0.0.0:8080");
//$worker->config(["deamon"=>true,"workerCount"=>2]);
$worker->on('message',function ($connection,$fd,$content){

    if (file_exists($_SERVER["PHP_SELF"]) && is_file($_SERVER["PHP_SELF"])) {
        $connection->send($fd,file_get_contents($_SERVER["PHP_SELF"]));
        return;
    }

    $connection->send($fd,json_encode($content));

});

$worker->run();