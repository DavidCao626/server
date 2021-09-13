<?php


namespace App\EofficeApp\Elastic\Utils;


class Camelize
{
    /**
     * 驼峰命名转为下划线命名
     *
     * @param string $str
     *
     * @return string
     */
    public static function toUnderScore($str)
    {
        $dstr = preg_replace_callback('/([A-Z]+)/', function($matchs) {
            return '_'.strtolower($matchs[0]);
        }, $str);
        return trim(preg_replace('/_{2,}/', '_', $dstr), '_');
    }

    /**
     * 下划线命名转为驼峰命名
     *
     * @param string $str
     *
     * @return string
     */
    public static function toCamelCase($str)
    {
        $array = explode('_', $str);
        $result = '';
        $len = count($array);
        if($len > 0)
        {
            for($i = 0; $i < $len; $i++)
            {
                $result .= ucfirst($array[$i]);
            }
        }
        return $result;
    }
}