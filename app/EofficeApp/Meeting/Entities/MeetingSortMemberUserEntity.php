<?php
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;

class MeetingSortMemberUserEntity extends BaseEntity
{
    /**
     * 协作区分类权限表
     *
     * @var string
     */
	public $table = 'meeting_sort_member_user';

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
