<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 16:30
 */

namespace Pengyu\Server\Scheduler;

use Pengyu\Server\Util\Log;

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

    /**
     * @var array 信号事件
     */
    protected $signal=[];

    /**
     * @var array 手动调用事件
     */
    public static $dispatchEvent=[];

    /**
     * @var int 全局定时器自增id（是新定时器的id，然后自增一）
     */
    public static $timerId;

    public function init()
    {
        if (!extension_loaded("event") || !class_exists("\Event")) {
            Log::error("can not found extension event");
            exit(1);
        }

        if ($this->base) {
            return;
        }
        $this->base=new \EventBase();
        $this->clear();
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

                return true;

            case self::TYPE_TIMER:
            case self::TYPE_ONCE_TIMER:
                $flag=$type == self::TYPE_TIMER ? (\Event::TIMEOUT | \Event::PERSIST) : \Event::TIMEOUT;
                $event=new \Event($this->base,-1,$flag,$callback,$arg);
                $event->addTimer($fd);

                if ($type == self::TYPE_ONCE_TIMER) {
                    $this->onceTimer[self::$timerId]=$event;
                } else {
                    $this->timer[self::$timerId]=$event;
                }

                return self::$timerId++;

            case self::TYPE_SIGNAL:
                $event=\Event::signal($this->base,$fd,$callback,$arg);
                $event->add();
                $this->signal[$fd]=$event;

                return true;
        }
    }

    public function del($fd, $type)
    {
        switch ($type) {
            case self::TYPE_READ:
            case self::TYPE_WRITE:
                if (!isset($this->event[intval($fd)][$type])) {
                    return false;
                }

                $this->event[intval($fd)][$type]->del();
                unset($this->event[intval($fd)][$type]);

                if (empty($this->event[intval($fd)])) {
                    unset($this->event[intval($fd)]);
                }
                return true;

            case self::TYPE_TIMER:
                if (!isset($this->timer[$fd])) {
                    return false;
                }

                $this->timer[$fd]->del();
                unset($this->timer[$fd]);
                return true;

            case self::TYPE_ONCE_TIMER:
                if (!isset($this->onceTimer[$fd])) {
                    return false;
                }

                unset($this->onceTimer[$fd]);
                return true;
        }
    }

    public static function register($name, $callback)
    {
        if (!is_callable($callback)) {
            return;
        }
        self::$dispatchEvent[$name]=$callback;
    }

    public static function dispatch($name,$param=[])
    {
        $callback=isset(self::$dispatchEvent[$name])?self::$dispatchEvent[$name]:false;
        if (!$callback || !is_callable($callback)) {
            return;
        }

        call_user_func_array($callback,$param);
    }

    public function clear()
    {
        foreach ($this->event as $e) {
            if (isset($e[self::TYPE_READ])) {
                $e[self::TYPE_READ]->del();
            } else if (isset($e[self::TYPE_WRITE])) {
                $e[self::TYPE_WRITE]->del();
            }
        }

        foreach ($this->timer as $timer) {
            $timer->del();
        }

        foreach ($this->onceTimer as $t) {
            $t->del();
        }

        foreach ($this->signal as $event) {
            $event->del();
        }

        $this->event=[];
        $this->timer=[];
        $this->onceTimer=[];
        $this->signal=[];
        self::$timerId=1;
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