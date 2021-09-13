<?php

namespace App\EofficeApp\System\Prompt\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 提示语Entity类:提供提示语数据表实体
 *
 * @author qishaobo
 *
 * @since  2016-12-28 创建
 */
class PromptEntity extends BaseEntity
{
    /**
     * 提示语数据表
     *
     * @var string
     */
	public $table = 'prompt';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'prompt_id';

    /** @var bool $timestamps 是否使用created_at和updated_at */
    public $timestamps = false;

    /**
     * 提示语分类
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-12-30 创建
     */
    public function promptType()
    {
        return  $this->hasOne('App\EofficeApp\System\Prompt\Entities\PromptTypeEntity', 'prompt_type_id', 'prompt_type_id');
    }
}
