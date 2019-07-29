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
//$worker->config(["workerCount"=>2]);
$worker->on('workerStart',function ($worker){
    $timer=new \PyServer\Util\Timer(3,function (){
        echo "timer run\n";
    },false);
    echo "worker ".$worker->id." started\n";
});
$worker->on('message',function (\PyServer\Transport\TransportInterface $connection,$fd,$content){

    echo "on message\n";
    $connection->send($fd,json_encode($content));
});
$worker->run();