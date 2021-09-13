<?php
namespace App\EofficeApp\YonyouVoucher\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\YonyouVoucher\Entities\VoucherIntergrationU8RelationFlowRunEntity;

class VoucherIntergrationU8RelationFlowRunRepository extends BaseRepository
{
    public function __construct(VoucherIntergrationU8RelationFlowRunEntity $entity)
    {
        parent::__construct($entity);
    }

}
