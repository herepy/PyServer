<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/29
 * Time: 15:42
 */

namespace PyServer\Util;

class Log
{
    protected static $file="/var/log/PyServer.log";

    public static function write($msg,$level="info")
    {
        $content=date("Y/m/d H:i:s")."  [{$level}] ".$msg.PHP_EOL;
        file_put_contents(self::$file,$content,FILE_APPEND );
    }

}