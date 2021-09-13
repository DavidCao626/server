<?php

namespace App\EofficeApp\Archives\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 档案文件Entity类:提供档案文件表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesFileEntity extends BaseEntity
{
    use SoftDeletes;

    /**
     * 档案管理文件表
     *
     * @var string
     */
	public $table = 'archives_file';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'file_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];

    /**
     * 档案文件和档案文件附表一对一
     *
     * @return object
     */
    public function subFields()
    {
        return  $this->HasOne('App\EofficeApp\Archives\Entities\ArchivesFileSubEntity','archives_file_id','file_id');
    }

    /**
     * 文件和案卷一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-16
     */
    public function fileHasOneVolume()
    {
        return  $this->HasOne('App\EofficeApp\Archives\Entities\ArchivesVolumeEntity','volume_id','volume_id');
    }

    /**
     * 文件创建人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-16
     */
    public function fileCreatorHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','file_creator');
    }
}