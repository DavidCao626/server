<?php

namespace App\EofficeApp\Book\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * book_manage表实体
 *
 * @author  朱从玺
 *
 * @since  2015-10-30 创建
 */
class BookManageEntity extends BaseEntity
{
	use SoftDeletes;

	/**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

	/**
	 * [$table 数据表名]
	 *
	 * @var [string]
	 */
	protected $table = 'book_manage';

	/**
	 * [$fillable 允许批量更新的字段]
	 *
	 * @var [array]
	 */
	protected $fillable = ['book_id', 'book_name', 'borrow_person', 'expire_date', 'borrow_date', 'return_date', 'borrow_number', 'return_status', 'remark'];

	/**
	 * [manageBelongsToBook 图书借阅记录与图书多对一关系]
	 *
	 * @author 朱从玺
	 *
	 * @return [object]              [关联关系]
	 */
	public function manageBelongsToBook()
	{
		return $this->belongsTo('App\EofficeApp\Book\Entities\BookInfoEntity', 'book_id');
	}

	/**
	 * [user 图书借阅记录表与用户表多对一关系]
	 *
	 * @author 朱从玺
	 *
	 * @return [object] [关联关系]
	 */
	public function user()
	{
		return $this->belongsTo('App\EofficeApp\User\Entities\UserEntity', 'borrow_person', 'user_id');
	}
}
