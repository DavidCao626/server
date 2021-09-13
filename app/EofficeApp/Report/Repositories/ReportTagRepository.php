<?php

namespace App\EofficeApp\Report\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Report\Entities\ReportTagEntity;
use DB;

class ReportTagRepository extends BaseRepository {

    public function __construct(ReportTagEntity $entity) {
        parent::__construct($entity);
    }
    //标签列表
    public function getAllTag($data=array()){
    	return $this->getAllSysTag($data);
    	/*
    	$default = [
    			'fields' => ["*"],
    			'page' => 0,
    			'limit' => config('eoffice.pagesize'),
    			'search' => [],
    			'order_by' => ['tag_id' => 'asc']
    	];
    	$param = array_merge($default, array_filter($data));
    	$query = $this->entity;
    	return $query->select($param['fields'])
    	->wheres($param['search'])
    	->orders($param['order_by'])
    	->parsePage($param['page'], $param['limit'])
    	->get()
    	->toArray();
    	*/
    }
    
    //获得系统标签
    public function getAllSysTag($data=array()){
    	$result = [];
    	$db_query = DB::select('select   tag_id,tag_name as tag_title  from tag order by tag_id ');
    	foreach($db_query as $v){
    		$result[] = ['tag_id'=>$v->tag_id,'tag_title'=>$v->tag_title];
    	}
		return $result;
    }
    
    //标签数量
    public function getAllTagTotal($data){
    	$default = ['search' => []];
    	$param = array_merge($default, array_filter($data));
    	$query = $this->entity;
    	return $query->wheres($param['search'])->count();
    }
    
    //标签信息
    public function findTag($id) {
    	$infoResult = $this->entity->where('tag_id', $id)
    	->get()
    	->toArray();
    	return isset($infoResult[0])?$infoResult[0]:array();
    }
    //查找标签
    public function getTag($key,$value){
    	$infoResult = $this->entity->where($key,$value)
    	->get()
    	->toArray();
    	return isset($infoResult[0])?$infoResult[0]:array();
    }
    //获得标签名
    public function getTagName($ids){
    	if(empty($ids)) return "";
    	$tags = $this->getTagArray();
    	$result = "";
    	$id_arr = explode(',',trim($ids));
    	foreach($id_arr as $item){
    		if(isset($tags[$item])){
    			$result .= $tags[$item].",";
    		}
    	}
    	return trim($result,",");
    }
    //标签数组
    public function getTagArray(){
    	static $const = null;
    	static $tag_result = array();
    	if(!is_null($const)) return $tag_result;
    	$data = $this->getAllTag();
    	foreach($data as $v){
    		$tag_result[$v['tag_id']] = $v['tag_title'];
    	}
    	$const =1;
    	return $tag_result;
    }
}