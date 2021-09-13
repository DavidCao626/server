<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程分表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowProcessRoleEntity extends BaseEntity
{
    /**
     * 流程分表
     *
     * @var string
     */
	public $table = 'flow_process_role';
    public $timestamps = false;
    public $primaryKey = 'auto_id';
    public $sort = 'asc';

    /**
     * 对应角色
     *
     * @method hasOneRole
     *
     * @return boolean    [description]
     */
    public function hasOneRole()
    {
        return  $this->HasOne('App\EofficeApp\Role\Entities\RoleEntity','role_id','role_id');
    }

}
