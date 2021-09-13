<?php
namespace App\EofficeApp\Meeting\Entities;

use App\EofficeApp\Base\BaseEntity;

class MeetingSubjectUserEntity extends BaseEntity
{
    /**
     * 协作主题权限表
     *
     * @var string
     */
	public $table = 'meeting_apply_member_user';

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
