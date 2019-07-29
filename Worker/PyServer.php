<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:45
 */

namespace PyServer\Worker;

use PyServer\Scheduler\Event;
use PyServer\Util\Log;

class PyServer implements WorkerInterface
{

    /**
     * @var string 应用层协议
     */
    protected $protocol=null;

    /**
     * @var string 传输层协议
     */
    protected $transport="tcp";

    /**
     * @var string 监听地址
     */
    protected $address;

    /**
     * @var int 监听端口
     */
    protected $port;

    /**
     * @var bool 是否守护进程模式
     */
    protected $deamon=false;

    /**
     * @var int 工作进程数量
     */
    protected $workerCount=1;

    /**
     * @var array 工作进程pid数组 [$pid=>$pid]
     */
    protected $workerPids=[];

    /**
     * @var string 日志文件名
     */
    protected $logFile="/var/log/PyServer.log";

    /**
     * @var string 存放守护进程pid文件
     */
    protected $pidFile="/run/pyserver.pid";

    /**
     * 创建一个主进程
     * PyServer constructor.
     * @param null $address 监听地址 如："http://0.0.0.0:8080"
     */
    public function __construct($address = null)
    {
        if (!$address) {
            return;
        }

        $info=explode("://",$address,2);
        if (count($info) < 2) {
            die("address is not right".PHP_EOL);
        }

        $tmp=explode(":",$info[1],2);
        if (count($tmp) < 2) {
            die("address is not right".PHP_EOL);
        }
        $this->setListen($info[0],$tmp[0],$tmp[1]);
    }

    /**
     * 设置监听地址
     * @param string $protocol 协议
     * @param self $address 地址
     * @param int $port 端口
     * @return null
     */
    public function setListen($protocol, $address, $port)
    {
        $protocol=strtolower($protocol);
        if ($protocol == "udp") {
            $this->transport="udp";
        } else if ($protocol == "unix") {
            $this->transport="unix";
        } else {
            if ($protocol != "tcp" && !class_exists('PyServer\\Protocol\\'.ucfirst($protocol))) {
                die("protocol is not exist".PHP_EOL);
            }
            if ($protocol != "tcp") {
                $this->protocol=$protocol;
            }
        }

        $this->address=$address;
        $this->port=$port;
    }

    public function on($event, $callback)
    {
        Event::register($event,$callback);
    }

    /**
     * 配置信息
     * @param array $config 配置内容
     * @return bool
     */
    public function config($config)
    {
        if (!is_array($config)) {
            return false;
        }

        if (isset($config["deamon"])) {
            $this->deamon=$config["deamon"];
        }

        if (isset($config["workerCount"])) {
            $this->workerCount=$config["workerCount"];
        }

        if (isset($config["logFile"])) {
            $this->logFile=$config["logFile"];
        }
        return true;
    }

    /**
     * 运行主进程
     */
    public function run()
    {
        check_env();

        $this->parseCmd();
    }

    /**
     * 显示logo
     */
    protected function showLogo()
    {
        echo <<<LOGO
    ------------------------------------------
    |       pppppp   \     /   qqqqqq        |
    |       p    P    \   /    q    q        |
    |       p    P     \ /     q    q        |
    |       pppppp      |      qqqqqq        |
    |       p           |           q        |
    |       p           |           q        |
    |       p           |           q        |
    ------------------------------------------
    
LOGO;

    }

    /**
     * 显示用法
     */
    protected function showUsage()
    {
        echo <<<USAGE
        
Usage: php <file> cmd [option]
    start   start to run PyServer,-d option is means run in deamon mode
    stop    stop running PyServer,only used when PyServer run in deamon mode
    status  get the running status and some other informations

USAGE;

    }

    /**
     * 解析命令
     */
    protected function parseCmd()
    {
        global $argc,$argv;

        if ($argc < 2) {
            $this->showLogo();
            $this->showUsage();
            exit(0);
        }

        $cmd=$argv[1];
        switch ($cmd) {
            case "start":
                if ($argc == 3 && $argv[2] == "-d") {
                    $this->deamon=true;
                }
                if ($pid=$this->checkAndGetPid()) {
                    die("already runed in deamon mode,pid is".$pid.PHP_EOL);
                }
                $this->start();
                break;
            case "stop":
                $this->stop();
                break;
            case "status":
                //todo
                break;
            case "help":
            default:
                die($this->showUsage());
        }
    }

    /**
     * 安装信号处理器
     */
    protected function installSignal()
    {
        //停止
        pcntl_signal(SIGINT,[$this,"signalHandler"],false);

        //重启
        pcntl_signal(SIGQUIT,[$this,"signalHandler"],false);

        //状态
        pcntl_signal(SIGUSR1,[$this,"signalHandler"],false);
    }

    /**
     * 信号处理器
     * @param int $sinal 接收到的信号
     */
    public function signalHandler($sinal)
    {
        switch ($sinal) {
            //停止
            case SIGINT:
                $this->doStop();
                break;
            case SIGQUIT:
                //todo
                break;
            case SIGUSR1:
                //todo
                break;
        }
    }

    /**
     * 守护进程执行停止操作
     */
    protected function doStop()
    {
        //向所有子进程发送停止信号
        foreach ($this->workerPids as $pid) {
            posix_kill($pid,SIGTERM);
        }
        //所有子进程退出完毕，自身退出
        while (count($this->workerPids) != 0) {
            $pid=pcntl_wait($status,WUNTRACED);
            if ($pid >0) {
                unset($this->workerPids[$pid]);
            }
        }
        unlink($this->pidFile);
        exit(0);
    }

    /**
     * 检查并获取主进程pid
     * @return bool|int
     */
    protected function checkAndGetPid()
    {
        if (!file_exists($this->pidFile)) {
            return false;
        }
        $pid=file_get_contents($this->pidFile);

        //进程是否存活
        if (posix_kill($pid,0) == false) {
            unlink($this->pidFile);
            return false;
        }
        return intval($pid);
    }

    /**
     * 守护进程方式运行
     */
    protected function deamon()
    {
        $pid=pcntl_fork();
        if ($pid == -1) {
            Log::write('fork failed,please try again');
            exit(1);
        } else if ($pid > 0) {
            exit(0);
        }

        $pid=pcntl_fork();
        if ($pid == -1) {
            Log::write('fork failed,please try again');
            exit(1);
        } else if ($pid > 0) {
            exit(0);
        }

        //设置会话组长
        if (posix_setsid() == -1) {
            Log::write("make the current process a session leader failed");
            exit(1);
        }
        umask(0);

        //保存主进程pid到文件
        $pid=posix_getpid();
        file_put_contents($this->pidFile,$pid);
    }

    /**
     * 监控子进程状态
     */
    protected function monitor()
    {
        while (1) {
            pcntl_signal_dispatch();
            $pid=pcntl_wait($status,WUNTRACED);
            if ($pid > 0) {
                unset($this->workerPids[$pid]);
                //todo 记录日志
                Log::write("monitoer get worker ".$pid." exited");
            }
            pcntl_signal_dispatch();
        }
    }

    /**
     * 发送停止操作信号给守护进程
     */
    protected function stop()
    {
        $pid=$this->checkAndGetPid();
        if (!$pid) {
            die("PyServer is not running".PHP_EOL);
        }
        //向守护进程发送停止信号
        posix_kill($pid,SIGINT);

        //查看是否关闭成功,最多等待五秒
        for ($i=0;$i<5;$i++) {
            if (posix_kill($pid,0) == false) {
                die("stop success".PHP_EOL);
            }
            $i++;
            sleep(1);
        }
        die("stop fail".PHP_EOL);
    }

    protected function reload()
    {
        //todo
    }

    /**
     * 开始执行
     */
    protected function start()
    {
        //设置进程名
        cli_set_process_title("PyServer-Master");

        //设置日志文件
        Log::setFile($this->logFile);

        //是否守护进程模式
        if ($this->deamon) {
            $this->deamon();
        }

        //创建工作进程
        $this->forkWorker();

        //安装信号处理器
        $this->installSignal();

        //监控工作进程
        $this->monitor();

    }

    /**
     * 创建工作进程
     */
    protected function forkWorker()
    {
        if (count($this->workerPids) == $this->workerCount) {
            return;
        }

        $needCount=$this->workerCount-count($this->workerPids);
        for ($i=0;$i<$needCount;$i++) {
            $pid=pcntl_fork();
            if ($pid == -1) {
                Log::write("fork worker failed");
                exit(1);
            } else if ($pid > 0) {  //主进程
                $this->workerPids[$pid]=$pid;
            } else {  //工作进程
                //设置进程名
                cli_set_process_title("PyServer-worker");
                $worker=new Worker($this->transport,$this->protocol,$this->address,$this->port);
                $worker->run();
                //工作进程异常退出loop
                Log::write("worker abnormal exit,pid is ".posix_getpid());
                exit(1);
            }
        }
    }

}