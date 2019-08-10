<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 17:40
 */

require_once "vendor/autoload.php";

use PyServer\Worker\PyServer;

$worker=new PyServer("ws://0.0.0.0:8080");
//$worker->config(["deamon"=>true,"workerCount"=>2]);
$worker->on('message',function ($connection,$fd,$content){

    $connection->send($fd,$content);
});

$worker->run();