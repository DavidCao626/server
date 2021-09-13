<?php
namespace app\EofficeApp\IpRules\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 访问控制实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class IpRulesEntity extends BaseEntity {
    
    use SoftDeletes;
    
     /** @var string $table 定义实体表 */
    public $table = 'ip_rules';
    
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'ip_rules_id';
    
     /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

  
}
