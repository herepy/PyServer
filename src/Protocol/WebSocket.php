<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/8/5
 * Time: 11:26
 */

namespace Pengyu\Server\Protocol;

use Pengyu\Server\Scheduler\Event;
use Pengyu\Server\Transport\TransportInterface;

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

    /**
     * 请求数据的大小
     * @param string $buffer 客户端请求数据
     * @param resource $fd 客户端连接句柄
     * @param TransportInterface $connection 传输层实例
     * @return int|bool 从数据包中读取完整数据包大小，如果数据包有误返回false,如果时数据包的一部分，返回0
     */
    public static function size($buffer,$fd,TransportInterface $connection)
    {
        //已经获取到过该包头，本次只是包部分数据
        if (isset($connection->buffer[intval($fd)])) {
            return 0;
        }

        //是否已经握手
        if (!isset($connection->handshake[intval($fd)])) {
            self::handshake($buffer,$fd,$connection);
            return false;
        }

        //错误的frame帧数据或数据的一部分
        if (strlen($buffer) < 6) {
            $connection->close($fd,"Bad websocket frame");
            return false;
        }

        $mask=ord($buffer[1]) >> 7;
        //mask标志位是否置1(客户端发送的数据mask必须为1)
        if (!$mask) {
            $connection->close($fd,"client must set mask flag to 1");
            return false;
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
     * @param resource $fd 客户端连接句柄
     * @param TransportInterface $connection 传输层实例
     * @return string 负载内容
     */
    public static function decode($buffer,$fd,TransportInterface $connection)
    {
        //frame帧中payload len的值(2byte中去掉mask部分)
        $payloadLen=ord($buffer[1]) & 127;

        //masking key 和 负载内容
        $maskingKey=$payloadData="";

        //opcode
        $opcode=ord($buffer[0]) & 127;
        //处理一些控制码
        if ($opcode == hexdec("%x8")) {   //close
            $connection->close($fd);
            return "";
        } else if ($opcode == hexdec("%x9")) {  //ping
            //生成pong返回给客户端
            $pong=self::pong();
            $connection->send($fd,$pong,true);

            //触发onPing回调
            try {
                Event::dispatch("ping",[$connection,$fd]);
            } catch (\Throwable $throwable) {
                $connection->close($fd,$throwable->getMessage());
            }
            return "";
        } else if ($opcode == hexdec("%xA")) {  //pong
            //触发onPing回调
            try {
                Event::dispatch("pong",[$connection,$fd]);
            } catch (\Throwable $throwable) {
                $connection->close($fd,$throwable->getMessage());
            }
            return "";
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

        //触发onMessage回调
        try {
            Event::dispatch("message",[$connection,$fd,$data]);
        } catch (\Throwable $throwable) {
            $connection->close($fd,$throwable->getMessage());
        }

        return $data;
    }

    /**
     * 生成frame帧数据
     * @param string $content 负载内容
     * @param resource $fd 客户端连接句柄
     * @param TransportInterface $connection 传输层实例
     * @return string frame帧内容
     */
    public static function encode($content,$fd,TransportInterface $connection)
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
     * 握手
     * @param string $buffer 客户端握手数据
     * @param resource $fd 客户端连接句柄
     * @param TransportInterface $connection 传输层实例
     * @return bool
     */
    public static function handshake($buffer,$fd,TransportInterface $connection)
    {
        //验证格式
        $tmp=explode("\r\n\r\n",$buffer,2);
        $header=$tmp[0];
        $headerArr=explode("\r\n",$header);
        if (!count($headerArr)) {
            $connection->close($fd,"Bad handshake http package");
            return false;
        }

        //解析请求首行
        $first=explode(" ",$headerArr[0]);
        if (count($first) != 3) {
            $connection->close($fd,"Bad handshake http package");
            return false;
        }

        //验证请求方法
        if ($first[0] != "GET") {
            $connection->close($fd,"HandShake http method must be GET");
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
        $connectionValue="";

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
                $connectionValue=$value;
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

        if ($connectionValue != "Upgrade" || $upgrade != "websocket") {
            $connection->close($fd,"Upgrade error");
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

        //发送握手信息
        $connection->send($fd,$data,true);
        $connection->handshake[intval($fd)]=1;

        return true;
    }

    /**
     * 生成pong响应
     * @return string
     */
    public static function pong()
    {
        //第一个byte 10001010
        $firstByte=chr(138);

        //负载长度
        $len=0;

        return $firstByte.chr($len);
    }
}