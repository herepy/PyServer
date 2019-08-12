<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:45
 */

namespace Pengyu\Server\Worker;


use Pengyu\Server\Scheduler\Event;
use Pengyu\Server\Scheduler\SchedulerInterface;
use Pengyu\Server\Util\Log;

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
     * @var \Pengyu\Server\Scheduler\SchedulerInterface 调度器实例
     */
    public static $scheduler;

    /**
     * @var \Pengyu\Server\Transport\TransportInterface 传输层实例
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

        $this->installSignal();
        $this->listen($transport,$protocol,$address,$port);
    }

    protected function installSignal()
    {
        //windows系统，跳过
        if (is_win()) {
            return;
        }

        //去掉可能从父进程继承的信号处理方式
        pcntl_signal(SIGINT,SIG_IGN,false);

        //安装停止信号
        self::$scheduler->add(SIGINT,SchedulerInterface::TYPE_SIGNAL,[$this,"stop"]);
    }

    public function stop()
    {
        Log::close();
        $this->transport->stop();
        self::$scheduler->clear();
        @socket_close($this->socket);
        exit(0);
    }

    public function listen($transport,$protocol,$address,$port)
    {
        $this->protocol=$protocol?"\\Pengyu\\Server\\Protocol\\".ucfirst($protocol):null;
        $transportName="\\Pengyu\\Server\\Transport\\".ucfirst($transport);
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
            Log::error("create socket failed");
            exit(1);
        }

        //不是unix,设置端口复用
        if ($protocol !== 0 && !is_win()) {
            socket_set_option($this->socket,SOL_SOCKET,SO_REUSEPORT,1);
        }
        socket_bind($this->socket,$address,$port);
        socket_listen($this->socket);

        if (function_exists('socket_import_stream') && $transport === 'tcp') {
            set_error_handler(function(){});
            socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
            socket_set_option($this->socket, SOL_TCP, TCP_NODELAY, 1);
            restore_error_handler();
        }
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
        //onWorkerStart回调
        Event::dispatch("workerStart",[$this]);

        //监听新连接
        self::$scheduler->add($this->socket,SchedulerInterface::TYPE_READ,[$this->transport,"accept"]);
        self::$scheduler->loop();
    }



}