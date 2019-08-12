<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/24
 * Time: 21:34
 */

namespace Pengyu\Server\Scheduler;


class Select implements SchedulerInterface
{
    /**
     * @var array 所有可读写事件 [intval(fd) =>[type=>[callback=>xxx,arg=>xxx]]]
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
     * @var array 定期执行事件 [timerId => [timeout=>xx,timeStamp=>xxx,callback=>xxx,arg=>xxx,persist=>false|true]]
     */
    protected $timer=[];

    /**
     * @var array 信号 [signalA,signalB]
     */
    protected $signal=[];

    /**
     * @var int 全局定时器自增id（是新定时器的id，然后自增一）
     */
    public static $timerId;

    /**
     * @var array 手动调用事件 [message=>callbackA,start=>callbackB]
     */
    public static $dispatchEvent=[];


    public function init()
    {
        $this->clear();
    }

    public function add($fd, $type, $callback, $arg = [])
    {
        switch ($type) {
            case self::TYPE_ONCE_TIMER:
            case self::TYPE_TIMER:
                $this->timer[self::$timerId]=[
                    "timeout"   =>  intval($fd),
                    "timeStamp" =>  time()+intval($fd),
                    "callback"  =>  $callback,
                    "arg"       =>  $arg,
                    "persist"   =>  $type == self::TYPE_ONCE_TIMER ? false : true
                ];

                return self::$timerId++;

            case self::TYPE_READ:
            case self::TYPE_WRITE:
                $this->event[intval($fd)][$type]=[
                    "callback"  =>  $callback,
                    "arg"       =>  $arg
                ];

                if ($type == self::TYPE_READ) {
                    $this->readEvent[intval($fd)]=$fd;
                } else {
                    $this->writeEvent[intval($fd)]=$fd;
                }

                return true;

            case self::TYPE_SIGNAL:
                pcntl_signal($fd,function ()use($callback,$fd,$arg)
                {
                    call_user_func_array($callback,array_merge([$fd,$arg]));
                },false);

                $this->signal[]=$fd;
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
                if (!isset($this->event[intval($fd)][$type])) {
                    return false;
                }
                unset($this->event[intval($fd)][$type]);

                if (empty($this->event[intval($fd)])) {
                    unset($this->event[intval($fd)]);
                }

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
        foreach ($this->signal as $signal) {
            pcntl_signal($signal,SIG_IGN);
        }

        $this->event=[];
        $this->readEvent=[];
        $this->writeEvent=[];
        $this->timer=[];
        $this->signal=[];
        self::$timerId=1;
    }

    public function loop()
    {
        while (true) {
            if (!is_win()) {
                //触发信号处理
                pcntl_signal_dispatch();
            }

            $read=$this->readEvent;
            $write=$this->writeEvent;
            $except=[];
            //定时器事件执行
            $this->dealTimer();

            //没有可读写事件产生
            set_error_handler(function (){});
            if (socket_select($read,$write,$except,1) == 0) {
                continue;
            }
            set_error_handler(null);

            //有可读事件产生
            if ($read) {
                foreach ($read as $rfd) {
                    $info=$this->event[intval($rfd)][self::TYPE_READ];
                    call_user_func_array($info["callback"],array_merge([$rfd],$info["arg"]));
                }
            }

            //有可写事件产生
            if ($write) {
                foreach ($write as $wfd) {
                    $info=$this->event[intval($wfd)][self::TYPE_WRITE];
                    call_user_func_array($info["callback"],array_merge([$wfd],$info["arg"]));
                }
            }

        }

    }

    public function dealTimer()
    {
        //没有定时器
        if (empty($this->timer)) {
            return;
        }

        $now=time();
        foreach ($this->timer as $id => $item) {
            if ($item["timeStamp"] <= $now) {
                call_user_func_array($item["callback"],$item["arg"]);

                //非持续化定时器，执行后删除
                if ($item["persist"] === false) {
                    unset($this->timer[$id]);
                } else {
                    $this->timer[$id]=[
                        "timeout"   =>  $item["timeout"],
                        "timeStamp" =>  $now+$item["timeout"],
                        "callback"  =>  $item["callback"],
                        "arg"       =>  $item["arg"],
                        "persist"   =>  true
                    ];
                }
            }
        }
    }

}