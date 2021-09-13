<?php

namespace App\EofficeApp\OfficeSupplies\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * office_supplies_apply数据表实体
 *
 * @author  朱从玺
 *
 * @since   2015-11-03
 */
class OfficeSuppliesApplyEntity extends BaseEntity
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
     * @var string
     */
    protected $table = 'office_supplies_apply';

    /**
     * [$fillable 允许批量赋值的字段]
     *
     * @var [array]
     */
    protected $fillable = ['apply_bill', 'office_supplies_id', 'apply_number', 'apply_type', 'receive_way', 'receive_date', 'return_date', 'explan', 'apply_user', 'apply_status', 'approval_opinion'];

    /**
     * [applyBelongsToSupplies 申请记录与办公用品多对一关系]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [object]                 [关联关系]
     */
    public function applyBelongsToSupplies()
    {
        return $this->belongsTo('App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesEntity', 'office_supplies_id', 'id');
    }

    /**
     * [applyBelongsToUser 申请记录与用户多对一关系]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [object]             [关联关系]
     */
    public function applyBelongsToUser()
    {
        return $this->belongsTo('App\EofficeApp\User\Entities\UserEntity', 'apply_user', 'user_id');
    }

    /**
     * [applyBelongsToUserSystemInfo 申请记录与用户系统信息多对一关系]
     *
     * @author miaochenchen
     *
     * @since  2016-11-28 创建
     *
     * @return [object]             [关联关系]
     */
    public function applyBelongsToUserSystemInfo()
    {
        return $this->belongsTo('App\EofficeApp\User\Entities\UserSystemInfoEntity','apply_user','user_id');
    }
}
