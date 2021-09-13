<?php
namespace App\EofficeApp\User\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 用户实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class UserEntity extends BaseEntity
{
    use SoftDeletes;

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * 用户数据表
     *
     * @var string
     */
    public $table = 'user';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey   = 'user_id';
    public $incrementing = false;

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
     * 和 UserInfo 的对应关系
     *
     * @return object
     */
    public function userHasOneInfo()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserInfoEntity', 'user_id', 'user_id');
    }

    /**
     * 和 UserSystemInfo 的对应关系
     *
     * @return object
     */
    public function userHasOneSystemInfo()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserSystemInfoEntity', 'user_id', 'user_id');
    }

    /**
     * 用户和角色一对多的对应关系
     *
     * @return object
     */
    public function userHasManyRole()
    {
        return $this->hasMany('App\EofficeApp\Role\Entities\UserRoleEntity', 'user_id', 'user_id');
    }

    /**
     * 和微博日志的对应关系
     *
     * @return object
     */
    public function diary()
    {
        return $this->hasMany('App\EofficeApp\Diary\Entities\DiaryEntity', 'user_id', 'user_id');
    }

    /**
     * 和用户排班表的对应关系
     *
     * @return object
     */
    public function userHasOneAttendanceSchedulingInfo()
    {
        return $this->hasOne('App\EofficeApp\Attendance\Entities\AttendanceSchedulingUserEntity', 'user_id', 'user_id');
    }

    /**
     * 用户和角色多对多
     *
     * @return object
     */
    public function userRole()
    {
        return $this->belongsToMany('App\EofficeApp\Role\Entities\RoleEntity', 'user_role', 'user_id', 'role_id');
    }

    /**
     * 用户和部门远层一对一
     *
     * @return object
     */
    public function userToDept()
    {
        return $this->belongsToMany('App\EofficeApp\System\Department\Entities\DepartmentEntity', 'user_system_info', 'user_id', 'dept_id');
    }

    /**
     * 用户和薪酬一对一
     *
     * @return object
     */
    public function userToSalary()
    {
        return $this->hasMany('App\EofficeApp\Salary\Entities\SalaryEntity', 'user_id', 'user_id');
    }

    /**
     * 薪酬项个人基准值
     */
    public function salaryPersonalDefault()
    {
        return $this->hasMany('App\EofficeApp\Salary\Entities\SalaryFieldPersonalDefaultEntity', 'user_id', 'user_id');
    }

    /**
     * 用户对应多个上级
     *
     * @return object
     */
    public function userHasManySuperior()
    {
        return $this->hasMany('App\EofficeApp\Role\Entities\UserSuperiorEntity', 'user_id', 'user_id');
    }

    /**
     * 用户对应多个下级
     *
     * @return object
     */
    public function userHasManySubordinate()
    {
        return $this->hasMany('App\EofficeApp\Role\Entities\UserSuperiorEntity', 'superior_user_id', 'user_id');
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
    /**
     * 和 user_secext 的对应关系
     *
     * @return object
     */
    public function userHasOneSecextInfo()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserSecextEntity','user_id','user_id');
    }
    //复杂查询测试
    //朱从玺
    public $allFields = [
        'userDept'      => ['userHasOneSystemInfo', 'dept_id'],
        'userDuty'      => ['userHasOneSystemInfo', 'duty_type'],
        'userStatus'    => ['userHasOneSystemInfo', 'user_status'],
        'userLastVisit'   => ['userHasOneSystemInfo', 'last_visit_time'],
        'userLastPass'    => ['userHasOneSystemInfo', 'last_pass_time'],
        'userSmsLogin'    => ['userHasOneSystemInfo', 'sms_login'],
        'userShortcut'    => ['userHasOneSystemInfo', 'shortcut'],
        'deptName'     => ['userHasOneSystemInfo.userSystemInfoBelongsToDepartment', 'dept_name'],
        'userBirthday'     => ['userHasOneInfo', 'birthday'],
        'userHome'     => ['userHasOneInfo', 'home_address'],
        'userMsn'     => ['userHasOneInfo', 'msn']
    ];

    public $relationFields = [
        'userHasOneSystemInfo' => ['userDept', 'userDuty', 'userStatus', 'userLastVisit', 'userLastPass', 'userSmsLogin', 'userShortcut'],
        'userHasOneSystemInfo.userSystemInfoBelongsToDepartment' => ['deptName'],
        'userHasOneInfo' => ['userBirthday', 'userHome', 'userMsn']
    ];

    public $relation = [
        'userHasOneSystemInfo'                       => ['user_id', 'user_id'],
        //'userHasOneSystemInfo.userSystemInfoBelongsToDepartment'                       => ['dept_id', 'dept_id'],
        'userHasOneInfo'                       => ['user_id', 'user_id']
    ];





    // public $relationFields = [
    //     'fields' => [
    //         'userHasOneSystemInfo' => ['userDept' => 'dept_id', 'userDuty' => 'duty_type', 'userStatus' => 'user_status', 'userLastVisit' => 'last_visit_time', 'userLastPass' => 'last_pass_time', 'userSmsLogin' => 'sms_login', 'userShortcut' => 'shortcut'],
    //         'userHasOneSystemInfo.userSystemInfoBelongsToDepartment' => ['deptName' => 'dept_name'],
    //         'userHasOneInfo' => ['userBirthday'=> 'birthday', 'userHome' => 'home_address', 'userMsn' => 'msn']
    //     ],
    //     'relation' => [
    //         'userHasOneSystemInfo'                       => ['user_id', 'user_id'],
    //         'userHasOneSystemInfo.userSystemInfoBelongsToDepartment'                       => ['dept_id', 'dept_id'],
    //         'userHasOneInfo'                       => ['user_id', 'user_id']
    //     ]
    // ];

}
