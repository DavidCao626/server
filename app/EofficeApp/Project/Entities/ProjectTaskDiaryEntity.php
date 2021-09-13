<?php
namespace App\EofficeApp\Project\Entities;

use App\EofficeApp\User\Entities\UserEntity;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * 项目任务处理实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class ProjectTaskDiaryEntity extends ProjectBaseEntity {
    
    use SoftDeletes;
    
     /** @var string $table 定义实体表 */
    public $table = 'project_task_diary';
    
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'taskdiary_id';
 
     /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

    protected $eqQuery = ['taskdiary_project', 'taskdiary_task', 'task_diary_replyid'];

    protected $fillable = [
        'taskdiary_id',
        'taskdiary_task',
        'taskdiary_project',
        'taskdiary_curtime',
        'taskdiary_creater',
        'taskdiary_persent',
        'task_diary_replyid',
        'task_diary_quoteid',
        'taskdiary_explain',
        'attachment_name',
        'discuss_readtime',
    ];

    public function quote()
    {
        return $this->hasOne(self::class, 'taskdiary_id', 'task_diary_quoteid');
    }

    public function reply()
    {
        return $this->hasMany(self::class, 'task_diary_replyid', 'taskdiary_id');
    }

    public function user()
    {
        return $this->hasOne(UserEntity::class, 'user_id', 'taskdiary_creater');
    }

    public function setTaskDiaryReplyidAttribute($value)
    {
        if (!floatval($value) > 0) {
            $value = 0;
        }
        $this->attributes['task_diary_replyid'] = $value;
    }

    public function setTaskDiaryQuoteidAttribute($value)
    {
        if (!floatval($value) > 0) {
            $value = 0;
        }
        $this->attributes['task_diary_quoteid'] = $value;
    }
}
