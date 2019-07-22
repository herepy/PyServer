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
    const TYPE_SIGNAL=2;
    const TYPE_READ=3;
    const TYPE_WRITE=4;

    /**
     * 初始化
     * @return mixed
     */
    public function init();

    /**
     * 添加调度事件
     * @param mixed $fd 标识
     * @param int $type 类型
     * @param mixed $callback 回调函数
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
     * 手动触发事件
     * @param mixed $fd 标识
     * @param int $type 类型
     * @return mixed
     */
    public function dispatch($fd,$type);

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