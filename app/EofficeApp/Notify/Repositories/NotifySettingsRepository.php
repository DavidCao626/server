<?php

namespace App\EofficeApp\Notify\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Notify\Entities\NotifySettingsEntity;

class NotifySettingsRepository extends BaseRepository
{
    public function __construct(NotifySettingsEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getExpiredVisibleSettings()
    {
        return $this->entity->get();
    }

//    public function setExpiredVisibleSettings($data)
//    {
//        foreach($data as $key => $value){
//            $this->entity->where('setting_key', $key)->update()
//        }
//    }


}
