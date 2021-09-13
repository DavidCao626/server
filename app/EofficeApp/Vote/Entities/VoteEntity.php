<?php
namespace App\EofficeApp\Vote\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 调查表实体
 *
 * @author 史瑶
 *
 * @since  2015-06-21 创建
 */
class VoteEntity extends BaseEntity
{
    use SoftDeletes;

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * 调查表数据表
     *
     * @var string
     */
	public $table = 'vote_manage';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'id';

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
     * 创建者信息
     *
     */
    function voteCreateInfo()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','creator');
    }
    /**
     * 调查表设计器控件
     *
     */
    function voteHasManyControl()
    {
        return $this->hasMany('App\EofficeApp\Vote\Entities\VoteControlDesignerEntity','vote_id','id');
    }
    /**
     * 对应参与人员
     *
     */
    function voteHasManyUser()
    {
        return $this->hasMany('App\EofficeApp\Vote\Entities\VoteUserEntity','vote_id','id');
    }
    /**
     * 对应参与部门
     *
     */
    function voteHasManyDept()
    {
        return $this->hasMany('App\EofficeApp\Vote\Entities\VoteDeptEntity','vote_id','id');
    }
    /**
     * 对应参与角色
     *
     */
    function voteHasManyRole()
    {
        return $this->hasMany('App\EofficeApp\Vote\Entities\VoteRoleEntity','vote_id','id');
    }
    /**
     * 对应样式
     *
     */
    function voteHasOneTemplate()
    {
        return $this->hasOne('App\EofficeApp\Vote\Entities\VoteModeEntity','mode_id','template');
    }


}
