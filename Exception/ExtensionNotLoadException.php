<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 16:46
 */

namespace Pengyu\Server\Exception;

use Throwable;

class ExtensionNotLoadException extends \Exception
{
    public function __construct($extension, $code = 0, Throwable $previous = null)
    {
        $message="$extension extension is not load,please install it.";
        parent::__construct($message, $code, $previous);
    }
}
