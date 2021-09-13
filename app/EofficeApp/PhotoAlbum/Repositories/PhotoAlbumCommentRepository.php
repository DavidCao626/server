<?php

namespace App\EofficeApp\PhotoAlbum\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumCommentEntity;

/**
 * 相册评论Repository类:提供相册评论表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-11-4 创建
 */
class PhotoAlbumCommentRepository extends BaseRepository
{
    public function __construct(PhotoAlbumCommentEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取相册评论列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    public function getCommentsList(array $param = [])
    {
        $default = [
            'fields'	=> ['*'],
            'search'	=> [],
            'page'  	=> 0,
            'limit'  	=> config('eoffice.pagesize'),
            'order_by'	=> ['comment_id' => 'DESC'],
        ];

        $param = array_merge($default, array_filter($param));

		$userId = $param['user_id'];

        $query = $this->entity
        ->select($param['fields'])
        ->with(['creatorWithUser' => function ($query) {
            $query->select('user_id', 'user_name');
        }]);

       	if ($userId != 'admin') {
        	$query = $query->selectRaw("IF(user_id = '$userId', 1, 0) AS canDelete");
        } else {
        	$query = $query->selectRaw("CONCAT('1') AS canDelete");
        }

        return $query
        ->wheres($param['search'])
        ->orders($param['order_by'])
        ->forPage($param['page'], $param['limit'])
        ->get()
        ->toArray();
    }
    public function getCommentsCountByAlbumId($photoAlbumId)
    {
        return $this->entity->where('photo_album_id',$photoAlbumId)->count();
    }
    /**
     * 用户是否有相册评论删除权限
     *
     * @param  int $commentId 相册id
     * @param  string $userId 用户id
     *
     * @return array 相册评论详情
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function hasPermission($commentId, $userId)
    {
     
        $canDelTime = date('Y-m-d H:i:s', time() - 600);
        return $this->entity
        ->where('user_id', $userId)
        ->where('comment_date','>', $canDelTime)
        ->find($commentId);
    }
}