<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:59
 */

namespace PyServer\Protocol;

class Http implements ProtocolInterface
{

    protected $transport;

    public static function size($buffer)
    {
        // TODO: Implement size() method.
        return strlen(substr($buffer,5));
    }

    public static function decode($buffer,$size)
    {
        // TODO: Implement decode() method.
        $content=substr($buffer,5);

        if (strlen($content) != $size) {
            return false;
        }
        return $content;
    }

    public static function encode($content)
    {
        // TODO: Implement encode() method.
        return "http:".$content;
    }

}