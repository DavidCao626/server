<?php
namespace App\EofficeApp\Cooperation\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 协作主题权限表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationSubjectUserEntity extends BaseEntity
{
    /**
     * 协作主题权限表
     *
     * @var string
     */
	public $table = 'cooperation_subject_user';

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
