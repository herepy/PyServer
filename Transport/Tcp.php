<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 16:15
 */

namespace Transport;

use Worker\WorkerInterface;

class Tcp implements Transport
{

    public function __construct(WorkerInterface $worker, $protocol)
    {

    }

    public function send($fd, $content)
    {
        // TODO: Implement send() method.
    }

    public function read($fd, $size)
    {
        // TODO: Implement read() method.
    }

    public function close($fd)
    {
        // TODO: Implement close() method.
    }

}