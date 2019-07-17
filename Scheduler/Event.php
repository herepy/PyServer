<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 16:30
 */

namespace PyServer\Scheduler;

use PyServer\Exception\ExtensionException;

class Event implements SchedulerInterface
{
    /**
     * @var object EventBase实例
     */
    protected static $base;

    /**
     * @var array 一次性定时事件
     */
    protected static $onceTimer=[];

    /**
     * @var array 定期执行事件
     */
    protected static $timer=[];

    /**
     * @var array 自定义事件，需手动dispatch调用
     */
    protected static $event=[];

    /**
     * const 事件类型
     */
    const TYPE_TIMER=0;
    const TYPE_SIGNAL=0;
    const TYPE_READ=0;
    const TYPE_WRITE=0;

    public static function init()
    {
        if (!extension_loaded("event") || class_exists("Event")) {
            throw new ExtensionException("Event");
        }

        self::$base=new \EventBase();
    }

    public static function add($fd, $type, $callback, $arg)
    {
        // TODO: Implement add() method.
    }

    public static function del($fd, $type)
    {
        // TODO: Implement del() method.
    }

    public static function dispatch($fd, $type)
    {
        // TODO: Implement dispatch() method.
    }

    public static function clear()
    {
        // TODO: Implement clear() method.
    }

}