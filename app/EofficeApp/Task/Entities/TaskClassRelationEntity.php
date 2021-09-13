<?php

namespace App\EofficeApp\Task\Entities;

use App\EofficeApp\Base\BaseEntity;

class TaskClassRelationEntity extends BaseEntity
{
	/**
	 * [$table 表名]
	 * @var string
	 */
	protected $table = 'task_class_relation';

	/**
	 * [$fillable 允许批量更新的字段]
	 * @var array
	 */
	protected $fillable = ['task_id', 'class_id'];

	/**
	 * [user 操作日志与用户表关联关系]
	 *
	 * @method 朱从玺
	 *
	 * @return [object]
	 */
	// public function user()
	// {
	// 	return $this->belongsTo('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'user_id');
	// }
}