<?php
namespace App\EofficeApp\Contract\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractEntity extends BaseEntity
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
    protected $table = 'contract_t';

    /**
     * [$fillable 允许批量更新的字段]
     *
     * @var [array]
     */
    protected $fillable = ['number', 'title', 'type_id', 'user_id', 'target_name', 'money', 'content', 'a_user', 'b_user', 'a_address', 'b_address', 'a_linkman', 'b_linkman', 'a_phone', 'b_phone', 'a_sign', 'b_sign', 'a_sign_time', 'b_sign_time', 'status', 'remarks'];

    public function users()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'user_id');
    }

    public function orders()
    {
        return  $this->HasMany('App\EofficeApp\Contract\Entities\ContractOrderEntity', 'contract_t_id', 'id');
    }

    public function projects()
    {
        return  $this->HasMany('App\EofficeApp\Contract\Entities\ContractProjectEntity', 'contract_t_id', 'id');
    }

    public function flows()
    {
        return  $this->HasMany('App\EofficeApp\Contract\Entities\ContractFlowEntity', 'contract_t_id', 'id');
    }

    public function reminds()
    {
        return  $this->HasMany('App\EofficeApp\Contract\Entities\ContractRemindEntity', 'contract_t_id', 'id');
    }
}
