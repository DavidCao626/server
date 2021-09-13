<?php

namespace App\EofficeApp\Dingtalk\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Dingtalk\Entities\DingtalkOrganizationSyncConfigEntity;

class DingtalkOrganizationSyncConfigRepository extends BaseRepository
{

    public function __construct(DingtalkOrganizationSyncConfigEntity $entity)
    {
        parent::__construct($entity);
    }

    public function truncateTable()
    {
        return $this->entity->truncate();
    }

    public function saveSyncConfig($data)
    {
        // $this->count();
        $this->entity->truncate();
        return $this->entity->insert($data);
    }
    public function getSyncConfig()
    {
        $res = $this->entity->first();
        // $res = $this->entity->get();
        // if ($res) {
        //     return $res->toArray();
        // }
        return $res;
    }

    public function count()
    {
        return $this->entity->count();
    }

}
