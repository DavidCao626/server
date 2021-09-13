<?php
namespace app\EofficeApp\PublicGroup\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 公共用户组实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class PublicGroupEntity extends BaseEntity {
    
    use SoftDeletes;
    
     /** @var string $table 定义实体表 */
    public $table = 'public_group';
    
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'group_id';
    
     /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];


    public function groupHasManyUser()
    {
    	return $this->hasMany('App\EofficeApp\PublicGroup\Entities\PublicGroupUserEntity','group_id','group_id');
    }
    public function groupHasManyRole()
    {
    	return $this->hasMany('App\EofficeApp\PublicGroup\Entities\PublicGroupRoleEntity','group_id','group_id');
    }
    public function groupHasManyDept()
    {
    	return $this->hasMany('App\EofficeApp\PublicGroup\Entities\PublicGroupDeptEntity','group_id','group_id');
    }
    public function groupHasManyMember()
    {
    	return $this->hasMany('App\EofficeApp\PublicGroup\Entities\PublicGroupMemberEntity','group_id','group_id');
    }
  
}
