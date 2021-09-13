<?php

namespace App\EofficeApp\System\ShortMessage\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 手机短信配置模板Entity类
 *
 * @author qishaobo
 *
 * @since  2017-03-06 创建
 */
class ShortMessageTemplateEntity extends BaseEntity
{
    /**
     * 手机短信数据表
     *
     * @var string
     */
	public $table = 'short_message_template';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'template_id';

    public $timestamps = false;

 }