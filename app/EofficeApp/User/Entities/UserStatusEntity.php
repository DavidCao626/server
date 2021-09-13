<?php
namespace App\EofficeApp\User\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 用户状态表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class UserStatusEntity extends BaseEntity
{

    /**
     * 用户状态数据表
     *
     * @var string
     */
    public $table = 'user_status';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'status_id';

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
     * 和 UserSystemInfo 的对应关系
     *
     * @return object
     */
    public function userStatusHasManySystemInfo() {
        return $this->hasMany('App\EofficeApp\User\Entities\UserSystemInfoEntity', 'user_status','status_id');
    }
}
