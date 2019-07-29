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

if (!function_exists("get_scheduler")) {

    function get_scheduler($name=null)
    {
        if (!$name) {
            if (extension_loaded("event") && class_exists("\PyServer\Scheduler\Event")) {
                $name="Event";
            } else {
                $name="Signal";
            }
        }

        $class='\PyServer\Scheduler\\'.$name;
        if (!class_exists($class)) {
            die("scheduler not found:".$class);
        }
        return $class;
    }
}