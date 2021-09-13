<?php
namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;
class AttendanceWifiEntity extends BaseEntity 
{
    protected $table = 'attend_wifi';
    protected $fillable = ['attend_wifi_id', 'attend_wifi_name', 'attend_wifi_mac', 'attend_all', 'attend_user', 'attend_dept', 'attend_role'];
    public $primaryKey = 'attend_wifi_id';

}
