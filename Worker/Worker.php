<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:45
 */

namespace PyServer\Worker;


use PyServer\Scheduler\Event;
use PyServer\Scheduler\SchedulerInterface;

class Worker implements WorkerInterface
{
    /**
     * @var resource 监听socket
     */
    protected $socket;

    /**
     * @var int 标识id
     */
    public $id;

    /**
     * @var \PyServer\Scheduler\SchedulerInterface 调度器实例
     */
    public static $scheduler;

    /**
     * @var \PyServer\Transport\TransportInterface 传输层实例
     */
    protected $transport;

    /**
     * @var string 协议层完整名
     */
    protected $protocol;


    public function __construct($transport,$protocol,$address,$port)
    {
        $this->id=spl_object_hash($this);

        if (!self::$scheduler) {
            //获取可用调度器
            self::$scheduler=get_scheduler();
            self::$scheduler->init();
        }

        $this->listen($transport,$protocol,$address,$port);
    }

    public function listen($transport,$protocol,$address,$port)
    {
        $this->protocol="\\PyServer\\Protocol\\".ucfirst($protocol);
        $transportName="\\PyServer\\Transport\\".ucfirst($transport);
        $this->transport=new $transportName($this,$this->protocol);

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
        //非阻塞模式
        socket_set_nonblock($this->socket);
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
        //todo onWorkerStart回调
        Event::dispatch("workerStart",[$this]);

        //监听新连接
        self::$scheduler->add($this->socket,SchedulerInterface::TYPE_READ,[$this->transport,"accept"]);
        self::$scheduler->loop();
    }



}