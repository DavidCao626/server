<?php

namespace App\EofficeApp\LogCenter\Controllers;
use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\LogCenter\Services\ElasticService;
use App\EofficeApp\LogCenter\Facades\LogScheme;
/**
 * Description of LogCenterController
 *
 * @author lizhijun
 */
class LogCenterController extends Controller
{
    private $elasticService;
    private $request;
    private $logRecordsService;
    private $logCenterService;
    public function __construct(
        Request $request,
        ElasticService $elasticService
    )
    {
        parent::__construct();
        $this->request = $request;
        $this->elasticService = $elasticService;
        $this->logRecordsService = 'App\EofficeApp\LogCenter\Services\LogRecordsService';
        $this->logCenterService = 'App\EofficeApp\LogCenter\Services\LogCenterService';
    }
    /**
     * 日志搜索
     * @return array
     */
    public function getLogList()
    {
        return $this->returnResult(app($this->logRecordsService)->lists($this->request->all() , $this->own));
    }
    public function getOneDataLogs()
    {
        return $this->returnResult(app($this->logRecordsService)->getOneDataLogs($this->request->all()));
    }
    /**
     * 用户轨迹
     * @return array
     */
    public function userActivityTrack(){
        return $this->returnResult($this->elasticService->userActivityTrack($this->request->all()));
    }

    /**
     * 模块使用率排行
     * @return array
     */
    public function moduleRank(){
        return $this->returnResult(app($this->logRecordsService)->moduleUseRank($this->request->all()));
    }

    /**
     * 获取系统日志统计
     * @return array
     */
    public function getLogStatistics()
    {
        $param = $this->request->all();
        $result = app($this->logCenterService)->getLogStatistics($param);
        return $this->returnResult($result);
    }

    /**
     * 获取日志详情
     * @return array
     */
    public function getLogDetail(){
        //todo 走一个增删改查汇总的接口 再调getChangeDataByLogId
        return $this->returnResult(app($this->logRecordsService)->getLogDetail($this->request->all()));
    }

    /**
     * 获取日志详情
     * @return array
     */
    public function getLogChange(){
        return $this->returnResult(app($this->logRecordsService)->getChangeDataByRelationId($this->request->all()));
    }

    /**
     * 我的下属
     * @param $userId
     * @return array
     */
    public function getSubordinate($userId) {
        $result = app($this->logRecordsService)->getSubordinate($userId, $this->own);
        return $this->returnResult($result);
    }

    public function getModuleCategory(){
        return $this->returnResult(LogScheme::getModuleConfigByModuleKey($this->request->input('module_key', '')));
    }

    public function getAllModule(){
        //todo 根据type=mine 下发模块  system模块排最前面
        return $this->returnResult(LogScheme::getAllLogModules($this->request->input('type', '')));
    }

    // 无category_key时，返回模块全部的操作类型
    public function getOneCategoryOperations()
    {
        $moduleKey = $this->request->input('module_key', '');
        $categoryKey = $this->request->input('category_key', '');
        $result = $categoryKey ? LogScheme::getOneCategoryOperations($moduleKey, $categoryKey) : LogScheme::getModuleCategoryOperations($moduleKey);

        return $this->returnResult($result);
    }
}
