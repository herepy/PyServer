<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:45
 */

namespace PyServer\Worker;

class MasterWorker implements WorkerInterface
{

    protected $protocol;

    protected $address;

    protected $port;

    public function __construct($address = null)
    {
        if (!$address) {
            return;
        }

        $tmp=explode("//",$address,2);
        if (count($tmp) < 2) {
            //todo 抛出异常
        }

        $protocol='\\Protocol\\'.ucfirst(strtolower($tmp[0]));
        if (!class_exists($protocol)) {
            //todo 抛出异常
        }

        $info=explode(":",$tmp[1]);
        if (count($info) < 2) {
            //todo 抛出异常
        }

        $this->address=$info[0];
        $this->port=$info[1];
        $this->protocol=$protocol;
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