<?php


namespace App\EofficeApp\Elastic\Utils;


/**
 * 处理文件编码内容
 */
class FileHandling
{
    /**
     * 支持的编码类型
     */
    const ENCODING_TYPES = [
        'GBK',
        'UTF-8',
        'UTF-16LE',
        'UTF-16BE',
        'ISO-8859-1',
    ];

    const ENCODING_BY_UTF8 = 'UTF-8';   // UTF8编码
    const ENCODING_BY_GBK = 'GBK';  // 国标编码
    const UNKNOWN = 'UNKNOWN';    // 未知编码

    /**
     * 检测文件编码
     *
     * @param string $filePath 文件路径
     * @return string $fileSize
     * @return string
     */
    public static function getFileContent(string $filePath, int $fileSize = 0): string
    {
        //判断文件路径中是否含有中文，如果有，那就对路径进行转码，如此才能识别
        if (preg_match('/[\x7f-\xff]/', $filePath)) {
            $filePath = iconv(self::ENCODING_BY_UTF8, self::ENCODING_BY_GBK, $filePath);
        }
        if (file_exists($filePath)) {
            $handler = fopen($filePath, "r");
            if ($fileSize === 0) {
                $fileSize = filesize($filePath);
            }

            $content = fread($handler, $fileSize);
            fclose($handler);

            return $content;
        } else {
            return '';
        }
    }

    /**
     * 获取文件编码类型
     *
     * @param  string $filePath    文件路径
     * @param  string $fileSize     需要获取的字符长度
     * @return string               返回字符编码
     */
    public static function detectEncoding(string $filePath, $fileSize = 1000): string
    {
        $list = self::ENCODING_TYPES;
        $str = self::getFileContent($filePath, $fileSize);
        foreach ($list as $item) {
            $tmp = mb_convert_encoding($str, $item, $item);
            if (md5($tmp) === md5($str)) {
                return $item;
            }
        }
        return self::UNKNOWN;
    }

    /**
     * 文件编码类型是否为UTF8编码
     *
     * @param string $filePath
     *
     * @return bool
     */
    public static function isEncodedByUTF8(string $filePath): bool
    {
        $type = self::detectEncoding($filePath, 1000);

        return $type === self::ENCODING_BY_UTF8;
    }

    /**
     * 按照指定编码类型写入
     *
     * @param string $filePath
     * @param string $content
     * @param string $encodingType
     */
    public static function putFileContent(string $filePath, string $content, string $encodingType = self::ENCODING_BY_UTF8): void
    {
        if (!in_array($encodingType, self::ENCODING_TYPES)) {
            $encodingType = self::ENCODING_BY_UTF8;
        }

        $encodedContent = mb_convert_encoding($content, $encodingType, $encodingType);

        file_put_contents($filePath, $encodedContent);
    }
}