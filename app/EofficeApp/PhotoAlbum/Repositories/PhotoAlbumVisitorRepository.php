<?php

namespace App\EofficeApp\PhotoAlbum\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumVisitorEntity;

class PhotoAlbumVisitorRepository extends BaseRepository
{
    public function __construct(PhotoAlbumVisitorEntity $entity)
    {
        parent::__construct($entity);
    }

    public function visitorList($param)
    {
        $default = [
            'fields'    => ['*'],
            'search'    => [],
            'page'      => 0,
            'limit'     => 25,
            'order_by'  => ['visit_time' => 'DESC'],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity
        ->select($param['fields'])
        ->with(['creatorWithUser' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])
        ->wheres($param['search'])
        ->orders($param['order_by']);
        if (!isset($param['getAll'])) {
            $query = $query->forPage($param['page'], $param['limit']);
        }
        return $query->get();
    }

    public function photoAlbumVisitorList($param)
    {
        $default = [
            'fields'    => ['photo_album_visitor.*','user.user_name','user.deleted_at'],
            'search'    => [],
            'page'      => 0,
            'limit'     => 25,
            'order_by'  => ['visit_time' => 'DESC'],
        ];

        $param = array_merge($default, array_filter($param));
        $param['search']['deleted_at'] = [null];
        $query = $this->entity
            ->select($param['fields'])
            ->leftjoin("user", "user.user_id", "=", "photo_album_visitor.user_id")
            ->wheres($param['search'])
            ->orders($param['order_by']);
        if (!isset($param['getAll'])) {
            $query = $query->forPage($param['page'], $param['limit']);
        }
        return $query->get();
    }

    public function visitorExists($PhotoAlbumId,$userId)
    {
        return $this->entity->where('photo_album_id',$PhotoAlbumId)->where('user_id',$userId)->count();
    }
}