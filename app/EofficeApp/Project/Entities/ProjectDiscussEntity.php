<?php
namespace App\EofficeApp\Project\Entities;

use App\EofficeApp\User\Entities\UserEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectDiscussEntity extends ProjectBaseEntity {
    
    use SoftDeletes;
    
     /** @var string $table 定义实体表 */
    public $table = 'project_discuss';
    
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'discuss_id';
 
     /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

    protected $eqQuery = ['discuss_project', 'discuss_replyid'];

    protected $fillable = [
        'discuss_project',
        'discuss_replyid',
        'discuss_quoteid',
        'discuss_order',
        'discuss_content',
        'discuss_time',
        'discuss_person',
        'attachment_id',
        'attachment_name',
        'discuss_readtime',
    ];

    public function quote()
    {
        return $this->hasOne(self::class, 'discuss_id', 'discuss_quoteid');
    }

    public function reply()
    {
        return $this->hasMany(self::class, 'discuss_replyid', 'discuss_id');
    }

    public function user()
    {
        return $this->hasOne(UserEntity::class, 'user_id', 'discuss_person');
    }

    public function setDiscussReplyidAttribute($value)
    {
        if (!floatval($value) > 0) {
            $value = 0;
        }
        $this->attributes['discuss_replyid'] = $value;
    }

    public function setDiscussQuoteidAttribute($value)
    {
        if (!floatval($value) > 0) {
            $value = 0;
        }
        $this->attributes['discuss_quoteid'] = $value;
    }
}
