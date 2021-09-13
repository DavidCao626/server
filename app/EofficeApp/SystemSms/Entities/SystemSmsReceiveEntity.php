<?php

namespace app\EofficeApp\SystemSms\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 内部消息实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class SystemSmsReceiveEntity extends BaseEntity {

    use SoftDeletes;

    /** @var string $table 定义实体表 */
    public $table = 'system_sms_receive';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'id';

    public $timestamps = false;

    /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

}
