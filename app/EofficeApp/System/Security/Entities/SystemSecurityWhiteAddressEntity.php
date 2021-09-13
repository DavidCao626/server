<?php

namespace App\EofficeApp\System\Security\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * system_security_white_address表实体
 *
 */
class SystemSecurityWhiteAddressEntity extends BaseEntity
{
    /**
     * [$table 数据表名]
     *
     * @var string
     */
    protected $table = 'system_security_white_address';

    /**
     * [$table 数据表主键]
     *
     * @var string
     */
    protected $primaryKey = 'white_address_id';

    /**
     * 从属于用户
     * @return
     */
    public function belongsToUser()
    {
        return $this->belongsTo('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'user_id');
    }

    /**
     * 表明模型是否应该被打上时间戳
     *
     * @var bool
     */
    public $timestamps = false;

}