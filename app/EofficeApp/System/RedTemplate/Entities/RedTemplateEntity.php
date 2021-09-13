<?php

namespace App\EofficeApp\System\RedTemplate\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 套红模板Entity类:提供套红模板数据表实体
 *
 * @author miaochenchen
 *
 * @since  2016-09-28 创建
 */
class RedTemplateEntity extends BaseEntity
{
    /**
     * 套红模板数据表
     *
     * @var string
     */
	public $table = 'red_template';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'template_id';
 }
