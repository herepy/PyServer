<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:45
 */

namespace PyServer\Worker;

class ChildWorker implements WorkerInterface
{

    public function __construct($address = null)
    {

    }

    public function setListen($protocol, $address, $port)
    {
        // TODO: Implement setListen() method.
    }

    public function on($event, $callback)
    {
        // TODO: Implement on() method.
    }

    public function config($config)
    {
        // TODO: Implement config() method.
    }

    public function run()
    {
        // TODO: Implement run() method.
    }

}