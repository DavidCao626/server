<?php

namespace App\EofficeApp\XiaoE\Controllers;

use App\Utils\XiaoE\Service;
use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\XiaoE\Services\XiaoEService;
use App\EofficeApp\XiaoE\Services\SystemService;

class XiaoEController extends Controller
{
    private $xiaoEService;
    private $systemService;

    public function __construct(Request $request, XiaoEService $xiaoEService, SystemService $systemService)
    {
        parent::__construct();
        $this->request = $request;
        $this->xiaoEService = $xiaoEService;
        $this->systemService = $systemService;
        Service::$own = $this->own;
    }

    public function getDictSource($method)
    {
        return $this->xiaoEService->getDictSource($method, $this->request->all());
    }

    public function boot($method)
    {
        return $this->returnResult($this->xiaoEService->boot($method, $this->request->all(), $this->own));
    }

    public function check($method)
    {
        return $this->xiaoEService->check($method, $this->request->all());
    }

    public function initData($method)
    {
        return $this->xiaoEService->initData($method, $this->request->all());
    }

    /**
     * 二次开发字典
     * @param $method
     * @return array
     */
    public function extendGetDictSource($module, $method)
    {
        return $this->xiaoEService->getDictSource($method, $this->request->all(), $module);
    }

    /**
     * 二次开发意图处理
     * @param $method
     * @return \App\EofficeApp\Base\json
     */
    public function extendBoot($module, $method)
    {
        return $this->returnResult($this->xiaoEService->boot($method, $this->request->all(), $this->own, $module));
    }

    /**
     * 二次开发验证
     * @param $method
     * @return array
     */
    public function extendCheck($module, $method)
    {
        return $this->xiaoEService->check($method, $this->request->all(), $module);
    }

    public function extendInitData($module, $method)
    {
        return $this->xiaoEService->initData($method, $this->request->all(), $module);
    }

    /**
     * 小e后台生成app_id和app_secret
     * @return mixed
     */
    public function authorise()
    {
        return $this->returnResult($this->systemService->authorise($this->request->all(), $this->own));
    }

    /**
     * 需要配置的意图
     * @return \App\EofficeApp\Base\json
     */
    public function getConfigIntention()
    {
        return $this->returnResult($this->systemService->getConfigIntention($this->request->all()));
    }

    /**
     * 获取某个意图的详细配置信息
     * @param $key
     * @return \App\EofficeApp\Base\json
     */
    public function getIntenTionDetail($key)
    {
        return $this->returnResult($this->systemService->getIntentionDetail($key));
    }

    /**
     * 更新某个意图params
     * @return \App\EofficeApp\Base\json
     */
    public function updateIntentionParams()
    {
        return $this->returnResult($this->systemService->updateIntentionParams($this->request->all()));
    }

    /**
     * 获取appid和appSecret
     * @return \App\EofficeApp\Base\json
     */
    public function getSecretInfo()
    {
        return $this->returnResult($this->systemService->getSecretInfo());
    }

    /**
     * 更新秘钥信息
     * @return \App\EofficeApp\Base\json
     */
    public function updateSecretInfo()
    {
        return $this->returnResult($this->systemService->updateSecretInfo($this->request->all()));
    }

    /**
     * 同步字典
     * @return \App\EofficeApp\Base\json
     */
    public function syncDictData()
    {
        return $this->returnResult($this->systemService->syncDictData());
    }

    /**
     * 配置域名时测试api
     * @return \App\EofficeApp\Base\json
     */
    public function testApi()
    {
        return $this->returnResult('success');
    }

    /**
     * 查询监控的意图
     */
    public function getMonitoringList()
    {
        return $this->returnResult($this->systemService->getMonitoringList($this->request->all()));
    }

    /**
     * 获取监控报表的数据和配置
     * @return \App\EofficeApp\Base\json
     */
    public function getMonitoringChartConfig()
    {
        return $this->returnResult($this->systemService->getMonitoringChartConfig($this->request->all()));
    }
}