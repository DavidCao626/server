<?php

namespace App\EofficeApp\System\Cas\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * cas_params Entity类:提供cas_params 数据表实体
 *
 * @author 缪晨晨
 *
 * @since  2018-01-29 创建
 */
class CasParamsEntity extends BaseEntity
{
    /**
     * cas_params表
     *
     * @var string
     */
	public $table = 'cas_params';


    /**
     * 是否使用created_at和updated_at
     *
     * @var bool
     */
    public $timestamps = false;
}
