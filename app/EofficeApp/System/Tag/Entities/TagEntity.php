<?php

namespace App\EofficeApp\System\Tag\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 系统标签Entity类:提供系统标签数据表实体
 *
 * @author qishaobo
 *
 * @since  2016-05-27 创建
 */
class TagEntity extends BaseEntity
{
    /**
     * 标签数据表
     *
     * @var string
     */
	public $table = 'tag';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'tag_id';

}
