<?php
namespace App\EofficeApp\Vote\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 调查表实体
 *
 * @author 史瑶
 *
 * @since  2015-06-21 创建
 */
class VoteDeptEntity extends BaseEntity
{
    /**
     * 调查表数据表
     *
     * @var string
     */
	public $table = 'vote_dept';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'id';
    public $incrementing = false;

    /**
     * 默认排序
     *
     * @var string
     */
	public $sort = 'desc';

    /**
     * 默认每页条数
     *
     * @var int
     */
	public $perPage = 10;



}
