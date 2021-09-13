<?php
namespace App\EofficeApp\User\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 用户状态表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class UserSystemInfoEntity extends BaseEntity
{
    use SoftDeletes;

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    /**
     * 用户系统信息数据表
     *
     * @var string
     */
    public $table = 'user_system_info';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'user_id';
    public $casts = ['user_id' => 'string'];

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
     * [userSystemInfoBelongsToDepartment 和部门表的对应关系]
     *
     * @author 朱从玺
     *
     * @since  2015-10-26 创建
     *
     * @return [object]                            [对应关系]
     */
    public function userSystemInfoBelongsToDepartment()
    {
        return  $this->belongsTo('App\EofficeApp\System\Department\Entities\DepartmentEntity', 'dept_id','dept_id');
    }

    /**
     * [userSystemInfoBelongsToUserStatus 和用户状态表的对应关系]
     *
     * @author 缪晨晨
     *
     * @since  2017-04-13 创建
     *
     * @return [object]                            [对应关系]
     */
    public function userSystemInfoBelongsToUserStatus()
    {
        return  $this->belongsTo('App\EofficeApp\User\Entities\UserStatusEntity', 'user_status','status_id');
    } 
     /**
     * 用户对应多个流程
     *
     * @return object
     */
    public function userHasManyFlowRunProcess()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowRunProcessEntity', 'user_id', 'user_id');
    }   
}
