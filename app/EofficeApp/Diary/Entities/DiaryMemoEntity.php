<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 微博便签Entity类:提供微博便签实体。
 *
 * @author qishaobo
 *
 * @since  2016-04-15 创建
 */
class DiaryMemoEntity extends BaseEntity
{
    /**
     * 微博便签数据表
     *
     * @var string
     */
	public $table = 'diary_memo';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'diary_memo_id';

}
