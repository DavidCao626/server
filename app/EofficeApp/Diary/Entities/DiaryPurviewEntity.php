<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 *
 * 微博设置Entity类:提供微博设置选项数据。
 *
 * Class DiaryPurviewEntity
 * @package App\EofficeApp\Diary\Entities
 */
class DiaryPurviewEntity extends BaseEntity
{
    /**
     * 微博便签数据表
     *
     * @var string
     */
    public $table = 'diary_purview';
    /**
     * [$table 数据表主键]
     *
     * @var string
     */
    protected $primaryKey = 'id';

}
