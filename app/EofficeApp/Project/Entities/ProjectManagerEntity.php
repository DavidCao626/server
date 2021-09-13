<?php

namespace App\EofficeApp\Project\Entities;

use App\EofficeApp\Project\NewServices\Managers\CacheManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\ProjectService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectManagerEntity extends ProjectBaseEntity {
    use SoftDeletes;

    /** @var string $table 定义实体表 */
    public $table = 'project_manager';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'manager_id';

    /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

    protected $eqQuery = ['manager_state', 'manager_number', 'manager_endtime', 'manager_begintime', 'manager_type', 'manager_level', 'manager_fast', 'manager_id', 'manager_name', 'is_overdue'];
    protected $inQuery = ['manager_state', 'manager_id'];
    protected $likeQuery = ['manager_name', 'manager_number'];
    protected $withQuery = ['tasks', 'documents', 'questions'];
    protected $withCountQuery = ['tasks as task_count', 'documents as doc_count', 'questions as question_count', 'not_read as project_new_disscuss'];

    public function tasks()
    {
        return $this->hasMany(ProjectTaskEntity::class, 'task_project', 'manager_id');
    }

    public function setManagerStateAttribute($value)
    {
        $originValue =  $this->getOriginal('manager_state');
        if ($originValue != $value) {
            if ($value == 5) {
                $this->attributes['complete_date'] = date('Y-m-d H:i:s');
            } else {
                $this->attributes['complete_date'] = null;
            }
            $this->attributes['is_overdue'] = $this->calculateIsOverdue($this->manager_endtime, $value);
            CacheManager::cleanProjectReportCache();
        }
        $this->attributes['manager_state'] = $value;
    }

    public function setManagerEndtimeAttribute($value)
    {
        $originValue =  $this->getOriginal('manager_endtime');
        if ($originValue != $value) {
            CacheManager::cleanProjectReportCache();
        }
        $this->attributes['is_overdue'] = $this->calculateIsOverdue($value);
        $this->attributes['manager_endtime'] = $value;
    }

    private function calculateIsOverdue($endDate, $managerState = null)
    {
        $managerState = $managerState ?: $this->manager_state;
        $completeDate = $this->complete_date ?: date('Y-m-d');
        if (in_array($managerState, [4, 5]) && $endDate < $completeDate) {
            return 1;
        }
        return 0;
    }

    public function setManagerPersonAttribute($value)
    {
        $originValue =  $this->getOriginal('manager_person');
        if ($originValue != $value) {
            CacheManager::cleanProjectReportCache();
        }
        $this->attributes['manager_person'] = $value;
    }

    public function setManagerExamineAttribute($value)
    {
        $originValue =  $this->getOriginal('manager_examine');
        if ($originValue != $value) {
            CacheManager::cleanProjectReportCache();
        }
        $this->attributes['manager_examine'] = $value;
    }

    public function setManagerMonitorAttribute($value)
    {
        $originValue =  $this->getOriginal('manager_monitor');
        if ($originValue != $value) {
            CacheManager::cleanProjectReportCache();
        }
        $this->attributes['manager_monitor'] = $value;
    }

    public function setManagerAppraisalAttribute($value)
    {
        $value = $value ?? "";
        $this->attributes['manager_appraisal'] = $value;
    }

    public $fillable = [
        'manager_name',
        'manager_number',
        'manager_begintime',
        'manager_endtime',
        'manager_type',
        'manager_person',
        'manager_examine',
        'manager_monitor',
        'manager_explain',
        'manager_team',
        'manager_template',
        'manager_fast',
        'manager_level',
        'manager_state',
        'manager_appraisal',
        'manager_appraisal_feedback',
        'manager_remark',
        'attachment_id',
        'attachment_name',
        'manager_creater',
        'creat_time',
//下列属性，不允许批量修改，否则修改器的赋值会被重新覆盖
//        'complete_date',
//        'is_overdue'
    ];

    public function questions()
    {
        return $this->hasMany(ProjectQuestionEntity::class, 'question_project', 'manager_id');
    }

    public function documents()
    {
        return $this->hasMany(ProjectDocumentEntity::class, 'doc_project', 'manager_id');
    }

    public function teams($teamRoleId) {
        return $this->hasMany(ProjectRoleUserEntity::class, 'manager_id', 'manager_id')->where('role_id', $teamRoleId);
    }

    public function discusses() {
        return $this->hasMany(ProjectDiscussEntity::class, 'discuss_project', 'manager_id');
    }

    public function not_read() {
        return $this->hasOne(ProjectStatusEntity::class, 'relation_id', 'manager_id')
            ->where('remind_flag', 0)
            ->where('type', 'project');
    }

    // 关联项目上得人员：负责人、审核人、监控人、创建人、团队成员
    public function project_person() {
        return $this->hasMany(ProjectRoleUserEntity::class, 'manager_id', 'manager_id');
    }

    public static $MANAGER_STATE = [
        1 => "in_the_project",
        2 => "examination_and_approval",
        3 => "retreated",
        4 => "have_in_hand",
        5 => "finished",
    ];

    protected function beforeSave(Model $model) {
        $this->transitVariable = [1,3,4,5,6];
        // 处理日程同步
        //save之后无法捕捉完成的数据变动状态，且新建时无id，因此采用勾子函数处理前后数据
        $cloneModel = clone $model;
        $this->transitVariable['syncCalendar'] = function ($managerId) use ($cloneModel) {
            $isCreated = $cloneModel->getKey() ? false : true;
            $managerState = $cloneModel->getAttribute('manager_state');
            if ($isCreated && $managerState != 5) {
                $cloneModel->manager_id = $managerId; // 新建时无id
                ProjectService::syncCalendar($cloneModel, $cloneModel['manager_creater']);
            }
            if (!$isCreated) {
                $curUserId = HelpersManager::getUserId();
                $originManagerState = $cloneModel->getOriginal('manager_state');
                $isDirtyManagerState = $cloneModel->isDirty('manager_state');
                // 修改了状态处理方式不同
                if ($isDirtyManagerState) {
                    if ($managerState == 5) {
                        ProjectService::syncCalendarStatus($managerId, $curUserId, false, true);
                    } elseif ($originManagerState == 5) {
                        // 删除后重新添加
                        ProjectService::syncCalendarStatus($managerId, $curUserId);
                        ProjectService::syncCalendar($cloneModel, $curUserId);
                    }
                } else {
                    if ($managerState < 5) {
                        ProjectService::syncCalendar($cloneModel, $curUserId, false);
                    }
                }
            }
        };
    }

    protected function afterSave(Model $model)
    {
        if (isset($this->transitVariable['syncCalendar']) && is_callable($this->transitVariable['syncCalendar'])) {
            $this->transitVariable['syncCalendar']($model->getKey());
        }
    }

    public static function getAllManagerStates()
    {
        return [1, 2, 3, 4, 5];
    }
}
