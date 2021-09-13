<?php

namespace App\EofficeApp\System\Remind\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * reminds表实体
 *
 * @author  朱从玺
 *
 * @since  2015-10-28 创建
 */
class RemindsEntity extends BaseEntity {

    /**
     * [$table 数据表名]
     * 
     * @var string
     */
    protected $table = 'reminds';

    /**
     * [$fillable 允许被赋值的字段]
     * 
     * @var [array]
     */
    protected $fillable = ['id', 'reminds', 'icon'];

}
