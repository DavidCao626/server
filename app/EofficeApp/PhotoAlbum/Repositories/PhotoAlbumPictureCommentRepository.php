<?php

namespace App\EofficeApp\PhotoAlbum\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumPictureCommentEntity;

class PhotoAlbumPictureCommentRepository extends BaseRepository
{
    private $limit;
    
    /** @var int 默认列表页 */
    private $page       = 0;
    private $orderBy    = ['created_at' => 'desc'];
    public function __construct(PhotoAlbumPictureCommentEntity $entity)
    {
        parent::__construct($entity);
        $this->limit = config('eoffice.pagesize');
    }
    public function getCommentList($pictureId, $param)
    {
        
        $param['fields']    = isset($param['fields']) ? $param['fields'] : ['*'];
        
        $param['limit']     = isset($param['limit']) ? $param['limit'] : $this->limit;
        
        $param['page']      = isset($param['page']) ? $param['page'] : $this->page;
        
        $param['order_by']  = isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
        
        $query = $this->entity->select($param['fields'])->where('picture_id',$pictureId);
        
            $query->forPage($param['page'], $param['limit']);
            $query->where('parent_id','=','0');
        
        return  $query->orders($param['order_by'])
                ->get();
    }   
    public function getChildrenComments($commentId)
    {
        return $this->entity->select(['photo_album_picture_comment.*','user.user_name'])
            ->leftJoin('user', function ($join) {
                    $join->on('photo_album_picture_comment.user_id', '=', 'user.user_id');
                })->where('parent_id', $commentId)->get();
    }  
    
    function getCommentsCount($pictureId)
    {
        return $this->entity->where(['picture_id' => $pictureId])->where('parent_id',0)->count();
    } 
    
    function checkDeleteCommentAuth($commentId,$userId)
    {
        $canDelTime = date('Y-m-d H:i:s', time() - 600);
        return $this->entity->where(['user_id' => $userId])->where('created_at', '>', $canDelTime)->find($commentId);
    }
    
}