<?php

namespace App\EofficeApp\Project\NewServices\Managers;

use App\EofficeApp\LogCenter\Facades\LogCenter;
use App\EofficeApp\Project\Entities\ProjectManagerEntity;
use App\EofficeApp\Project\NewRepositories\OtherModuleRepository;
use App\EofficeApp\Project\NewRepositories\ProjectLogRepository;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewServices\ProjectService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
class ProjectLogManager
{
    protected $actions = [
            'add',
            'modify',
            'delete',
            'proExamine',
            'proApprove',
            'proRefuse',
            'proEnd',
            'proRestart',
        ];
    // 记录编辑变更的字段
    protected const EDIT_FIELDS = [
        'person' => ['manager_person', 'manager_examine', 'manager_monitor', 'task_persondo', 'question_doperson'],
        'date' => ['manager_begintime', 'manager_endtime', 'task_begintime', 'task_endtime', 'question_endtime'],
        'other' => ['manager_name', 'manager_number', 'task_name', 'weights', 'question_name', 'doc_name'],
        'percent' => ['task_persent'],
        'manager_state' => ['manager_state'],
    ];
    private  $editFields = [];
    private $logCenterKeyMap = [
        'project' => 'project_manager',
        'question' => 'project_question',
        'document' => 'project_document',
        'task' => 'project_task',
    ];
    private $relationTableKeyMap = [
        'project' => 'project_manager',
        'question' => 'project_question',
        'document' => 'project_document',
        'task' => 'project_task',
    ];
    protected $fieldTypes;
    protected $operator;
    protected $managerId;
    protected $managerName;
    protected $operateTime;
    static $instance = null;

    protected $setRelation = false;
    protected $useRelation = false;
    protected $relationType = 1; // 关联类型：1、无关联;2、本表，3、审核
    protected $relationId = 0;
    // 批量日志
    protected $fillDataModule = false;
    protected $fillData = [];

    private function __construct($operator, $managerId)
    {
        $this->editFields = self::EDIT_FIELDS;
        $this->operator = $operator;
        $this->managerId = $managerId;
        if (DataManager::hasIns()) {
            $this->managerName = DataManager::getIns()->getProject('manager_name');
        } else {
            $project = ProjectManagerRepository::buildQuery()->whereKey($managerId)->first();
            $this->managerName = $project ? $project->manager_name : '';
        }
        $this->operateTime = date('Y-m-d H:i:s');
        $this->fieldTypes = self::initFieldTypes();
    }

    public static function getIns($operator, $managerId, $new = false)
    {
        if ($new || is_null(self::$instance)) {
            self::$instance = new static($operator, $managerId);
        }
        return self::$instance;
    }

    ##########添加###########
    public function projectAddLog($newName, $managerId = null)
    {
        $this->addLog($newName, 'project', $this->managerId);
    }
    public function taskAddLog($newName, $taskId)
    {
        $this->addLog($newName, 'task', $taskId);
    }
    public function questionAddLog($newName, $questionId)
    {
        $this->addLog($newName, 'question', $questionId);
    }
    public function documentAddLog($newName, $documentId)
    {
        $this->addLog($newName, 'document', $documentId);
    }

    ##########编辑###########
    // 项目状态存在几个action：'proExamine','proApprove','proRefuse','proEnd','proRestart'
    public function projectEditLog($model)
    {
        $fpi = $this->getFpi('project', 'modify');
        $this->editLog('project', $model);
    }

    public function taskEditLog($model)
    {
        $this->editLog('task', $model);
    }

    public function questionEditLog($model)
    {
        $this->editLog('question', $model);
    }

    public function documentEditLog($model)
    {
        $this->editLog('document', $model);
    }

    ##########删除###########

    public function projectDeleteLog($oldName)
    {
        $this->deleteLog('project', $this->managerId, $oldName);
    }
    public function taskDeleteLog($taskId, $oldName = '')
    {
        $this->deleteLog('task', $taskId, $oldName);
    }
    public function questionDeleteLog($questionId, $oldName = '')
    {
        $this->deleteLog('question', $questionId, $oldName);
    }
    public function documentDeleteLog($documentId, $oldName = '')
    {
        $this->deleteLog('document', $documentId, $oldName);
    }

    ##########私有函数###########
    private function getLogCenterIdentifier($category, $actionType)
    {
            return "project.{$this->logCenterKeyMap[$category]}.{$actionType}";
    }

    private function getRelationTableName($category)
    {
            return $this->relationTableKeyMap[$category];
    }

    private function getTitleKey($category)
    {
        $fieldPrefix = $category;
        if ($category == 'project') {
            $fieldPrefix = 'manager';
        } else if ($category == 'document') {
            $fieldPrefix = 'doc';
        }

        return $fieldPrefix . '_name';
    }

    private function initLogData($newName, $category, $categoryId, $content = '') {
        return [
            'creator' => $this->operator,
            'content' => $content,
            'relation_id' => $categoryId,
            'relation_table' => $this->getRelationTableName($category),
            'relation_title' => $newName,
            'relation_sup_id' => $this->managerId,
            'relation_sup_title' => $category == 'project' ? '' : $this->managerName
        ];
    }

    private function editLog($category, Model $model, $content = '')
    {
        $dirtyData = $model->getDirty();
        if ($dirtyData) {
            $identifier = $this->getLogCenterIdentifier($category, 'edit');
            $titleKey = $this->getTitleKey($category);
            $data = $this->initLogData($model->$titleKey, $category, $model->getKey(), $content);
            $newData = $historyData = [];
            foreach ($dirtyData as $key => $newValue) {
                if ($this->needEditLog($key)) {
                    $oldValue = $model->getOriginal($key);
                    if ($this->testMultiplePersonNotChange($key, $oldValue, $newValue)) {
                        continue;
                    }
                    $newData[$key] = $this->formatInsertData($key, $newValue);
                    $historyData[$key] = $this->formatInsertData($key, $oldValue);
                }
            }
            if (emptyWithoutZero($historyData) && emptyWithoutZero($newData)) {
                return;
            }
            LogCenter::syncInfo($identifier, $data, $historyData, $newData);
        }
    }

    private function addLog($newName, $category, $categoryId)
    {
        $identifier = $this->getLogCenterIdentifier($category, 'add');
        logCenter::syncInfo($identifier , $this->initLogData($newName, $category, $categoryId));
    }

    private function deleteLog($category, $categoryId, $oldName = '')
    {
        $identifier = $this->getLogCenterIdentifier($category, 'delete');
        logCenter::syncInfo($identifier , $this->initLogData($oldName, $category, $categoryId));
    }

    public function needEditLog($field)
    {
        return isset($this->fieldTypes[$field]);
    }

    // 会使用上次保存的数据，如果是其它表则传入覆盖
    public function useRelation($relationType = null, $relationId = null)
    {
        $this->useRelation = true;
        $relationId && $this->relationId = $relationId;
        $relationType && $this->relationType = $relationType;
        return $this;
    }

    // 只有2时才需要记住id值
    public function setRelation($relationType)
    {
        if ($relationType === 2) {
            $this->setRelation = true;
        }
    }

    // 开启填充数据模式
    public function beginFillDataModule()
    {
        $this->fillDataModule = true;
        return $this;
    }

    // 存储已填充的数据
    public function storageFillData()
    {
        if ($this->fillData) {
            ProjectLogRepository::buildQuery()->insert($this->fillData);
        }
        $this->fillDataModule = false;
    }

    private function initData($category, $categoryId, $action, $field = '', $oldValue = '', $newValue = '', $remark = '')
    {
        return [
            'manager_id' => $this->managerId,
            'category_type' => $category,
            'category_id' => $categoryId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'action' => $action,
            'field' => $field,
            'remark' => $remark,
            'relation_type' => 1,
            'relation_id' => 0,
            'operator' => $this->operator,
            'operate_time' => $this->operateTime,
            'fpi' => $this->getFpi($category, $action),
        ];
    }

    private function getFpi($category, $action)
    {
        // 暂时只处理项目的提交审核等5个操作
        if ($category == 'project' && $action == 'modify') {
            try {
                return DataManager::getIns()->getFunctionPageBin()->getFunctionPageId();
            } catch (\Exception $e) {
            }
        }
        return '';
    }

    /**
     * 添加数据
     * @param $data
     */
    private function createLog($data)
    {
        if ($this->useRelation) {
            $this->useRelation = false;
            $data['relation_type'] = $this->relationType;
            $data['relation_id'] = $this->relationId;
        }

        if ($this->fillDataModule) {
            $this->fillData[] = $data;
        } else {
            $res = ProjectLogRepository::buildQuery()->create($data);

            if ($this->setRelation) {
                $this->relationId = $res->id;
                $this->setRelation = false;
            }
        }
    }

    // 将所有的编辑字段变成key，类型变成value
    private static function initFieldTypes()
    {
        $fieldTypes = self::EDIT_FIELDS;
        if ($fieldTypes) {
            $res = [];
            foreach ($fieldTypes as $type => $fields) {
                $temp = array_fill_keys($fields, $type);
                $res = array_merge($res, $temp);
            }
            return $res;
        }
        return [];
    }

    private function isMultiplePersonField($field)
    {
        return in_array($field, ['manager_person', 'manager_examine', 'manager_monitor']);
    }

    private function getMultiplePersonRemark($oldValue, $newValue)
    {
        if (!is_array($oldValue)) {
            $oldValue = explode(',', $oldValue);
            $newValue = explode(',', $newValue);
        }
        $add = array_diff($newValue, $oldValue);
        $remove = array_diff($oldValue, $newValue);
        $data = [];
        $add && $data['add'] = array_values($add);
        $remove && $data['remove'] = array_values($remove);
        if ($data) {
            return json_encode($data);
        }
        return '';
    }

    // 检测到多用户字段未发生实际改变时返回true，即仅交换人员顺序将返回true
    private function testMultiplePersonNotChange($field, $oldValue, $newValue) {
        if ($this->isMultiplePersonField($field)) {
            $oldValue = $oldValue ? explode(',', $oldValue) : [];
            $newValue = $newValue ? explode(',', $newValue) : [];
            return !(array_diff($oldValue, $newValue) || array_diff($newValue, $oldValue));
        }
        return false;
    }

    #############翻译数据################
    public function formatData(&$data)
    {
        $actionKV = $this->getActionKeyValue();
        $userKV = $this->getAllUserKeyValue();
        $fieldKV = $this->getAllFieldKeyValue();
        $categoryKV = $this->getCategoryTypeKeyValue();
        $managerStateKV = ProjectManagerEntity::getSelectFieldKeyValue('manager_state', 'project');
        foreach ($data as &$item) {
            $item['t_operator'] = Arr::get($userKV, $item['operator']);
            $item['t_action'] = $this->formatAction($item['action'], $item['field'], $actionKV, $fieldKV);
            $item['t_action_origin'] = $this->formatAction($item['action'], $item['field'], $actionKV, $fieldKV, true);
            $categoryTypeName = Arr::get($categoryKV, $item['category_type']);
            $categoryObject = $this->formatCategoryName($item['category_type'], $item['category'], $item['category_id']);
            $item['category_description'] = '[' . $categoryTypeName . '] ' . $categoryObject;
            $item['t_old_value'] = $this->formatValue($item['field'], $item['old_value'], $userKV, $managerStateKV);
            $item['t_new_value'] = $this->formatValue($item['field'], $item['new_value'], $userKV, $managerStateKV);
            $item['t_remark'] = $item['remark'];
            if ($this->isMultiplePersonField($item['field'])) {
                $item['t_remark'] = $this->formatUserRemark($item['remark'], $userKV);
            }

            unset($item['category']);
        }
    }

    private function formatAction($action, $field, $actionKV, $fieldKV, $withOutField = false)
    {
        $actionName = Arr::get($actionKV, $action);
        if (!$withOutField && $action === 'modify') {
            $actionName .= ' ' . Arr::get($fieldKV, $field);
        }
        return $actionName;
    }

    // 格式化多选用户的备注
    private function formatUserRemark($remark, &$userKV)
    {
        if (is_string($remark)) {
            $remark = json_decode($remark, true);
            if (is_array($remark)) {
                $res = [];
                if (isset($remark['add'])) {
                    $remark['add'] = array_intersect_key($userKV, array_flip($remark['add']));
                    $res[] = trans('project.log.add') . ': ' . implode(',', $remark['add']);
                }
                if (isset($remark['remove'])) {
                    $remark['remove'] = array_intersect_key($userKV, array_flip($remark['remove']));
                    $res[] = trans('project.log.remove') . ': ' . implode(',', $remark['remove']);
                }
                return implode(';', $res);
            }
        }
        return '';
    }

    // 格式化值
    private function formatValue($field, $value, &$userKV, &$managerStateKV)
    {
        $fieldType = Arr::get($this->fieldTypes, $field);
        if ($fieldType && !emptyWithoutZero($value)) {
            switch ($fieldType) {
                case 'percent':
                    return $value . '%';
                case 'person':
                    return Arr::get($userKV, $value);
                case 'manager_state':
                    return Arr::get($managerStateKV, $value);
            }
        }
        return $value;
    }

    private function formatCategoryName($type, $category, $categoryId)
    {
        if ($category) {
            switch ($type) {
                case 'project':
                    return $category['manager_name'];
                case 'task':
                    return $category['task_name'];
                case 'document':
                    return $category['doc_name'];
                case 'question':
                    return $category['question_name'];
            }
        }
        return $categoryId;
    }

    private function getActionKeyValue()
    {
        return trans('project.log.actions');
    }

    private function getCategoryTypeKeyValue()
    {
        return [
            'project' => trans('project.project'),
            'task' => trans('project.task'),
            'question' => trans('project.problem'),
            'document' => trans('project.file'),
        ];
    }

    private function getAllFieldKeyValue()
    {
        $fields = array_keys($this->fieldTypes);
        $keyValue = [];
        $project = ProjectManagerRepository::buildQuery()->find($this->managerId);
        $managerType = object_get($project, 'manager_type');
        $projectTableKey = ProjectService::getProjectCustomTableKey($managerType);
        $taskTableKey = ProjectService::getProjectTaskCustomTableKey($managerType);
        foreach ($fields as $field) {
            if (strpos($field, 'manager_') === 0) {
                $keyValue[$field] = mulit_trans_dynamic("custom_fields_table.field_name." . $projectTableKey . "_" . $field);
                if (!$keyValue[$field]) {
                    $keyValue[$field] = trans('project.fields.' . $field);
                }
            } else {
                $keyValue[$field] = mulit_trans_dynamic("custom_fields_table.field_name." . $taskTableKey . "_" . $field);
            }
        }
        return $keyValue;
    }

    private function getAllUserKeyValue()
    {
        return OtherModuleRepository::buildUserQuery()->withTrashed()->pluck('user_name', 'user_id')->toArray();
    }

    private function formatInsertData($field, $value) {
        $type = $this->fieldTypes[$field];
        switch ($type) {
            case 'person' :
                    $value = $value ? explode(',', $value) : [];
                    return json_encode($value);
            case 'date' :
                if (strtotime($value) !== false) {
                    return $value;
                }
                return '';
//            case 'other' :
//                return $value;
//            case 'percent' :
//                return $value;
//            case 'manager_state' :
//                return $value;
        }
        return $value;
    }

    public static function formatInsertTranslateValue($data) {
        $types = self::initFieldTypes();
        foreach ($data as $key => $value) {
            $type = array_extract($types, $key, '');
            switch ($type) {
                case 'person' :
                    if ($value) {
                        $value[0] = json_decode($value[0]);
                        $value[1] = json_decode($value[1]);
                        $userIds = array_merge($value[0], $value[1]);
                        $userNames = OtherModuleRepository::buildUserQuery(['in_user_id' => $userIds])->pluck('user_name', 'user_id')->toArray();
                        $value[0] = implode(',', array_extract($userNames, $value[0], []));
                        $value[1] = implode(',', array_extract($userNames, $value[1], []));
                    }
                    break;
                case 'percent' :
                    $value[0] = $value[0] . '%';
                    $value[1] = $value[1] . '%';
                    break;
                case 'manager_state' :
                    $managerStateKV = ProjectManagerEntity::getSelectFieldKeyValue('manager_state', 'project');
                    $value[0] = array_extract($managerStateKV, $value[0], '');
                    $value[1] = array_extract($managerStateKV, $value[1], '');
                    break;
            }
            $data[$key] = $value;
        }
        return $data;
    }

    #############列表筛选################
    public function getSearchData()
    {
        $actions = $this->getActionKeyValue();
        $categories = $this->getCategoryTypeKeyValue();
        $editTypeKeyValue = $this->editTypeKeyValue();
        $operators = ProjectLogRepository::operatorsIdName($this->managerId);
        $operators = Arr::pluck($operators, 'operator_name', 'operator');
        $actions = Arr::except($actions, [
            'proExamine',
            'proApprove',
            'proRefuse',
            'proEnd',
            'proRestart',
        ]);
        return [
            'action' => $actions,
            'category_type' => $categories,
            'edit_type' => $editTypeKeyValue,
            'operator' => $operators,
        ];
    }

    private function editTypeKeyValue()
    {
        return trans('project.log.editType');
    }

    public static function getEditTypeFields()
    {
        return (new static('', ''))->editFields;
    }
}
