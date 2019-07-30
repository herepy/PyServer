# PyServer

**PyServer是一款纯PHP编写的网络通信框架，架构简单易上手**

## 简介

传统PHP编写的Web应用所使用的架构一般是LNMP模式，Nginx+PHP-FPM的经典搭配可以帮助开发者快速搭建一套Web服务器用于自身的业务。随着业务的发展，功能需求也变得多样性，单一“请求-回复”的响应模式变得不够用，比如一些长连接的业务使用原先的架构实现就会变得非除困难。虽然有Swoole的出现，但是上手门槛对于习惯了CURD一把梭的PHPer来说有点高，有一定的学习成本。PyServer其实就是一个缩水版的Workerman，在代码和功能缩减的情况下保证业务的完整性，框架默认实现了HTTP协议，使得开发者可以脱离Nginx当作独立的Server，自定义协议可方便的适应业务需求。

## 功能特色

- 架构简单(Master-Worker模型)
- composer快速安装引入项目
- 守护进程模式
- 工作进程数可调整
- 毫秒级定时器
- 多种调度器供选择(在安装了event扩展时，默认是Event调度器)
- Tcp长连接
- 协议可自定义
- 可作为传统Web服务器
- 可作为WebSocket服务器(待实现)

## 示例

##### 配置
```php
require_once "vendor/autoload.php";

use PyServer\Worker\PyServer;

$worker=new PyServer("http://0.0.0.0:8080");

$worker->config([
    "workerCount"   =>  4,                //工作进程数
    "deamon"        =>  true              //守护进程模式
    "logFile"       =>  "/log/xx.log"     //日志文件地址
]);

//设置工作进程启动时的回调
$worker->on('workerStart',function ($worker){
    //do something
});

$worker->run();
```

##### WebServer

```php
require_once "vendor/autoload.php";

use PyServer\Worker\PyServer;

$worker=new PyServer("http://0.0.0.0:8080");

//设置接收到客户端消息时的回调
$worker->on('message',function ($connection,$fd,$content){
    //发送数据并断开连接
    $connection->close($fd,json_encode($content));
});

$worker->run();
```
##### 定时器
```php
require_once "vendor/autoload.php";

use PyServer\Worker\PyServer;
use PyServer\Util\Timer;

$worker=new PyServer("http://0.0.0.0:8080");

$worker->on('workerStart',function (){
    //持续性定时器
    $timerA=new Timer(2,function(){
        echo "persist timer";
    },true);
    //一次性定时器
    $timerB=new Timer(5,function()use($timerA){
        echo "once timer";
        //取消定时器
        $timerA->cancel();
    });
});

$worker->run();
```


