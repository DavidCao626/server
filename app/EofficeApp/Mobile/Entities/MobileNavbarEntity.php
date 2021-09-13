<?php

namespace App\EofficeApp\Mobile\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 公告实体类
 *
 * @author 李志军
 *
 * @since 2015-10-17
 */
class MobileNavbarEntity extends BaseEntity 
{

    public $primaryKey = 'navbar_id';
    public $table = 'mobile_navbar';
    public $timestamps = false;
    protected $fillable = ['navbar_name', 'navbar_url', 'is_system', 'has_children', 'sort', 'parent_id', 'is_open', 'params', 'navbar_category'];
}
