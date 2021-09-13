<?php
namespace App\EofficeApp\Lang\Entities;

use App\EofficeApp\Base\BaseEntity;
class LangPackageEntity extends BaseEntity 
{
    protected $table = 'lang_package';
	
    public $primaryKey = 'lang_id';
   
    protected $fillable = ['lang_code', 'lang_name', 'effect','sort', 'remark'];
}
