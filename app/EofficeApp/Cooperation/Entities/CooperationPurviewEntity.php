<?php
namespace App\EofficeApp\Cooperation\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 协作区权限表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationPurviewEntity extends BaseEntity
{
    /**
     * 协作区权限表
     *
     * @var string
     */
	public $table = 'cooperation_purview';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'purview_id';

    /**
     * 默认排序
     *
     * @var string
     */
	public $sort = 'desc';

    /**
     * 默认每页条数
     *
     * @var int
     */
	public $perPage = 10;

    /**
     * 权限对应人员
     *
     * @method hasOneDept
     *
     * @return boolean    [description]
     */
    public function purviewHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }
}
