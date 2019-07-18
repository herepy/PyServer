<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 15:45
 */

namespace PyServer\Worker;

class MasterWorker implements WorkerInterface
{

    protected $protocol;

    protected $address;

    protected $port;

    protected $deamon=false;

    protected $workerCount=1;

    protected $workerPids=[];

    protected $logDir;

    protected $logFile;

    protected $pidFile;

    public function __construct($address = null)
    {
        if (!$address) {
            return;
        }

        $tmp=explode("://",$address,2);
        if (count($tmp) < 2) {
            die("address is not right");
        }

        $protocol='PyServer\\Protocol\\'.ucfirst(strtolower($tmp[0]));
        if (!class_exists($protocol)) {
            die("protocol is not exist");
        }

        $info=explode(":",$tmp[1]);
        if (count($info) < 2) {
            die("address is not right");
        }

        $this->address=$info[0];
        $this->port=$info[1];
        $this->protocol=$protocol;
    }

    public function setListen($protocol, $address, $port)
    {
        $protocol='PyServer\\Protocol\\'.ucfirst(strtolower($protocol));
        if (!class_exists($protocol)) {
            die("protocol is not exist");
        }

        $this->address=$address;
        $this->port=$port;
        $this->protocol=$protocol;
    }

    public function on($event, $callback)
    {
        // TODO: Implement on() method.
    }

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
    }

    public function run()
    {
        check_env();

        $this->parseCmd();

    }

    protected function showLogo()
    {
        echo <<<LOGO
    ------------------------------------------
    |       pppppp      |      qqqqqq        |
    |       p    P      |      q    q        |
    |       p    P      |      q    q        |
    |       pppppp      |      qqqqqq        |
    |       p          / \          q        |
    |       p         /   \         q        |
    |       p        /     \        q        |
    ------------------------------------------
    
LOGO;

    }

    protected function showUsage()
    {
        echo <<<USAGE
        
Usage: php <file> cmd [option]
    start   start to run PyServer,-d option is means run in deamon mode
    stop    stop running PyServer,only used when PyServer run in deamon mode
    status  get the running status and some other informations

USAGE;

    }

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
     * 获取主进程pid
     * @return bool|string
     */
    protected function masterPid()
    {
        if (!file_exists($this->pidFile)) {
            die("the masterPid is not exists:".$this->pidFile);
        }
        $pid=file_get_contents($this->pidFile);

        //进程是否存活
        if (posix_kill($pid,0) == false) {
            unlink($this->pidFile);
            die("pyserver is not run");
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
            die("fork failed,please try again");
        } else if ($pid > 0) {
            exit(0);
        }

        $pid=pcntl_fork();
        if ($pid == -1) {
            die("fork failed,please try again");
        } else if ($pid > 0) {
            exit(0);
        }

        //设置会话组长
        if (posix_setsid() == -1) {
            die("make the current process a session leader failed");
        }
        umask(0);

        //保存主进程pid到文件
        $pid=posix_getpid();
        $this->pidFile="/run/pyserver.pid";
        file_put_contents($this->pidFile,$pid);
    }

    protected function forkChild()
    {
        //todo
    }

    protected function monitor()
    {
        //todo
    }

    protected function stop()
    {
        //todo
    }

    protected function reload()
    {
        //todo
    }

    protected function start()
    {
        //todo
    }

}