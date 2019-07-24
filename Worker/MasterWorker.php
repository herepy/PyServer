<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:45
 */

namespace PyServer\Worker;

use PyServer\Scheduler\Event;

class MasterWorker implements WorkerInterface
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
     * @var string 日志目录
     */
    protected $logDir;

    /**
     * @var string 日志文件名
     */
    protected $logFile;

    /**
     * @var string 存放守护进程pid文件
     */
    protected $pidFile;

    /**
     * @var \PyServer\Scheduler\SchedulerInterface 调度器实例
     */
    public static $scheduler;

    /**
     * 创建一个主进程
     * MasterWorker constructor.
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
            $this->logDir=dirname($config["logFile"]).DIRECTORY_SEPARATOR;
            $this->logDir=basename($config["logFile"]);
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
                if (!$this->checkAndGetPid()) {
                    die("PyServer in not running".PHP_EOL);
                }
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
        pcntl_signal(SIGINT,[$this,"signalHandler"]);

        //重启
        pcntl_signal(SIGQUIT,[$this,"signalHandler"]);

        //状态
        pcntl_signal(SIGUSR1,[$this,"signalHandler"]);
    }

    /**
     * 信号处理器
     * @param int $sinal 接收到的信号
     */
    protected function signalHandler($sinal)
    {
        switch ($sinal) {
            case SIGINT:
                //todo
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
        return $pid;
    }

    /**
     * 守护进程方式运行
     */
    protected function deamon()
    {
        $pid=pcntl_fork();
        if ($pid == -1) {
            die("fork failed,please try again".PHP_EOL);
        } else if ($pid > 0) {
            exit(0);
        }

        $pid=pcntl_fork();
        if ($pid == -1) {
            die("fork failed,please try again".PHP_EOL);
        } else if ($pid > 0) {
            exit(0);
        }

        //设置会话组长
        if (posix_setsid() == -1) {
            die("make the current process a session leader failed".PHP_EOL);
        }
        umask(0);

        //保存主进程pid到文件
        $pid=posix_getpid();
        $this->pidFile="/run/pyserver.pid";
        file_put_contents($this->pidFile,$pid);
    }

    /**
     * 监控子进程状态
     */
    protected function monitor()
    {
        while (1) {
            pcntl_signal_dispatch();
            $pid=pcntl_wait($status);
            if ($pid > 0) {
                unset($this->workerPids[$pid]);
                //todo 记录日志
                echo "worker ".$pid." exited".PHP_EOL;
            }
            pcntl_signal_dispatch();
        }
    }

    protected function stop()
    {
        //todo
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

        //是否守护进程模式
        if ($this->deamon) {
            $this->deamon();
        }

        //初始化调度器
        $this->initScheduler();

        //todo onMasterStart回调
        Event::dispatch("masterStart",[$this]);

        //创建工作进程
        $this->forkWorker();

        //安装信号处理器
        $this->installSignal();

        //监控工作进程
        $this->monitor();

    }

    /**
     * 初始化调度器
     */
    protected function initScheduler()
    {
        $scheduler=new Event();
        $scheduler->init();
        self::$scheduler=$scheduler;
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
                die("fork worker failed");
            } else if ($pid > 0) {  //主进程
                $this->workerPids[$pid]=$pid;
            } else {  //工作进程
                //设置进程名
                cli_set_process_title("PyServer-worker");
                $worker=new ChildWorker($this->transport,$this->protocol,$this->address,$this->port);
                $worker->run();
                //工作进程异常退出loop
                die("worker abnormal exit");
            }
        }
    }

}