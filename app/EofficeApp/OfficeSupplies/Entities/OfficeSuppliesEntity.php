<?php

namespace App\EofficeApp\OfficeSupplies\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * office_supplies数据表实体
 *
 * @author  朱从玺
 *
 * @since   2015-11-03
 */
class OfficeSuppliesEntity extends BaseEntity
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
    protected $table = 'office_supplies';

    /**
     * [$fillable 允许批量赋值的字段]
     *
     * @var [array]
     */
    protected $fillable = ['office_supplies_no', 'office_supplies_name', 'type_id', 'unit', 'specifications', 'stock_remind', 'usage', 'remind_max', 'remind_min', 'attachment_id', 'attachment_name', 'reference_price','apply_controller'];

    /**
     * [suppliesBelongsToType 办公用品与类型多对一关系]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [object]                 [关联关系]
     */
    public function suppliesBelongsToType()
    {
        return $this->belongsTo('App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesTypeEntity', 'type_id');
    }

    /**
     * [applyBelongsToSupplies 办公用品与入库记录一对多关系]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [object]                 [关联关系]
     */
    public function suppliesHasManyStorage()
    {
        return $this->hasMany('App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesStorageEntity', 'office_supplies_id');
    }

    /**
     * [applyBelongsToSupplies 办公用品与申请记录一对多关系]
     *
     * @author miaochenchen
     *
     * @since  2016-10-08 创建
     *
     * @return [object]                 [关联关系]
     */
    public function suppliesHasManyApply()
    {
        return $this->hasMany('App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesApplyEntity', 'office_supplies_id');
    }

    public function attachments()
    {
        return $this->hasMany(OfficeSuppliesAttachmentEntity::class, 'entity_id', 'id');
    }
}
