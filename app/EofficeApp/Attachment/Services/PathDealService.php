<?php


namespace App\EofficeApp\Attachment\Services;


use App\EofficeApp\Base\BaseService;

/**
 * 处理附件中路径相关
 *
 * Class PathDealService
 * @package App\EofficeApp\Attachment\Services
 */
class PathDealService extends BaseService
{
    /**
     * 替换url中拼接的get参数
     *  如 example.com/index.php?a=1&b=2&c=3 中$targetKey可为字符串a, $targetValue为目标值
     *
     * @param string $url
     * @param string $targetKey
     * @param string $targetValue
     * @return bool|string
     */
    public function replaceUrlParam($url, $targetKey, $targetValue)
    {
        $urlParams = parse_url($url);

        $queryParams = [];
        if (isset($urlParams['query'])) {
            $queryParams = explode('&', $urlParams['query']);

            array_walk($queryParams, function (&$value) use ($targetKey, $targetValue) {
                $item = explode('=', $value);
                if (isset($item[0]) && ($item[0] === $targetKey)) {
                    $value = $item[0].'='.$targetValue;
                }
            });
        }
        $queryParamsStr = implode('&', $queryParams);

        if (strpos($url, '?') !== false) {
            $url = substr($url,0,strpos($url, '?'));
            $url = $url.'?'.$queryParamsStr;
        }

        return $url;
    }

    /**
     * 删除url中拼接的get参数
     *  如 example.com/index.php?a=1&b=2&c=3 中$targetKey可为字符串a
     *
     * @param string $url
     * @param string $targetKey
     * @return bool|string
     */
    public function deleteUrlParam($url, $targetKey)
    {
        // 若没有get参数则直接返回
        if (strpos($url, '?') === false) {
            return $url;
        }
        $urlParams = parse_url($url);

        $queryParams = [];
        if (isset($urlParams['query'])) {
            $queryParams = explode('&', $urlParams['query']);

            array_walk($queryParams, function ($value, $key) use ($targetKey, &$queryParams) {
                $item = explode('=', $value);
                if (isset($item[0]) && ($item[0] === $targetKey)) {
                   unset($queryParams[$key]);
                }
            });
        }
        $queryParamsStr = implode($queryParams, '&');

        $originUrl = substr($url,0,strpos($url, '?'));
        // 若只有一个参数则则返回?前地址
        if ($queryParamsStr) {
            $url = $originUrl.'?'.$queryParamsStr;
        } else {
            $url = $originUrl;
        }

        return $url;
    }
}