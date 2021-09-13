<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits\Authority;

use App\EofficeApp\Project\Entities\ProjectRoleEntity;
use App\EofficeApp\Project\Entities\ProjectRoleUserGroupEntity;
use App\EofficeApp\Project\NewRepositories\OtherModuleRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleManagerTypeRepository;
use App\EofficeApp\Project\NewRepositories\ProjectRoleRepository;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\RoleManager;
use App\Utils\ResponseService;
use Illuminate\Support\Arr;

Trait ProjectRoleTrait
{
    public static function roleList($params) {
        $query = ProjectRoleRepository::buildDataRoleQuery($params);
        $data = HelpersManager::paginate($query);
        self::handleRoleListData($data['list']);
        self::setRoleAuth($data['list']);
        return $data;
    }

    public static function roleInfo($roleId)
    {
        $params = ['relation_penetrate' => 1];
        $modal = ProjectRoleRepository::buildQuery($params)->find($roleId);
        if (!$modal) {
            return ['code' => ['no_data', 'project']];
        }

        $modal->setRelation('relation_penetrate', $modal->relation_penetrate->pluck('type_value'));
        self::handleRoleListData($modal);

        return $modal;
    }

    public static function roleAdd($params)
    {
        $managerType = Arr::get($params, 'manager_type');
        $typeAndRoleFieldKey = Arr::get($params, 'role_field_key');
        $typeAndRoleFieldKey = explode('.', $typeAndRoleFieldKey);
        $type = Arr::get($typeAndRoleFieldKey, 0);
        $roleFieldKey = Arr::get($typeAndRoleFieldKey, 1);
        $relationPenetrate = self::getRelationPenetrate($params);

        $roleFunctionPageCheckedData = Arr::get($params, 'checked_function_pages', []);
        if (!$managerType || !$type || !$roleFieldKey || !$roleFunctionPageCheckedData) {
            ResponseService::throwException('0x036001', 'project');
        }


        $roleInsertData = [
            'manager_type' => $managerType,
            'type' => $type,
            'role_field_key' => $roleFieldKey,
        ];

//        if (ProjectRoleRepository::buildQuery($roleInsertData)->exists()) {
//            ResponseService::throwException('this_field_role_exist', 'project');
//        }

        $model = (new ProjectRoleEntity())->fill($roleInsertData);
        if ($model->save()) {
            RoleManager::clearRoleCache();
            self::createRoleFunctionPageByRoleId($model->role_id, $roleFunctionPageCheckedData);
            self::createDataRoleRelationByRoleId($model->role_id, $relationPenetrate);
            self::setNewRoleRelation($model);
        } else {
            return false;
        }

        return true;
    }

    public static function roleEdit($roleId, $params)
    {
        $model = ProjectRoleRepository::buildQuery()->find($roleId);
        !$model && ResponseService::throwException('no_data', 'common');
        $relationPenetrate = self::getRelationPenetrate($params);
        $roleFunctionPageCheckedData = Arr::get($params, 'checked_function_pages', []);

        self::updateRoleFunctionPageByRoleId($roleId, $roleFunctionPageCheckedData);
        self::updateDataRoleRelationByRoleId($model->role_id, $relationPenetrate);

        return true;
    }

    public static function roleDelete($roleId)
    {
        $model = ProjectRoleRepository::buildQuery()->find($roleId);
        !$model && ResponseService::throwException('no_data', 'common');
        $model->is_system && ResponseService::throwException('0x000017', 'common');
        $model->delete();
        self::deleteRoleFunctionPageByRoleId($roleId);
        self::deleteDataRoleRelationByRoleId($roleId);
        self::deleteRoleRelation($roleId);

        return true;
    }

    // Todo 表单建模如果调整了删除字段逻辑，这里要同步修改
    // 表单建模删除字段时，这里需要同步处理权限清除
    public static function deleteDataRoleDeleteByFormModeling($config, $tableKey)
    {
        $type = '';
        if (self::isProjectCustomTableKey($tableKey)) {
            $type = 'project';
        } else if (self::isProjectTaskCustomTableKey($tableKey)) {
            $type = 'task';
        } else {
            return;
        }

        $fields = [];
        foreach ($config as $item) {
            $fieldDirective = Arr::get($item, 'field_directive');
            if ($fieldDirective == 'selector') {
                $fields[] = Arr::get($item, 'field_code');
            }
        }
        if ($fields) {
            $params = [
                'role_field_key' => $fields,
                'type' => $type
            ];
            if ($type == 'project') {
                $managerType = explode('_', $tableKey);
                $managerType = array_pop($managerType);
                $params['manager_type'] = $managerType;
            }
            $roleIds = ProjectRoleRepository::buildQuery($params)
                ->pluck('role_id')->toArray();
            DatabaseManager::deleteByIds(ProjectRoleEntity::class, $roleIds);
            self::deleteRoleFunctionPageByRoleId($roleIds);
            self::deleteDataRoleRelationByRoleId($roleIds);
            self::deleteRoleRelation($roleIds);
        }
    }

    public static function monitorRoleList($params) {
        $params['with_manager_types'] = 1;
        $params['with_user_group'] = 1;
        $query = ProjectRoleRepository::buildMonitorRoleQuery($params);
        $data = HelpersManager::paginate($query);
        self::handleMonitorRoleListData($data['list']);
        self::setRoleAuth($data['list']);
        return $data;
    }


    public static function monitorRoleInfo($roleId)
    {
        $modal = ProjectRoleRepository::buildMonitorRoleQuery()->find($roleId);
        if (!$modal) {
            return ['code' => ['no_data', 'project']];
        }
        self::handleMonitorRoleListData($modal);

        return $modal;
    }

    public static function monitorRoleAdd($params)
    {
        $roleFunctionPageCheckedData = Arr::get($params, 'checked_function_pages', []);
        $relationData = self::extractMonitorInputData($params);

        $roleInsertData = [
            'manager_type' => 0,
            'type' => 'project',
            'role_field_key' => '',
        ];

        $model = (new ProjectRoleEntity())->fill($roleInsertData);
        if ($model->save()) {
            RoleManager::clearRoleCache();
            $roleId = $model->role_id;
            self::createRoleManagerTypeByRoleId($roleId, $relationData['manager_types']);
            self::createRoleUserGroupByRoleId($roleId, $relationData['user_group']);
            self::createRoleFunctionPageByRoleId($roleId, $roleFunctionPageCheckedData);
        } else {
            return false;
        }

        return true;
    }

    public static function monitorRoleEdit($roleId, $params)
    {
        $model = ProjectRoleRepository::buildMonitorRoleQuery()->find($roleId);
        !$model && ResponseService::throwException('no_data', 'common');
        $roleFunctionPageCheckedData = Arr::get($params, 'checked_function_pages', []);
        $relationData = self::extractMonitorInputData($params);

        self::updateRoleManagerTypeByRoleId($roleId, $relationData['manager_types']);
        self::updateRoleUserGroupByRoleId($roleId, $relationData['user_group']);
        self::updateRoleFunctionPageByRoleId($roleId, $roleFunctionPageCheckedData);

        return true;
    }

    public static function monitorRoleDelete($roleId)
    {
        $model = ProjectRoleRepository::buildMonitorRoleQuery()->find($roleId);
        !$model && ResponseService::throwException('no_data', 'common');
        $model->is_system && ResponseService::throwException('0x000017', 'common');
        $model->delete();

        self::deleteRoleManagerTypeByRoleId($roleId);
        self::deleteRoleUserGroupByRoleId($roleId);
        self::deleteRoleFunctionPageByRoleId($roleId);

        return true;
    }

    // 角色关联的项目分类字段列表
    public static function roleRelationFieldsList($params) {
        $managerType = Arr::get($params, 'manager_type');
        if (!$managerType) {
            return [];
        }
        $projectTableName = self::getProjectCustomTableKey($managerType);
        $taskTableName = self::getProjectTaskCustomTableKey($managerType);
        $projectFields = self::getFormModelingService()->getRedisCustomFields($projectTableName);
        $taskFields = self::getFormModelingService()->getRedisCustomFields($taskTableName);
//        $existFields = ProjectRoleRepository::buildQuery(['manager_type' => $managerType])->select('role_field_key', 'type')->get()->toArray();
//        foreach ($existFields as $key => $item) {
//            $existFields[$key] = $item['type'] . "." . $item['role_field_key'];
//        }

        // 获取各个分类的字段信息
        $projectData = $taskData = [];
        foreach ($projectFields as $field) {
            $field = (array) $field;
            if (isset($field['field_options'])) {
                $fieldOptions = json_decode($field['field_options'], true);
                if (Arr::get($fieldOptions, 'selectorConfig.type') == 'user' && !isset($fieldOptions['parentField'])) {
                    if ($field['field_code'] == 'team_person') {
                        continue;
                    }
                    $fieldKey = 'project.' . $field['field_code'];
                    $projectData[] = [
                        'field_key' => $fieldKey,
                        'field_name' => self::getRoleFieldKeyName($field['field_code'], 'project', $managerType),
                        'group' => trans('project.project'),
//                        'disabled' => in_array($fieldKey, $existFields)
                    ];
                }
            }
        }

        foreach ($taskFields as $field) {
            $field = (array) $field;
            if (isset($field['field_options'])) {
                $fieldOptions = json_decode($field['field_options'], true);
                if (Arr::get($fieldOptions, 'selectorConfig.type') == 'user' && !isset($fieldOptions['parentField'])) {
                    $fieldKey = 'task.' . $field['field_code'];
                    $taskData[] = [
                        'field_key' => $fieldKey,
                        'field_name' => self::getRoleFieldKeyName($field['field_code'], 'task', $managerType),
                        'group' => trans('project.task'),
//                        'disabled' => in_array($fieldKey, $existFields)
                    ];
                }
            }
        }

        $data = array_merge($projectData, $taskData);

        return $data;
    }

    // 新建项目分类后，创建默认角色与权限
    public static function createDefaultRoles($managerType)
    {
        $defaultData = ProjectRoleRepository::buildDefaultQuery()->get()->toArray();
        foreach ($defaultData as $key => $item) {
            $item['manager_type'] = $managerType;
            $item['is_default'] = 0;
            $item['is_system'] = 1;
            unset($item['role_id'], $item['deleted_at']);
            $defaultData[$key] = $item;
        }

        DatabaseManager::insertBatch(ProjectRoleEntity::class, $defaultData, true);
        self::createDefaultRoleFunctionPages($managerType);
        self::createDefaultDataRoleRelations($managerType);
        RoleManager::clearRoleCache();
    }

    public static function deleteManagerTypeRoles($managerType)
    {
        //删除数据权限
        $dataRoleIds = ProjectRoleRepository::buildDataRoleQuery(['manager_type' => $managerType])->pluck('role_id')->toArray();

        if ($dataRoleIds) {
            ProjectRoleRepository::buildQuery(['role_id' => $dataRoleIds])->delete();
            self::deleteRoleFunctionPageByRoleId($dataRoleIds);
            self::deleteDataRoleRelationByRoleId($dataRoleIds);
            self::deleteRoleRelation($dataRoleIds);
        }

        // 删除监控权限
        $monitorRoleIds = ProjectRoleManagerTypeRepository::buildQuery(['manager_type' => $managerType])->pluck('role_id')->toArray();
        $monitorRoleIds = ProjectRoleManagerTypeRepository::buildQuery(['role_id' => $monitorRoleIds])->groupBy('role_id')
            ->selectRaw('count(*) as count,role_id')
            ->pluck('count', 'role_id');
        $deleteMonitorRoleIds = [];
        foreach ($monitorRoleIds as $tempRoleId => $count) {
            $count == 1 && $deleteMonitorRoleIds[] = $tempRoleId; // 为1时代表该权限没有被其它类型引用，才可以删除
        }
        if ($deleteMonitorRoleIds) {
            ProjectRoleRepository::buildQuery(['role_id' => $deleteMonitorRoleIds])->delete();
            self::deleteRoleManagerTypeByRoleId($deleteMonitorRoleIds);
            self::deleteRoleUserGroupByRoleId($deleteMonitorRoleIds);
            self::deleteRoleFunctionPageByRoleId($deleteMonitorRoleIds);
        }
        RoleManager::clearRoleCache();
    }

    /**
     * 提取监控权限的人员、类型数据
     * @param $params
     * @return array eg: [manager_types: [], user_group: [all: ['all'], user_ids: [], dept_ids: [], role_ids: []]]
     */
    private static function extractMonitorInputData($params)
    {
        $params = array_extract($params, [
            'alias',
            'all_manager_type',
            'manager_type',
            'all_user',
            'user_ids',
            'dept_ids',
            'role_ids',
        ], '');
        $data = [];
        if ($params['all_manager_type']) {
            $data['manager_types'] = ['all'];
        } else {
            if (!$params['manager_type'] || !is_array($params['manager_type'])) {
                ResponseService::throwException('0x036001', 'project');
            }
            $data['manager_types'] = $params['manager_type'];
        }

        if ($params['all_user']) {
            $data['user_group'] = ['all' => ['all']];
        } else {
            $userGroup = [];
            foreach (['user_ids', 'dept_ids', 'role_ids'] as $key) {
                $item = $params[$key];
                if ($item && is_array($item)) {
                    $userGroup[$key] = $item;
                }
            }
            !$userGroup && ResponseService::throwException('0x036001', 'project');
            $data['user_group'] = $userGroup;
        }

        return $data;
    }

    private static function handleRoleListData(&$list)
    {
        HelpersManager::toEloquentCollection($list, function ($data) {
            $managerTypes = self::getAllProjectTypes();
            foreach ($data as $item) {
                $type = $item['type'];
                $item['type_name'] = trans("project.{$type}");
                $item['manager_type_name'] = Arr::get($managerTypes, $item['manager_type'], '');
                $item['role_field_key_name'] = self::getRoleFieldKeyName($item['role_field_key'], $type, $item['manager_type']);
            }
            return $data;
        });
    }

    // 处理监控角色的数据
    private static function handleMonitorRoleListData(&$list)
    {
        HelpersManager::toEloquentCollection($list, function ($data) {
            $managerTypesName = self::getAllProjectTypes();
            $relations = [
                'user_ids' => [],
                'role_ids' => [],
                'dept_ids' => [],
            ];
            foreach ($data as $item) {
                $managerTypes = $item->manager_types->pluck('manager_type')->toArray();

                // 处理项目分类名称
                $item['manager_type_name'] = '';
                $item['all_manager_type'] = 0;
                $item['all_user'] = 0;
                if (in_array('all', $managerTypes)) {
                    $item['all_manager_type'] = 1;
//                    $item['manager_type_name'] = implode('　', $managerTypesName);
                } else {
                    if ($managerTypes) {
                        $item['manager_type'] = $managerTypes;
                        $managerTypes = array_extract($managerTypesName, $managerTypes);
                        $managerTypes = array_filter($managerTypes);
                        $item['manager_type_name'] = implode('　', $managerTypes);
                    }
                }
                // 处理人员id
                $userGroup = $item->user_group->groupBy('type');
                foreach ($userGroup as $type => $value) {
                    if ($type == 'all') {
                        $item['all_user'] = 1;
                        break;
                    }
                    // 合并同类数据
                    $value = $value->pluck('type_value')->toArray();
                    $relations[$type] = array_merge($relations[$type], $value);
                    $item[$type] = $value;
                }
            }

            // 根据人员id获取名称
            $relations['user_ids'] = OtherModuleRepository::buildUserQuery(['in_user_id' => $relations['user_ids']])
                ->pluck('user_name', 'user_id')->toArray();
            $relations['dept_ids'] = OtherModuleRepository::buildDepartmentQuery(['in_dept_id' => $relations['dept_ids']])
                ->pluck('dept_name', 'dept_id')->toArray();
            $relations['role_ids'] = OtherModuleRepository::buildRoleQuery(['in_role_id' => $relations['role_ids']])
                ->pluck('role_name', 'role_id')->toArray();

            // 处理前端需要展示的人员名称
            foreach ($data as $item) {
                if ($item['all_user']) {
                    continue;
                }
                foreach ($relations as $key => $names) {
                    if (isset($item[$key])) {
                        $allName = array_extract($names, $item[$key]);
                        $item["show_" . $key . '_name'] = implode('　', array_slice($allName, 0, 5));
                        $item[ $key . '_name'] = implode('　', $allName);
                    }
                }
            }
            return $data;
        });
    }

    private static function getRoleFieldKeyName($roleFieldKey, $type, $managerType)
    {
        $name = '';
        if (in_array($type, ['project', 'task'])) {
            $tableName = '';
            $type == 'project' && $tableName = self::getProjectCustomTableKey($managerType);
            $type == 'task' && $tableName = self::getProjectTaskCustomTableKey($managerType);
            $name = mulit_trans_dynamic("custom_fields_table.field_name." . $tableName . '_' . $roleFieldKey);
        }
        !$name && $name = trans("project.role.{$roleFieldKey}");
        return $name;
    }

    private static function setRoleAuth(&$list)
    {
        HelpersManager::toEloquentCollection($list, function ($data) {
            foreach ($data as $item) {
                $functionPageConfigs = ['role_edit' => []];
                if (!$item->is_system) {
                    $functionPageConfigs['role_delete'] = [];
                }
                $item['function_page_configs'] = $functionPageConfigs;
            }
            return $data;
        });
    }


    private static function getRelationPenetrate($params) {
        $relationPenetrate = Arr::get($params, 'relation_penetrate', []);
        $relationPenetrate = array_intersect(ProjectRoleUserGroupEntity::getAllRelationPenetrateValue(), $relationPenetrate);
        if (!$relationPenetrate) {
            ResponseService::throwException('0x036001', 'project');
        }
        return $relationPenetrate;
    }

}
