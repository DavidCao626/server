<?php
namespace App\EofficeApp\YonyouVoucher\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\YonyouVoucher\Entities\VoucherIntergrationBaseInfoEntity;

class VoucherIntergrationBaseInfoRepository extends BaseRepository
{
    public function __construct(VoucherIntergrationBaseInfoEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getInfoByDatabaseConfig($databaseIds)
    {
        $info = $this->entity->whereIn('database_config', $databaseIds)->get()->toArray();
        return $info;
    }
}
