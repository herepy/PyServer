<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/17
 * Time: 20:43
 */

namespace Pengyu\Server\Exception;

use Throwable;

class ClassNotFoundException extends \Exception
{
    public function __construct($class = "", $code = 0, Throwable $previous = null)
    {
        $message="Class not found:".$class;
        parent::__construct($message, $code, $previous);
    }
}