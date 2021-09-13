<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程分表 监控范围指定人员表
 *
 * @author 缪晨晨
 *
 * @since  2018-04-16 创建
 */
class FlowTypeManageScopeUserEntity extends BaseEntity
{
    /**
     * 流程分表 监控范围指定人员表
     *
     * @var string
     */
	public $table = 'flow_type_manage_scope_user';
    public $timestamps = false;
    /**
     * 对应用户
     *
     * @method hasOneUser
     *
     * @return boolean    [description]
     */
    public function hasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }
}
