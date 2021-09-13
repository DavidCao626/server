<?php

namespace App\EofficeApp\PhotoAlbum\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 相片Entity类:提供相片表实体
 *
 * @author qishaobo
 *
 * @since  2015-11-3 创建
 */
class PhotoAlbumPictureEntity extends BaseEntity
{
	/** @var string 相片表 */
	protected $table = 'photo_album_picture';

	/** @var string 主键 */
	public $primaryKey = 'picture_id';

    /**
     * 相册和相册分类一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function hasAdminPermission()
    {
 		return $this->hasOne('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumEntity', 'photo_album_id', 'photo_album_id');
    }

    /**
     * 相册和附件一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-01-28
     */
    public function hasOnePicture()
    {
        return $this->hasOne('app\EofficeApp\Attachment\Entities\AttachmentEntity', 'id', 'attachment_id');
    }
}