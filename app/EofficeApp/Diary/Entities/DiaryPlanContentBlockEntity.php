<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 计划内容，使用模板2/3的时候，储存一个区块的内容，包括保存头部信息，带顺序
 *
 * @author dp
 *
 * @since  2017-06-20 创建
 */
class DiaryPlanContentBlockEntity extends BaseEntity
{

    /**
     * 计划模板内容
     *
     * @var string
     */
	public $table = 'diary_plan_content_block';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'block_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];


    /**
     * 有多个明细
     *
     * @return object
     *
     * @author dp
     *
     * @since  2016-01-27
     */
    public function hasManyDetail()
    {
        return  $this->HasMany('App\EofficeApp\Diary\Entities\DiaryPlanContentBlockDetailEntity', 'block_id','block_id');
    }
}
