<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/24
 * Time: 21:34
 */

namespace PyServer\Scheduler;


class Select implements SchedulerInterface
{
    /**
     * @var array 所有可读写事件 [intval(fd) =>[callback=>xxx,arg=>xxx]]
     */
    protected $event=[];

    /**
     * @var array 可读事件 [fd1=>fd1,fd2=>fd2..]
     */
    protected $readEvent=[];

    /**
     * @var array 可写事件
     */
    protected $writeEvent=[];

    /**
     * @var array 定期执行事件 [timerId => [timeStamp=>xxx,callback=>xxx,arg=>xxx,persist=>false|true]]
     */
    protected $timer=[];

    /**
     * @var int 全局定时器自增id（是新定时器的id，然后自增一）
     */
    public static $timerId=0;

    /**
     * @var array 手动调用事件
     */
    public static $dispatchEvent=[];

    /**
     * @var bool 是否已经触发loop方法
     */
    protected static $loop=false;


    public function init()
    {
        $this->event=[];
        $this->readEvent=[];
        $this->writeEvent=[];
        $this->timer=[];
        self::$timerId=0;
//        self::$dispatchEvent=[];
    }

    public function add($fd, $type, $callback, $arg = [])
    {
        switch ($type) {
            case self::TYPE_ONCE_TIMER:
            case self::TYPE_TIMER:
                $this->timer[self::$timerId]=[
                    "timeStamp" =>  time()+intval($fd),
                    "callback"  =>  $callback,
                    "arg"       =>  $arg,
                    "persist"   =>  $type == self::TYPE_ONCE_TIMER ? false : true
                ];
                return self::$timerId++;
            case self::TYPE_READ:
            case self::TYPE_WRITE:
                $this->event[intval($fd)]=[
                    "callback"  =>  $callback,
                    "arg"       =>  $arg
                ];
                if ($type == self::TYPE_READ) {
                    $this->readEvent[]=$fd;
                } else {
                    $this->writeEvent[]=$fd;
                }
                return true;
            case self::TYPE_SIGNAL:
                pcntl_signal($fd,function ()use($callback,$fd,$arg){
                    call_user_func_array($callback,array_merge([$fd,$arg]));
                },false);
                return true;
        }

    }

    public function del($fd, $type)
    {
        switch ($type) {
            case self::TYPE_SIGNAL:
                pcntl_signal($fd,SIG_IGN);
                return true;
            case self::TYPE_READ:
            case self::TYPE_WRITE:
                if (!isset($this->event[intval($fd)])) {
                    return false;
                }
                unset($this->event[intval($fd)]);
                if ($type == self::TYPE_READ) {
                    unset($this->readEvent[intval($fd)]);
                } else {
                    unset($this->writeEvent[intval($fd)]);
                }
                return true;
            case self::TYPE_ONCE_TIMER:
            case self::TYPE_TIMER:
                if (!isset($this->timer)) {
                    return false;
                }
                unset($this->timer[intval($fd)]);
                return true;
        }
    }

    public static function register($name, $callback)
    {
        // TODO: Implement register() method.
    }

    public static function dispatch($name)
    {
        // TODO: Implement dispatch() method.
    }

    public function clear()
    {
        // TODO: Implement clear() method.
    }

    public function loop()
    {
        if (self::$loop) {
            return;
        }

        while (true) {
            pcntl_signal_dispatch();
            //没有定时器，停止
            if (empty($this->timer)) {
                pcntl_alarm(0);
                self::$loop=false;
                return;
            }
            sleep(20);
        }

    }

    public function deal()
    {
        //没有定时器，停止
        if (empty($this->timer)) {
            pcntl_alarm(0);
            return;
        }

        $now=time();
        foreach ($this->timer as $id => $item) {
            if ($item["timeStamp"] <= $now) {
                call_user_func_array($item["callback"],$item["arg"]);
                //非持续化定时器，执行后删除
                if ($item["persist"] === false) {
                    unset($this->timer[$id]);
                }
            }
        }

        pcntl_alarm(1);
    }

}