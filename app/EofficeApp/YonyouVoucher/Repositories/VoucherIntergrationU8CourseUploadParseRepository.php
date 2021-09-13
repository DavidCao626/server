<?php
namespace App\EofficeApp\YonyouVoucher\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\YonyouVoucher\Entities\VoucherIntergrationU8CourseUploadParseEntity;

class VoucherIntergrationU8CourseUploadParseRepository extends BaseRepository
{
    public function __construct(VoucherIntergrationU8CourseUploadParseEntity $entity)
    {
        parent::__construct($entity);
    }

}
