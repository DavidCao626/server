<?php

namespace App\EofficeApp\Dingtalk\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Dingtalk\Entities\DingtalkOrganizationSyncDepartmentEntity;

class DingtalkOrganizationSyncDepartmentRepository extends BaseRepository
{

    public function __construct(DingtalkOrganizationSyncDepartmentEntity $entity)
    {
        parent::__construct($entity);
    }

    public function truncateTable()
    {
        return $this->entity->truncate();
    }

    public function addDingtalkDepartmentRelation($data)
    {
        // 需要判断该OAId没有被绑定
        if (isset($data['oa_dep_id']) && $this->checkExist($data['oa_dep_id']) == 0) {
            return $this->entity->insert($data);
        }
        return false;
    }
    public function getSyncByDingtalkId($dingtalkId)
    {
        if (!empty($dingtalkId)) {
            $res = $this->entity->where(['dingtalk_dep_id' => $dingtalkId])->first();
            if ($res) {
                return $res->toArray();
            }
        }
        return '';
    }

    public function getSyncByOAId($OAId)
    {
        if (!empty($OAId)) {
            return $this->entity->where(['oa_dep_id' => $OAId])->count();
        }
        return 0;
    }

    public function checkExist($OAId)
    {
        return $this->entity->where(['oa_dep_id' => $OAId])->count();
    }

    // 根据OAId删除记录
    public function deleteByOAId($OAId)
    {
        return $this->entity->where(['oa_dep_id' => $OAId])->delete();
    }

}
