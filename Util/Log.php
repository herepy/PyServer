<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/29
 * Time: 15:42
 */

namespace PyServer\Util;

class Log
{
    protected static $logFile;
    protected static $accessFile;

    protected static $logFileHandle;
    protected static $accessFileHandle;

    protected static $level=["debug","info","notice","warning","error","critical","alert","emergency"];

    protected static function write($msg,$level="info")
    {
        if (!in_array($level,self::$level)) {
            self::write("log level {$level} not allowed");
            $level="info";
        }

        if (!is_resource(self::$logFileHandle)) {
            self::$logFileHandle=fopen(self::$logFile,"a");
        }

        $content=date("Y/m/d H:i:s")."  [{$level}] ".$msg.PHP_EOL;
        fwrite(self::$logFileHandle,$content);
    }

    public static function setFile($logFile,$accessFile)
    {
        self::$logFile=$logFile;
        self::$accessFile=$accessFile;
    }

    public static function access($info)
    {
        if (!is_resource(self::$accessFileHandle)) {
            self::$accessFileHandle=fopen(self::$accessFile,"a");
        }

        //clientIp - - [date] "method uri HTTP/1.1" httpCode httpSize "referer" "clientAgent"
        $content=$info["ip"].' - - ['.date("Y/m/d H:i:s").'] "'.$info["method"].' '.$info["uri"].' '.$info["protocol"].'" '.
            $info["code"].' '.$info["size"].' "'.$info["referfer"].'" "'.$info["client"].'"'.PHP_EOL;

        fwrite(self::$accessFileHandle,$content);
    }

    public static function debug($msg)
    {
        self::write($msg,"debug");
    }

    public static function info($msg)
    {
        self::write($msg);
    }

    public static function notice($msg)
    {
        self::write($msg,"notice");
    }

    public static function warning($msg)
    {
        self::write($msg,"warning");
    }

    public static function error($msg)
    {
        self::write($msg,"error");
    }

    public static function critical($msg)
    {
        self::write($msg,"critical");
    }

    public static function alert($msg)
    {
        self::write($msg,"alert");
    }

    public static function emergency($msg)
    {
        self::write($msg,"emergency");
    }

    public static function close()
    {
        if (is_resource(self::$logFileHandle)) {
            fclose(self::$logFileHandle);
        }

        if (is_resource(self::$accessFileHandle)) {
            fclose(self::$accessFileHandle);
        }
    }



}