<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 16:15
 */

namespace PyServer\Transport;

use PyServer\Scheduler\SchedulerInterface;
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

    public function __construct(WorkerInterface $worker, $protocol)
    {
        $this->worker=$worker;
        $this->protocol=$protocol;
    }

    public function send($fd, $content)
    {
        // TODO: Implement send() method.
    }

    public function read($fd)
    {

        $content=socket_read($fd,$this->maxSize);
        if (!$content) {  //客户端断开时，content是空
            $this->close($fd);
            return;
        }
        echo "client ".intval($fd)." say:".$content."\n";
    }

    public function close($fd)
    {
        // TODO: Implement close() method.
        echo "client:".intval($fd)." disconnect\n";
        $this->worker->scheduler->del($fd,SchedulerInterface::TYPE_READ);
        $this->worker->scheduler->del($fd,SchedulerInterface::TYPE_WRITE);
        unset($this->worker->connections[$fd]);
//        socket_close($fd);
    }

}