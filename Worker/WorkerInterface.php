<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:28
 */

namespace PyServer\Worker;

interface WorkerInterface
{

    /**
     * 设置服务触发事件的回调函数
     * @param string $event 事件
     * @param mixed $callback 回调函数
     * @return mixed
     */
    public function on($event,$callback);

    /**
     * 配置服务参数
     * @param array $config 配置参数
     * @return mixed
     */
    public function config($config);

    /**
     * 启动服务
     * @return mixed
     */
    public function run();

}