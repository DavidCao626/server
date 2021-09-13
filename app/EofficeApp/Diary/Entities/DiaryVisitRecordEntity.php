<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 微博浏览记录Entity类:提供微博浏览记录实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class DiaryVisitRecordEntity extends BaseEntity
{
    /**
     * 微博日志浏览记录数据表
     *
     * @var string
     */
	public $table = 'diary_visit_record';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'visit_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];

    /**
     * 微博访问人和用户表一对一关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-21
     */
    public function userVisitPerson()
    {
        return  $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'visit_person');
    }

    /**
     * 微博被访问人和用户表一对一关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-21
     */
    public function userVisitToPerson()
    {
        return  $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'visit_to_person');
    }

    /**
     * 微博访问人和用户表一对一关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-21
     */
    public function userVisitSystemPerson()
    {
        return  $this->hasOne('App\EofficeApp\User\Entities\UserSystemInfoEntity', 'user_id', 'visit_person');
    }

    /**
     * 微博被访问人和用户表一对一关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-21
     */
    public function userVisitToSystemPerson()
    {
        return  $this->hasOne('App\EofficeApp\User\Entities\UserSystemInfoEntity', 'user_id', 'visit_to_person');
    }
}
