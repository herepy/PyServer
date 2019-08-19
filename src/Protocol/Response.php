<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/8/19
 * Time: 12:23
 */

namespace Pengyu\Server\Protocol;

use Pengyu\Server\Transport\TransportInterface;

class Response
{
    protected $fd;
    protected $connection;

    public function __construct($fd,TransportInterface $connection)
    {
        $this->fd=$fd;
        $this->connection=$connection;
    }

    public function end($content)
    {
        $this->connection->send($this->fd,$content);

        if (!isset($_SERVER["HTTP_CONNECTION"]) || $_SERVER["HTTP_CONNECTION"] != "keep-alive") {
            $this->connection->close($this->fd);
        }
    }

    public function status($status)
    {
        Http::setStatus($status);
    }

    public function header($key,$value)
    {
        Http::setHeader($key,$value);
    }

    public function cookie($key,$value,$expire="",$domain="",$path="",$httpOnly=false,$secure=false)
    {
        Http::setCookie($key,$value,$expire,$domain,$path,$httpOnly,$secure);
    }

}