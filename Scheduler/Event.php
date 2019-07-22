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
    public $base;

    /**
     * @var array 一次性定时事件
     */
    protected $onceTimer=[];

    /**
     * @var array 定期执行事件
     */
    protected $timer=[];

    /**
     * @var array read\write事件
     */
    protected $event=[];

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
                $flag=$type == self::TYPE_READ ? (\Event::READ | \Event::PERSIST) : (\Event::WRITE | \Event::PERSIST);
                $event=new \Event($this->base,$fd,$flag,$callback,$arg);
                $event->add();
                $this->event[intval($fd)][$type]=$event;
                break;
        }
    }

    public function del($fd, $type)
    {
        switch ($type) {
            case self::TYPE_READ:
            case self::TYPE_WRITE:
                if (!isset($this->event[intval($fd)][$type])) {
                    return;
                }

                $this->event[intval($fd)][$type]->del();
                unset($this->event[intval($fd)][$type]);

                if (empty($this->event[intval($fd)])) {
                    unset($this->event[intval($fd)]);
                }
                break;
        }
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

    public function getEvent($fd,$type)
    {
        return $this->event[intval($fd)][$type];
    }

}