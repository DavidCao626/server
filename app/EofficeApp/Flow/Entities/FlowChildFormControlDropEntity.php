<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 */
class FlowChildFormControlDropEntity extends BaseEntity
{
    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_child_form_control_drop';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'id';

    /**
     * 默认排序
     *
     * @var string
     */
	public $sort = 'asc';

    /**
     * 默认每页条数
     *
     * @var int
     */
	public $perPage = 10;

}
