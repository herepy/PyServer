<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 17:40
 */

require_once "vendor/autoload.php";

use Pengyu\Server\Worker\Server;

$worker=new Server("http://0.0.0.0:8080");
//$worker->config(["deamon"=>true,"workerCount"=>2]);
$worker->on('request',function ($content,$response){
    $response->end(json_encode($content));
});

$worker->on('message',function (\Pengyu\Server\Transport\TransportInterface $connection,$fd,$content){
    $connection->send($fd,"client say:".$content);
});
$worker->run();