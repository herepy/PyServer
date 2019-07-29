<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/24
 * Time: 21:19
 */

namespace PyServer\Util;

use PyServer\Scheduler\SchedulerInterface;
use PyServer\Worker\Worker;

class Timer
{
    protected $id;

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
        $this->persist=$persist;
        $type=$persist?SchedulerInterface::TYPE_TIMER:SchedulerInterface::TYPE_ONCE_TIMER;
        $timerId=Worker::$scheduler->add($seconds,$type,$callback);
        $this->id=$timerId;
    }

    /**
     * 取消定时器
     */
    public function cancel()
    {
        if (!$this->id) {
            return false;
        }
        $type=$this->persist?SchedulerInterface::TYPE_TIMER:SchedulerInterface::TYPE_ONCE_TIMER;
        return Worker::$scheduler->del($this->id,$type);
    }

}