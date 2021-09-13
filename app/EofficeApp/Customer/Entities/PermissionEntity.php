<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 客户申请Entity类:提供客户申请实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class PermissionEntity extends BaseEntity
{
    /** @var string 权限申请表 */
	public $table = 'customer_apply_permission';

    /**  @var string 主键 */
    public $primaryKey = 'apply_id';

    /**  @var array 关联字段 */
    public $relationFields = [
        'fields' => [
            'applyPermissionToCustomer'             => ['customer_name', 'view_permission'],
            'user'                 => ['apply_permission_to_user_name' => 'user_name' ],
            'applyPermissionToCustomer.userManager' => ['customer_manager_name' => 'user_name' ],
        ],
        'relation' => [
            'applyPermissionToCustomer'             => ['customer_id', 'customer_id'],
            'user'                 => ['user_id', 'proposer'],
            'applyPermissionToCustomer.userManager' => ['user_id', 'customer_id'],
        ]
    ];


    public function applyPermissionToCustomer($operate = 0)
    {
        return $this->hasOneModel('App\EofficeApp\Customer\Entities\CustomerEntity', 'customer_id', 'customer_id', $operate);
    }


    public function customer($operate = 0)
    {
        return $this->hasOneModel('App\EofficeApp\Customer\Entities\CustomerEntity', 'customer_id', 'customer_id', $operate);
    }

    public function user($operate = 0)
    {
        return $this->hasOneModel('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'proposer', $operate);
    }
}