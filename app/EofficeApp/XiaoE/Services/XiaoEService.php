<?php

namespace App\EofficeApp\XiaoE\Services;
/**
 * 对接小e助手服务类
 *
 * @author lizhijun
 */

use App\EofficeApp\Base\BaseService;

class XiaoEService extends BaseService
{
    private $extendDir = '../ext/xiaoe/extend/';
    // 字典返回数据头
    private $responseHead = ['id' => 0, 'title' => 1, 'extendShowTitle' => 2];
    //字典依赖的字段
    private $filterFields;
    // 引导服务
    private $bootService;
    // 字典获取服务
    private $dictService;
    //验证服务
    private $checkService;
    //数据初始化服务
    private $initService;

    public function __construct()
    {
        parent::__construct();
        $this->bootService = 'App\EofficeApp\XiaoE\Services\BootService';
        $this->dictService = 'App\EofficeApp\XiaoE\Services\DictService';
        $this->checkService = 'App\EofficeApp\XiaoE\Services\CheckService';
        $this->initService = 'App\EofficeApp\XiaoE\Services\InitService';
    }

    /**
     * 获取字典数据源
     *
     * @param string $method
     * @param array $params
     *
     * @return array
     */
    public function getDictSource($method, $params, $module = false)
    {
        if ($module) {
            $data = $this->extendDispatcher($module, 'DictService', $method, $params, null, function ($service) {
                if (isset($service->filterFields)) {
                    $this->filterFields = $service->filterFields;
                }
            });
        } else {
            $data = $this->dispatcher($method, $params, $this->dictService);
            $this->filterFields = app($this->dictService)->filterFields;
        }
        if (isset($this->filterFields[$method]) && !empty($this->filterFields[$method])) {
            //对于没有配置extra的字典
            $dictFields = $this->filterFields[$method];
            if (count($dictFields) == 2) {
                $dictFields[] = false;
            }
            list($id, $title, $extra) = $dictFields;
            return $this->filterResponse($data, $id, $title, $extra);
        }

        return $this->combineResponse($this->responseHead, []);
    }

    /**
     * 意图完成后，接受参数进行业务逻辑处理的引导函数
     *
     * @param string $method
     * @param array $response
     *
     * @return void
     */
    public function boot($method, $response, $own, $module = false)
    {
        //记录操作日志
        if (isset($response['extra'])) {
            $log = $response['extra'];
            $log['user_id'] = $own['user_id'];
            $log['user_name'] = $own['user_name'];
            $log['date'] = date('Y-m-d');
            app('App\EofficeApp\XiaoE\Repositories\XiaoELogRepository')->insertData($log);
        }
        if ($module) {
            return $this->extendDispatcher($module, 'BootService', $method, $response, $own);
        }
        return $this->dispatcher($method, $response, $this->bootService, $own);
    }

    /**
     * 小e数据验证
     * @param $method
     * @param $response
     * @return array
     */
    public function check($method, $response, $module = false)
    {
        if ($module) {
            return $this->extendDispatcher($module, 'CheckService', $method, $response);
        }
        return $this->dispatcher($method, $response, $this->checkService);
    }

    /**
     * 小e数据初始化
     * @param $method
     * @param $response
     * @return array
     */
    public function initData($method, $response, $module = false)
    {
        if ($module) {
            return $this->extendDispatcher($module, 'InitService', $method, $response);
        }

        return $this->dispatcher($method, $response, $this->initService);
    }

    /**
     * 功能分发函数
     *
     * @param string $method
     * @param array $data
     * @param string $service
     *
     * @return array
     */
    private function dispatcher($method, $data, $service, $own = null)
    {
        $dispatcherMethod = $this->toCamelCase($method);

        return app($service)->{$dispatcherMethod}($data, $own);
    }

    /**
     * 功能分发函数(第三方)
     *
     * @param string $method
     * @param array $data
     * @param string $service
     *
     * @return array
     */
    private function extendDispatcher($module, $serviceName, $method, $data, $own = null, $callBack = false)
    {
        $dispatcherMethod = $this->toCamelCase($method);
        if (!file_exists($this->extendDir . $module . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . $serviceName . '.php')) {
            return false;
        }
        require_once $this->extendDir . $module . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . $serviceName . '.php';
        $service = new $serviceName();
        if ($callBack) {
            $callBack($service);
        }
        return $service->{$dispatcherMethod}($data, $own);
    }

    /**
     * 过滤响应结果
     *
     * @param array $data
     * @param string $id
     * @param string $title
     *
     * @return array
     */
    private function filterResponse($data, $id, $title, $extra)
    {

        $responseHead = $this->responseHead;
        if (empty($data)) {
            return $this->combineResponse($responseHead, []);
        }

        $result = [];

        foreach ($data as $item) {
            $row = array();
            if (is_array($item)) {
                $row[] = $item[$id] ?? '';
                $row[] = $item[$title] ?? '';
                if ($extra) {
                    $row[] = $item[$extra] ?? '';
                }
            } else {
                $row[] = $item->{$id} ?? '';
                $row[] = $item->{$title} ?? '';
                if ($extra) {
                    $row[] = $item->$extra ?? '';
                }
            }

            array_push($result, $row);
        }
        if (!$extra) {
            unset($responseHead['extendShowTitle']);
        }
        return $this->combineResponse($responseHead, $result);
    }

    /**
     * 组合响应数据
     *
     * @param array $head
     * @param array $data
     *
     * @return array
     */
    private function combineResponse(array $head, array $data)
    {
        return compact('head', 'data');
    }

    /**
     * 转驼峰
     *
     * @param string $str
     * @param string $delimter
     *
     * @return string
     */
    private function toCamelCase($str, $delimter = '-')
    {
        $array = explode($delimter, $str);

        $name = array_reduce($array, function ($carry, $item) {
            return $carry . ucfirst($item);
        });

        return lcfirst($name);
    }
}