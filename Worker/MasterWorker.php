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

    /**
     * @var string 应用层协议
     */
    protected $protocol;

    /**
     * @var string 传输层协议
     */
    protected $transport;

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
     * @var array 工作进程pid数组
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
     * 创建一个主进程
     * MasterWorker constructor.
     * @param null $address 监听地址 如："http://0.0.0.0:8080"
     */
    public function __construct($address = null)
    {
        if (!$address) {
            return;
        }

        $tmp=explode("://",$address,2);
        if (count($tmp) < 2) {
            die("address is not right".PHP_EOL);
        }

        $protocol=ucfirst(strtolower($tmp[0]));
        if (!class_exists('PyServer\\Protocol\\'.$protocol)) {
            $transport=$protocol;
            $protocol=get_protocol($transport);

            if (!$protocol) {
                die("protocol is not exist".PHP_EOL);
            }
        } else {
            $protocol='PyServer\\Protocol\\'.$protocol;
        }

        $info=explode(":",$tmp[1]);
        if (count($info) < 2) {
            die("address is not right".PHP_EOL);
        }

        $this->address=$info[0];
        $this->port=$info[1];
        $this->protocol=$protocol;
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
        $protocol=ucfirst(strtolower($protocol));
        if (!class_exists('PyServer\\Protocol\\'.$protocol)) {
            $transport=$protocol;
            $protocol=get_protocol($transport);

            if (!$protocol) {
                die("protocol is not exist".PHP_EOL);
            }
        } else {
            $protocol='PyServer\\Protocol\\'.$protocol;
        }

        $this->address=$address;
        $this->port=$port;
        $this->protocol=$protocol;
    }

    public function on($event, $callback)
    {
        // TODO: Implement on() method.
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

    /**
     * 开始执行
     */
    protected function start()
    {
        //是否守护进程模式
        if ($this->deamon) {
            $this->deamon();
        }

        //安装信号处理器
        $this->installSignal();

        //创建监听socket
        $this->listen();

    }

    protected function listen()
    {

    }

}