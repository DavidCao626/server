<?php

namespace App\EofficeApp\Role\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 用户上下级Entity类:提供用户上下级表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class UserSuperiorEntity extends BaseEntity
{
    /**
     * 用户上下级表
     *
     * @var string
     */
	protected $table = 'user_superior';

    /**
     * 是否使用created_at和updated_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 用户下级和用户表一对一关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-24
     */
    public function subordinateHasOneUser()
    {
        return  $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id','user_id');
    }

    /**
     * 用户上级级和用户表一对一关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-24
     */
    public function superiorHasOneUser()
    {
        return  $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id','superior_user_id');
    }

    /**
     * 和 UserSystemInfo 的对应关系
     *
     * @return object
     */
    public function userHasOneSystemInfo()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserSystemInfoEntity','user_id','user_id');
    }
}
