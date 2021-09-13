<?php
namespace App\EofficeApp\YonyouVoucher\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\YonyouVoucher\Entities\VoucherIntergrationU8FieldConfigEntity;

class VoucherIntergrationU8FieldConfigRepository extends BaseRepository
{
    public function __construct(VoucherIntergrationU8FieldConfigEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getOneInfo($voucherConfigId,$debitCreditType)
    {
        $allInfo = $this->entity->where(['voucher_config_id'=>$voucherConfigId,'debit_credit_type'=>$debitCreditType])->first();
        if(!empty($allInfo)){
            $allInfo=$allInfo->toArray();
        }
        return $allInfo;
    }
}
