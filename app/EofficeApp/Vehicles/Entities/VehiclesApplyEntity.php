<?php
namespace App\EofficeApp\Vehicles\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 用车申请
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class VehiclesApplyEntity extends BaseEntity
{
    use SoftDeletes;

    const APPLY_STATUS_TO_AUDIT = 1;    // 待审核
    const APPLY_STATUS_APPROVED = 2;    // 批准
    const APPLY_STATUS_REFUSED = 3;     // 拒绝
    const APPLY_STATUS_RETURNED = 4;    // 归还
    const APPLY_STATUS_ACCEPTED = 5;    // 验收
    const APPLY_STATUS_REJECT = 6;      // 已驳回
    const APPLY_STATUS_FINISHED = [self::APPLY_STATUS_REFUSED, self::APPLY_STATUS_ACCEPTED]; // 车辆申请结束状态

    /** @var string $table 定义实体表 */
    public $table = 'vehicles_apply';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'vehicles_apply_id';

    /** @var array $guarded 保护字段 */
    protected $guarded = ['vehicles_apply_id'];

    /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

    /**
     * [vehiclesApplyBelongsToUserSystemInfo 申请记录与用户系统信息多对一关系]
     *
     * @author miaochenchen
     *
     * @since  2016-12-05 创建
     *
     * @return [object]             [关联关系]
     */
    public function vehiclesApplyBelongsToUserSystemInfo()
    {
        return $this->belongsTo('App\EofficeApp\User\Entities\UserSystemInfoEntity','vehicles_apply_apply_user','user_id');
    }

}
