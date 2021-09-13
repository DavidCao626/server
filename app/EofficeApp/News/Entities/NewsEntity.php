<?php

namespace App\EofficeApp\News\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 新闻Entity类:提供新闻实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class NewsEntity extends BaseEntity
{
	/**
     * 新闻表
     *
     * @var string
     */
    public $table = 'news';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'news_id';

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];
    /**
     * 获取公共对象的类别
     */
    public function newsType(){
        return $this->belongsTo('App\EofficeApp\News\Entities\NewsTypeEntity', 'news_type_id','news_type_id');
    }

    public function user()
    {
        return $this->belongsTo('App\EofficeApp\User\Entities\UserEntity', 'creator', 'user_id');
    }

    public function scopeCanSee($query, $userId)
    {
        return $query->where(function ($query) use ($userId) {
            $query->where('creator', $userId)
                ->orWhere('publish', 1);
        });
    }
}
