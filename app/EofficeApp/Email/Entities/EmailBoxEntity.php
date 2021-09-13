<?php
namespace App\EofficeApp\Email\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 邮件-文件夹实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class EmailBoxEntity extends BaseEntity {
    
    use SoftDeletes;
    
     /** @var string $table 定义实体表 */
    public $table = 'email_box';
    
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'box_id';
    
     /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

  
}
