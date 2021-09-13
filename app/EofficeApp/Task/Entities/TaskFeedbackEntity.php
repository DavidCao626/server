<?php

namespace App\EofficeApp\Task\Entities;

use App\EofficeApp\Base\BaseEntity;

class TaskFeedbackEntity extends BaseEntity
{
	/**
	 * [$table 表名]
	 * @var string
	 */
	protected $table = 'task_feedback';

	/**
	 * [$fillable 允许批量更新的字段]
	 * @var array
	 */
	protected $fillable = ['feedback_content', 'user_id', 'task_id', 'parent_id'];

	/**
	 * [user 反馈与用户表关联关系]
	 *
	 * @author 朱从玺
	 *
	 * @return [object]
	 */
	public function user()
	{
		return $this->belongsTo('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'user_id');
	}

	/**
	 * [parentFeedback 反馈与父级反馈关联关系]
	 *
	 * @author 朱从玺
	 *
	 * @return [object]
	 */
	public function parentFeedback()
	{
		return $this->hasOne('App\EofficeApp\Task\Entities\TaskFeedbackEntity', 'id', 'parent_id');
	}

	/**
	 * [task 反馈与任务多对一关联关系]
	 *
	 * @author 朱从玺
	 *
	 * @return [object]
	 */
	public function task()
	{
		return $this->belongsTo('App\EofficeApp\Task\Entities\TaskManageEntity', 'task_id', 'id');
	}

	/**
	 * [feedbackHasManySon 反馈与自反馈一对多关系]
	 *
	 * @author 朱从玺
	 *
	 * @return [object]
	 */
	public function feedbackHasManySon()
	{
		return $this->hasMany('App\EofficeApp\Task\Entities\TaskFeedbackEntity', 'parent_id', 'id');
	}

	public function unread()
	{
		return $this->hasMany('App\EofficeApp\Task\Entities\TaskFeedbackUnreadEntity', 'feedback_id', 'id');
	}
}