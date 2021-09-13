<?php

namespace App\EofficeApp\Report\Controllers;

use App\EofficeApp\Base\Controller;
use Illuminate\Http\Request;
use App\EofficeApp\Report\Services\ReportService;
use App\EofficeApp\Report\Requests\ReportRequest;

class ReportController extends Controller {

    public function __construct(
        Request $request,
        ReportService $reportService,
        ReportRequest $reportRequest
    ){
        parent::__construct();
        $this->request = $request;
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->reportService = $reportService;
        $this->reportRequest = $reportRequest;
        $this->formFilter($request,$reportRequest);
    }
	//添加标签
	public function addTag(){
        $result = $this->reportService->addTag($this->request->all());
        return $this->returnResult($result);
    }
    //标签列表
    public function getAllTag(){
    	$result = $this->reportService->getAllTag($this->request->all());
    	return $this->returnResult($result);
    }
    //标签信息
    public function getOneTagList() {
    	$result = $this->reportService->getOneTagList($this->request->all());
    	return $this->returnResult($result);
    }
    //编辑标签
    public function editTag(){
    	return $this->returnResult($this->reportService->editTag($this->request->all()));
    }
    //删除标签
    public function deleteTag($itemId){
    	return $this->returnResult($this->reportService->deleteTag($itemId));
    }
    //数据源列表
    public function  getDatasource(){
    	return $this->returnResult($this->reportService->getDatasourceMultiList($this->request->all()));
    }
    //数据源类型
    public function getDatasourceType(){
    	return $this->returnResult($this->reportService->getDatasourceType($this->request->all()));
    }
    //添加数据源
    public function addDatasource(){
        $param = $this->request->all();
        if(isset($param['datasource_type']) && $param['datasource_type'] == "workflow")
        {
            $permission = app($this->userMenuService)->judgeMenuPermission(5);
            if($permission == "false")
            {
                return $this->returnResult(['code' => ['0x065008', 'report']]);
            }
        }
        if(isset($param['datasource_type']) && $param['datasource_type'] == "notify")
        {
            $permission = app($this->userMenuService)->judgeMenuPermission(131);
            if($permission == "false")
            {
                return $this->returnResult(['code' => ['0x065008', 'report']]);
            }
        }
    	$result = $this->reportService->addDatasource($this->request->all());
    	return $this->returnResult($result);
    }
    public function findDatasource(){
    	$result = $this->reportService->findDatasource($this->request->all());
    	return $this->returnResult($result);
    }
    public function editDatasource(){
        $param = $this->request->all();
        if(isset($param['datasource_type']) && $param['datasource_type'] == "workflow")
        {
            $permission = app($this->userMenuService)->judgeMenuPermission(5);
            if($permission == "false")
            {
                return $this->returnResult(['code' => ['0x065008', 'report']]);
            }
        }
        if(isset($param['datasource_type']) && $param['datasource_type'] == "notify")
        {
            $permission = app($this->userMenuService)->judgeMenuPermission(131);
            if($permission == "false")
            {
                return $this->returnResult(['code' => ['0x065008', 'report']]);
            }
        }
    	return $this->returnResult($this->reportService->editDatasource($this->request->all()));
    }
    public function deleteDatasource($datasourceId){
    	return $this->returnResult($this->reportService->deleteDatasource($datasourceId));
    }
    public function getChartExample(){
    	return $this->returnResult($this->reportService->getChartExample($this->request->all()));
    }
    public function chartList(){
    	return $this->returnResult($this->reportService->getChartMultiList($this->request->all(),$this->own));
    }
    //报表列表
    public function getChartList(){
    	return $this->returnResult($this->reportService->getChartList($this->request->all(),$this->own));
    }
    //查看报表
    public function getChartListPermission(){
    	return $this->returnResult($this->reportService->getChartListPermission($this->request->all(),$this->own));
    }
    //获得报表信息
    public function findChart(){
    	$result = $this->reportService->findChart($this->request->all(),$this->own);
    	return $this->returnResult($result);
    }
    //删除报表
    public function deleteChart($chartId){
    	return $this->returnResult($this->reportService->deleteChart($chartId));
    }
    //添加报表
    public function addChart(){
    	$data  = $this->request->all();
    	$data['create_user'] = isset($this->own['user_id'])?$this->own['user_id']:"";
    	return $this->returnResult($this->reportService->addChart($data));
    }
    //编辑报表
    public function editChart(){
    	return $this->returnResult($this->reportService->editChart($this->request->all()));
    }

    //获得报表信息
    public function getChart(){
    	return $this->returnResult($this->reportService->getChart($this->request->all(),$this->own));
    }

    //数据过滤
    public function getDatasourceFilter(){
    	return $this->returnResult($this->reportService->getDatasourceFilter($this->request->all()));
    }
    //远程数据
   	public function getOriginList(){
   		return $this->returnResult($this->reportService->getOriginList($this->request->all()));
   	}
    //请求自定义报表数据，加载报表图表
    public function getCustomChart(){
        return $this->returnResult($this->reportService->getCustomChart($this->request->all()));
    }
    //保存自定义报表数据
    public function saveCustomData(){
        return $this->returnResult($this->reportService->saveCustomData($this->request->all(), $this->own));
    }
    //编辑自定义报表数据
    public function editCustomData(){
        return $this->returnResult($this->reportService->editCustomData($this->request->all(), $this->own));
    }
     public function getUrlData(){
        return $this->returnResult($this->reportService->getUrlData($this->request->all()));
    }
    public function getImportData(){
        return $this->returnResult($this->reportService->getImportData($this->request->all()));
    }
    public function getGridList()
    {
        return $this->returnResult($this->reportService->getGridList($this->request->all(),$this->own));
    }
}