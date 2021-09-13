<?php

namespace App\EofficeApp\SystemSms\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\SystemSms\Entities\SystemSmsEntity;
use DB;
/**
 * 内部消息 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class SystemSmsRepository extends BaseRepository {

    public function __construct(SystemSmsEntity $entity) {
        parent::__construct($entity);
    }

    public function getSmsType() { 
    	
        return $this->entity->select(DB::raw('distinct  sms_menu as sms_menu'))
                        ->get()->toArray();
    }
    
    public function getSmsDetailByIds($smsIds, $fields = ['*'])
    {
        return $this->entity->select($fields)->whereIn('sms_id',$smsIds)->get();
    }
}
