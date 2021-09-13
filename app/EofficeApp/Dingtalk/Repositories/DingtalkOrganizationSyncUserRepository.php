<?php

namespace App\EofficeApp\Dingtalk\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Dingtalk\Entities\DingtalkOrganizationSyncUserEntity;

class DingtalkOrganizationSyncUserRepository extends BaseRepository
{

    public function __construct(DingtalkOrganizationSyncUserEntity $entity)
    {
        parent::__construct($entity);
    }

    public function truncateTable()
    {
        return $this->entity->truncate();
    }

    public function addDingtalkUserRelation($data)
    {
        // 需要判断该OAId没有被绑定
        if (isset($data['oa_user_id']) && $this->checkExist($data['oa_user_id']) == 0) {
            return $this->entity->insert($data);
        }
        return false;
    }

    public function getSyncByDingtalkId($dingtalkId)
    {
        if (!empty($dingtalkId)) {
            $res = $this->entity->where(['dingtalk_user_id' => $dingtalkId])->first();
            if ($res) {
                return $res->toArray();
            }
        }
        return '';
    }
    public function getSyncByOAId($OAId)
    {
        if (!empty($OAId)) {
            return $this->entity->where(['oa_user_id' => $OAId])->first();
        }
        return 0;
    }
    public function checkExist($OAId)
    {
        return $this->entity->where(['oa_user_id' => $OAId])->count();
    }

    // 根据OAId删除记录
    public function deleteByOAId($OAId)
    {
        return $this->entity->where(['oa_user_id' => $OAId])->delete();
    }
    // 根据OAId更新记录不存在则新增
    public function updateOrInsertByOAId($OAId,$data)
    {
        $sync = $this->getSyncByOAId($OAId);
        if($sync){
            return $this->entity->where(['oa_user_id' => $OAId])->update($data);
        }else{
            // 不存在则新增关联
            return $this->addDingtalkUserRelation($data);
        }
        
    }
}
