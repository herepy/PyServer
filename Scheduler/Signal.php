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
     * @var array 一次性定时事件
     */
    protected $onceTimer=[];

    /**
     * @var array 定期执行事件
     */
    protected $timer=[];

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