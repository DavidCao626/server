<?php

namespace App\EofficeApp\OfficeSupplies\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * office_supplies_permission数据表实体
 *
 * @author  Jason
 *
 * @since   2018-08-29
 */
class OfficeSuppliesPermissionEntity extends BaseEntity
{

    public $primaryKey		= 'id';
    /**
     * [$table 数据表名]
     *
     * @var string
     */
    protected $table = 'office_supplies_permission';

    /**
     * [$fillable 允许批量赋值的字段]
     *
     * @var [array]
     */
    protected $fillable = ['office_supplies_type_id', 'permission_type', 'manager_dept', 'manager_role', 'manager_user','manager_all'];

    /**
     * 该模型是否被自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;


    /**
     * [权限表对类型表一对一关系]
     *
     * @since  2018-08-29 创建
     *
     * @return [object]               [关联关系]
     */
    public function permissionBelongsToType()
    {
        return $this->belongsTo('App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesTypeEntity', 'office_supplies_type_id');
    }
}
