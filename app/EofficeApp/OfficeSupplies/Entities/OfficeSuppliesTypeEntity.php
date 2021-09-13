<?php

namespace App\EofficeApp\OfficeSupplies\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * office_supplies_type数据表实体
 *
 * @author  朱从玺
 *
 * @since   2015-11-03
 */
class OfficeSuppliesTypeEntity extends BaseEntity
{
    /**
     * [$table 数据表名]
     *
     * @var string
     */
    protected $table = 'office_supplies_type';

    /**
     * [$fillable 允许批量赋值的字段]
     *
     * @var [array]
     */
    protected $fillable = ['type_no', 'type_name', 'type_sort','parent_id', 'remark'];

    /**
     * [typeHasManySupplies 用品类型表与办公用品表一对多关系]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function typeHasManySupplies()
    {
        return $this->hasMany('App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesEntity', 'type_id');
    }

    /**
     * [typeHasManySuppliesCount 用品类型表与办公用品表一对多关系,为同时获取办公用品数量而写]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function typeHasManySuppliesCount()
    {
        return $this->hasMany('App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesEntity', 'type_id');
    }


    /**
     * 类型权限表一对一
     *
     * @return object
     *
     * @author Jason
     *
     * @since  2018-08-29
     */
    public function typeHasManyPermission()
    {
        return  $this->hasMany('App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesPermissionEntity','office_supplies_type_id');
    }

    /**
     *
     * 类型父类 一对一
     */
    public function typeHasOneParent()
    {
        return $this->hasOne('App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesTypeEntity','id','parent_id');
    }
    /**
     *
     * 类型父类 一对一
     */
    public function typeParentBelongsToChild()
    {
        return $this->belongsTo('App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesTypeEntity','parent_id','id');
    }


}
