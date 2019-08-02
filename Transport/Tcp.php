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
    protected $maxSize=10240;

    /**
     * @var array 所有连接fd [intval($fd)=>$fd]
     */
    public $connections=[];

    /**
     * @var string 连接当前已接受到的数据 [intval($fd)=>[size=>xxx,buffer=>xxx]]
     */
    protected $buffer=[];


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

            //onConnceted回调
            Event::dispatch("connect",[$this,$con]);
            var_dump($this->connections);
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

            if ($contentSize) {
                $this->buffer[intval($fd)]=["size"=>$contentSize,"buffer"=>$content];

                //只接受了一部分数据，等待下一次的读取
                if (!strlen($content) < $contentSize) {
                    return;
                }

                $content=substr($this->buffer[intval($fd)]["buffer"],0,$this->buffer[intval($fd)]["size"]);
            } else { //数据的一部分或者
                $this->buffer[intval($fd)]["buffer"].=$content;

                //只接受了一部分数据，等待下一次的读取
                if (strlen($this->buffer[intval($fd)]["buffer"]) < $this->buffer[intval($fd)]["size"]) {
                    return;
                }

                $content=substr($this->buffer[intval($fd)]["buffer"],0,$this->buffer[intval($fd)]["size"]);
            }

            set_error_handler(function (){});
            $content=($this->protocol)::decode($content);
            set_error_handler(null);

            //清空本次接收数据
            $this->buffer[intval($fd)]=["size"=>0,"buffer"=>""];
        }

        //onMessage回调
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
        unset($this->buffer[intval($fd)]);

        //onClose回调
        Event::dispatch("close",[$this,$fd]);
        @socket_close($fd);
    }

    public function stop()
    {
        foreach ($this->connections as $fd) {
            $this->close($fd);
        }
        $this->connections=[];
        $this->buffer=[];
    }

}