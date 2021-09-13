<?php
namespace App\EofficeApp\YonyouVoucher\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\YonyouVoucher\Entities\VoucherIntergrationU8CourseSelectConfigEntity;

class VoucherIntergrationU8CourseSelectConfigRepository extends BaseRepository
{
    public function __construct(VoucherIntergrationU8CourseSelectConfigEntity $entity)
    {
        parent::__construct($entity);
    }

}
