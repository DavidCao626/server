<?php

namespace App\EofficeApp\System\Tag\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 系统标签分类Entity类:提供系统标签分类数据表实体
 *
 * @author qishaobo
 *
 * @since  2016-05-30 创建
 */
class TagTypeEntity extends BaseEntity
{
    /**
     * 标签分类数据表
     *
     * @var string
     */
	public $table = 'tag_type';

    public $timestamps = false;

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'tag_type_id';

    /**
     * 与标签一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-30 创建
     */
    public function hasManyTags()
    {
        return  $this->hasMany('App\EofficeApp\System\Tag\Entities\TagEntity', 'tag_type_id', 'tag_type_id');
    }
}
