<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 17:40
 */

require_once "vendor/autoload.php";

use Pengyu\Server\Worker\Server;

$worker=new Server("ws://0.0.0.0:8080");
//$worker->config(["deamon"=>true,"workerCount"=>2]);
$worker->on('message',function ($connection,$fd,$content){
    foreach ($connection->connections as $con) {
        $connection->send($con,intval($fd)." say: hello,".$content);
    }
});

$worker->run();