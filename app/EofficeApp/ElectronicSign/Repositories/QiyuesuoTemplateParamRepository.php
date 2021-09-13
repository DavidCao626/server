<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoTemplateParamEntity;

class QiyuesuoTemplateParamRepository extends BaseRepository
{
    public function __construct(QiyuesuoTemplateParamEntity $entity)
    {
        parent::__construct($entity);
    }

}
