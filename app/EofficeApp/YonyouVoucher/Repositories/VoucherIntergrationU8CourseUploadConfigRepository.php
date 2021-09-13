<?php
namespace App\EofficeApp\YonyouVoucher\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\YonyouVoucher\Entities\VoucherIntergrationU8CourseUploadConfigEntity;

class VoucherIntergrationU8CourseUploadConfigRepository extends BaseRepository
{
    public function __construct(VoucherIntergrationU8CourseUploadConfigEntity $entity)
    {
        parent::__construct($entity);
    }

}
