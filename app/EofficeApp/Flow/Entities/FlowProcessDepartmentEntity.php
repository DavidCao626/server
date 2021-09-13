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
class FlowProcessDepartmentEntity extends BaseEntity
{
    /**
     * 流程分表
     *
     * @var string
     */
	public $table = 'flow_process_department';
    public $timestamps = false;
    public $primaryKey = 'auto_id';
    public $sort = 'asc';

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
