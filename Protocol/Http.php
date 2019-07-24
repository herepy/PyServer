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
        $_GET=$_POST=$_SESSION=$_COOKIE=$_REQUEST=array();
        $_SERVER=[
            'SERVER_ADDR'           =>  '',
            'SERVER_SOFTWARE'       =>  'PyServer/1.0',
            'SERVER_PROTOCOL'       =>  '',
            'REQUEST_METHOD'        =>  '',
            'REQUEST_URI'           =>  '',
            'REQUEST_TIME'          =>  time(),
            'REMOTE_ADDR'           =>  '',
            'REMOTE_PORT'           =>  '',
            'QUERY_STRING'          =>  '',
            'HTTP_ACCEPT_CHARSET'   =>  '',
            'HTTP_ACCEPT_ENCODING'  =>  '',
            'HTTP_CONNECTION'       =>  '',
            'HTTP_HOST'             =>  '',
            'HTTP_REFERER'          =>  '',
            'HTTP_USER_AGENT'       =>  '',
        ];

        $tmp=explode("\r\n\r\n",$buffer,2);
        $headerArr=explode("\r\n",$tmp[0]);

        //请求首行
        $firstLine=explode(" ",$headerArr[0],3);
        $_SERVER['REQUEST_METHOD']=$firstLine[0];
        $_SERVER['REQUEST_URI']=$firstLine[1];
        $_SERVER['SERVER_PROTOCOL']=$firstLine[2];
        //queryString
        $_SERVER["QUERY_STRING"]=strpos($firstLine[1],"?") === false?"":parse_url($firstLine[1],PHP_URL_QUERY);
        unset($headerArr[0]);

        foreach ($headerArr as $line) {
            $info=explode(":",$line,2);
            if ($info[0] == "Host") {
                $_SERVER["HTTP_HOST"]=trim($info[1]);
            }
            if ($info[0] == "Connection") {
                $_SERVER["HTTP_CONNECTION"]=$info[1];
            }
            if ($info[0] == "Referer") {
                $_SERVER["HTTP_REFERER"]=$info[1];
            }
            if ($info[0] == "User-Agent") {
                $_SERVER["HTTP_USER_AGENT"]=$info[1];
            }
            if ($info[0] == "Accept-Encoding") {
                $_SERVER["HTTP_ACCEPT_ENCODING"]=$info[1];
            }
            if ($info[0] == "Accept-Charset") {
                $_SERVER["HTTP_ACCEPT_CHARSET"]=$info[1];
            }
        }

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