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
$worker->on('workerStart',function (\PyServer\Worker\WorkerInterface $worker){
    $i=0;
    $timer=new \PyServer\Util\Timer(3,function ()use(&$i,&$timer){
        if ($i == 2) {
            $timer->cancel();
            return;
        }
        echo "timer run,i= {$i} \n";
        $i++;
    },true);
    echo "worker ".$worker->id." started\n";

});
$worker->run();