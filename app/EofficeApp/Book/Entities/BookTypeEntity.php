<?php

namespace App\EofficeApp\Book\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * book_type表实体
 *
 * @author  朱从玺
 *
 * @since  2015-10-30 创建
 */
class BookTypeEntity extends BaseEntity
{
	/**
	 * [$table 数据表名]
	 *
	 * @var [string]
	 */
	protected $table = 'book_type';

	/**
	 * [$fillable 允许批量更新的字段]
	 *
	 * @var [array]
	 */
	protected $fillable = ['type_name', 'remark'];

	/**
	 * [typeHasManyBook 图书类型与图书的一对多关系]
	 *
	 * @method 朱从玺
	 *
	 * @since  2015-10-30 创建
	 *
	 * @return [object]          [关联关系]
	 */
	public function typeHasManyBook()
	{
		return $this->hasMany('App\EofficeApp\Book\Entities\BookInfoEntity', 'type_id');
	}
}