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
            die("This server must run in cli mode".PHP_EOL);
        }

        if (is_win()) {
            return;
        }

        if (!extension_loaded("pcntl")) {
            die("Pcntl extension is necessary".PHP_EOL);
        }

        if (!extension_loaded("posix")) {
            die("Posix extension is necessary".PHP_EOL);
        }
    }
}

if (!function_exists("get_scheduler")) {

    /**
     * 获取系统可用调度器
     * @param string $name 手动指定调度器
     * @return PyServer\Scheduler\SchedulerInterface 调度器实例
     */
    function get_scheduler($name=null)
    {
        if (!$name) {
            //优先使用Event调度器
            if (extension_loaded("event") && class_exists("\PyServer\Scheduler\Event")) {
                $name="Event";
            } else {
                $name="Select";
            }
        }

        $class='\PyServer\Scheduler\\'.$name;
        if (!class_exists($class)) {
            \PyServer\Util\Log::error("scheduler not found:".$class);
            exit(1);
        }
        return new $class();
    }
}

if (!function_exists("is_win")) {

    /**
     * 是否是windows系统
     * @return bool
     */
    function is_win()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === "WIN" ? true : false;
    }
}