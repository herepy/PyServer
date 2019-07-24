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
    /**
     * @var int 响应状态码
     */
    public static $status=200;

    /**
     * @var array 响应状态码对应状态信息
     */
    public static $codes = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    );

    /**
     * @var array 响应头
     */
    public static $header=[];

    /**
     * @var array 接受的请求方法
     */
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

    public static function decode($buffer)
    {
        //初始化全局变量
        self::$header=[];
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
        $body=$tmp[1];

        //请求首行
        $firstLine=explode(" ",$headerArr[0],3);
        $_SERVER['REQUEST_METHOD']=$firstLine[0];
        $_SERVER['REQUEST_URI']=$firstLine[1];
        $_SERVER['SERVER_PROTOCOL']=$firstLine[2];
        unset($headerArr[0]);

        //queryString
        $_SERVER["QUERY_STRING"]=strpos($firstLine[1],"?") === false?"":parse_url($firstLine[1],PHP_URL_QUERY);
        //$_GET
        if ($_SERVER["QUERY_STRING"]) {
            $getStr=trim($_SERVER["QUERY_STRING"],"?");
            parse_str($getStr,$_GET);
        }

        //解析请求头
        foreach ($headerArr as $line) {
            $info=explode(":",$line,2);
            //$_SERVER
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
            if ($info[0] == "Content-Length") {
                $_SERVER["CONTENT_LENGTH"]=$info[1];
            }
            if ($info[0] == "Content-Type") {
                $_SERVER["CONTENT_TYPE"]=$info[1];
            }

            //$_COOKIE
            if ($info[0] == "COOKIE") {
                $cookieStr=str_replace(";","&",$info[1]);
                parse_str($cookieStr,$_COOKIE);
            }

        }

        //$_POST
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_SERVER["CONTENT_TYPE"])) {
                if (strpos($_SERVER["CONTENT_TYPE"],"application/json") !== false) {
                    $_POST=json_decode($body,true);
                } else if (strpos($_SERVER["CONTENT_TYPE"],"application/x-www-form-urlencoded") !== false) {
                    parse_str($body,$_POST);
                } else if (strpos($_SERVER["CONTENT_TYPE"],"multipart/form-data") !== false) {
                    //todo 表单文件解析
                }
            }
        }

        //$_REQUEST
        $_REQUEST=array_merge($_GET,$_POST,$_COOKIE);


        return ["get"=>$_GET,"post"=>$_POST,"cookie"=>$_COOKIE,"server"=>$_SERVER];
    }

    public static function encode($content)
    {
        // TODO: Implement encode() method.
        // Default http-code.
        $header = "HTTP/1.1 ".self::$status." OK\r\n";
        $header .= "Content-Type: text/html;charset=utf-8\r\n";
        $header .= "Server: PyServer/1.0\r\n";
        $header .= "Content-Length: ".strlen($content)."\r\n";

        foreach (self::$header as $key => $value) {
            $header.=$key.": ".$value."\r\n";
        }
        // the whole http package
        return $header."\r\n".$content;
    }

    public static function setStatus($code)
    {
        if (!array_key_exists($code,self::$codes)) {
            return;
        }
        self::$status=$code;
    }

    /**
     * 设置响应头
     * @param mixed $key 键值或键值对数组
     * @param string $value 值
     * @return bool
     */
    public static function setHeader($key,$value="")
    {
        if (is_array($key)) {
            foreach ($key as $k => $val) {
                self::setHeader($k,$val);
            }
            return true;
        }

        $key=str_replace("_","-",$key);
        if (is_array($value)) {
            $value=implode(";",$value);
        }
        self::$header[$key]=$value;

        return true;
    }

}