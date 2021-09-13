<?php

namespace App\EofficeApp\Dingtalk\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Dingtalk\Entities\DingtalkOrganizationSyncRoleEntity;

class DingtalkOrganizationSyncRoleRepository extends BaseRepository
{

    public function __construct(DingtalkOrganizationSyncRoleEntity $entity)
    {
        parent::__construct($entity);
    }

    public function truncateTable()
    {
        return $this->entity->truncate();
    }

    public function addDingtalkRoleRelation($data)
    {
        // 需要判断该OAId没有被绑定
        if (isset($data['oa_role_id']) && $this->checkExist($data['oa_role_id']) == 0) {
            return $this->entity->insert($data);
        }
        return false;
    }
    public function getSyncByDingtalkId($dingtalkId)
    {
        if (!empty($dingtalkId)) {
            $res = $this->entity->where(['dingtalk_role_id' => $dingtalkId])->first();
            if ($res) {
                return $res->toArray();
            }
        }
        return '';
    }
    public function getSyncByOAId($OAId)
    {
        if (!empty($OAId)) {
            return $this->entity->where(['oa_role_id' => $OAId])->count();
        }
        return 0;
    }
    public function checkExist($OAId)
    {
        return $this->entity->where(['oa_role_id' => $OAId])->count();
    }

    // 根据OAId删除记录
    public function deleteByOAId($OAId)
    {
        return $this->entity->where(['oa_role_id' => $OAId])->delete();
    }

}
