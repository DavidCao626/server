<?php

namespace App\EofficeApp\Dingtalk\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Dingtalk\Entities\DingtalkLogsEntity;

/**
 * 企业号token资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class DingtalkLogsRepository extends BaseRepository {

    public function __construct(DingtalkLogsEntity $entity) {
        parent::__construct($entity);
    }

    public function dingtalkLogsGet($param)
    {
        $result = $this->entity->forPage($param['page'], $param['limit'])->orderBy('created_at', 'desc')->get();
        return $result;
    }
    public function dingtalkLogsCount()
    {
        $result = $this->entity->count();
        return $result;
    }

    public function deleteDingtalkLog($id)
    {
       return $this->entity->where("log_id",$id)->delete();
    }

    
 

}
