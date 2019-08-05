<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/31
 * Time: 10:52
 */

namespace PyServer\Util;


class Session
{
    /**
     * @var \SessionHandlerInterface session处理接口
     */
    protected static $handler;

    /**
     * @var string sessionId
     */
    public $id;

    /**
     * @var string sessionName
     */
    public $name;

    /**
     * @var string 存放目录
     */
    public $dir;

    /**
     * @var int 有效期
     */
    public $expire;

    /**
     * @var bool 是否已开启session
     */
    protected $started=false;

    /**
     * 构建session对象
     * Session constructor.
     * @param string $dir 存放目录
     * @param string $sessionName session名
     * @param int $expire 有效期
     */
    public function __construct($dir,$sessionName,$expire=86400)
    {
        $this->dir=$dir;
        $this->name=$sessionName;
        $this->expire=$expire;

        //todo 目前默认使用文件储存
        self::$handler=new FileSessionHandler($dir);
    }

    /**
     * 开启session
     * @param string $sessionId 手动指定sessionId
     */
    public function start($sessionId=null)
    {
        if ($this->started) {
            return;
        }

        self::$handler->open($this->dir,$this->name);

        //session清理
        self::$handler->gc($this->expire);

        if ($sessionId) {  //手动传入sessionId
            $this->id=$sessionId;
        } else if (isset($_COOKIE[$this->name])) {  //cookie中读取sessionId
            $this->id=$_COOKIE[$this->name];
        } else {  //生成sessionId
            $this->id=$this->createId();
        }

        $data=self::$handler->read($this->id);
        $_SESSION=$data ? unserialize($data) : [];
        $this->started=true;
        return;
    }

    /**
     * 获取/设置sessionId
     * @param string $sessionId 要设置的sessionId
     * @return string 当前sessionId
     */
    public function id($sessionId=null)
    {
        if ($sessionId) {
            $this->id=$sessionId;
        }
        return $this->id;
    }

    /**
     * 获取/设置session名
     * @param string $sessionName 要设置的session名
     * @return string 当前session名
     */
    public function name($sessionName=null)
    {
        if ($sessionName) {
            $this->name=$sessionName;
        }
        return $this->name;
    }

    /**
     * 生成sessionId
     * @param string $prefix 前缀
     * @return string 生成的sessionId
     */
    public function createId($prefix="")
    {
        $lib="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $length=16;
        do{
            $str="";
            while ($length>0) {
                $str.=$lib[mt_rand(0,strlen($lib)-1)];
                $length--;
            }
            $id=$prefix.$str;
        }while(file_exists($this->dir.DIRECTORY_SEPARATOR.$id));

        return $id;
    }

    /**
     * 关闭并保存session
     */
    public function close()
    {
        self::$handler->write($this->id,$_SESSION);
        self::$handler->close();
        $_SESSION=[];
        $_COOKIE[$this->name]=$this->id;
    }

    /**
     * @return bool 销毁session
     */
    public function destroy()
    {
        $_SESSION=[];
        return self::$handler->destroy($this->id);
    }

    /**
     * 析构函数，自动收尾处理
     */
    public function __destruct()
    {
        $this->close();
    }


}