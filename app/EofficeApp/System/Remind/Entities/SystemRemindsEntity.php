<?php

namespace App\EofficeApp\System\Remind\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * system_reminds表实体
 *
 * @author  朱从玺
 *
 * @since  2016-02-26 创建
 */
class SystemRemindsEntity extends BaseEntity
{
	/**
	 * [$table 数据表名]
	 *
	 * @var string
	 */
	protected $table = 'system_reminds';

	/**
	 * [$fillable 允许被赋值的字段]
	 *
	 * @var [array]
	 */
	protected $fillable = ['reminds_select'];

	public function systemReminds()
	{
		return $this->hasMany('App\EofficeApp\System\Remind\Entities\SystemRemindsEntity', 'remind_menu', 'remind_menu');
	}
}