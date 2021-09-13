<?php

namespace App\EofficeApp\Sso\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 系统配置：单点登录设置
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class SsoLoginEntity extends BaseEntity {

    use SoftDeletes;

    /** @var string $table 定义实体表 */
    public $table = 'sso_login';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'sso_login_id';

    /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

    public function ssoSystem()
    {
        return $this->belongsTo(SsoEntity::class, 'sso_id');
    }


}
