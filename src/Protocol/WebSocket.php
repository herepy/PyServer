<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/8/5
 * Time: 11:26
 */

namespace Pengyu\Server\Protocol;

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

    const CLOSE="ws00ws";
    const PING="ws11ws";
    const PONG="ws22ws";

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

    /**
     * 解析负载内容
     * @param string $buffer 客户端发送的数据
     * @return mixed 负载内容
     */
    public static function decode($buffer)
    {
        //frame帧中payload len的值(2byte中去掉mask部分)
        $payloadLen=ord($buffer[1]) & 127;

        //masking key 和 负载内容
        $maskingKey=$payloadData="";

        //opcode
        $opcode=ord($buffer[0]) & 127;
        //处理一些控制码
        if ($opcode == hexdec("%x8")) {
            return self::CLOSE;
        } else if ($opcode == hexdec("%x9")) {
            return self::PING;
        } else if ($opcode == hexdec("%xA")) {
            return self::PONG;
        }

        if ($payloadLen == 126) {
            $maskingKey=substr($buffer,4,4);
            $payloadData=substr($buffer,8);
        } else if ($payloadLen == 127) {
            $maskingKey=substr($buffer,10,4);
            $payloadData=substr($buffer,14);
        } else {
            $maskingKey=substr($buffer,2,4);
            $payloadData=substr($buffer,6);
        }

        $data="";
        //解码负载数据
        for ($i=0;$i<strlen($payloadData);$i++) {
            $data.=$payloadData[$i] ^ $maskingKey[($i%4)];
        }

        return $data;
    }

    /**
     * 生成frame帧数据
     * @param string $content 负载内容
     * @return string frame帧内容
     */
    public static function encode($content)
    {
        //第一个byte 10000001
        $firstByte=chr(129);

        //负载内容长度
        $len=strlen($content);

        if ($len <= 125) {
            //0xxxxxxx (xxxxxxx)是负载内容长度
            $middleByte=chr($len);
        } else if ($len <= 65535) {
            //01111110
            $secondByte=chr(126);
            $middleByte=$secondByte.pack("n",$len);
        } else {
            //01111111
            $secondByte=chr(127);
            $middleByte=$secondByte.pack("J",$len);
        }

        return $firstByte.$middleByte.$content;
    }

    /**
     * 生成握手信息
     * @param string $buffer 客户端握手数据
     * @return mixed 握手返回信息，如果不是http请求，返回false
     */
    public static function handshake($buffer)
    {
        //验证格式
        $tmp=explode("\r\n\r\n",$buffer,2);
        $header=$tmp[0];
        $headerArr=explode("\r\n",$header);
        if (!count($headerArr)) {
            return false;
        }

        //解析请求首行
        $first=explode(" ",$headerArr[0]);
        if (count($first) != 3) {
            return false;
        }

        //验证请求方法
        if ($first[0] != "GET") {
            return false;
        }

        $tmp=explode("\r\n\r\n",$buffer,2);
        $headerArr=explode("\r\n",$tmp[0]);

        //去掉请求首行
        unset($headerArr[0]);

        $secWebSocketKey="";
        $secWebSocketProtocol="";
        $secWebSocketVersion="13";

        $upgrade="";
        $connection="";

        //解析请求头
        foreach ($headerArr as $line) {
            $info=explode(":",$line,2);
            $key=trim($info[0]);
            $value=trim($info[1]);

            //获取Upgrade
            if ($key == "Upgrade") {
                $upgrade=$value;
            }

            //获取Connection
            if ($key == "Connection") {
                $connection=$value;
            }

            //获取秘钥key
            if ($key == "Sec-WebSocket-Key") {
                $secWebSocketKey=$value;
            }

            //获取子协议
            if ($key == "Sec-WebSocket-Protocol") {
                $secWebSocketProtocol=$value;
            }
        }

        if ($connection != "Upgrade" || $upgrade != "websocket") {
            return false;
        }

        //生成Sec-WebSocket-Accept
        $secWebSocketAccept=base64_encode(sha1($secWebSocketKey.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11',true));

        $data="HTTP/1.1 101 Switching Protocols\r\n";
        $data.="Upgrade: websocket\r\n";
        $data.="Connection: Upgrade\r\n";
        $data.="Sec-WebSocket-Accept: ".$secWebSocketAccept."\r\n";
        $data.="Sec-WebSocket-Version: ".$secWebSocketVersion."\r\n";
        if ($secWebSocketProtocol) {
            $data.="Sec-WebSocket-Protocol: ".$secWebSocketProtocol."\r\n\r\n";
        } else {
            $data.="\r\n";
        }

        return $data;
    }
}