<?php

namespace App\EofficeApp\Performance\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * performance_performer数据表实体
 *
 * @author  朱从玺
 *
 * @since   2015-10-22
 *
 */
class PerformancePerformerEntity extends BaseEntity
{
    /**
     * [$table 数据表名]
     *
     * @var string
     */
    protected $table = 'performance_performer';

    /**
     * [$fillable 允许批量赋值的字段]
     *
     * @var [array]
     */
    protected $fillable = ['performance_user', 'user_performer', 'user_performer_status', 'user_approve_status'];

    public function performerHasOneUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'performance_user');
    }

    /**
     * 和 UserSystemInfo 的对应关系
     *
     * @return object
     */
    public function userHasOneSystemInfo()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserSystemInfoEntity','user_id','performance_user');
    }
}
