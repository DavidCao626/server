<?php
namespace App\EofficeApp\Project\Entities;

use App\EofficeApp\Project\NewServices\Managers\CacheManager;
use App\EofficeApp\Project\NewServices\Managers\MessageManager;
use App\EofficeApp\User\Entities\UserEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTaskEntity extends ProjectBaseEntity {
    
    use SoftDeletes;
    
     /** @var string $table 定义实体表 */
    public $table = 'project_task';
    
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'task_id';
 
     /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

    public function project_manager()
    {
        return $this->hasOne(ProjectManagerEntity::class, 'manager_id', 'task_project');
    }

    public function son_tasks()
    {
        return $this->hasMany(self::class, 'parent_task_id', 'task_id');
    }

    public function parent_task()
    {
        return $this->belongsTo(self::class, 'parent_task_id', 'task_id');
    }

    public function person_do() {
        return $this->hasOne(UserEntity::class, 'user_id', 'task_persondo');
    }

    public function front_task()
    {
        return $this->belongsTo(static::class, 'task_frontid', 'task_id');
    }

    public function not_read() {
        return $this->hasMany(ProjectStatusEntity::class, 'relation_id', 'task_id')
            ->where('remind_flag', 0)
            ->where('type', 'task');
    }

    protected $eqQuery = ['task_name', 'task_project', 'task_id', 'task_frontid', 'task_level', 'task_persent', 'task_mark', 'task_begintime', 'task_endtime', 'creat_time', 'task_complate', 'is_overdue'];
    protected $inQuery = ['task_project', 'task_id'];
    protected $withQuery = ['person_do', 'project_manager', 'front_task'];
    protected $withCountQuery = ['not_read as task_new_feedback', 'not_read as task_read_flag'];

    public function setTaskPersentAttribute($present)
    {
        // 在非百分比模式下，会传入中文，而实际修改进度都是传如数字，因此作此过滤
        if (!is_numeric($present)) {
            return $this->task_persent;
        }
        if ($this->task_persent != $present) {
            if ($present == 100) {
                $this->attributes['complete_date'] = date('Y-m-d H:i:s');
                MessageManager::sendProjectTaskCompleteReminder($this->getAttributes());
                MessageManager::sendProjectFrontTaskCompleteReminder($this->getAttributes());
            } else {
                $this->attributes['complete_date'] = null;
            }
            $this->attributes['is_overdue'] = $this->calculateIsOverdue($this->task_endtime);
            CacheManager::cleanProjectReportCache();
        }
        $this->attributes['task_persent'] = $present;
    }

    public function setTaskEndtimeAttribute($endTime)
    {
        $endTime = $endTime ?? '';
        if ($this->task_endtime != $endTime) {
            $this->attributes['is_overdue'] = $this->calculateIsOverdue($endTime);
            CacheManager::cleanProjectReportCache();
        }
        $this->attributes['task_endtime'] = $endTime;
    }

    public function setTaskBegintimeAttribute($endTime)
    {
        $endTime = $endTime ?? '';
        $this->attributes['task_begintime'] = $endTime;
    }

    public function setTaskExplainAttribute($value)
    {
        $value = $value ?? '';
        $this->attributes['task_explain'] = $value;
    }

    private function calculateIsOverdue($endTime)
    {
        $completeDate = $this->complete_date ?: date('Y-m-d H:i:s');
        $completeDate = date('Y-m-d', strtotime($completeDate));
        if ($endTime < $completeDate) {
            return 1;
        }
        return 0;
    }

    public function setTaskPersondoAttribute($value)
    {
        $originValue =  $this->getOriginal('task_persondo');
        if ($originValue != $value) {
            CacheManager::cleanProjectReportCache();
        }
        $this->attributes['task_persondo'] = $value;
    }

    public function setWeightsAttribute($value)
    {
        if (!floatval($value) > 0) {
            $value = 1;
        } else {
            $value = floor($value);
        }
        $this->attributes['weights'] = $value;
    }

    public function setWorkingDaysAttribute($value)
    {
        if (!floatval($value) > 0) {
            $value = 0;
        }
        $this->attributes['working_days'] = $value;
    }

    public function setTaskLevelAttribute($value)
    {
        $value = $value ?? '';
        $this->attributes['task_level'] = $value;
    }

    public function setTaskNameAttribute($value)
    {
        $value = $value ?? '';
        $this->attributes['task_name'] = $value;
    }

    protected $fillable = [
        'task_project',
        'task_complate',
        'task_name',
        'task_persondo',
        'task_frontid',
        'task_begintime',
        'task_endtime',
        'task_explain',
        'task_level',
        'task_mark',
        'task_remark',
        'task_creater',
        'creat_time',
        'task_persent',// Todo 需要注释
        'sort_id',
//        'parent_task_id',
//        'tree_level',
//        'parent_task_ids',
        'working_days',
        'weights',
//        'complete_date',
//        'is_overdue'
    ];

    // 校对工时
    protected function beforeSave(Model $model) {
        $beginDate = $model->task_begintime;
        $endDate = $model->task_endtime;
        if ($this->isDate($beginDate) && $this->isDate($endDate)) {
            $workDays = Carbon::createFromDate($beginDate)->diffInDays($endDate) + 1;
            if ($model->working_days != $workDays) {
                $model->working_days = $workDays;
            }
        }
    }

    private function isDate($date) {
        return !(!$date || $date == '0000-00-00');
    }
}
