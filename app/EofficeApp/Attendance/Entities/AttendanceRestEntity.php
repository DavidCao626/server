<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRestEntity extends BaseEntity 
{
    use SoftDeletes;

    /** @var string $table 定义实体表 */
    public $table = 'attend_rest';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'rest_id';
    
    public $timestamps = false;

    protected $fillable = ['rest_name','scheme_id','start_date', 'end_date'];

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];
}




