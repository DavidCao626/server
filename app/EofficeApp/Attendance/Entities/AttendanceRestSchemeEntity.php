<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRestSchemeEntity extends BaseEntity
{
    use SoftDeletes;

    /** @var string $table 定义实体表 */
    public $table = 'attend_rest_scheme';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'scheme_id';

    public $timestamps = true;

    protected $fillable = ['scheme_name', 'status', 'color'];

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
     public $dates = ['deleted_at'];

    /**
     * 节假日方案表和节假日建立关联关系，一对多
     */
    public function rest()
    {
        return $this->hasMany('App\EofficeApp\Attendance\Entities\AttendanceRestEntity', 'scheme_id');
    }
}




