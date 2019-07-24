<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/24
 * Time: 21:19
 */

namespace PyServer\Scheduler;

class Timer
{
    /**
     * @var SchedulerInterface 调度器实例
     */
    protected static $scheduler;

    /**
     * @var float 定时器秒数
     */
    protected $seconds;

    /**
     * @var bool 是否持续化
     */
    protected $persist;

    /**
     * @var callable 回调函数
     */
    protected $callback;

    /**
     * 创建定时器
     * Timer constructor.
     * @param float $seconds 秒数
     * @param callable $callback 回调函数
     * @param bool $persist 是否持续化
     */
    public function __construct($seconds,$callback,$persist=false)
    {
        if (!self::$scheduler) {
            $this->init();
        }

        $this->secondes=$seconds;
        $this->callback=$callback;
        $this->persist=$persist;

        return (self::$scheduler)::timerId;
    }

    /**
     * 初始化调度器
     */
    protected function init()
    {
        if (extension_loaded("\Event") && class_exists("\PyServer\Scheduler\Event")) {
            self::$scheduler=new Event();
        } else {
            self::$scheduler=new Signal();
        }

        self::$scheduler->init();
    }

    /**
     * 设置回调函数
     * @param callable $callback
     */
    public function setCallBack(callable $callback)
    {
        $this->callback=$callback;
    }

    /**
     * 开始执行定时器
     */
    public function start()
    {
        //todo
    }

}