<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 16:18
 */

namespace Scheduler;

interface SchedulerInterface
{
    /**
     * 初始化
     * @return mixed
     */
    public static function init();

    /**
     * 添加调度事件
     * @param mixed $fd 标识
     * @param int $type 类型
     * @param mixed $callback 回调函数
     * @param array $arg 自定义参数
     * @return mixed
     */
    public static function add($fd,$type,$callback,$arg);

    /**
     * 删除调度事件
     * @param mixed $fd
     * @param int $type
     * @return mixed
     */
    public static function del($fd,$type);

    /**
     * 触发事件
     * @param mixed $fd 标识
     * @param int $type 类型
     * @return mixed
     */
    public static function dispatch($fd,$type);

    /**
     * 清空事件
     * @return mixed
     */
    public static function clear();

}