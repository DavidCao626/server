<?php


namespace App\EofficeApp\Vehicles\Entities;


use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehiclesInsuranceEntity extends BaseEntity
{
    use SoftDeletes;

    /** @var string $table 定义实体表 */
    public $table = 'vehicles_insurance';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'vehicles_insurance_id';

    /** @var array $guarded 保护字段 */
    protected $guarded = ['vehicles_insurance_id'];

    /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];
}