<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoSealEntity;

class QiyuesuoSealRepository extends BaseRepository
{
    public function __construct(QiyuesuoSealEntity $entity)
    {
        parent::__construct($entity);
    }

}
