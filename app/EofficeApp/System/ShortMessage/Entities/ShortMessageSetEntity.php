<?php

namespace App\EofficeApp\System\ShortMessage\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 手机短信Entity类:提供手机短信数据表实体
 *
 * @author qishaobo
 *
 * @since  2017-03-06 创建
 */
class ShortMessageSetEntity extends BaseEntity
{
    /**
     * 手机短信数据表
     *
     * @var string
     */
	public $table = 'short_message_set';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'sms_id';

    public $timestamps = false;

 }