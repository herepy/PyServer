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

        socket_bind($this->socket,"0.0.0.0",8080);
        socket_listen($this->socket);
        //todo 目前调度器都使用even
        Event::init();
        Event::add($this->socket,Event::TYPE_READ,[$this,"accept"]);
        Event::loop();
    }

    protected function accept($fd)
    {
        echo $fd."connected".PHP_EOL;
    }

}