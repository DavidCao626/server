<?php
namespace App\EofficeApp\Vote\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 调查表投票记录实体
 *
 * @author 史瑶
 *
 * @since  2015-06-21 创建
 */
class VoteLogEntity extends BaseEntity
{
    

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
    public $table = 'vote_log';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'vote_log_id';

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
     * 投票人信息
     *
     */
    function voteUserInfo()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }
}
