<?php
namespace App\EofficeApp\User\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 用户个人信息实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class UserInfoEntity extends BaseEntity
{
    use SoftDeletes;

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
	/**
     * 用户个人信息数据表
     *
     * @var string
     */
	public $table = 'user_info';

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
     * 和 UserSystemInfo 的对应关系
     *
     * @return object
     */
    public function userInfoHasOneSystemInfo()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserSystemInfoEntity','user_id','user_id');
    }

}
