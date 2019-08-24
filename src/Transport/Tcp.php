<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 16:15
 */

namespace Pengyu\Server\Transport;

use Pengyu\Server\Scheduler\Event;
use Pengyu\Server\Scheduler\SchedulerInterface;
use Pengyu\Server\Worker\Worker;

class Tcp implements TransportInterface
{
    /**
     * @var Worker 所属工作进程实例
     */
    public $worker;

    /**
     * @var string 应用层完整名
     */
    protected $protocol;

    /**
     * @var int 缓冲区大小 byte
     */
    public $maxSize=65535;

    /**
     * @var int 可接受单个包大小 byte
     */
    public $maxPackageSize=8192000;

    /**
     * @var int 最大连接数
     */
    public $maxConnection=1000;

    /**
     * @var array 所有连接fd [intval($fd)=>$fd]
     */
    public $connections=[];

    /**
     * @var array 连接当前已接受到的数据 [intval($fd)=>[size=>xxx,buffer=>xxx]]
     */
    public $buffer=[];

    /**
     * @var array websocket已握手的连接 [intval(fd1)=>1,intval(fd2)=>1]
     */
    public $handshake=[];


    public function __construct(Worker $worker, $protocol=null)
    {
        $this->worker=$worker;
        $this->protocol=$protocol;
    }

    public function accept($socket)
    {
        //是否达到最大连接数
        if (count($this->connections) >= $this->maxConnection) {
            return;
        }

        $con=socket_accept($socket);
        if ($con) {
            //非阻塞模式
            socket_set_nonblock($con);

            Worker::$scheduler->add($con,SchedulerInterface::TYPE_READ,[$this,"read"]);
            $this->connections[intval($con)]=$con;

            //onConnceted回调
            try {
                Event::dispatch("connect",[$this,$con]);
            }  catch (\Throwable $throwable) {
                socket_write($con,$throwable->getMessage());
                socket_close($con);
            }
        }
    }

    public function send($fd,$content,$raw=false)
    {
        if (!$content || !is_resource($fd)) {
            return;
        }

        //是否有应用层协议，使用协议编码内容
        if ($this->protocol && !$raw) {
            $content=($this->protocol)::encode($content,$fd,$this);
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

        //onReceive回调
        try {
            Event::dispatch("receive",[$this,$fd,$content]);
        } catch (\Throwable $throwable) {
            $this->close($fd,$throwable->getMessage());
            return;
        }

        //是否有应用层协议，使用协议解码内容
        if ($this->protocol) {

            //获取完整包内容大小
            $contentSize=($this->protocol)::size($content,$fd,$this);
            //有问题的包或者是websocket握手包
            if ($contentSize === false) {
                return;
            }

            //首次接受到包头
            if ($contentSize) {
                $this->buffer[intval($fd)]=["size"=>$contentSize,"buffer"=>$content];

                //只接受了一部分数据，等待下一次的读取
                if (strlen($content) < $contentSize) {
                    return;
                }
            } else { //数据的一部分或者
                $this->buffer[intval($fd)]["buffer"].=$content;

                //只接受了一部分数据，等待下一次的读取
                if (strlen($this->buffer[intval($fd)]["buffer"]) < $this->buffer[intval($fd)]["size"]) {
                    return;
                }
            }

            //完整包内容
            $completeContent=substr($this->buffer[intval($fd)]["buffer"],0,$this->buffer[intval($fd)]["size"]);

            set_error_handler(function (){});
            ($this->protocol)::decode($completeContent,$fd,$this);
            set_error_handler(null);

            //清空本次接收数据
            unset($this->buffer[intval($fd)]);
        }
    }

    public function close($fd,$content=null)
    {
        if ($content) {
            $this->send($fd,$content);
        }

        set_error_handler(function(){});
        Worker::$scheduler->del($fd,SchedulerInterface::TYPE_READ);
        Worker::$scheduler->del($fd,SchedulerInterface::TYPE_WRITE);
        unset($this->connections[intval($fd)]);
        unset($this->buffer[intval($fd)]);
        unset($this->handshake[intval($fd)]);
        set_error_handler(null);

        //onClose回调
        try {
            Event::dispatch("close",[$this,$fd]);
        } catch (\Throwable $throwable) {
            $this->close($fd,$throwable->getMessage());
            return;
        }
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