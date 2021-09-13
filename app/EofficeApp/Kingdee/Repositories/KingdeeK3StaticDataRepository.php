<?php
namespace App\EofficeApp\Kingdee\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Kingdee\Entities\KingdeeK3StaticDataEntity;

class KingdeeK3StaticDataRepository extends BaseRepository
{
    public function __construct(KingdeeK3StaticDataEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 新增静态数据接口
     */
    public function addStaticData($data)
    {
        if(empty($data['name']) || empty($data['type']) || empty($data['data'])){
            return false;
        }
        return $this->entity->create($data);
    }
    /**
     * 获取静态数据列表
     */
    public function getStaticDataList($param = [])
    {
        $entity = $this->entity;
        if(!empty($param['search'])){
            if(!is_array($param['search'])){
                $param['search'] = json_decode($param['search']);
            }
            $entity = $entity->wheres($param['search']);
        }
        // $entity = $entity->with(["table" => function ($query) {
        //         $query->select("kingdee_table_id","name");
        // }]);
        if(isset($param['return_type']) && $param['return_type'] == 'count'){
            return $entity->count();
        }
        if(isset($param['page']) && isset($param['limit'])){
            $entity = $entity->forPage($param['page'], $param['limit']);
        }
        return $entity->get()->toArray();
    }

    /**
     * 获取静态数据信息
     */
    public function getStaticData($dataId)
    {
        if(!$dataId){
            return false;
        }
        return $this->entity->where('kingdee_static_data_id',$dataId)->first();
    }

    /**
     * 删除单个静态数据信息
     */
    public function deleteStaticData($dataId)
    {
        if(!$dataId){
            return false;
        }
        return $this->entity->where('kingdee_static_data_id',$dataId)->delete();
    }

    /**
     * 更新单个静态数据信息
     */
    public function updateStaticData($dataId,$data)
    {
        if(!$dataId){
            return false;
        }
        return $this->entity->where('kingdee_static_data_id',$dataId)->update($data);
    }

    /**
     * 根据单据id删除静态数据信息
     */
    // public function deleteStaticDataByTableId($tableId)
    // {
    //     if(!$tableId){
    //         return false;
    //     }
    //     return $this->entity->where('table_id',$tableId)->delete();
    // }
}
