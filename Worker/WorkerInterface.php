<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:28
 */

namespace Worker;

interface WorkerInterface
{
    /**
     * 创建一个服务
     * WorkerInterface constructor.
     * @param null $address 服务监听地址 "http://0.0.0.0:80"
     */
    public function __construct($address=null);

    /**
     * 设置服务监听地址
     *
     * @param string $protocol 协议
     * @param string $address 地址
     * @param int $port 端口
     * @return mixed
     */
    public function setListen($protocol,$address,$port);

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