<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/24
 * Time: 21:34
 */

namespace PyServer\Scheduler;


class Signal implements SchedulerInterface
{

    /**
     * @var array 定期执行事件 [id => [timeStamp=>xxx,callback=>xxx,arg=>xxx,persist=>false|true]]
     */
    protected $timer=[];

    /**
     * @var int 全局定时器自增id（是新定时器的id，然后自增一）
     */
    public static $timerId=0;

    /**
     * @var bool 是否已经触发loop方法
     */
    protected static $loop=false;


    public function init()
    {
        pcntl_alarm(0);
        pcntl_signal(SIGALRM,[$this,"deal"]);
    }

    public function add($fd, $type, $callback, $arg = [])
    {
        $this->timer[self::$timerId]=[
            "timeStamp" =>  time()+$fd,
            "callback"  =>  $callback,
            "arg"       =>  $arg,
            "persist"   =>  $type == SchedulerInterface::TYPE_ONCE_TIMER ? false : true
        ];

        if (self::$loop === false) {
            self::$loop=true;
            pcntl_alarm(1);
            $this->loop();
        }
        return self::$timerId++;
    }

    public function del($fd, $type)
    {
        // TODO: Implement del() method.
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