<?php


namespace App\EofficeApp\Notify\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Notify\Entities\NotifyCommentEntity;

/**
 * 公告评论Repository类:提供公告评论相关的数据库操作方法。
 *
 * @author nitianhua
 *
 * @since  2018-11-26 创建
 */
class NotifyCommentRepository extends BaseRepository
{
    /** @var int 默认列表条数 */
    private $limit;

    /** @var int 默认列表页 */
    private $page		= 0;

    /** @var array  默认排序 */
    private $orderBy	= ['created_at' => 'desc'];

    public function __construct(NotifyCommentEntity $entity)
    {
        parent::__construct($entity);

        $this->limit = config('eoffice.pagesize');
    }

    /**
     * 获取评论列表
     *
     * @param  integer  $notifyId 公告id
     * @param  $param
     *
     * @return array          	公告评论列表
     *
     * @author nitianhua
     *
     * @since  2018-11-26
     */
    public function getCommentList($notifyId, $param)
    {

        $param['fields']	= isset($param['fields']) ? $param['fields'] : ['*'];

        $param['limit']		= isset($param['limit']) ? $param['limit'] : $this->limit;

        $param['page']		= isset($param['page']) ? $param['page'] : $this->page;

        $param['order_by']	= isset($param['order_by']) ? $param['order_by'] : $this->orderBy;

        $query = $this->entity->select($param['fields'])->where('notify_id',$notifyId);

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
     * 公告子评论
     * @param  integer $commentId 评论id
     *
     * @return array              获取子评论列表
     *
     * @author nitianhua
     *
     * @since  2018-11-28
     */
    public function getChildrenComments($commentId)
    {
        return $this->entity->select(['notify_comments.*','user.user_name'])
            ->leftJoin('user', function ($join) {
                $join->on('notify_comments.user_id', '=', 'user.user_id');
            })
            ->where('parent_id', $commentId)
            ->with(["revertHasOneUser" => function($query) {
                $query->select("user_id","user_name");
            }])->get()->toArray();
    }

    /**
     * 获取评论数量
     *
     * @param  integer $notifyId 公告id
     *
     * @return integer         公告评论数
     *
     * @author nitianhua
     *
     * @since  2018-11-28
     */
    function getCommentsCount($notifyId)
    {
        return $this->entity->where(['notify_id' => $notifyId])->count();
    }

    /**
     * 查看用户是否具有评论权限
     *
     * @param  integer $commentId 评论id
     * @param  string  $userId    用户id
     *
     * @return object             查询结果
     *
     * @author nitianhua
     *
     * @since  2018-11-28
     */
    function checkDeleteCommentAuth($commentId,$userId)
    {
        return $this->entity->where(['user_id' => $userId])->find($commentId);
    }

    /**
     * 删除评论
     *
     * @param  array|int $commentId 评论id
     *
     * @return bool            		删除是否成功
     *
     * @author nitianhua
     *
     * @since  2018-11-28
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
