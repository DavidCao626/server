<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoSettingOperationInfoEntity;

class QiyuesuoSettingOperationInfoRepository extends BaseRepository
{
    public function __construct(QiyuesuoSettingOperationInfoEntity $entity)
    {
        parent::__construct($entity);
    }

    public function index($param = [])
    {
    }
}
