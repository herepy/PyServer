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
     * @var array 一次性定时事件 [id => [timeStamp=>xxx,callback=>xxx]]
     */
    protected $onceTimer=[];

    /**
     * @var array 定期执行事件
     */
    protected $timer=[];

    /**
     * @var int 全局定时器自增id（是新定时器的id，然后自增一）
     */
    public static $timerId;

    /**
     * @var bool 是否已经触发loop方法
     */
    protected static $loop=false;


    public function init()
    {
        // TODO: Implement init() method.
    }

    public function add($fd, $type, $callback, $arg = [])
    {
        // TODO: Implement add() method.
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
        // TODO: Implement loop() method.
    }

}