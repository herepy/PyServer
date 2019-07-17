<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 16:02
 */

namespace Transport;

use Worker\WorkerInterface;

interface Transport
{
    /**
     * 创建一个传输层工具
     * Transport constructor.
     * @param WorkerInterface $worker 所属的worker
     * @param string $protocol 使用的协议
     */
    public function __construct(WorkerInterface $worker,$protocol);

    /**
     * 发送数据
     * @param resource $fd 接受者
     * @param string $content 发送内容
     * @return mixed
     */
    public function send($fd,$content);

    /**
     * 接受数据
     * @param resource $fd 数据来源
     * @param int $size 读取大小
     * @return mixed
     */
    public function read($fd,$size);

    /**
     * 关闭连接
     * @param resource $fd 要断开的客户源
     * @return mixed
     */
    public function close($fd);

}