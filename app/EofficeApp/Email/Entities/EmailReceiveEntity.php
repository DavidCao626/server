<?php
namespace app\EofficeApp\Email\Entities;

use App\EofficeApp\Base\BaseEntity;
use App\EofficeApp\User\Entities\UserEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

//use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 邮件实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class EmailReceiveEntity extends BaseEntity {
    
    use SoftDeletes;
    
     /** @var string $table 定义实体表 */
    public $table = 'email_receive';
    
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'id';
    
     /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];
    
    public function user() {
        return $this->hasOne(UserEntity::class, 'user_id', 'recipients')->withTrashed();
    }
    
}
