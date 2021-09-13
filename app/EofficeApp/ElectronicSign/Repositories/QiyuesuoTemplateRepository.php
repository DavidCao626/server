<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoTemplateEntity;

class QiyuesuoTemplateRepository extends BaseRepository
{
    public function __construct(QiyuesuoTemplateEntity $entity)
    {
        parent::__construct($entity);
    }

}
