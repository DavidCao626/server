<?php 

namespace App\EofficeApp\News\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 新闻阅读人Entity类:提供新闻阅读人实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class NewsReaderEntity extends BaseEntity
{
	/**
     * 新闻阅读表
     *
     * @var string
     */ 	
	public $table = 'news_reader';

    /**
     * 主键
     *
     * @var string
     */  
    public $primaryKey = 'id';
}
