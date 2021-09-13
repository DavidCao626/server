<?php

namespace App\EofficeApp\PhotoAlbum\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 相册Entity类:提供相册表实体
 *
 * @author qishaobo
 *
 * @since  2015-11-3 创建
 */
class PhotoAlbumEntity extends BaseEntity
{

	/** @var string 相册表 */
	protected $table = 'photo_album';

	/** @var string 主键 */
	public $primaryKey = 'photo_album_id';

    /**
     * 相册和相片一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    public function withPictures()
    {
        return  $this->HasMany('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumPictureEntity','photo_album_id','photo_album_id');
    }

    /**
     * 相册和评论一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    public function withComments()
    {
        return  $this->HasMany('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumCommentEntity','photo_album_id','photo_album_id');
    }

    /**
     * 相册和相册分类一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */
    public function hasAdminPermission()
    {
        return  $this->HasMany('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumTypeEntity','type_id','type_id');
    }

    /**
     * 多个相册分类管理员
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */
    public function hasManyManageUser()
    {
        return  $this->HasMany('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumManageUserEntity','photo_album_id','photo_album_id');
    }

    /**
     * 多个相册共享部门
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */
    public function hasManyDept()
    {
        return  $this->HasMany('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumDepartmentEntity','photo_album_id','photo_album_id');
    }

    /**
     * 多个相册共享角色
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */
    public function hasManyRole()
    {
        return  $this->HasMany('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumRoleEntity','photo_album_id','photo_album_id');
    }

    /**
     * 多个相册共享用户
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */
    public function hasManyUser()
    {
        return  $this->HasMany('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumUserEntity','photo_album_id','photo_album_id');
    }

    /**
    * 相册权限
    *
    * @param  object $query    查询条件
    * @param  array  $userInfo 用户信息
    *
    * @return array 查询列表
    *
    * @author qishaobo
    *
    * @since  2015-11-03
    */
    public function scopePermission($query, $userInfo)
    {
        return  $query->where(function ($query) use ($userInfo) {
                    $query->where('creator', $userInfo['user_id'])
                    ->orWhere('permission', 1)
                    ->orWhere(function ($query) use ($userInfo) {
                        $query->where('permission', 2)
                        ->where(function ($query) use ($userInfo) {
                            $deptId = [$userInfo['dept_id']];
                            $roleId = $userInfo['role_id'];
                            $userId = $userInfo['user_id'];
                            $query
                            ->orWhereHas('hasManyDept', function ($query) use ($deptId) {
                                $query->wheres(['dept_id' => [$deptId, 'in']]);
                            })
                            ->orWhereHas('hasManyRole', function ($query) use ($roleId) {
                                $query->wheres(['role_id' => [$roleId, 'in']]);
                            })
                            ->orWhereHas('hasManyUser', function ($query) use ($userId) {
                                $query->wheres(['user_id' => [$userId]]);
                            });
                        });
                    })->orWhereHas('hasManyManageUser', function ($query) use ($userInfo) {
                        $query->wheres(['user_id' => [$userInfo['user_id']]]);
                    });
                });
    }

}