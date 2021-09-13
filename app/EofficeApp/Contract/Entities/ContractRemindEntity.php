<?php
namespace App\EofficeApp\Contract\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractRemindEntity extends BaseEntity
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
    protected $table = 'contract_t_remind';

    /**
     * [$fillable 允许批量更新的字段]
     *
     * @var [array]
     */
//    protected $fillable = ['contract_id', 'user_id', 'remind_date', 'content'];

    public function user()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'user_id');
    }
    public function contract()
    {
        return  $this->belongsTo('App\EofficeApp\Contract\Entities\ContractEntity', 'contract_t_id', 'id');
    }
}
