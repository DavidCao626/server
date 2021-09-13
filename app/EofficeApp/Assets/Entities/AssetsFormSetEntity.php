<?php

namespace App\EofficeApp\Assets\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 *
 *
 * @author zw
 *
 * @since  2019-12-17 创建
 */
class AssetsFormSetEntity extends BaseEntity
{
    /** @var string $table 资产相关表单设置表 */
	public $table = 'assets_form_set';

    /** @var bool $timestamps 是否使用created_at和updated_at */
    public $timestamps = true;

    /** @var string $primaryKey 主键 */
    public $primaryKey = 'id';

}