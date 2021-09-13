<?php

namespace App\EofficeApp\Task\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskManageEntity extends BaseEntity
{
	/**
	 * 启用软删除
	 */
	use SoftDeletes;

	/**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

	/**
	 * [$table 表名]
	 * @var string
	 */
	protected $table = 'task_manage';

	/**
	 * [$fillable 允许批量更新的字段]
	 * @var array
	 */
	protected $fillable = ['task_name', 'task_description', 'important_level', 'start_date', 'end_date', 'progress','lock'];

	/**
	 * [taskUser 与任务用户关联表1对多关系]
	 *
	 * @author 朱从玺
	 *
	 * @return [type]    [description]
	 */
	public function taskUser()
	{
		return $this->hasMany('App\EofficeApp\Task\Entities\TaskUserEntity', 'task_id');
	}

	/**
	 * [taskHasManyTagRelation 与任务日志表关联关系]
	 *
	 * @author 朱从玺
	 *
	 * @return [type]    [description]
	 */
	public function taskLog()
	{
		return $this->hasMany('App\EofficeApp\Task\Entities\TaskLogEntity', 'task_id');
	}

	public function taskHasOneManager()
	{
		return $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'manage_user');
	}

	public function taskHasManySonTask()
	{
		return $this->hasMany('App\EofficeApp\Task\Entities\TaskManageEntity', 'parent_id', 'id');
	}

	public function taskHasManyCompleteSon()
	{
		return $this->hasMany('App\EofficeApp\Task\Entities\TaskManageEntity', 'parent_id', 'id');
	}

	public function taskBelongsToParent()
	{
		return $this->belongsTo('App\EofficeApp\Task\Entities\TaskManageEntity', 'parent_id');
	}




	//所有可以查询的关联表字段
	public $allFields = [
		'relationTask' => ['taskUser', 'task_id'],
		'relationUser' => ['taskUser', 'user_id'],
		'relationType' => ['taskUser', 'task_relation']
	];

	//关联关系对应的字段
	public $relationFields = [
		'taskUser' => ['relationTask', 'relationUser', 'relationType'],
	];

}