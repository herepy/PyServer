<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 16:30
 */

namespace PyServer\Scheduler;

use PyServer\Exception\ExtensionNotLoadException;

class Event implements SchedulerInterface
{
    /**
     * @var object EventBase实例
     */
    protected $base;

    /**
     * @var array 一次性定时事件
     */
    protected $onceTimer=[];

    /**
     * @var array 定期执行事件
     */
    protected $timer=[];

    /**
     * @var array 自定义事件，需手动dispatch调用
     */
    protected $event=[];

    /**
     * const 事件类型
     */
    const TYPE_TIMER=\Event::TIMEOUT;
    const TYPE_SIGNAL=\Event::SIGNAL;
    const TYPE_READ=\Event::READ;
    const TYPE_WRITE=\Event::WRITE;

    public function init()
    {
        if (!extension_loaded("event") || !class_exists("\Event")) {
            throw new ExtensionNotLoadException("Event");
        }

        if ($this->base) {
            return;
        }

        $this->base=new \EventBase();
    }

    public function add($fd, $type, $callback, $arg=[])
    {

        switch ($type) {
            case self::TYPE_READ:
            case self::TYPE_WRITE:
                $event=new \Event($this->base,$fd,\Event::READ,$callback,$fd);
                $event->add();
                break;
        }
    }

    public function del($fd, $type)
    {
        // TODO: Implement del() method.
    }

    public function dispatch($fd, $type)
    {
        // TODO: Implement dispatch() method.
    }

    public function clear()
    {
        // TODO: Implement clear() method.
    }

    public function loop()
    {
        $this->base->loop();
    }

}