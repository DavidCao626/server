<?php

namespace App\EofficeApp\PhotoAlbum\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumTypeEntity;

/**
 * 相册分类Repository类:提供相册分类表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-11-3 创建
 */
class PhotoAlbumTypeRepository extends BaseRepository
{
    public function __construct(PhotoAlbumTypeEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取相册分类列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    public function getPhotoAlbumTypeList(array $param = [])
    {
        $default = [
            'fields'    => ['*'],
            'search'    => [],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'order_by'  => ['serial_number' => 'ASC'],
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
    /**
     * 查看相册分类信息详情
     *
     * @param  int $typeId 相册分类id
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */
    public function getphotoAlbumTypeDetail($typeId)
    {
        return $this->entity->find($typeId);
    }

    /**
     * 获取有相册的分类
     *
     * @param  array $param 查询条件
     *
     * @return array 相册分类列表
     *
     * @author qishaobo
     *
     * @since  2016-04-25
     */
    public function getHasPhotoAlbumsTypes($where = [])
    {
        return $this->entity
            ->whereDoesntHave('withPhotoAlbums')
            ->wheres($where)
            ->pluck('type_id');
    }
}