<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 微博模板内容Entity类。
 *
 * @author lixuanxuan
 *
 * @since  2018-11-24 创建
 */
class DiaryTemplateContentEntity extends BaseEntity
{
    /**
     * 微博模板内容数据表
     *
     * @var string
     */
    public $table = 'diary_template_content';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'content_id';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = ['report_kind_id','template_kind_id','content'];

    /**
     * 执行模型是否自动维护时间戳.
     *
     * @var bool
     */
    public $timestamps = false;

}
