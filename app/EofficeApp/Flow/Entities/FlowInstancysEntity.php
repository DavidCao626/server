<?php
namespace App\EofficeApp\Flow\Entities;

/**
 * Description of FlowInstancysEntity
 *
 * @author lizhijun
 */
use App\EofficeApp\Base\BaseEntity;

class FlowInstancysEntity extends BaseEntity 
{
    public $table = 'flow_instancys';
    
    protected $fillable = ['instancy_id', 'instancy_name', 'sort','sign_color', 'default_selected', 'show_effect', 'creator'];
    
    public $timestamps = false;
}
