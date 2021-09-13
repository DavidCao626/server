<?php

namespace EassistantClient\Utils;
/**
 * 简单封转针对小e的请求类
 * Class Request
 * @package EassistantClient\Utils
 */
class Request
{
    private $request;

    public function __construct()
    {
        $this->request = $_GET;
    }

    public function get($key, $default = null)
    {
        if (!$key) {
            return false;
        }
        if (isset($this->request[$key]) && !empty($this->request[$key])) {
            $value = $this->request[$key];
            //缓存起来防止页面刷新
            $_SESSION[$key] = $value;
        } else {
            //从缓存里面读取
            $value = $_SESSION[$key] ?? $default;
        }
        return $value ? $value : $default;
    }
}