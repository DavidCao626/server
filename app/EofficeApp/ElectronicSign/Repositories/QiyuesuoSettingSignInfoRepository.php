<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoSettingSignInfoEntity;

class QiyuesuoSettingSignInfoRepository extends BaseRepository
{
    public function __construct(QiyuesuoSettingSignInfoEntity $entity)
    {
        parent::__construct($entity);
    }

    public function index($param = [])
    {
    }
}
