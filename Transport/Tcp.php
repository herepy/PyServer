<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 16:15
 */

namespace PyServer\Transport;

use PyServer\Scheduler\Event;
use PyServer\Scheduler\SchedulerInterface;
use PyServer\Worker\Worker;
use PyServer\Worker\WorkerInterface;

class Tcp implements TransportInterface
{
    /**
     * @var WorkerInterface 所属工作进程实例
     */
    public $worker;

    /**
     * @var string 应用层完整名
     */
    protected $protocol;

    /**
     * @var int 缓冲区大小
     */
    protected $maxSize=1024;

    /**
     * @var array 所有连接fd [intval($fd)=>$fd]
     */
    public $connections=[];


    public function __construct(WorkerInterface $worker, $protocol=null)
    {
        $this->worker=$worker;
        $this->protocol=$protocol;
    }

    public function accept($socket)
    {
        $con=socket_accept($socket);
        if ($con) {
            //非阻塞模式
            socket_set_nonblock($con);

            Worker::$scheduler->add($con,SchedulerInterface::TYPE_READ,[$this,"read"]);
            $this->connections[intval($con)]=$con;

            //todo onConnceted回调
            Event::dispatch("connect",[$this,$con]);

        }
    }

    public function send($fd, $content)
    {
        if (!$content || !is_resource($fd)) {
            return;
        }

        //是否有应用层协议，使用协议编码内容
        if ($this->protocol) {
            $content=($this->protocol)::encode($content);
        }
        socket_write($fd,$content,strlen($content));
    }

    public function read($fd)
    {
        $content=socket_read($fd,$this->maxSize);
        if (!$content || !is_resource($fd)) {  //客户端断开时，content是空
            $this->close($fd);
            return;
        }

        //是否有应用层协议，使用协议解码内容
        if ($this->protocol) {
            $contentSize=($this->protocol)::size($content);
            //数据出错，丢弃
            if (!$contentSize) {
                return;
            }
            $content=($this->protocol)::decode($content);
        }

        //todo onMessage回调
        Event::dispatch("message",[$this,$fd,$content]);

    }

    public function close($fd,$content=null)
    {
        if ($content) {
            $this->send($fd,$content);
        }

        Worker::$scheduler->del($fd,SchedulerInterface::TYPE_READ);
        Worker::$scheduler->del($fd,SchedulerInterface::TYPE_WRITE);
        unset($this->connections[intval($fd)]);

        //todo onClose回调
        Event::dispatch("close",[$this,$fd]);
        @socket_close($fd);
    }

}