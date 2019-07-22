<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:45
 */

namespace PyServer\Worker;


use PyServer\Scheduler\Event;

class ChildWorker implements WorkerInterface
{
    /**
     * @var resource 监听socket
     */
    protected $socket;

    /**
     * @var int 标识id
     */
    protected $id;

    /**
     * @var object 调度器
     */
    protected $scheduler;

    public function __construct($transport,$address,$port)
    {
        $this->id=spl_object_hash($this);

        //todo 记录
        echo "listen:".$address." port:".$port."\n";
        //创建监听socket
        $domain=$transport == "unix"?AF_UNIX:AF_INET;
        $type=$transport == "tcp"?SOCK_STREAM:SOCK_DGRAM;

        if ($transport == "unix") {
            $protocol=0;
        }else{
            $protocol=getprotobyname($transport);
        }

        //创建监听socket
        $this->socket=socket_create($domain,$type,$protocol);
        if (!$this->socket) {
            die("create socket failed");
        }

        //不是unix,设置端口复用
        if ($protocol !== 0) {
            socket_set_option($this->socket,SOL_SOCKET,SO_REUSEPORT,1);
        }
        socket_bind($this->socket,$address,$port);
        socket_listen($this->socket);
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
        //todo 目前只有event调度器
        $this->scheduler=new Event();
        $this->scheduler->init();
        $this->scheduler->add($this->socket,Event::TYPE_READ,[$this,"accept"],$this->socket);
        $this->scheduler->loop();
    }

    public function accept($socket)
    {
        $con=socket_accept($socket);
        echo "worker:{$this->id} get connected:".$con."\n";
    }

}