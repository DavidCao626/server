<?php

namespace App\EofficeApp\Report\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Report\Entities\ReportChartEntity;

class ReportChartRepository extends BaseRepository {

    public function __construct(ReportChartEntity $entity) {
        parent::__construct($entity);
    }
    public $user_info;
    //列表
    public function getAllList($data=array()){
    	if(!empty($data['limit_permission'])){
    		if(isset($data['returnType'])&&$data['returnType']=='count'){
    			return $this->getAllListPermission($data,'count');
    		}else{
    			return $this->getAllListPermission($data);
    		}
    	}
    	$default = [
    			'fields' => ["*"],
    			'page' => 0,
    			'limit' => config('eoffice.pagesize'),
    			'search' => [],
    			'order_by' => ['chart_id' => 'desc'],
                'returnType' => 'array'
    	];
    	$param = array_merge($default, array_filter($data));
    	$query = $this->entity;
        // 只显示某个流程的报表列表
        $flowId = isset($param["search"]["flow_id"]) ? $param["search"]["flow_id"][0] : false;
        if ($flowId && !is_array($flowId)) {
            unset($param["search"]["flow_id"]);
            // 模块工厂查看报表菜单需要根据当前流程筛选报表列表
            $query = $query->leftJoin('report_datasource', 'report_datasource.datasource_id', '=', 'report_chart.datasource_id')
                           ->where('report_datasource.datasource_type', 'workflow')
                           ->where('report_datasource.custom_report', '{"workflowID":"'.$flowId.'"}');
        }
        $query = $query->select($param['fields'])
                       ->wheres($param['search'])
                       ->orders($param['order_by']);
        if ($param['returnType'] == 'array') {
            return $query->parsePage($param['page'], $param['limit'])->get()->toArray();
        } elseif ($param['returnType'] == 'count') {
            return $query->get()->count();
        } else {
            return $query->parsePage($param['page'], $param['limit'])->get();
        }
    }
    //权限控制
    public function getAllListPermission($data=array(),$num=null){
    	$default = [
    			'fields' => ["*"],
    			'page' => 0,
    			'limit' => config('eoffice.pagesize'),
    			'search' => [],
    			'order_by' => ['chart_id' => 'desc']
    	];
    	$param = array_merge($default, array_filter($data));
    	$query = $this->entity;
        // 只显示某个流程的报表列表
        $flowId = isset($param["search"]["flow_id"]) ? $param["search"]["flow_id"][0] : false;
        if ($flowId && !is_array($flowId)) {
            unset($param["search"]["flow_id"]);
            // 模块工厂查看报表菜单需要根据当前流程筛选报表列表
            $query = $query->leftJoin('report_datasource', 'report_datasource.datasource_id', '=', 'report_chart.datasource_id')
                           ->where('report_datasource.datasource_type', 'workflow')
                           ->where('report_datasource.custom_report', '{"workflowID":"'.$flowId.'"}');
        }
    	$result = array();
    	$info =  $query->select($param['fields'])
    	->wheres($param['search'])
    	->orders($param['order_by'])->get()
    	->toArray();
    	if(empty($info)) $info = array();
    	if(empty($this->user_info)){
    		$info = array();
    	}else{
    		foreach($info as $k =>$v){
    			if(!$this->hasPermission($this->user_info,$v)){
    				unset($info[$k]);
    			}
    		}
    	}
    	if(!empty($num)) return count($info);
    	if(!empty($param['page'])){
    		$info = array_slice($info,($param['page']-1)*$param['limit'],$param['limit']);
    	}
    	$result_info = [];
    	foreach($info as $v){
    		$result_info[] = $v;
    	}
    	return $result_info;
    }
    //数量
    public function getTotalNum($data){
        $data['returnType'] = 'count';
        return $this->getAllList($data);
    }
    function getTotalNumPermission($data){
    	return $this->getAllListPermission($data,'num');
    }
    //删除报表
    public function deleteChart($chart_id){
    	$data = explode(",",trim($chart_id,","));
    	$key = array_values($data);
    	foreach($key as $item){
    		$this->deleteByWhere(['chart_id' => [$item]]);
    	}
    	return true;
    }
    //getChart
    public function getChart($key,$value,$return=""){
    	$infoResult = $this->entity->where($key,$value)->get()->toArray();
        if ($return == "array") {
            return $infoResult;
        }
    	return isset($infoResult[0])?$infoResult[0]:array();
    }

    //获得报表数据
    public function findItem($id){
    	$result = array();
    	$infoResult = $this->entity->where('chart_id',$id)->get()->toArray();
    	if(isset($infoResult[0])){
    		$result = $infoResult[0];
    		//from,to
    		$filter = array();
    		$filter['createTime'] = 'createTime';
    		$filter['overTime'] = 'overTime';
    		if(isset($result['chart_search'])){
    			foreach($filter as $k =>$v){
    				$result['chart_search'] = str_replace($k,$v,$result['chart_search']);
    			}
    		}
    	}
    	return $result;
    }

    //左侧报表列表
    public function getChartMultiList(&$tagRepository,$data=array(),$user_info=array())
    {
    	$resultList = [];
    	$list = $this->getAllList($data);
    	$item_array = [];
    	foreach($list as $k =>$v){
    		if(!$this->hasPermission($user_info,$v)) continue;
    		if(!empty($v['chart_tag'])){
    			$tag_info = explode(",",$v['chart_tag']);
    			foreach($tag_info as $tag){
    				if(!isset($item_array[$tag])){
    					$item_array[$tag] = array();
    					$item_array[$tag]['chart_list']=array();
    				}
    				$item_array[$tag]['chart_list'][] = $v;
    			}
    		}else{
    			$item_array['else']['chart_list'][] = $v;
    		}
    	}
    	$tags = $tagRepository->getAllTag();
    	foreach($tags as $k =>$v){
    		if(isset($item_array[$v['tag_id']])){
    			$item_array[$v['tag_id']] = array_merge($item_array[$v['tag_id']],$v);
    			$resultList[] = $item_array[$v['tag_id']];
    			unset($item_array[$v['tag_id']]);
    		}
    	}
    	if(!empty($item_array)){
    		$array = [];
    		$array['tag_title'] = '其他';
    		$array['chart_list'] = [];
    		foreach($item_array as $v){
    			foreach($v['chart_list'] as $val){
    				$array['chart_list'][] = $val;
    			}
    		}
    		$resultList[] = $array;
    	}
    	return $resultList;
    }
    //权限
    public function hasPermission(&$user_info,&$chart_info){
    	$res = false;
    	if(!empty($user_info['user_id'])&&isset($chart_info['chart_permission_user'])){
    		$item_info = array($chart_info['chart_permission_user'],$chart_info['chart_permission_dept'],$chart_info['chart_permission_role']);
            if (!isset($user_info['dept_id'])) {
                $user_info['dept_id'] = '';
            }
            if (!isset($user_info['role_id'])) {
                $user_info['role_id'] = '';
            }
    		$data = array($user_info['user_id'],$user_info['dept_id'],$user_info['role_id']);
    		foreach($data as $k =>$v){
    			$info = $item_info[$k];
    			if(!empty($info)){
    				if($info=="DEPT_ALL"){
    					$res = true;
    					break;
    				}else{
    					$arr =  explode(',',$info);
    					if(!is_array($v)) $v = explode(',',$v);
    					foreach($v as $val){
    						if(in_array($val,$arr)) return true;
    					}
    				}
    			}
    		}
    	}
    	return $res;
    }


}
