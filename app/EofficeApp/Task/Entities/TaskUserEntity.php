<?php

namespace App\EofficeApp\Task\Entities;

use App\EofficeApp\Base\BaseEntity;

class TaskUserEntity extends BaseEntity
{
	/**
	 * [$table 表名]
	 * @var string
	 */
	protected $table = 'task_user';

	/**
	 * [$fillable 允许批量更新的字段]
	 * @var array
	 */
	protected $fillable = ['task_id', 'user_id', 'task_relation'];
}