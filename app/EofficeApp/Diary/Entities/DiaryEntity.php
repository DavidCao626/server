<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 微博Entity类:提供微博实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class DiaryEntity extends BaseEntity
{
    /**
     * 微博日志数据表
     *
     * @var string
     */
	public $table = 'diary';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'diary_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];

    /**  @var array 关联字段 */
    public $relationFields = [
        'fields' => [
            'replys'                    => ['diary_reply_id', 'diary_reply_content', 'replys_created_at' => 'created_at'],
            'replys.hasOneUser'         => ['replys_user_name' => 'user_name'],
            'user'                      => ['diary_user_name' => 'user_name']
        ],
        'relation' => [
            'replys'                    => ['diary_reply_id', 'diary_id'],
            'replys.hasOneUser'         => ['user_id', 'diary_id'],
            'user'                      => ['user_id', 'user_id']
        ]
    ];

    /**
     * 与微博回复关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function replys()
    {
        return  $this->hasMany('App\EofficeApp\Diary\Entities\DiaryReplyEntity', 'diary_id','diary_id');
    }

    /**
     * 与微博点赞人关系
     * @return  object
     */
    public function diaryLikePeople()
    {
        return $this->hasMany('App\EofficeApp\Diary\Entities\DiaryLikePeopleEntity', 'diary_id','diary_id');
    }

    /**
     * 与用户表关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function user()
    {
        return  $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id','user_id');
    }

    public function userSystemInfo()
    {
        return $this->belongsTo('App\EofficeApp\User\Entities\UserSystemInfoEntity','user_id','user_id');
    }

    public function userHasManyRole()
    {
        return $this->hasMany('App\EofficeApp\Role\Entities\UserRoleEntity','user_id','user_id');
    }



}
