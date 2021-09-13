<?php

namespace App\EofficeApp\OfficeSupplies\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * office_supplies_storage数据表实体
 *
 * @author  朱从玺
 *
 * @since   2015-11-03
 */
class OfficeSuppliesStorageEntity extends BaseEntity
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
    protected $table = 'office_supplies_storage';

    /**
     * [$fillable 允许批量赋值的字段]
     *
     * @var [array]
     */
    protected $fillable = ['storage_date', 'storage_bill', 'office_supplies_id', 'type_id', 'price', 'storage_amount', 'arithmetic', 'money', 'operator'];

    /**
     * [storageBelongsToUser 入库记录表与用户表多对一关系]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function storageBelongsToUser()
    {
        return $this->belongsTo('App\EofficeApp\User\Entities\UserEntity', 'operator', 'user_id');
    }

    /**
     * [storageBelongsToUser 入库记录表与办公用品表多对一关系]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function storageBelongsToSupplies()
    {
        return $this->belongsTo('App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesEntity', 'office_supplies_id', 'id');
    }

    /**
     * [storageBelongsToType 入库记录表与办公用品类型多对一关系]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [type]               [关联关系]
     */
    public function storageBelongsToType()
    {
        return $this->belongsTo('App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesTypeEntity', 'type_id');
    }
}
