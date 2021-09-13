<?php
namespace App\EofficeApp\Contract\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractTypeEntity extends BaseEntity
{
    use SoftDeletes;

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * [$table 数据表名]
     *
     * @var [string]
     */
    protected $table = 'contract_t_type';

    /**
     * [$fillable 允许批量更新的字段]
     *
     * @var [array]
     */
    protected $fillable = ['name', 'number', 'all_user', 'role_ids', 'user_ids', 'dept_ids'];

    public function permissions()
    {
        return  $this->HasOne('App\EofficeApp\Contract\Entities\ContractTypePermissionsEntity','type_id','id');
    }
}
