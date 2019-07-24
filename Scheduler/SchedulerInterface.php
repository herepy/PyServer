<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 16:18
 */

namespace PyServer\Scheduler;

interface SchedulerInterface
{
    /**
     * const 事件类型
     */
    const TYPE_TIMER=1;
    const TYPE_ONCE_TIMER=2;
    const TYPE_SIGNAL=3;
    const TYPE_READ=4;
    const TYPE_WRITE=5;

    /**
     * 初始化
     * @return mixed
     */
    public function init();

    /**
     * 添加调度事件
     * @param mixed $fd 标识
     * @param int $type 类型
     * @param callable $callback 回调函数
     * @param array $arg 自定义参数
     * @return mixed
     */
    public function add($fd,$type,$callback,$arg=[]);

    /**
     * 删除调度事件
     * @param mixed $fd
     * @param int $type
     * @return mixed
     */
    public function del($fd,$type);

    /**
     * 注册手动调用事件
     * @param string $name 事件名
     * @param callable $callback 回调函数
     * @return mixed
     */
    public static function register($name,$callback);

    /**
     * 手动触发事件
     * @param string $name 事件名
     * @return mixed
     */
    public static function dispatch($name);

    /**
     * 清空调度器事件
     * @return mixed
     */
    public function clear();

    /**
     * 开始调度
     * @return mixed
     */
    public function loop();

}