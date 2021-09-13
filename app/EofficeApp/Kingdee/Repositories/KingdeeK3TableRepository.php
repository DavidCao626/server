<?php
namespace App\EofficeApp\Kingdee\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Kingdee\Entities\KingdeeK3TableEntity;

class KingdeeK3TableRepository extends BaseRepository
{
    public function __construct(KingdeeK3TableEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 新增单据接口
     */
    public function addTable($data)
    {
        if(empty($data['name']) || empty($data['web_api_content']) || empty($data['account_id']) || empty($data['form_id'])){
            return false;
        }
        return $this->entity->create($data);
    }
    /**
     * 获取单据列表
     */
    public function getTableList($param = [])
    {
        $entity = $this->entity;
        if(!empty($param['search'])){
            $entity = $entity->wheres($param['search']);
        }
        if(isset($param['return_type']) && $param['return_type'] == 'count'){
            return $entity->count();
        }
        if(isset($param['page']) && isset($param['limit'])){
            $entity = $entity->forPage($param['page'], $param['limit']);
        }
        $entity = $entity->with(['account' => function($query){
            $query->select('kingdee_account_id','name');
        }]);
        return $entity->orderBy('updated_at','desc')->get()->toArray();
    }

    /**
     * 获取单据信息
     */
    public function getTableDetail($tableId)
    {
        if(!$tableId){
            return false;
        }
        return $this->entity->where('kingdee_table_id',$tableId)->first();
    }

    /**
     * 删除单个单据信息
     */
    public function deleteTable($tableId)
    {
        if(!$tableId){
            return false;
        }
        return $this->entity->where('kingdee_table_id',$tableId)->delete();
    }

    /**
     * 更新单个单据信息
     */
    public function updateTable($tableId,$data)
    {
        if(!$tableId){
            return false;
        }
        return $this->entity->where('kingdee_table_id',$tableId)->update($data);
    }

    /**
     * 根据单据id获取单据与账套信息
     */
    public function getK3AccountByTableId($tableId)
    {
        if(!$tableId){
            return false;
        }
        $entity = $this->entity->where('kingdee_table_id',$tableId);
        $entity = $entity->with(['account' => function($query){
            $query->select('kingdee_account_id','name');
        }]);
        return $entity->first();
    }
}
