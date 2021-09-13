<?php

namespace App\EofficeApp\PhotoAlbum\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumPictureEntity;

/**
 * 相片Repository类:提供相片表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-11-3 创建
 */
class PhotoAlbumPictureRepository extends BaseRepository
{
    public function __construct(PhotoAlbumPictureEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取相片列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    public function getPicturesList(array $param = [])
    {
        $default = [
            'fields' => ['picture_id', 'photo_album_id', 'attachment_id', 'picture_name', 'picture_size', 'picture_cover', 'creator', 'top', 'created_at'],
            'search' => [],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => ['picture_id' => 'DESC'],
        ];

        $param = array_merge($default, array_filter($param));
        return $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->forPage($param['page'], $param['limit'])
            ->get();
    }

    /**
     * 用户是否有相片管理权限
     *
     * @param  array $pictureIds 相片id
     * @param  string $userId 用户id
     *
     * @return integer 图片数
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function hasPermission($pictureIds, $userId)
    {
        $query = $this->entity->whereHas('hasAdminPermission', function ($query) use ($userId) {
            $query->whereHas('hasManyManageUser', function ($query) use ($userId) {
                $query->wheres(['user_id' => [$userId]]);
            })->orWhere(['creator' => [$userId]]);
        });
        return $query
            ->wheres(['picture_id' => [$pictureIds, 'in']])
            ->count();
    }

    public function getCoverAttachmentId($photoAlbumId)
    {
        return $this->entity->where('picture_cover', 1)->where('photo_album_id', $photoAlbumId)->first();
    }

    public function getFirstAttachmentId($photoAlbumId)
    {
        return $this->entity->where('photo_album_id', $photoAlbumId)->orderBy('picture_id', 'desc')->first();
    }

    /**
     * 获取图片的大小
     * @param $pictureIds
     * @return mixed
     */
    public function getPicturesSizeByPictureIds($pictureIds)
    {
        $size = $this->entity->whereIn('picture_id', $pictureIds)->sum('picture_size');
        return $size;
    }
}