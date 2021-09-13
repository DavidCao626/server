<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits;

use App\EofficeApp\Project\NewServices\Managers\RolePermissionManager;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Arr;
Trait FlowOutSendTrait
{

    ###################项目外发############################
    public static function flowOutProjectAdd($data)
    {
        $userId = Arr::get($data, 'current_user_id', '');
        $projectData = Arr::get($data, 'data', []);
        $tasks = Arr::get($projectData, 'additional', []); // 多维数组
        unset($projectData['additional']);
        $projectData['manager_type'] = Arr::get($projectData, 'project_type', '');
        $projectData['manager_state'] = Arr::get($projectData, 'project_state', 1);

        $emptyMsg = self::buildFlowOutProjectMsg($projectData, $projectData['manager_type']);
        if ($emptyMsg) {
            return ['code' => $emptyMsg];
        }

        $projectData['outsource'] = true;
        $res = self::tryCatchToCode(function () use ($projectData, $userId) {
            return self::projectAdd($projectData, $userId);
        });
        if (isset($res['code'])) {
            return $res;
        }
        $returnRes = ['status' => true, 'project_id' => $res['model']['manager_id']];

        $saveTaskResult = [];
        if ($tasks) {
            if (isset($tasks[0]['task_name'])) {
                foreach ($tasks as $key => $task) {
                    $tasks[$key]['outsource'] = true;
                }
            } else {
                $tasks['outsource'] = true;
            }
            $saveTaskResult = self::flowOutAddTask($tasks, $res['model']);
        }
        $returnRes['dynamic'] = self::buildFlowOutTasksMsg($saveTaskResult);
        return $returnRes;
    }

    public static function flowOutProjectEdit($data)
    {
        $userId = Arr::get($data, 'current_user_id', '');
        $projectData = Arr::get($data, 'data', []);
        $own = ['user_id' => $userId];
        $uniqueId = Arr::get($data, 'unique_id');
        $projectData['manager_id'] = $uniqueId;
        $projectData['outsourceForEdit'] = true;

        $res = self::tryCatchToCode(function () use ($own, $projectData) {
            unset($projectData['manager_type']); // 不允许修改项目类型
            $dataManager = RolePermissionManager::getDataManager($own, 'projectEdit', 'project_edit', $projectData);
            $managerType = $dataManager->getProject('manager_type');
            $emptyMsg = self::buildFlowOutProjectMsg($projectData, $managerType, 'edit');
            if ($emptyMsg) {
                return ['code' => $emptyMsg];
            }
            return self::projectEdit($dataManager);
        });
        isset($res['model']) && $res = $uniqueId;
        return $res;
    }

    public static function flowOutProjectDelete($data)
    {
        $userId = Arr::get($data, 'current_user_id', '');
        $own = ['user_id' => $userId];
        $uniqueId = Arr::get($data, 'unique_id');

        $projectData['manager_id'] = $uniqueId;
        $res = self::tryCatchToCode(function () use ($own, $projectData) {
            $dataManager = RolePermissionManager::getDataManager($own, 'projectDelete', '', $projectData);
            return self::projectDelete($dataManager);
        });

        $res === true && $res = $uniqueId;

        return $res;
    }

    ###################项目外发############################


    // 外发项目，明细外发任务时，组装失败原因
    private static function buildFlowOutTasksMsg($saveTaskResult)
    {
        $errorInfo = [];
        foreach ($saveTaskResult as $key => $result) {
            if (is_string($result)) {
                $errorInfo[$key] = $result;
            } else if (is_array($result) && array_key_exists('code', $result)) {
                $errorInfo[$key] = trans(Arr::get($result, 'code.1', '') . '.' . Arr::get($result, 'code.0', ''));
            } else if (!$result) {
                $errorInfo[$key] = trans('flow.0x030148');
            }
        }
        if ($errorInfo) {
            return flow_out_extra_msg('project.task', $errorInfo);
        }
        return '';
    }

    // 外发项目，项目必填验证，外发这里处理，实际添加编辑项目没有严重必填，临时方案
    private static function buildFlowOutProjectMsg($data, $projectType, $mode = 'add'): string
    {
        $msg = trans('common.0x000001');
        $empty = [];
        $testFieldNames = self::getProjectRequiredField($projectType);
        foreach ($testFieldNames as $testFieldName) {
            if (Arr::get($data, $testFieldName)) {
                continue;
            }
            if ($mode === 'edit' && !array_key_exists($testFieldName, $data)) {
                continue; // 编辑时key不存在不必填验证
            }
            $empty[] = mulit_trans_dynamic("custom_fields_table.field_name.project_value_" . '' . $projectType . '_' . $testFieldName);
        }
        if ($empty) {
            return $msg . ': ' . implode(', ', $empty);
        } else {
            return '';
        }
    }

    // 获取必填的字段，如：[manager_person...]
    private static function getProjectRequiredField($projectType): array
    {
        $key = 'project_value_' . $projectType;
        $tempKey = 'project_value_field_is_required_' . $key;
        if (!Redis::exists($tempKey)) {
            $fields = self::getFormModelingRepository()->entity->where('field_table_key', $key)->get();
            $isRequiredField = [];
            foreach ($fields as $field) {
                $option = json_decode($field->field_options, 1);
                if (Arr::get($option, 'validate.required')) {
                    $isRequiredField[] = $field->field_code;
                }
            }
            Redis::set($tempKey, json_encode($isRequiredField));
        } else {
            $isRequiredField = json_decode(Redis::get($tempKey), 1);
        }
        return $isRequiredField;
    }

}
