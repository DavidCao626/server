<?php
namespace App\EofficeApp\Kingdee\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Kingdee\Entities\KingdeeK3LogEntity;

class KingdeeK3LogRepository extends BaseRepository
{
    public function __construct(KingdeeK3LogEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 新增日志接口
     */
    public function addLog($data)
    {
        if(empty($data['table_id']) || empty($data['flow_id']) || empty($data['origin_data'])){
            return false;
        }
        return $this->entity->create($data);
    }
    /**
     * 获取k3日志列表
     */
    public function getLogList($param = [])
    {
        if(isset($param['return_type']) && $param['return_type'] == 'count'){
            return $this->entity->count();
        }
        $entity = $this->entity;
        if(isset($param['page']) && isset($param['limit'])){
            $entity = $entity->forPage($param['page'], $param['limit']);
        }
        $entity = $entity
            ->with(["flow" => function ($query) {
                $query->select("flow_id","flow_name");
            }])->with(["k3table" => function ($query) {
                $query->select("kingdee_table_id","name");
            }])->orderBy('created_at','desc')->get();
        return $entity->toArray();
    }

    /**
     * 获取日志信息
     */
    public function getLogDetail($logId)
    {
        if(!$logId){
            return false;
        }
        $entity = $this->entity->where('kingdee_k3_log_id',$logId)
            ->with(["flow" => function ($query) {
                $query->select("flow_id","flow_name");
            }])->with(["k3table" => function ($query) {
                $query->select("kingdee_table_id","name");
            }])->get();
        return $entity->first();
    }

    public function deleteLogByTableId($tableId)
    {
        if(empty($tableId)){
            return false;
        }
        return $this->entity->where('table_id',$tableId)->delete();
    }

}
