<?php


namespace App\EofficeApp\Elastic\Utils;


class Filter
{
    /**
     * 过滤html符号
     *
     * @param string html
     *
     * @return array
     */
    public static function htmlFilter(string $str): string
    {
        $str = strip_tags($str); // 去掉html标签
//        $str = htmlspecialchars($str);    // 转义html符号
        return $str;
    }

    /**
     * 过滤图片
     *
     * @param string $str
     * @return string
     */
    public static function imageFilter(string $str): string
    {
        $str = preg_replace('/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i','',$str);

        return $str;
    }

    /**
     * 过滤表情符号(客户联系人备注中 以 &nbsp;[emoji-开始 以]结束)
     *
     * @param string $str
     * @return string
     */
    public static function emojiFilter(string $str): string
    {
        $str = preg_replace('/&nbsp;\[emoji-.*\]/','',$str);

        return $str;
    }

    /**
     * 过滤nbsp符号(公告中出现 DT202004290032 )
     *
     * @param string $str
     * @return string
     */
    public static function nbspFilter(string $str): string
    {
        $str = preg_replace('/&nbsp;/',' ',$str);

        return $str;
    }

    /**
     * 过滤实体符号(内部邮件中出现 DT202102070020)
     *
     * @param string $str
     * @return string
     */
    public static function entityFilter(string  $str): string
    {
        $str = html_entity_decode($str);

        return $str;
    }
}