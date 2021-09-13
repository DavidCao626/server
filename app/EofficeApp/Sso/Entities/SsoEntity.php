<?php

namespace App\EofficeApp\Sso\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 外部系统：配置用户项
 *
 * @author:喻威
 *
 * @since：2015-10-27
 *
 */
class SsoEntity extends BaseEntity {

    use SoftDeletes;

    /** @var string $table 定义实体表 */
    public $table = 'sso';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'sso_id';

    /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];



}
