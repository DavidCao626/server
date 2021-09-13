<?php
namespace App\EofficeApp\LogCenter\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * Description of LogModuleConfigEntity
 *
 * @author lizhijun
 */
class LogModuleConfigEntity extends BaseEntity
{
    public $table = 'eo_log_module_config';
    protected $fillable = ['module_key', 'module_id', 'options'];
    public $timestamps = false;
}
