<?php


namespace App\EofficeApp\Elastic\Utils;


use App\EofficeApp\Elastic\Configurations\Constant;

class JSON
{
    /**
     * json_decode 的封装，允许在 JSON 文件中添加注释.
     *
     * @param $json
     * @param bool $assoc
     * @param int  $depth
     * @param int  $options
     *
     * @return mixed
     */
    public static function decode($json, $assoc = false, $depth = 512, $options = 0)
    {
        // search and remove comments like /* */ and //
        $json = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', $json);

        if (version_compare(phpversion(), '5.4.0', '>=')) {
            $json = json_decode($json, $assoc, $depth, $options);
        } elseif (version_compare(phpversion(), '5.3.0', '>=')) {
            $json = json_decode($json, $assoc, $depth);
        } else {
            $json = json_decode($json, $assoc);
        }

        return $json;
    }

    /**
     * 获取所有配置.
     *
     * @param $category
     * @param string $version
     *
     * @return mixed|null
     */
    public static function getElasticSearchIndexSchema($category, $version = Constant::DEFAULT_VERSION)
    {
        $filePath = __DIR__.'/../Resource/schema/'.Constant::ALIAS_PREFIX.strtolower($category).'_'.strtolower($version).'.json';

        if (file_exists($filePath)) {
            return self::decode(file_get_contents($filePath), true);
        }

        return null;
    }
}