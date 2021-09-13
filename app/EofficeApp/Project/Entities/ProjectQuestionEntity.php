<?php
namespace App\EofficeApp\Project\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 项目问题实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class ProjectQuestionEntity extends ProjectBaseEntity {

    use SoftDeletes;
    
     /** @var string $table 定义实体表 */
    public $table = 'project_question';
    
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'question_id';
 
     /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

    protected $eqQuery = ['question_name', 'question_state', 'question_project', 'question_id', 'question_createtime', 'question_endtime', 'question_level', 'question_type'];
    protected $inQuery = ['question_project', 'question_id'];

    public static $QUESTION_STATE = [
        0 => "unsubmitted",
        1 => "submission",
        2 => "in_the_process_of_processing",
        3 => "already_processed",
        4 => "unsolved",
        5 => "resolved",
    ];

    protected $fillable = [
//        'question_project',
        'question_task',
        'question_name',
        'question_level',
        'question_explain',
        'question_person',
        'question_doperson',
        'question_endtime',
//        'question_creater',
//        'question_createtime',
        'question_do',
        'question_dotime',
        'question_back',
        'question_backtime',
        'question_state',
        'question_type',
    ];

    public function task()
    {
        return $this->hasOne(ProjectTaskEntity::class, 'task_id', 'question_task');
    }

    public function setQuestionTypeAttribute($value)
    {
        $value = $value ?? 0;
        $this->attributes['question_type'] = $value;
    }
}
