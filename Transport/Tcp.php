<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 16:15
 */

namespace PyServer\Transport;

use PyServer\Scheduler\SchedulerInterface;
use PyServer\Worker\ChildWorker;
use PyServer\Worker\WorkerInterface;

class Tcp implements TransportInterface
{
    /**
     * @var WorkerInterface 所属工作进程实例
     */
    protected $worker;

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


    public function __construct(WorkerInterface $worker, $protocol)
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

            ChildWorker::$scheduler->add($con,SchedulerInterface::TYPE_READ,[$this,"read"]);
            $this->connections[intval($con)]=$con;
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
            $content=($this->protocol)::decode($content,$contentSize);
            //解码数据出错，丢弃
            if (!$content) {
                return;
            }
        }

        //todo 后续内容处理

    }

    public function close($fd)
    {
        // TODO: Implement close() method.
        ChildWorker::$scheduler->del($fd,SchedulerInterface::TYPE_READ);
        ChildWorker::$scheduler->del($fd,SchedulerInterface::TYPE_WRITE);
        unset($this->connections[$fd]);

    }

}