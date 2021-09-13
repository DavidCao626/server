<?php

namespace App\EofficeApp\Dingtalk\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Dingtalk\Entities\DingtalkOrganizationSyncExceptionEntity;

class DingtalkOrganizationSyncExceptionRepository extends BaseRepository
{
    public function __construct(DingtalkOrganizationSyncExceptionEntity $entity)
    {
        parent::__construct($entity);
    }

    public function addException($data, $type)
    {
        if (!empty($data)) {
            // 填入必要的type信息
            foreach ($data as $index => $id) {
                $temp['id_type']   = $type;
                $temp['except_id'] = $id;
                $insertData[]      = $temp;
            }
            // $result = $this->entity->insertData($insertData);// 报找不到方法错误
            // $result = $this->insertData($insertData);
            $result = $this->entity->insert($insertData);
            return $result;
        }
    }

    public function getException($type = '')
    {
        $enty = $this->entity;
        if (!empty($type)) {
            $enty = $enty->where(['id_type' => $type]);
        }
        $result = $enty->get();
        return $result->toArray();
    }

    public function truncateTable()
    {
        return $this->entity->truncate();
    }

}
