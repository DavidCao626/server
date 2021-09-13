<?php
namespace app\EofficeApp\Birthday\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 生日贺卡实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class BirthdayEntity extends BaseEntity {
    
    use SoftDeletes;
    
     /** @var string $table 定义实体表 */
    public $table = 'birthday';
    
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'birthday_id';
    
     /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

  
}
