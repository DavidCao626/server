<?php 

namespace App\EofficeApp\News\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 新闻评论Entity类:提供新闻评论实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class NewsCommentEntity extends BaseEntity
{
	/**
     * 新闻评论数据表
     *
     * @var string
     */ 
    protected $table = 'news_comment';
    
    /**
     * 主键
     *
     * @var string
     */  
    public $primaryKey = 'comment_id';
    /**
     * 关联引用回复
     *
     * @return object
     */
    public function revertHasOneBlockquote()
    {
        return $this->HasOne('App\EofficeApp\News\Entities\NewsCommentEntity','comment_id','blockquote');
    }
    /**
     * 回复对应人员
     *
     * @method hasOneDept
     *
     * @return boolean    [description]
     */
    public function revertHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }
}
