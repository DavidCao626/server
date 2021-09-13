<?php
namespace App\EofficeApp\Vehicles\Entities;

use App\EofficeApp\Base\BaseEntity;


class VehiclesSortMemberUserEntity extends BaseEntity
{
    /**
     * 用车分类权限表
     *
     * @var string
     */
	public $table = 'vehicles_sort_member_user';

    /**
     * 对应用户
     *
     * @method hasOneDept
     *
     * @return boolean    [description]
     */
    public function hasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }
}
