<?php
namespace App\EofficeApp\EofficeCase\Services\lib;

class Utils
{
    /**
     * 文件夹文件拷贝
     *
     * @param string $src 来源文件夹
     * @param string $dst 目的地文件夹
     * @return bool
     */
    function dir_copy($src = '', $dst = '')
    {
        if (empty($src) || empty($dst)) {
            return false;
        }

        if (!is_dir($src)) {
            return false;
        }

        $dir = opendir($src);
        $this->dir_mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->dir_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);

        return true;
    }

    /**
     * 创建文件夹
     *
     * @param string $path 文件夹路径
     * @param int $mode 访问权限
     * @param bool $recursive 是否递归创建
     * @return bool
     */
    function dir_mkdir($path = '', $mode = 0777, $recursive = true)
    {
        clearstatcache();
        if (!is_dir($path)) {
            mkdir($path, $mode, $recursive);
            return chmod($path, $mode);
        }

        return true;
    }

    //删除指定文件夹以及文件夹下的所有文件
    function dir_del($dir)
    {
        if (!file_exists($dir)) {
            return;
        }
        //先删除目录下的文件：
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    @unlink($fullpath);
                } else {
                    $this->dir_del($fullpath);
                }
            }
        }

        closedir($dh);
        //删除当前文件夹：
        if (@rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    function addFileToZip($path, $zip)
    {
        $handler = opendir($path); //打开当前文件夹由$path指定。
        /*
            循环的读取文件夹下的所有文件和文件夹
            其中$filename = readdir($handler)是每次循环的时候将读取的文件名赋值给$filename，
            为了不陷于死循环，所以还要让$filename !== false。
            一定要用!==，因为如果某个文件名如果叫'0'，或者某些被系统认为是代表false，用!=就会停止循环
        */
        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") { //文件夹文件名字为'.'和‘..’，不要对他们进行操作
                if (is_dir($path . "/" . $filename)) { // 如果读取的某个对象是文件夹，则递归
                    $this->addFileToZip($path . "/" . $filename, $zip);
                } else { //将文件加入zip对象
                    $zip->addFile($path . "/" . $filename);
                }
            }
        }
        @closedir($path);
    }

    function extractZip($zipPathTo, $extractPathTo)
    {
        $zip = new \ZipArchive; //新建一个ZipArchive的对象
        /*
            通过ZipArchive的对象处理zip文件
            $zip->open这个方法的参数表示处理的zip文件名。
            如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
        */
        if ($zip->open($zipPathTo) === TRUE) {
            $zip->extractTo($extractPathTo);
            $zip->close(); //关闭处理的zip文件
        }
    }

    /**
     * Zip a folder (without itself).
     *
     * @param string $sourcePath Path of directory to be zip.
     * @param string $outZipPath Path of output zip file.
     */
    public function zipDir($sourcePath, $outZipPath)
    {
        ini_set('memory_limit', '2048M');
        $pathInfo = pathInfo($sourcePath);
        $parentPath = $pathInfo['dirname'] . '/' . $pathInfo['basename'];

        $z = new \ZipArchive();
        $z->open($outZipPath, \ZIPARCHIVE::CREATE);
        $this->folderToZip($parentPath, $z, strlen("$parentPath/"));
        $z->close();
    }

    /**
     * Add files and sub-directories in a folder to zip file.
     * @param string $folder
     * @param ZipArchive $zipFile
     * @param int $exclusiveLength Number of text to be exclusived from the file path.
     */
    private function folderToZip($folder, &$zipFile, $exclusiveLength)
    {
        if (!is_dir($folder)) {
            return false;
        }
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";
                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zipFile->addEmptyDir($localPath);
                    $this->folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    /**
     * 获取文件夹大小
     */
    public function dirSize($dir)
    {
        $size = 0;
        $dirs = [$dir];
        while (@$dir = array_shift($dirs)) {
            $fd = opendir($dir);
            while (@$file = readdir($fd)) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $file = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($file)) {
                    array_push($dirs, $file);
                } else {
                    $size += filesize($file);
                }
            }
            closedir($fd);
        }
        return $size;
    }

    /**
     * 获得随机字符串
     * @param $len             需要的长度
     * @param $special        是否需要特殊符号
     * @return string       返回随机字符串
     */
    function getRandomStr($len, $special = false)
    {
        if (!is_numeric($len) || $len <= 0) {
            return '';
        }
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "0", "1", "2", "3", "4", "5", "6",
            "7", "8", "9"
        );

        if ($special) {
            $chars = array_merge($chars, array(
                "!", "@", "#", "$", "?", "|", "{", "/", ":", ";",
                "%", "^", "&", "*", "(", ")", "-", "_", "[", "]",
                "}", "<", ">", "~", "+", "=", ",", "."
            ));
        }

        $charsLen = count($chars) - 1;
        shuffle($chars);
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $charsLen)];
        }
        return $str;
    }
    /**
     * 获取数字字符串
     */
    function getDigitStr($len)
    {
        if (!is_numeric($len) || $len <= 0) {
            return '';
        }
        $chars = array(
            "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"
        );

        $charsLen = count($chars) - 1;
        shuffle($chars);
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $tmpChar = $chars[mt_rand(0, $charsLen)];
            while ($i == 0 && $tmpChar == 0) {
                // 首位不能为0
                $tmpChar = $chars[mt_rand(0, $charsLen)];
            }
            $str .= $tmpChar;
        }
        return $str;
    }
}
