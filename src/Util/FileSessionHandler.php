<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2019/7/31
 * Time: 10:06
 */

namespace Pengyu\Server\Util;

class FileSessionHandler implements \SessionHandlerInterface
{
    public $dir;

    public function __construct($dir)
    {
        $this->dir=$dir;
    }

    public function open($save_path, $name)
    {
        if (!file_exists($this->dir)) {
            mkdir($this->dir,0777,true);
        }
        return true;
    }

    public function close()
    {
        return true;
    }

    public function gc($maxlifetime)
    {
        $handle = opendir($this->dir);
        if ($handle === false) {
            return 1;
        }

        $nowTime=time();
        while (false !== ($file = readdir($handle))) {
            if ($file == "." || $file == "..") {
                continue;
            }

            $filename=$this->dir.DIRECTORY_SEPARATOR.$file;
            if ((filemtime($filename) + $maxlifetime) < $nowTime) {
                unlink($filename);
            }
        }
        closedir($handle);
        return 0;
    }

    public function read($session_id)
    {
        $filename=$this->dir.DIRECTORY_SEPARATOR.$session_id;
        if (!file_exists($filename)) {
            return "";
        }

        return file_get_contents($filename);
    }

    public function write($session_id, $session_data)
    {
        $filename=$this->dir.DIRECTORY_SEPARATOR.$session_id;
        return file_put_contents($filename,serialize($session_data)) > 0;
    }

    public function destroy($session_id)
    {
        $filename=$this->dir.DIRECTORY_SEPARATOR.$session_id;
        if (!file_exists($filename)) {
            return 1;
        }

        unlink($filename);
        return 0;
    }

}