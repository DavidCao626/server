<?php
namespace App\EofficeApp\System\Template\Entities;

use App\EofficeApp\Base\BaseEntity;

class TemplateHtmlEntity extends BaseEntity
{
    protected $table            = 'template_html';
	
    public $primaryKey          = 'template_id';
}
