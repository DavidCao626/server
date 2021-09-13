<?php
namespace App\EofficeApp\Kingdee\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Kingdee\Entities\KingdeeK3CloudApiEntity;

class KingdeeK3CloudApiRepository extends BaseRepository
{
    public function __construct(KingdeeK3CloudApiEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 新增cloudApi接口
     */
    public function addCloudApiData($data)
    {
        if(empty($data['name']) || empty($data['form_id']) || empty($data['field']) || empty($data['data_type'])){
            return false;
        }
        return $this->entity->create($data);
    }
    /**
     * 获取cloudApi列表
     */
    public function getCloudApiDataList($param = [])
    {
        $entity = $this->entity;
        if(!empty($param['search'])){
            if(!is_array($param['search'])){
                $param['search'] = json_decode($param['search']);
            }
            $entity = $entity->wheres($param['search']);
        }
        // $entity = $entity->with(["account" => function ($query) {
        //         $query->select("kingdee_account_id","name");
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
     * 获取cloudApi信息
     */
    public function getCloudApiData($dataId)
    {
        if(!$dataId){
            return false;
        }
        return $this->entity->where('kingdee_cloud_api_id',$dataId)->first();
    }

    /**
     * 删除单个cloudApi信息
     */
    public function deleteCloudApiData($dataId)
    {
        if(!$dataId){
            return false;
        }
        return $this->entity->where('kingdee_cloud_api_id',$dataId)->delete();
    }

    /**
     * 更新单个cloudApi信息
     */
    public function updateCloudApiData($dataId,$data)
    {
        if(!$dataId){
            return false;
        }
        return $this->entity->where('kingdee_cloud_api_id',$dataId)->update($data);
    }

    /**
     * 根据form_id获取cloudAPI的id
     */
    public function getByFormId($formId)
    {
        if(empty($formId)){
            return [];
        }
        $res = $this->entity->where('form_id',$formId)->first();
        if($res){
            return $res->toArray();
        }
        return [];
        
    }
}
