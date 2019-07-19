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
$worker->run();


//=====================================================================

//$socket=socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
//if (!$socket) {
//    die("create fail");
//}
////socket_set_option($socket,SOL_SOCKET,SO_REUSEPORT,1);
//
//socket_bind($socket,"127.0.0.1","8888");
//socket_listen($socket);
//
//$event_base=new \EventBase();
//$event=new \Event($event_base,$socket,\Event::READ,"connect_cb",$socket);
//$event->add();
//$event_base->loop();
//echo "end";
//function connect_cb($socket)
//{
//    $con=socket_accept($socket);
//    echo "client connected:".$con;
//}