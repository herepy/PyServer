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
    public static $allowMethods=["GET","POST","HEAD","DELETE","OPTIONS","PUT"];

    public static function size($buffer)
    {
        //验证格式
        $tmp=explode("\r\n\r\n",$buffer,2);
        $header=$tmp[0];
        $headerArr=explode("\r\n",$header);
        if (!count($headerArr)) {
            return 0;
        }

        //解析请求首行
        $first=explode(" ",$headerArr[0]);
        if (count($first) != 3) {
            return 0;
        }

        //验证请求方法
        if (!in_array($first[0],self::$allowMethods)) {
            return 0;
        }

        //没有负载的请求
        if ($first[0] == "GET" || $first[0] == "HEAD" || $first[0] == "DELETE" || $first[0] == "OPTIONS") {
            return strlen($header)+strlen("\r\n\r\n");
        }

        //有负载的,从Content-Length中获取负载大小
        if (preg_match("/Content-Length: ?(\d+)/",$header,$matches)) {
            return strlen($header)+strlen("\r\n\r\n")+$matches[1];
        }
        return 0;
    }

    public static function decode($buffer,$size)
    {
        
    }

    public static function encode($content)
    {
        // TODO: Implement encode() method.
        // Default http-code.
        $header = "HTTP/1.1 200 OK\r\n";
        $header .= "Content-Type: text/html;charset=utf-8\r\n";

        // header
        $header .= "Server: PyServer/1.0\r\nContent-Length: " . strlen($content) . "\r\n\r\n";

        // the whole http package
        return $header . $content;
    }

}