<?php
namespace App\EofficeApp\Elastic\Services\Dictionary;


Trait DictionaryTrait
{
    /**
     * 字符串按换行符转为数组
     *
     * @param string $content
     *
     * @return array
     */
    public function stringConvertToArrByEOL(string $content): array
    {
        return explode(PHP_EOL, $content);
    }

    /**
     * 字符串按英文逗号(,)转为数组
     *
     * @param string $content
     *
     * @return array
     */
    public function stringConvertToArrByComma(string $content): array
    {
        return explode(',', $content);
    }

    /**
     * 数组以逗号为分隔符转为字符串
     *
     * @param array $paramsArr
     *
     * @return string
     */
    public function arrayConvertToStringByComma(array $paramsArr): string
    {
        return implode(',', $paramsArr);
    }

    /**
     * 数组按换行符转为字符串
     * @param array $paramsArr
     *
     * @return string
     */
    public function arrayConvertToStringByEOL(array  $paramsArr): string
    {
        return implode(PHP_EOL, $paramsArr);
    }
    /**
     * 获取文件内容
     *
     * @param string $path
     *
     * @return string
     */
    public function getFileContent(string $path): string
    {
        if (file_exists($path)) {
            return file_get_contents($path);
        }

        return '';
    }
}