<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:59
 */

namespace Pengyu\Server\Protocol;

use Pengyu\Server\Protocol\Http\Response;
use Pengyu\Server\Protocol\Http\Session;
use Pengyu\Server\Scheduler\Event;
use Pengyu\Server\Transport\TransportInterface;
use Pengyu\Server\Util\Log;

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
     * @var array 响应COOKIE
     */
    public static $cookie=[];

    /**
     * @var array 接受的请求方法
     */
    public static $allowMethods=["GET","POST","HEAD","DELETE","OPTIONS","PUT"];

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

        //验证格式
        $tmp=explode("\r\n\r\n",$buffer,2);
        $header=$tmp[0];
        $headerArr=explode("\r\n",$header);
        if (!count($headerArr)) {
            $connection->close($fd,"Bat http package");
            return false;
        }

        //解析请求首行
        $first=explode(" ",$headerArr[0]);
        if (count($first) != 3) {
            $connection->close($fd,"Bat http package");
            return false;
        }

        //验证请求方法
        if (!in_array($first[0],self::$allowMethods)) {
            $connection->close($fd,"http method is not allowd");
            return false;
        }

        //有负载,从Content-Length中获取负载大小
        if (preg_match("/Content-Length: ?(\d+)/",$header,$matches)) {
            $size=strlen($header)+strlen("\r\n\r\n")+$matches[1];
        } else { //没有负载
            $size=strlen($header)+strlen("\r\n\r\n");
        }

        //超过单个包最大限制
        if ($size > $connection->maxPackageSize) {
            $connection->close($fd,"Beyond the maximum package limit");
            return false;
        }

        return $size;
    }

    /**
     * 解码请求数据
     * @param string $buffer 客户端请求数据
     * @param resource $fd 客户端连接句柄
     * @param TransportInterface $connection 传输层实例
     * @return mixed 解码后的相关数据
     */
    public static function decode($buffer,$fd,TransportInterface $connection)
    {
        //初始化全局变量
        self::$status=200;
        self::$header=[];
        self::$cookie=[];
        $_GET=$_POST=$_SESSION=$_COOKIE=$_REQUEST=$_FILES=array();

        $_SERVER=[
            'SERVER_SOFTWARE'       =>  'PyServer/1.0',
            'SERVER_PROTOCOL'       =>  '',
            'REQUEST_METHOD'        =>  '',
            'REQUEST_URI'           =>  '',
            'REQUEST_TIME'          =>  time(),
            'REQUEST_FILE'          =>  '',
            'REMOTE_ADDR'           =>  '',
            'REMOTE_PORT'           =>  '',
            'QUERY_STRING'          =>  '',
            'HTTP_ACCEPT_CHARSET'   =>  '',
            'HTTP_ACCEPT_ENCODING'  =>  '',
            'HTTP_CONNECTION'       =>  '',
            'HTTP_HOST'             =>  '',
            'HTTP_REFERER'          =>  '',
            'HTTP_USER_AGENT'       =>  ''
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

        //PHP_SELF
        $_SERVER["REQUEST_FILE"]=str_replace("?".$_SERVER["QUERY_STRING"],"",$_SERVER['REQUEST_URI']);

        //$_GET
        if ($_SERVER["QUERY_STRING"]) {
            $getStr=trim($_SERVER["QUERY_STRING"],"?");
            parse_str($getStr,$_GET);
        }

        //解析请求头
        foreach ($headerArr as $line) {
            $info=explode(":",$line,2);
            $key=trim($info[0]);
            $value=trim($info[1]);

            //$_SERVER
            if ($key == "Host") {
                $_SERVER["HTTP_HOST"]=$value;
            }
            if ($key == "Connection") {
                $_SERVER["HTTP_CONNECTION"]=$value;
            }
            if ($key == "Referer") {
                $_SERVER["HTTP_REFERER"]=$value;
            }
            if ($key == "User-Agent") {
                $_SERVER["HTTP_USER_AGENT"]=$value;
            }
            if ($key == "Accept-Encoding") {
                $_SERVER["HTTP_ACCEPT_ENCODING"]=$value;
            }
            if ($key == "Accept-Charset") {
                $_SERVER["HTTP_ACCEPT_CHARSET"]=$value;
            }
            if ($key == "Content-Length") {
                $_SERVER["CONTENT_LENGTH"]=$value;
            }
            if ($key == "Content-Type") {
                $_SERVER["CONTENT_TYPE"]=$value;
            }

            //$_COOKIE
            if ($key == "Cookie") {
                $cookieStr=str_replace(";","&",$value);
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
                    //表单文件解析
                    if (preg_match("/boundary=(.+)$/",$_SERVER["CONTENT_TYPE"],$matches)) {
                        $boundary=$matches[1];
                        self::getFromData($body,$boundary);
                    }
                }
            }
        }

        //$_REQUEST
        $_REQUEST=array_merge($_GET,$_POST,$_COOKIE);

        //$_SESSION session默认有效期86400秒 业务中开启使用session： $session->start();
        $session=new Session("./runtime/session","PYSESSION");

        $data=["get"=>$_GET,"post"=>$_POST,"cookie"=>$_COOKIE,"file"=>$_FILES,
            "server"=>$_SERVER,"session"=>$_SESSION,"sessionHandler"=>$session
        ];

        //触发onRequest回调
        try {
            $response=new Response($fd,$connection);
            Event::dispatch("request",[$data,$response]);
        } catch (\Throwable $throwable) {
            $connection->close($fd,$throwable->getMessage());
            return "";
        }

        return $data;
    }

    /**
     * 编码发送的数据
     * @param string $content 数据内容
     * @param resource $fd 客户端连接句柄
     * @param TransportInterface $connection 传输层实例
     * @return string
     */
    public static function encode($content,$fd,TransportInterface $connection)
    {
        $header = "HTTP/1.1 ".self::$status." ".self::$codes[self::$status]."\r\n";
        $header .= "Server: PyServer/1.0\r\n";
        $header .= "Content-Length: ".strlen($content)."\r\n";

        if (!array_key_exists("Content-Type",self::$header)) {
            self::$header["Content-Type"]="text/html;charset=utf-8";
        }

        //常规header
        foreach (self::$header as $key => $value) {
            $header.=$key.": ".$value."\r\n";
        }

        //cookie
        foreach (self::$cookie as $key => $value) {
            $header.="Set-Cookie: ".$key."=".$value."\r\n";
        }

        //记录访问日志
        socket_getpeername($fd,$ip);
        $info=[
            "ip"        =>  $ip,
            "method"    =>  $_SERVER["REQUEST_METHOD"],
            "uri"       =>  $_SERVER["REQUEST_URI"],
            "protocol"  =>  $_SERVER["SERVER_PROTOCOL"],
            "code"      =>  self::$status,
            "size"      =>  strlen($content) ? strlen($content) : "-",
            "referfer"  =>  $_SERVER["HTTP_REFERER"] ? $_SERVER["HTTP_REFERER"] : "--",
            "client"    =>  $_SERVER["HTTP_USER_AGENT"],
        ];

        Log::access($info);

        return $header."\r\n".$content;
    }

    /**
     * 设置响应状态码
     * @param int $code
     */
    public static function setStatus($code)
    {
        if (!array_key_exists($code,self::$codes)) {
            return;
        }
        self::$status=$code;
    }

    /**
     * 设置响应头cookie
     * @param string $key 键
     * @param string $value 值
     * @param string $expire 有效期时长
     * @param string $domain 域名
     * @param string $path 路径
     * @param bool $httpOnly 是否仅传输
     * @param bool $secure 是否仅在ssl下使用
     */
    public static function setCookie($key,$value,$expire="",$domain="",$path="",$httpOnly=false,$secure=false)
    {
        $expire=$expire?$expire:ini_get('session.cookie_lifetime');
        $domain=$domain?$domain:ini_get('session.cookie_domain');
        $path=$path?$path:ini_get('session.cookie_path');

        $httpOnly=$httpOnly?";HttpOnly":"";
        $secure=$secure?";secure":"";

        $option=";Path=".$path.";Domain=".$domain.";Max-Age=".$expire.$httpOnly.$secure;

        self::$cookie[$key]=$value.$option;
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

        if (is_array($value)) {
            $value=implode(";",$value);
        }
        self::$header[$key]=$value;

        return true;
    }

    /**
     * 解析multipart/form-data数据到全局变量$_POST/$_FILES
     * @param string $body 请求正文
     * @param string $boundary 请求正文边界（分割符）
     */
    protected static function getFromData($body,$boundary)
    {
        //去掉请求正文中的结尾边界
        $body=str_replace("--".$boundary."--","",$body);
        //分割正文
        $formData=explode("--".$boundary."\r\n",$body);

        //去掉分割后可能有的第一个空白符
        if ($formData[0] === "") {
            unset($formData[0]);
        }

        //循环处理单个正文项
        foreach ($formData as $item) {
            //分割键值属性内容
            $itemInfo=explode("\r\n\r\n",$item,2);
            //去掉值的尾部换行符
            $itemValue=substr($itemInfo[1],0,-2);

            //匹配键属性，看是否时文件上传
            if (strpos($itemInfo[0],"filename") === false) {
                preg_match('/Content-Disposition: form-data; name="(.+)"/',$itemInfo[0],$matches);
            } else {
                preg_match_all('/Content-Disposition: form-data; name="(.+)"; filename="(.+)"\\r\\nContent-Type: (.+)/',$itemInfo[0],$matches);
            }

            if (count($matches) == 2) {
                $_POST[$matches[1][0]]=$itemValue;
            } else {
                //保存文件到运行时目录
                $tmpFile="./runtime/upload/".$matches[2][0];
                file_put_contents($tmpFile,$itemValue);

                //保存文件信息到全局变量
                $_FILES[$matches[1][0]]=[
                    "name"      =>  $matches[2][0],
                    "type"      =>  $matches[3][0],
                    "tmp_name"  =>  $tmpFile,
                    "error"     =>  0,
                    "size"      =>  strlen($itemValue)
                ];
            }
        }
    }

}