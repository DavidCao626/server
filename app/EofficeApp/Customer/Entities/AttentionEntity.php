<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttentionEntity extends BaseEntity
{
    /** @var string 客户关注表 */
	public $table = 'customer_attention';

    public $timestamps = false;

    /**
     * 客户关注人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-24
     */
    public function hasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }
}