<?php

namespace App\Jobs\Export;

abstract class Base
{
    /**
     * 导出文件
     *
     * @param array $config 配置信息
     * @param mixed $data 数据
     *
     * @return array 文件名称file_name，文件类型file_type
     */
    abstract public function export($config, $data);

    /**
     * 生成导出文件名
     *
     * @param string $fileName 文件名
     *
     * @return string
     */
    public function makeFileName($fileName)
    {
        return 'e-office_' . $fileName . '_' . date('YmdHis');
    }

    /**
     * 生成导出文件名
     *
     * @param string $str 需要转码的字符串
     *
     * @return string
     */
    public function encodeFileName($str, $inCharset = "UTF-8", $outCharset = "GBK", $suffix = '')
    {
        return iconv($inCharset, $outCharset . $suffix, $str);
    }

    /**
     * 生成文件
     *
     * @param string $fileName 文件名
     * @param string $fileType 文件类型
     *
     * @return string
     */
    public function createFile($fileName, $fileType)
    {
        $dir = $this->createDir();
        return $dir . $fileName . '.' . $fileType;
    }

    /**
     * 创建目录
     *
     * @param string $fileName 文件名
     * @param string $fileType 文件类型
     *
     * @return string
     */
    public function createDir()
    {
        return createExportDir();
    }
}
