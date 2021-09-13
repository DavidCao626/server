<?php

namespace App\EofficeApp\News\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\News\Entities\NewsSettingsEntity;

class NewsSettingsRepository extends BaseRepository
{
    public function __construct(NewsSettingsEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getSettings()
    {
        return $this->entity->get();
    }

    public function saveSettings($data)
    {
//        foreach($data as $key => $value){
//            $this->entity->where('setting_key', $key)->update();
//        }
        foreach ($data as $key => $value) {
            $result = $this->entity->where('setting_key', $key)->update(['setting_value'=>$value]);
        }
        return $result;
    }


}
