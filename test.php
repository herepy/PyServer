<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/17
 * Time: 17:40
 */

require_once "vendor/autoload.php";

use PyServer\Worker\MasterWorker;

$worker=new MasterWorker("hTTp://0.0.0.0:8080");