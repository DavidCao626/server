<?php

namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceMobileRecordsEntity extends BaseEntity 
{

    /** @var string $table 定义实体表 */
    public $table = 'attend_mobile_records';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'record_id';
    
    protected $fillable = ['user_id', 'sign_date', 'sign_time', 'ip', 'long', 'lat', 'address','customer_id' ,'platform','sign_type','sign_status', 'remark', 'wifi_name', 'wifi_mac', 'sign_category'];
}




