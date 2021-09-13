<?php

namespace App\Utils;

use App\Exceptions\ResponseException;
use Illuminate\Support\Arr;
// 函数间的返回数据管理服务类
class ResponseService
{
    private static $_instance = null;//实例
    private $status = 1; // 状态
    private $data = [];
    // 错误数据
    private $langCode;
    private $langModule;
    private $dynamic;

    /**
     * 获取实例
     * @param array $data
     * @return ResponseService
     */
    public static function getIns(array $data = []): ResponseService
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self ();
        }

        $data && self::$_instance->data = $data;
        return self::$_instance;
    }

    /**
     * 销毁原实例并重新创建实例
     * @param array $data
     * @return ResponseService
     */
    public static function getNewIns(array $data = []): ResponseService
    {
        self::$_instance = null;
        return self::getIns($data);
    }

    /**
     * 设置数据
     * @param $key
     * @param $value
     * @return ResponseService
     */
    public function setInData($key, $value): ResponseService
    {
        if (is_null($key)) {
            return $this;
        }
        Arr::set($this->data, $key, $value);
        return $this;
    }

    /**
     * 直接抛出异常，不建议深层次的函数直接抛出异常
     * @param $langCode
     * @param $langModule
     * @param string $dynamic
     * @throws ResponseException
     */
    public static function throwException($langCode, $langModule, $dynamic = '') {
        self::getIns()->setException($langCode, $langModule, $dynamic); // 放入进去，方便后续检测到异常后可以直接重新抛出
        throw (new ResponseException())->setErrorResponse($langCode, $langModule, $dynamic);
    }

    // 设置异常：丢给调用处决定是否处理异常
    public function setException($langCode, $langModule, $dynamic = '') {

        $this->langCode = $langCode;
        $this->langModule = $langModule;
        $this->dynamic = $dynamic;
        $this->status = 0;
    }

    // 兼容过去的模式，查找里面是否有错误，有返回错误，无返回false
    public function setCodeException($data) {
        if (Arr::get($data, 'code')) {
            $this->setException($data['code'][0], $data['code'][1], Arr::get($data, 'dynamic'));
            return true;
        }
        return false;
    }

    // 检查到异常直接抛出
    public function checkException() {
        if (!$this->status) {
            self::throwException($this->langCode, $this->langModule, $this->dynamic);
        }
    }

    /**
     * @param array $data
     * @return ResponseService
     */
    public function setDataUseMerge(array $data): ResponseService
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * @param array|string $keys
     * @return ResponseService
     */
    public function forget($keys): ResponseService
    {
        Arr::forget($this->data, $keys);
        return $this;
    }

    /**
     * 获取数据，默认返回全部
     * @param null|string|array $key
     * @param null $default
     * @return array
     */
    public function getData($key = null, $default = null) {
        if (is_null($key)) {
            return $this->data;
        }
        return Arr::pluck($this->data, $key, $default);
    }
}


