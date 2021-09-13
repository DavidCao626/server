<?php

namespace App\EofficeApp\Task\Entities;

use App\EofficeApp\Base\BaseEntity;

class TaskLogEntity extends BaseEntity
{
	/**
	 * [$table 表名]
	 * @var string
	 */
	protected $table = 'task_log';

	/**
	 * [$primaryKey 主键]
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * [$fillable 允许批量更新的字段]
	 * @var array
	 */
	protected $fillable = ['task_id', 'user_id', 'log_content', 'log_type'];

	/**
	 * [user 操作日志与用户表关联关系]
	 *
	 * @method 朱从玺
	 *
	 * @return [object]
	 */
	public function user()
	{
		return $this->belongsTo('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'user_id');
	}

	public function task()
	{
		return $this->belongsTo('App\EofficeApp\Task\Entities\TaskManageEntity', 'task_id', 'id');
	}
}