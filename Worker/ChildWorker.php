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

    public function __construct($socket)
    {
        $this->socket=$socket;
        $this->id=spl_object_hash($this);
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
    }

    public function accept($socket)
    {
        $con=socket_accept($socket);
        echo "connected:".$con;
    }

}