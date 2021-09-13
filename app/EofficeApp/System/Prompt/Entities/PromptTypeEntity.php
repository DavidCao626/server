<?php

namespace App\EofficeApp\System\Prompt\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 提示语类别Entity类:提供提示语类别数据表实体
 *
 * @author qishaobo
 *
 * @since  2016-12-28 创建
 */
class PromptTypeEntity extends BaseEntity
{
    /**
     * 提示语类别数据表
     *
     * @var string
     */
	public $table = 'prompt_type';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'prompt_type_id';

    /** @var bool $timestamps 是否使用created_at和updated_at */
    public $timestamps = false;

    /**
     * 多条提示语
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function prompts()
    {
        return  $this->hasMany('App\EofficeApp\System\Prompt\Entities\PromptEntity', 'prompt_type_id', 'prompt_type_id');
    }

}
