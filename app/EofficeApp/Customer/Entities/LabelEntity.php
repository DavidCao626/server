<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 *
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class LabelEntity extends BaseEntity
{
    use SoftDeletes;

    /** @var string 客户标签表 */
	public $table = 'customer_label';

    /** @var string 主键 */
    public $primaryKey = 'id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];

}