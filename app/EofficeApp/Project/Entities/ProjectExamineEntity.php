<?php
namespace app\EofficeApp\Project\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 审批权限实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class ProjectExamineEntity extends BaseEntity {
    
    //use SoftDeletes;
    
     /** @var string $table 定义实体表 */
    public $table = 'project_examine';
    
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'exam_id';
 
     /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

  
}
