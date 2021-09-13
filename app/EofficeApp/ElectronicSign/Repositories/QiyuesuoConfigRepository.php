<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoConfigEntity;

class QiyuesuoConfigRepository extends BaseRepository
{
    public function __construct(QiyuesuoConfigEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getItem($key)
    {
        if (is_string($key)) {
            return $this->entity->where('paramKey', $key)->get(['paramKey', 'paramValue']);
        } else {
            return $this->entity->whereIn('paramKey', $key)->get(['paramKey', 'paramValue']);
        }
    }
}
