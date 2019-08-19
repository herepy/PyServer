<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:49
 */

namespace Pengyu\Server\Protocol;

use Pengyu\Server\Transport\TransportInterface;

interface ProtocolInterface
{
    /**
     * 获取内容长度大小
     * @param string $buffer 内容
     * @param TransportInterface $connection 内容
     * @return int 大小
     */
    public static function size($buffer,TransportInterface $connection);

    /**
     * 解析内容数据
     * @param string $buffer 要解析的数据
     * @param TransportInterface $connection 内容
     * @return mixed 解析后的数据
     */
    public static function decode($buffer,TransportInterface $connection);

    /**
     * 编码内容
     * @param string $content 要编码的内容
     * @param TransportInterface $connection 内容
     * @return string 编码后的内容
     */
    public static function encode($content,TransportInterface $connection);

}