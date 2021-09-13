<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程分表 监控规则表
 *
 * @author 缪晨晨
 *
 * @since  2018-10-29 创建
 */
class FlowTypeManageRuleEntity extends BaseEntity
{
    /**
     * 流程分表 监控规则表
     *
     * @var string
     */
	public $table = 'flow_type_manage_rule';
    public $timestamps = false;
    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'rule_id';

    /**
     * 对应多个监控用户
     *
     * @method hasManyManageUser
     *
     * @return boolean    [description]
     */
    public function hasManyManageUser()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTypeManageUserEntity','rule_id','rule_id');
    }

    /**
     * 对应多个监控角色
     *
     * @method hasManyManageRole
     *
     * @return boolean    [description]
     */
    public function hasManyManageRole()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTypeManageRoleEntity','rule_id','rule_id');
    }

    /**
     * 对应多个监控用户范围
     *
     * @method hasManyManageScopeUser
     *
     * @return boolean    [description]
     */
    public function hasManyManageScopeUser()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTypeManageScopeUserEntity','rule_id','rule_id');
    }

    /**
     * 对应多个监控部门范围
     *
     * @method hasManyManageScopeDept
     *
     * @return boolean    [description]
     */
    public function hasManyManageScopeDept()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowTypeManageScopeDeptEntity','rule_id','rule_id');
    }
}
