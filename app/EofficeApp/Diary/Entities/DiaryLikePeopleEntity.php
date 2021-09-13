<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 微博Entity类:提供微博实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class DiaryLikePeopleEntity extends BaseEntity
{
    /**
     * 微博日志数据表
     *
     * @var string
     */
	public $table = 'diary_like_people';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'diary_like_id';

}
