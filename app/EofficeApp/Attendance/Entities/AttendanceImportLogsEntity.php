<?php
namespace App\EofficeApp\Attendance\Entities;

use App\EofficeApp\Base\BaseEntity;

class AttendanceImportLogsEntity extends BaseEntity 
{
    public $table = 'attend_import_logs';
    public $primaryKey = 'log_id';
    protected $fillable = ['creator', 'import_datetime'];
    public $timestamps = false;
}
