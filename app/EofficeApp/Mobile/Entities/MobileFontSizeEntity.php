<?php

namespace App\EofficeApp\Mobile\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 手机版字体大小实体类
 *
 * @author 李志军
 *
 * @since 2019-12-17
 */
class MobileFontSizeEntity extends BaseEntity 
{
    public $table = 'mobile_font_size';
    public $timestamps = false;
    protected $fillable = ['user_id', 'font_size'];
}
