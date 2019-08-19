<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/8/19
 * Time: 12:23
 */

namespace Pengyu\Server\Protocol\Http;

use Pengyu\Server\Protocol\Http;
use Pengyu\Server\Transport\TransportInterface;

class Response
{
    /**
     * @var resource 连接句柄
     */
    protected $fd;

    /**
     * @var TransportInterface 传输层实例
     */
    protected $connection;

    public function __construct($fd,TransportInterface $connection)
    {
        $this->fd=$fd;
        $this->connection=$connection;
    }

    /**
     * 发送响应
     * @param string $content 内容
     */
    public function end($content)
    {
        $this->connection->send($this->fd,$content);

        if (!isset($_SERVER["HTTP_CONNECTION"]) || $_SERVER["HTTP_CONNECTION"] != "keep-alive") {
            $this->connection->close($this->fd);
        }
    }

    /**
     * 设置http响应状态码
     * @param int $status
     */
    public function status($status)
    {
        Http::setStatus($status);
    }

    /**
     * 设置响应头
     * @param string $key 键
     * @param string $value 值
     */
    public function header($key,$value)
    {
        Http::setHeader($key,$value);
    }

    /**
     * 设置cookie
     * @param string $key 键
     * @param string $value 值
     * @param int $expire 有效期，秒
     * @param string $domain 所属域名
     * @param string $path 路径
     * @param bool $httpOnly 是否仅传输
     * @param bool $secure 是否仅https下传输
     */
    public function cookie($key,$value,$expire=0,$domain="",$path="",$httpOnly=false,$secure=false)
    {
        Http::setCookie($key,$value,$expire,$domain,$path,$httpOnly,$secure);
    }

}