<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 计划内容，使用模板2/3的时候，储存一个区块的详细内容
 *
 * @author dp
 *
 * @since  2017-06-20 创建
 */
class DiaryPlanContentBlockDetailEntity extends BaseEntity
{
    /**
     * 计划内容
     *
     * @var string
     */
	public $table = 'diary_plan_content_block_detail';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'item_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];

}
