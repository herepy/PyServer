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
$worker->on('workerStart',function ($worker){
    $wid=$worker->id;
    $timer=new \PyServer\Util\Timer(3,function()use($wid){
        echo time()." in timer:worker id is ".$wid."\n";
    });
});
$worker->run();