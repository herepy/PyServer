<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:45
 */

namespace PyServer\Worker;

use PyServer\Exception\ClassNotFoundException;

class MasterWorker implements WorkerInterface
{

    protected $protocol;

    protected $address;

    protected $port;

    protected $deamon=false;

    protected $workerCount=1;

    protected $workerPids=[];

    protected $logDir;

    protected $logFile;

    public function __construct($address = null)
    {
        if (!$address) {
            return;
        }

        $tmp=explode("://",$address,2);
        if (count($tmp) < 2) {
            throw new \Exception("address is not right");
        }

        $protocol='PyServer\\Protocol\\'.ucfirst(strtolower($tmp[0]));
        if (!class_exists($protocol)) {
            throw new ClassNotFoundException($protocol);
        }

        $info=explode(":",$tmp[1]);
        if (count($info) < 2) {
            throw new \Exception("address is not right");
        }

        $this->address=$info[0];
        $this->port=$info[1];
        $this->protocol=$protocol;
    }

    public function setListen($protocol, $address, $port)
    {
        $protocol='PyServer\\Protocol\\'.ucfirst(strtolower($protocol));
        if (!class_exists($protocol)) {
            throw new ClassNotFoundException($protocol);
        }

        $this->address=$address;
        $this->port=$port;
        $this->protocol=$protocol;
    }

    public function on($event, $callback)
    {
        // TODO: Implement on() method.
    }

    public function config($config)
    {
        if (!is_array($config)) {
            return false;
        }

        if (isset($config["deamon"])) {
            $this->deamon=$config["deamon"];
        }

        if (isset($config["workerCount"])) {
            $this->workerCount=$config["workerCount"];
        }

        if (isset($config["logFile"])) {
            $this->logDir=dirname($config["logFile"]).DIRECTORY_SEPARATOR;
            $this->logDir=basename($config["logFile"]);
        }
    }

    public function run()
    {
        check_env();
    }

}