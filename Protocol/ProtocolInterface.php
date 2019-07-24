<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:49
 */

namespace PyServer\Protocol;

interface ProtocolInterface
{
    /**
     * 获取内容长度大小
     * @param string $buffer 内容
     * @return int 大小
     */
    public static function size($buffer);

    /**
     * 解析内容数据
     * @param string $buffer 要解析的数据
     * @return mixed 解析后的数据
     */
    public static function decode($buffer);

    /**
     * 编码内容
     * @param string $content 要编码的内容
     * @return string 编码后的内容
     */
    public static function encode($content);

}