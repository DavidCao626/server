<?php
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 协作区分类权限表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class MeetingSortMemberDepartmentEntity extends BaseEntity
{
    /**
     * 协作区分类权限表
     *
     * @var string
     */
	public $table = 'meeting_sort_member_department';

    /**
     * 对应部门
     *
     * @method hasOneDept
     *
     * @return boolean    [description]
     */
    public function hasOneDept()
    {
        return  $this->HasOne('App\EofficeApp\System\Department\Entities\DepartmentEntity','dept_id','dept_id');
    }
}
