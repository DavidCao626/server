<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 模板设置信息表
 *
 * @author dp
 *
 * @since  2017-06-20 创建
 */
class DiaryTemplateSetEntity extends BaseEntity
{
    /**
     * 模板设置信息表
     *
     * @var string
     */
	public $table = 'diary_template_set';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'set_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];


    /**
     * 有多个范围内用户
     *
     * @return object
     *
     * @author dp
     *
     * @since  2016-01-27
     */
    public function hasManyUser()
    {
        return  $this->HasMany('App\EofficeApp\Diary\Entities\DiaryTemplateSetUserEntity', 'set_id','set_id');
    }
}
