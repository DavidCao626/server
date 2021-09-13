<?php

namespace App\EofficeApp\Archives\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 卷库Entity类:提供卷库表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesLibraryEntity extends BaseEntity
{
    /**
     * 卷库表
     *
     * @var string
     */
	public $table = 'archives_library';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'library_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];

    /**
     * 卷库和卷库附表一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function subFields()
    {
        return  $this->HasOne('App\EofficeApp\Archives\Entities\ArchivesLibrarySubEntity','archives_library_id','library_id');
    }

    /**
     * 卷库和权限一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function libraryPermission()
    {
        return  $this->HasMany('App\EofficeApp\Archives\Entities\ArchivesLibraryPermissionEntity','library_id','library_id');
    }

    /**
     * 卷库创建人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-16
     */
    public function libraryCreatorHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','library_creator');
    }
}