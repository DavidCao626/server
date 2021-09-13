<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程表单分类权限用户表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormSortUserEntity extends BaseEntity
{
    /**
     * 流程表单分类权限用户表
     *
     * @var string
     */
	public $table = 'flow_form_sort_user';
    public $timestamps = false;
    /**
     * 对应用户
     *
     * @method hasOneDept
     *
     * @return boolean    [description]
     */
    public function hasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }
}
