<?php

namespace App\EofficeApp\Archives\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 案卷Entity类:提供案卷表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesVolumeEntity extends BaseEntity
{
    use SoftDeletes;

    /**
     * 案卷表
     *
     * @var string
     */
	public $table = 'archives_volume';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'volume_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];


    /**
     * 案卷和案卷附表一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function subFields()
    {
        return  $this->HasOne('App\EofficeApp\Archives\Entities\ArchivesVolumeSubEntity','archives_volume_id','volume_id');
    }

    /**
     * 档案和文件一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function volumeFiles()
    {
        return  $this->HasMany('App\EofficeApp\Archives\Entities\ArchivesFileEntity','volume_id','volume_id');
    }

    /**
     * 案卷和卷库一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-16
     */
    public function volumeHasOneLibrary()
    {
        return  $this->HasOne('App\EofficeApp\Archives\Entities\ArchivesLibraryEntity','library_id','library_id');
    }

    /**
     * 案卷创建人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-16
     */
    public function volumeCreatorHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','volume_creator');
    }
}