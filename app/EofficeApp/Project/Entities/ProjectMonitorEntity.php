<?php
namespace app\EofficeApp\Project\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 监控权限 实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class ProjectMonitorEntity extends BaseEntity {
    
    //use SoftDeletes;
    
     /** @var string $table 定义实体表 */
    public $table = 'project_monitor';
    
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'monitor_id';
 
     /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

  
}
