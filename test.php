<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 17:40
 */

require_once "vendor/autoload.php";

use PyServer\Worker\MasterWorker;

$worker=new MasterWorker("http://127.0.0.1:8080");
//$worker->config(["workerCount"=>2]);
$worker->on('connect',function (\PyServer\Transport\TransportInterface $transport,$fd){
    echo "client connected:".intval($fd)."\n";
});
$worker->run();
