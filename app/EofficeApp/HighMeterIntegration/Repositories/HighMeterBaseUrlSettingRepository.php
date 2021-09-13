<?php
namespace App\EofficeApp\HighMeterIntegration\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\HighMeterIntegration\Entities\HighMeterBaseUrlSettingEntity;

class HighMeterBaseUrlSettingRepository extends BaseRepository
{
    public function __construct(HighMeterBaseUrlSettingEntity $entity)
    {
        parent::__construct($entity);
    }

    public function updateOrCreate($data)
    {
        return $this->entity->updateOrCreate($data);
    }
}
