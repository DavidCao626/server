<?php

namespace App\EofficeApp\Archives\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 档案日志Entity类:提供档案日志表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesLogEntity extends BaseEntity
{
    /**
     * 档案管理日志表
     *
     * @var string
     */
	public $table = 'archives_log';

    /**
     * 是否使用created_at和updated_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'log_id';

    /**
     * 日志创建人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-21
     */
    public function logCreatorHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }
}
