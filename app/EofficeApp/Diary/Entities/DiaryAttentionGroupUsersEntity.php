<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 微博关注分组用户Entity
 *
 * @author lixuanxuan
 *
 * @since  2018-11-13 创建
 */
class DiaryAttentionGroupUsersEntity extends BaseEntity
{
    /**
     * 微博日志关注人数据表
     *
     * @var string
     */
    public $table = 'diary_attention_group_users';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'group_users_id';

    /**
     * 和关注组表一对一关系
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @auther lixuanxuan
     * @since 2018-11-13
     */
    public function belongsGroup()
    {
        return $this->belongsTo('App\EofficeApp\Diary\Entities\DiaryAttentionGroupEntity','group_id','group_id');
    }

    /**
     * 和用户表一对一关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function userInfo()
    {
        return  $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id','user_id');
    }

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = ['group_id','user_id'];

    /**
     * 执行模型是否自动维护时间戳.
     *
     * @var bool
     */
    public $timestamps = false;
}
