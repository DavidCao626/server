<?php

namespace App\EofficeApp\Book\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * book_info表实体
 *
 * @author  朱从玺
 *
 * @since  2015-10-30 创建
 */
class BookInfoEntity extends BaseEntity
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
	protected $table = 'book_info';

	/**
	 * [$fillable 允许批量更新的字段]
	 *
	 * @var [array]
	 */
	protected $fillable = ['book_name', 'type_id', 'dept_id', 'author', 'press', 'publish_date', 'isbn', 'deposit_location', 'book_total', 'book_remainder', 'price', 'borrow_range', 'borrow_status', 'remark', 'simple_introduction'];

	/**
	 * [bookHasOneType book_info表与book_type表一对一关系]
	 *
	 * @author 朱从玺
	 *
	 * @since  2015-11-02 创建
	 *
	 * @return [object]         [关联关系]
	 */
	public function bookHasOneType()
	{
		return $this->hasOne('App\EofficeApp\Book\Entities\BookTypeEntity', 'id', 'type_id');
	}

	/**
	 * [department book_info表与department表多对一关系]
	 *
	 * @author 朱从玺
	 *
	 * @since  2016-04-12 创建
	 *
	 * @return [type]     [关联关系]
	 */
	public function department()
	{
		return $this->belongsTo('App\EofficeApp\System\Department\Entities\DepartmentEntity', 'dept_id', 'dept_id');
	}

	/**
	 * [bookManage book_info表与book_manage表一对多关系]
	 *
	 * @author 朱从玺
	 *
	 * @since  2016-04-12 创建
	 *
	 * @return [type]     [关联关系]
	 */
	public function bookManage()
	{
		return $this->hasMany('App\EofficeApp\Book\Entities\BookManageEntity', 'book_id', 'id');
	}
}
