<?php
namespace App\EofficeApp\Contract\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractTypePermissionsEntity extends BaseEntity
{

    use SoftDeletes;
    /**
     * [$table 数据表名]
     *
     * @var [string]
     */
    protected $table = 'contract_t_type_permissions';


}
