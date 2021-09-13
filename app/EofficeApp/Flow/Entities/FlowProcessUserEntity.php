<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程分表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowProcessUserEntity extends BaseEntity
{
    /**
     * 流程分表
     *
     * @var string
     */
	public $table = 'flow_process_user';
    public $timestamps = false;
    public $primaryKey = 'auto_id';
    public $sort = 'asc';

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
