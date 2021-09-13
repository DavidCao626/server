<?php

namespace App\EofficeApp\News\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\News\Entities\NewsCommentEntity;

/**
 * 新闻评论Repository类:提供新闻评论相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class NewsCommentRepository extends BaseRepository
{
	/** @var int 默认列表条数 */
	private $limit;

	/** @var int 默认列表页 */
	private $page		= 0;

	/** @var array  默认排序 */
	private $orderBy	= ['created_at' => 'desc'];

    public function __construct(NewsCommentEntity $entity)
    {
        parent::__construct($entity);

		$this->limit = config('eoffice.pagesize');
    }

    /**
     * 获取新闻评论列表
     *
     * @param  integer  $newsId 新闻id
     * @param  integer $page   	当前页
     * @param  integer $limit  	分页条数
     *
     * @return array          	新闻评论列表
     *
     * @author qishaobo
     *
     * @since  2015-11-12
     */
	public function getCommentList($newsId, $param)
	{

		$param['fields']	= isset($param['fields']) ? $param['fields'] : ['*'];

		$param['limit']		= isset($param['limit']) ? $param['limit'] : $this->limit;

		$param['page']		= isset($param['page']) ? $param['page'] : $this->page;

		$param['order_by']	= isset($param['order_by']) ? $param['order_by'] : $this->orderBy;

		$query = $this->entity->select($param['fields'])->where('news_id',$newsId);

			$query->forPage($param['page'], $param['limit']);
			$query->where('parent_id','=','0')
			->with(["revertHasOneUser" => function($query) {
			    $query->select("user_id","user_name");
			}])
			->with(["revertHasOneBlockquote" => function($query) {
	            $query->select("*")
	                    ->with(["revertHasOneUser" => function($query) {
	                        $query->select("user_id","user_name");
	                    }]);
	        }]);
		return	$query->orders($param['order_by'])
				->get();
	}
	/**
	 * 新闻子评论
	 * @param  integer $commentId 评论id
	 *
	 * @return array              获取新闻子评论列表
	 *
     * @author qishaobo
     *
     * @since  2015-11-12
	 */
	public function getChildrenComments($commentId)
	{
		return $this->entity->select(['news_comment.*','user.user_name'])
			->leftJoin('user', function ($join) {
		            $join->on('news_comment.user_id', '=', 'user.user_id');
		        })
			->where('parent_id', $commentId)
			->with(["revertHasOneUser" => function($query) {
			    $query->select("user_id","user_name");
			}])->get()->toArray();
	}

    /**
     * 获取新闻评论数量
     *
     * @param  integer $newsId 新闻id
     *
     * @return integer         新闻评论数
     *
     * @author qishaobo
     *
     * @since  2015-11-12
     */
	function getCommentsCount($newsId)
	{
		return $this->entity->where(['news_id' => $newsId])->count();
	}

    /**
     * 查看用户是否具有新闻评论权限
     *
     * @param  integer $commentId 评论id
     * @param  string  $userId    用户id
     *
     * @return object             查询结果
     *
     * @author qishaobo
     *
     * @since  2015-11-12
     */
	function checkDeleteCommentAuth($commentId,$userId)
	{
		return $this->entity->where(['user_id' => $userId])->find($commentId);
	}

    /**
     * 删除新闻评论
     *
     * @param  array|int $commentId 评论id
     *
     * @return bool            		删除是否成功
     *
     * @author qishaobo
     *
     * @since  2015-11-12
     */
	function deleteComment($commentId)
	{
		$query = $this->entity;
		if (is_array($commentId)) {
			$query = $query->whereIn('comment_id',$commentId);
		} else {
			$query = $query->where('comment_id',$commentId);
		}
		return $query->delete();
	}

}
