<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoCategoryEntity;

class QiyuesuoCategoryRepository extends BaseRepository
{
    public function __construct(QiyuesuoCategoryEntity $entity)
    {
        parent::__construct($entity);
    }

}
