<?php
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;

class MeetingSubjectDepartmentEntity extends BaseEntity
{
    /**
     * 协作区权限表
     *
     * @var string
     */
	public $table = 'meeting_apply_member_department';

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
