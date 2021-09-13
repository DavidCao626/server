<?php

namespace App\EofficeApp\PersonnelFiles\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * personnel_files表实体
 *
 * @author  朱从玺
 *
 * @since  2015-10-28 创建
 */
class PersonnelFilesEntity extends BaseEntity
{
	/**
	 * [$table 数据表名]
	 *
	 * @var [string]
	 */
	protected $table = 'personnel_files';

	/**
     * [$fillable 允许被赋值的字段]
     *
     * @var [array]
     */
    // protected $fillable = ['user_id', 'user_name', 'card_no', 'certificate', 'photo', 'no', 'nation', 'post', 'sex', 'status', 'work_date', 'join_date', 'birthday', 'labor_start_time', 'labor_end_time', 'marry', 'education', 'politics', 'dept_id', 'native_place', 'speciality', 'school', 'home_addr', 'home_tel', 'email', 'reward', 'edu', 'work', 'sociaty', 'attachment_id', 'attachment_name', 'train', 'resume', 'others'];

    /**
     * [personnelFilesHasOneSub 与自定义字段表关系]
     *
     * @author 朱从玺
     *
     * @return [object]                  [关联关系]
     */
  //   public function personnelFilesHasOneSub()
  //   {
		// return $this->hasOne('App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesSubEntity', 'personnel_files_id');
  //   }

    public function department()
    {
        return $this->belongsTo('App\EofficeApp\System\Department\Entities\DepartmentEntity', 'dept_id', 'dept_id');
    }

    public function personnelFilesToUser()
    {
        return $this->belongsTo('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'user_id');
    }

    /**
     * 薪酬项个人基准值
     */
    public function salaryPersonalDefault()
    {
        return $this->hasMany('App\EofficeApp\Salary\Entities\SalaryFieldPersonalDefaultEntity', 'user_id', 'id');
    }
}