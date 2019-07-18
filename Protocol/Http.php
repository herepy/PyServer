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
    }

    public static function decode($buffer, $size)
    {
        // TODO: Implement decode() method.
    }

    public static function encode($content)
    {
        // TODO: Implement encode() method.
    }

}