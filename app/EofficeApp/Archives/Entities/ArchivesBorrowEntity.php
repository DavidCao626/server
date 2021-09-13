<?php

namespace App\EofficeApp\Archives\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 档案借阅Entity类:提供档案借阅表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesBorrowEntity extends BaseEntity
{
    use SoftDeletes;

    /**
     * 档案借阅表
     *
     * @var string
     */
	public $table = 'archives_borrow';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'borrow_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];

    /**
     * 档案借阅和档案借阅附表一对一
     *
     * @return object
     */
    public function subFields()
    {
        return  $this->HasOne('App\EofficeApp\Archives\Entities\ArchivesBorrowSubEntity','archives_borrow_id','borrow_id');
    }

    /**
     * 档案借阅案卷和案卷表一对一
     *
     * @return object
     */
    public function borrowVolume()
    {
        return  $this->HasOne('App\EofficeApp\Archives\Entities\ArchivesVolumeEntity','volume_id','borrow_data_id');
    }

    /**
     * 档案借阅文件和档案文件表一对一
     *
     * @return object
     */
    public function borrowFile()
    {
        return  $this->HasOne('App\EofficeApp\Archives\Entities\ArchivesFileEntity','file_id','borrow_data_id');
    }

    /**
     * 档案借阅人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-16
     */
    public function borrowCreatorHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','borrow_user_id');
    }

    /**
     * 档案审核人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-06
     */
    public function borrowAuditorHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','auditor_id');
    }

    /**
     * 档案收回人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-06
     */
    public function takeBackUserHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','take_back_user');
    }
}