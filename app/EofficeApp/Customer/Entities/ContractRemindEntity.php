<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 合同提醒Entity类:提供合同提醒实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class ContractRemindEntity extends BaseEntity
{
    /** @var string 合同提醒表 */
	public $table = 'customer_contract_remind';

    /** @var string 主键 */
    public $primaryKey = 'contract_remind_id';

    /**
     * 合同提醒和合同一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-06-15
     */
    public function hasOneContract()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\CustomerContractEntity', 'contract_id', 'contract_id');
    }

}