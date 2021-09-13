<?php
namespace App\EofficeApp\YonyouVoucher\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\YonyouVoucher\Entities\VoucherIntergrationU8CourseDatabaseConfigEntity;

class VoucherIntergrationU8CourseDatabaseConfigRepository extends BaseRepository
{
    public function __construct(VoucherIntergrationU8CourseDatabaseConfigEntity $entity)
    {
        parent::__construct($entity);
    }

}
