<?php
namespace App\EofficeApp\User\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 用户即时通讯已登录信息实体
 */
class UserSocketEntity extends BaseEntity
{
	public $table = 'user_instant_message_socket';
    public $timestamps = false;
}
