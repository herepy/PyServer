<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/17
 * Time: 21:08
 */

if (!function_exists("check_env")) {
    /**
     * 检查运行环境
     */
    function check_env()
    {
        if (php_sapi_name() !== "cli") {
            die("This server must run in cli mode");
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            die("This server can not run in WIN system");
        }

        if (!extension_loaded("pcntl")) {
            die("Pcntl extension is necessary");
        }

        if (!extension_loaded("posix")) {
            die("Posix extension is necessary");
        }
    }
}

if (!function_exists("get_protocol")) {
    /**
     * 获取应用层协议
     * @param string $transport 传输层名
     * @return null|string 应用层完整名
     */
    function get_protocol($transport)
    {
        $deafault=[
            "tcp"   =>  "http",
            "unix"  =>  "file",
            "udp"   =>  "file"
        ];

        if (isset($deafault[$transport])) {
            return null;
        }

        $protocol=$deafault[$transport];
        $protocol='\\PyServer\\Protocol\\'.ucfirst($protocol);

        if (class_exists($protocol)) {
            return null;
        }
        return $protocol;
    }
}
