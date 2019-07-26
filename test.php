<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 17:40
 */

require_once "vendor/autoload.php";

use PyServer\Worker\MasterWorker;

$worker=new MasterWorker("http://0.0.0.0:8080");
//$worker->config(["workerCount"=>2]);
$worker->on('masterStart',function (PyServer\Worker\MasterWorker $worker){
    $timer=new \PyServer\Util\Timer(3,function (){
        echo "run timer";
    },false,$worker::$scheduler);
    $timer->start();
    echo "do something";
});
$worker->run();