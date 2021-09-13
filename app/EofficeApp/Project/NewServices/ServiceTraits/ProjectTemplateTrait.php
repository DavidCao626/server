<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits;

use App\EofficeApp\Project\Entities\ProjectTaskEntity;
use App\EofficeApp\Project\Entities\ProjectTemplateEntity;
use App\EofficeApp\Project\NewRepositories\ProjectTaskRepository;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\Managers\ProjectTasksManager;
use App\Utils\ResponseService;
use Illuminate\Support\Arr;
Trait ProjectTemplateTrait
{
    public static function taskTemplateList($templateId, $inputParams = [])
    {
        $params = [
            '@with_custom_info' => 0
        ];
        $params = array_merge($params, $inputParams);
        $inputParams =  self::handleListParams($params);
        $template = self::getTemplateModel($templateId);
        $query = ProjectTaskRepository::buildQuery(['task_complate' => $templateId]);
        if (isset($inputParams['task_frontid'])) {
            $inputParams['task_frontid'] === 'un-exist' && $inputParams['task_frontid'] = 0;
            $inputParams['task_frontid'] === 'exist' && $inputParams['task_frontid'] = [0, '>'];
        }
        ProjectTaskRepository::buildQuery($inputParams, $query);
        $data = paginate($query, null, Arr::get($inputParams, 'page'), Arr::get($inputParams, 'limit'));

        $params['@with_custom_info'] && self::setTaskListCustomData($template['template_type'], $data['list']);
        $newList = ProjectTasksManager::toGridList($data['list']);
        if (count($newList) == $data['total']) {
            $data['list'] = $newList;
            $data['is_tree'] = 1;
        } else {
            $data['is_tree'] = 0;
        }

        return $data;
    }

    public static function taskTemplateAdd($data, $userId)
    {
        $templateId = Arr::get($data, 'task_complate');
        $template = self::getTemplateModel($templateId);

        return self::commonTaskTemplateAdd($data, $userId, $template);
    }

    // @throw ResponseException
    public static function taskTemplateEdit($taskId, $data)
    {
        $templateId = Arr::get($data, 'task_complate');
        $template = self::getTemplateModel($templateId);
        $model = ProjectTaskRepository::buildQuery()->find($taskId);
        if (!$model) {
            return ['code' => ['0x000006', 'common']];
        }
        $data['task_project'] = 0;
        self::filterProjectInputData($data)->checkException();
        self::filterTaskData($data)->checkException();
        $model->fill($data);
        $result = $model->save();

        $customKey = self::getProjectTaskCustomTableKey($template['template_type']);
        self::auxiliaryTableSave($customKey, $data, null, $model->getKey(), 'task', 'edit', false);

        if ($result) {
            ProjectTasksManager::setMode();
            ProjectTasksManager::syncProjectTasks($template['template_id']);
        }

        return ['model' => $model];
    }

    public static function taskTemplateInfo($data)
    {
        $templateId = Arr::get($data, 'task_complate');
        $taskId = Arr::get($data, 'task_id');
        $template = self::getTemplateModel($templateId);
        $model = ProjectTaskRepository::buildQuery()->find($taskId);
        if (!$model) {
            return ['code' => ['0x000006', 'common']];
        }
        $model->front_task;
        $model->parent_task;
        self::setTaskCustomData($model, $template['template_type']);
        self::setTaskInfo($model);
        return $model;
    }

    public static function taskTemplateDelete($data)
    {
        $templateId = Arr::get($data, 'task_complate', '');
        self::getTemplateModel($templateId);
        $deleteIds = Arr::get($data, 'task_id', '');
        $deleteIds = explode(',', $deleteIds);
        $allSonTaskIds = self::getAllSonTaskTemplateIds($deleteIds, $templateId);
        $deleteIds = array_merge($deleteIds, $allSonTaskIds);

        ProjectTaskRepository::buildQuery(['task_id' => $deleteIds])->delete();

        ProjectTasksManager::setMode();
        ProjectTasksManager::syncProjectTasks($templateId);

        return [];
    }

    private static function getTemplateModel($templateId)
    {
        $template = ProjectTemplateEntity::query()->find($templateId);
        !$template && ResponseService::throwException('0x036001', 'project');

        return $template;
    }

    /**
     * 获取所有的子任务（不限层级，不含本身）
     * @param int|array $taskIds
     * @param int $managerId
     * @return array
     */
    private static function getAllSonTaskTemplateIds($taskIds, $managerId)
    {
        $taskIds = HelpersManager::scalarToArray($taskIds);
        if (!$taskIds) {
            return [];
        }
        $query = ProjectTaskRepository::buildQuery(['task_complate' => $managerId]);
        ProjectTaskRepository::buildQuery(['is_leaf' => 1], $query);
        $leafTaskParentTaskIds = $query->pluck('parent_task_ids', 'task_id');
        $allSonTaskIds = [];
        foreach ($leafTaskParentTaskIds as $selfTaskId => $parentTaskIds) {
            $parentTaskIds = explode(',', $parentTaskIds);
            if (!$parentTaskIds) {
                continue;
            }
            foreach ($taskIds as $taskId) {
                if (($index = array_search($taskId, $parentTaskIds)) !== false) {
                    $sonTaskIds = array_slice($parentTaskIds, $index + 1);
                    array_push($sonTaskIds, $selfTaskId); // 插入本身
                    $allSonTaskIds = array_merge($allSonTaskIds, $sonTaskIds);
                }
            }
        }

        return array_unique($allSonTaskIds);
    }

    // @throw ResponseException
    private static function commonTaskTemplateAdd($data, $userId, $template)
    {
        self::filterProjectInputData($data)->checkException();
        self::filterTaskData($data)->checkException();
        self::filterNull($data);
        // 模板添加任务 不需要执行人
        $data['task_persondo'] = Arr::get($data, 'task_persondo', '');
        // 项目中添加任务 需要
        $data['task_project'] = 0;
        $data['task_creater'] = $userId;
        $data['creat_time'] = date("Y-m-d H:i:s", time());
        $data['task_persent'] = 0;
        $data['task_frontid'] = $data['task_frontid'] ?? 0;
        $data['sort_id'] = $data['sort_id'] ?? 0;

        $model = new ProjectTaskEntity();
        $model->fill($data);
        isset($data['parent_task_id']) && $model->parent_task_id = $data['parent_task_id']; // 父级任务赋值，不支持批量赋值，防止绕过权限修改父级任务
        $result = $model->save();

        try {
            $customTableKey = self::getProjectTaskCustomTableKey($template['template_type']);
            self::auxiliaryTableSave($customTableKey, $data, null, $model->getKey(), 'task', 'add', false);
        } catch (\Exception $e) {
            $model->forceDelete();
            throw $e;
        }


        ProjectTasksManager::setMode();
        ProjectTasksManager::syncProjectTasks($template['template_id']);

        return ['model' => $model];
    }

    // 变化成自定字段的数据
    private static function setTaskListCustomData($templateType, &$data)
    {
//        $dataIds = $data->pluck('task_id')->toArray();
        $customTableKey = self::getProjectTaskCustomTableKey($templateType);
//        $tableName = 'custom_data_project_task_value_' . $templateType;
        try {
            foreach ($data as $key => $item) {
                $detailItem = self::getFormModelingService()->getCustomDataDetail($customTableKey, $item['task_id']);
                $data[$key]->setRawAttributes(array_merge($item->getAttributes(), (array) $detailItem));
            }
//            $customDatas = \DB::table($tableName)->whereIn('data_id', $dataIds)->get()->keyBy('data_id');
//            foreach ($data as $key => $item) {
//                if ($customData = $customDatas->get($item['task_id'])) {
//                    $customData = (array) $customData;
//                    unset($customData['data_id'], $customData['creator'], $customData['created_at'], $customData['updated_at'], $customData['deleted_at']);
//                    $data[$key] = $item->setRawAttributes(array_merge($item->getAttributes(), $customData));
//                }
//            }
        } catch (\Exception $e) {
        }
    }
}
