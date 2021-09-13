<?php

namespace App\EofficeApp\News\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 新闻类型Entity类:提供新闻类型实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class NewsTypeEntity extends BaseEntity
{
	/**
     * 新闻表类型表
     *
     * @var string
     */
    public $table = 'news_type';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'news_type_id';

    public function parent()
    {
        return $this->belongsTo(self::class, 'news_type_parent', 'news_type_id');
    }

    public function news()
    {
        return $this->hasMany(NewsEntity::class, 'news_type_id', 'news_type_id');
    }

}
