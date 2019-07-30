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
                $this->event[intval($fd)][$type]=[
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
        // TODO: Implement clear() method.
    }

    public function loop()
    {
        if (self::$loop) {
            return;
        }

        self::$loop=true;
        while (true) {
            $read=$this->readEvent;
            $write=$this->writeEvent;
            $except=[];

            //定时器事件执行
            $this->dealTimer();

            //没有可读写事件产生
            if (socket_select($read,$write,$except,1) == 0) {
                continue;
            }

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
                }
            }
        }
    }

}