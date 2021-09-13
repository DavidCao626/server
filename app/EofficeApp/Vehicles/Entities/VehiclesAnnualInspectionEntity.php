<?php


namespace App\EofficeApp\Vehicles\Entities;


use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehiclesAnnualInspectionEntity extends BaseEntity
{
    use SoftDeletes;

    /** @var string $table 定义实体表 */
    public $table = 'vehicles_annual_inspection';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'vehicles_annual_inspection_id';

    /** @var array $guarded 保护字段 */
    protected $guarded = ['vehicles_annual_inspection_id'];

    /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];
}