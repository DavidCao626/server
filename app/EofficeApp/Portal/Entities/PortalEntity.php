<?php 
namespace App\EofficeApp\Portal\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 门户实体类
 * 
 * @author 李志军
 * 
 * @since 2015-10-27
 */
class PortalEntity extends BaseEntity
{
    public $primaryKey 		= 'portal_id';
	
    public $table 			= 'portal';
	
    public function userViewPurview()
    {
        return $this->hasMany('App\EofficeApp\Portal\Entities\PortalUserViewPurviewEntity',$this->primaryKey,  $this->primaryKey);
    }
	
	public function roleViewPurview()
    {
        return $this->hasMany('App\EofficeApp\Portal\Entities\PortalRoleViewPurviewEntity',$this->primaryKey,  $this->primaryKey);
    }
	
	public function deptViewPurview()
    {
        return $this->hasMany('App\EofficeApp\Portal\Entities\PortalDeptViewPurviewEntity',$this->primaryKey,  $this->primaryKey);
    }
	
	public function userEditPurview()
    {
        return $this->hasMany('App\EofficeApp\Portal\Entities\PortalUserEditPurviewEntity',$this->primaryKey,  $this->primaryKey);
    }
	
	public function deptEditPurview()
    {
        return $this->hasMany('App\EofficeApp\Portal\Entities\PortalDeptEditPurviewEntity',$this->primaryKey,  $this->primaryKey);
    }
	
	public function roleEditPurview()
    {
        return $this->hasMany('App\EofficeApp\Portal\Entities\PortalRoleEditPurviewEntity',$this->primaryKey,  $this->primaryKey);
    }
}
