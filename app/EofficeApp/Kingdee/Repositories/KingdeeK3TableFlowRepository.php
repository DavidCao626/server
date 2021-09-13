<?php
namespace App\EofficeApp\Kingdee\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Kingdee\Entities\KingdeeK3TableFlowEntity;

class KingdeeK3TableFlowRepository extends BaseRepository
{
    public function __construct(KingdeeK3TableFlowEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 新增账套信息
     */
    public function addK3Flow($data)
    {
        // 先判断该关联关系是否存在
        if(empty($data['k3table_id']) || empty($data['flow_id']) || empty($data['description'])){
            return false;
        }
        $param = [
            ['k3table_id','=',$data['k3table_id']],
            ['flow_id','=',$data['flow_id']]
        ];
        $count = $this->entity->where($param)->count();
        if($count == 0){
            return $this->entity->create($data)->toArray();
        }else{
            return false;
        }
        
    }

    public function getK3flow($kingdeeTableFlowId)
    {
        if(empty($kingdeeTableFlowId)){
            return false;
        }
        $res = $this->entity->where('kingdee_table_flow_id',$kingdeeTableFlowId)->with(["flow" => function ($query) {
            $query->select("flow_id","flow_name");
        }])->first();
        return $res ? $res->toArray() : '';
    }


    public function updateK3Flow($kingdeeTableFlowId,$date)
    {
        if(empty($kingdeeTableFlowId)){
            return false;
        }
        $res = $this->entity->where('kingdee_table_flow_id',$kingdeeTableFlowId)->update($date);
        return $res;
    }

    // 判断该单据与流程关联是否存在
    public function checkK3FlowExist($kingdeeTableFlowId)
    {
        if(empty($kingdeeTableFlowId)){
            return false;
        }
        $res = $this->entity->where('kingdee_table_flow_id',$kingdeeTableFlowId)->count();
        return $res > 0 ? true : flase;
    }

    // 获取单据与流程关联列表
    public function getK3FlowList($param = [])
    {
        $entity = $this->entity;
        if(!empty($param['search'])){
            foreach($param['search'] as $key => $value){
                if(is_array($value) && count($value) == 2){
                    $entity = $entity->where($key,$value[0],$value[1]);
                }
            }
        }
        
        if(isset($param['return_type']) && $param['return_type'] == 'count'){
            return $entity->count();
        }else{
            if(isset($param['page']) && isset($param['limit'])){
                $entity = $entity->forPage($param['page'], $param['limit']);
            }
            $res = $entity
            ->with(["flow" => function ($query) {
                $query->select("flow_id","flow_name");
            }])->with(["k3table" => function ($query) {
                $query->select("kingdee_table_id","name");
            }])->get();
            
            if($res){
                return $res->toArray();
            }
        }
        
        
    }

    public function deleteK3Flow($k3FlowId)
    {
        if(empty($k3FlowId)){
            return false;
        }
        $this->entity->where('kingdee_table_flow_id',$k3FlowId)->delete();
    }

    // 单据id与流程id可以确定唯一的关联
    public function getInfoByTableAndFlow($tableId,$flowId)
    {
        if(empty($tableId) || empty($flowId)){
            return [];
        }
        return $this->entity->where('flow_id',$flowId)->where('k3table_id',$tableId)->first();
    }

    // 删除该单据下的所有关联关系
    public function deleteK3FlowByTableId($tableId)
    {
        if(empty($tableId)){
            return false;
        }
        return $this->entity->where('k3table_id',$tableId)->delete();
    }


}
