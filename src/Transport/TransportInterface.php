<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 16:02
 */

namespace Pengyu\Server\Transport;

use Pengyu\Server\Worker\WorkerInterface;

interface TransportInterface
{
    /**
     * 创建一个传输层工具
     * Transport constructor.
     * @param WorkerInterface $worker 所属的worker
     * @param string $protocol 使用的协议
     */
    public function __construct(WorkerInterface $worker,$protocol=null);

    /**
     * 接受客户端连接
     * @param int $fd 客户端
     * @return mixed
     */
    public function accept($fd);

    /**
     * 发送数据
     * @param resource $fd 接受者
     * @param string $content 发送内容
     * @param bool $raw 是否发送原始内容
     * @return mixed
     */
    public function send($fd,$content,$raw=false);

    /**
     * 接受数据
     * @param resource $fd 数据来源
     * @return mixed
     */
    public function read($fd);

    /**
     * 关闭连接
     * @param resource $fd 要断开的客户源
     * @param string $content 发送的内容
     * @return mixed
     */
    public function close($fd,$content=null);

    /**
     * 停止接受新的连接，处理关闭现有连接
     * @return mixed
     */
    public function stop();

}