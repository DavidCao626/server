<?php

namespace App\EofficeApp\Calendar\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 微博关注分组Entity
 *
 * @author lixuanxuan
 *
 * @since  2018-11-13 创建
 */
class CalendarAttentionGroupEntity extends BaseEntity
{
    /**
     * 微博日志关注人数据表
     *
     * @var string
     */
    public $table = 'calendar_attention_group';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'group_id';

    /**
     * 有多个用户
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function users()
    {
        return  $this->hasMany('App\EofficeApp\Calendar\Entities\CalendarAttentionEntity', 'group_id','group_id');
    }

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = ['group_name','user_id','field_sort'];

    /**
     * 执行模型是否自动维护时间戳.
     *
     * @var bool
     */
    public $timestamps = false;
}
