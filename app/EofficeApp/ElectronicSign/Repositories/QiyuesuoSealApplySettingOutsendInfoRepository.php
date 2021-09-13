<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoSealApplySettingOutsendInfoEntity;

class QiyuesuoSealApplySettingOutsendInfoRepository extends BaseRepository
{
    public function __construct(QiyuesuoSealApplySettingOutsendInfoEntity $entity)
    {
        parent::__construct($entity);
    }
}
