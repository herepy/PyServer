<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/8/5
 * Time: 11:26
 */

namespace PyServer\Protocol;

class WebSocket implements ProtocolInterface
{
    /*       0                   1                   2                   3
      0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
     +-+-+-+-+-------+-+-------------+-------------------------------+
     |F|R|R|R| opcode|M| Payload len |    Extended payload length    |
     |I|S|S|S|  (4)  |A|     (7)     |             (16/64)           |
     |N|V|V|V|       |S|             |   (if payload len==126/127)   |
     | |1|2|3|       |K|             |                               |
     +-+-+-+-+-------+-+-------------+ - - - - - - - - - - - - - - - +
     |     Extended payload length continued, if payload len == 127  |
     + - - - - - - - - - - - - - - - +-------------------------------+
     |                               |Masking-key, if MASK set to 1  |
     +-------------------------------+-------------------------------+
     | Masking-key (continued)       |          Payload Data         |
     +-------------------------------- - - - - - - - - - - - - - - - +
     :                     Payload Data continued ...                :
     + - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - +
     |                     Payload Data continued ...                |
     +---------------------------------------------------------------+
     */

    public static function size($buffer)
    {
        //错误的frame帧数据或数据的一部分
        if (strlen($buffer) < 6) {
            return 0;
        }

        $mask=ord($buffer[1]) >> 7;
        //mask标志位是否置1(客户端发送的数据mask必须为1)
        if (!$mask) {
            return 0;
        }

        //frame帧中payload len的值(2byte中去掉mask部分)
        $payloadLen=ord($buffer[1]) & 127;
        //真实负载数据长度
        $dataLen=$payloadLen;
        //头部长度(非负载部分)
        $headLen=6;

        //真实长度存在第3-4byte中
        if ($payloadLen == 126) {
            //头部增加了第3-4byte
            $headLen+=2;

            $tmp=unpack("n/nlen",$buffer);
            $dataLen=$tmp["len"];
        } else if ($payloadLen == 127) {  //真实长度存在3-10byte中
            //头部增加了第3-10byte
            $headLen+=8;

            $tmp=unpack('n/N2c', $buffer);
            $dataLen=$tmp['c1']*4294967296 + $tmp['c2'];
        }

        return $headLen+$dataLen;
    }

    public static function decode($buffer)
    {
        // TODO: Implement decode() method.
    }

    public static function encode($content)
    {
        // TODO: Implement encode() method.
    }
}