<?php

namespace App\EofficeApp\Project\NewServices;

use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\FormModeling\Repositories\FormModelingRepository;
use App\EofficeApp\FormModeling\Services\FormModelingService;
use App\EofficeApp\Project\Entities\ProjectRoleUserGroupEntity;
use App\EofficeApp\Project\NewRepositories\OtherModuleRepository;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewRepositories\ProjectTaskRepository;
use App\EofficeApp\Project\NewServices\Managers\CacheManager;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\PermissionManager;
use App\EofficeApp\Project\NewServices\ServiceTraits\Authority\ProjectFunctionPageTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\Authority\ProjectRoleFunctionPageTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\Authority\ProjectRoleManagerTypeTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\Authority\ProjectRoleTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\Authority\ProjectRoleUserGroupTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\FlowOutSendTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\ProjectDiscussTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\Document\ProjectDocumentTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\ProjectLogTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\ProjectPermissionTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\ProjectQuestionTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\ProjectReportTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\ProjectRoleUserTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\setting\ProjectSettingTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\ProjectTaskDiscussTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\ProjectTaskTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\ProjectTemplateTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\ProjectThirdApi;
use App\EofficeApp\Project\NewServices\ServiceTraits\ProjectTrait;
use App\EofficeApp\Project\NewServices\ServiceTraits\ProjectUserReportTrait;
use App\EofficeApp\User\Services\UserService;
use App\Exceptions\ResponseException;
use App\Utils\ResponseService;
use App\Utils\Utils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use DB;
use Illuminate\Support\Arr;

class ProjectService extends BaseService
{
    const REPORT_CACHE_SECOND = 3600 * 6;
    use ProjectUserReportTrait;
    use ProjectReportTrait;
    use ProjectPermissionTrait;
    use ProjectTrait;
    use ProjectLogTrait;
    use ProjectRoleUserTrait;
    use ProjectQuestionTrait;
    use ProjectDocumentTrait;
    use ProjectTaskTrait;
    use ProjectTemplateTrait;
    use ProjectDiscussTrait;
    use ProjectTaskDiscussTrait;
    use FlowOutSendTrait;
    use ProjectThirdApi;
    use ProjectSettingTrait;
    use ProjectFunctionPageTrait;
    use ProjectRoleTrait;
    use ProjectRoleFunctionPageTrait;
    use ProjectRoleManagerTypeTrait;
    use ProjectRoleUserGroupTrait;
//    const systemComboBoxService = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
    const systemComboBoxFieldRepository = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxFieldRepository';
    const formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
    const formModelingRepository = 'App\EofficeApp\FormModeling\Repositories\FormModelingRepository';
    const attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
    const calendarService = 'App\EofficeApp\Calendar\Services\CalendarService';
    const userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
    const roleService = 'App\EofficeApp\Role\Services\RoleService';
    const userService = 'App\EofficeApp\User\Services\UserService';
    const departmentDirectorRepository = 'App\EofficeApp\System\Department\Repositories\DepartmentDirectorRepository';

    public function __construct()
    {
//        parent::__construct();
//        $this->userRepository                = 'App\EofficeApp\User\Repositories\UserRepository';
//        $this->attachmentService             = 'App\EofficeApp\Attachment\Services\AttachmentService';
//        $this->systemComboboxService         = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
//        $this->systemComboboxFieldRepository = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxFieldRepository';
//        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
//        $this->formModelingRepository = 'App\EofficeApp\FormModeling\Repositories\FormModelingRepository';
//        $this->calendarService = 'App\EofficeApp\Calendar\Services\CalendarService';
    }

    // 每日更新过期的项目与任务
    public static function updateProjectAndTaskOverdue()
    {
        $now = date('Y-m-d');
        ProjectManagerRepository::buildQuery()->where('manager_state', 4)
            ->where('manager_endtime', '<', $now)
            ->where('is_overdue', '!=', 1)
            ->update(['is_overdue' => 1]);
        ProjectTaskRepository::buildQuery()->where('task_persent', '<', 100)
            ->where('task_endtime', '<', date('Y-m-d'))
            ->where('is_overdue', '!=', 1)
            ->update(['is_overdue' => 1]);
        // 更新开始日期，当前方案是与开始日期相等
        $nowTime = date('Y-m-d H:i:s');
        \DB::select("update `project_task` set `start_date` = `task_begintime`, `updated_at` = '{$nowTime}' where `start_date` is null and task_begintime <= '{$now}'");
        CacheManager::cleanProjectReportCache();
    }


    /**
     * 获取项目自定义表单中，外键的自定义标签信息
     * @param Model $project
     * @param array $params
     * @param array $own
     * @return array
     */
    public static function customTabMenus($project, $params, $own)
    {
        $isMobile = Arr::get($params, 'is_mobile', 0);//手机端固定的tab不在电脑端生成
        $type = $project->manager_type;
        $projectId = $project->manager_id;
        $menuLists = FormModelingRepository::getCustomTabMenus(self::getProjectCustomTableKey($type), function () use ($own, $isMobile, $projectId, $project) {
            if (!$isMobile) {
                return self::getProjectTabMenus($own, $project);
            } else {
                $menuTemp = [];
                self::handleCustomTabMenus($project, $own, $menuTemp, $isMobile);
                return $menuTemp;
            }
        }, $projectId);

        //自定义合同标签
        if (envOverload('PROJECT_CUSTOMER_TAB_WITH_CONTRACT', false)) {
            foreach ($menuLists as $key => $menuList) {
                if ($menuList['key'] == 'customer') {
                    $customerId = Arr::get($menuList, 'id', 0);
                    $menuLists[] = [
                        'isShow' => true,
                        'fixed' => true,
                        'id' => $customerId,
                        'key' => 'customer_contract',
                        'menu_code' => 'customer_contract',
                        'foreign_key' => 'customer_id',
                        'title' => '合同',
                        'view' => [
                            "custom/list",
                            ['menu_code'=>'customer_contract','primary_key'=>'contract_id','foreign_key'=>'customer_id','id'=>$customerId]
                        ]
                    ];
                }
            }
        }

        //数据格式化
        foreach ($menuLists as $key => $menuList) {
            //手机端获取数量
            if ($isMobile) {
                $stateUrl = Arr::get($menuList, 'view.0');
                $id = Arr::get($menuList, 'view.1.id');
                if ($stateUrl == 'custom/detail') {
                    $menuLists[$key]['count'] =  $id? 1 : 0;
                } elseif ($stateUrl == 'custom/list') {
                    $menuParams = [
                        'response' => 'count',
                    ];
                    // 合同与其它自定义模块
                    if (isset($menuList['foreign_key'])) {
                        $menuParams['search'] = [
                            $menuList['foreign_key'] => [$id, '=']
                        ];
                    } else {
                        $menuParams['foreign_key'] = $projectId;
                    }
                    $list = self::getFormModelingService()->getCustomDataLists($menuParams, $menuList['key'], $own);

                    $menuLists[$key]['count'] = !is_array($list) ? $list : 0;
                }
                Arr::set($menuLists[$key]['view'], 0, '/project/mine/tasks/' . $stateUrl);
                Arr::set($menuLists[$key]['view'], '1.menu_name', $menuList['title']);
            }
            // 插入项目id
            Arr::set($menuLists[$key]['view'][1], 'manager_id', $projectId);
            // 差异化自定义字段的链接，否则前端active点击会无法正整使用,放在最后
            if (in_array($menuList['view'][0], ['custom/detail', 'custom/list'])) {
                $menuLists[$key]['view'][0] .= '/' . $menuList['key'];
            }
        }

        $data = [
            'menus' => $menuLists,
            'manager_type' => $type
        ];
        if ($isMobile) {
            $showStatus = self::getMobileTabStatus($type, $projectId, $own);
            $showStatus['log'] = false;
            $data['tab_show_status'] = $showStatus;
        }

        return $data;
    }

    public static function getAllProjectTypes()
    {
        return self::getSystemComboBoxFieldValueName();
    }

    public static function getAllProjectPriorities()
    {
        return self::getSystemComboBoxFieldValueName('PROJECT_PRIORITY');
    }

    public static function getAllQuestionTypes()
    {
        return self::getSystemComboBoxFieldValueName('QUESTION_TYPE');
    }

    public static function getAllProjectDegrees()
    {
        return self::getSystemComboBoxFieldValueName('PROJECT_DEGREE');
    }

    public static function getProjectStateName($managerState) {
        $state_name = "";
        switch ($managerState) {
            case 1:
                $state_name = trans("project.in_the_project");
                break;
            case 2:
                $state_name = trans("project.examination_and_approval");
                break;
            case 3:
                $state_name = trans("project.retreated");
                break;
            case 4:
                $state_name = trans("project.have_in_hand");
                break;
            case 5:
                $state_name = trans("project.finished");
                break;
        }

        return $state_name;
    }

    // 格式化导出数据的header
    public static function formatExportHeader(array $includeColumns)
    {
        $header = [];
        foreach ($includeColumns as $item) {
            if (isset($item['key']) && isset($item['key'])) {
                $header[$item['key']] = [
                    'data' => $item['name']
                ];
            }
        }
        return $header;
    }

    // 排除自定义的特有的参数，即@开头的
    private static function exceptCustomParams(array $params, $prefix = '@') {
        foreach ($params as $key => $item) {
            if (strpos($key, $prefix) === 0) {
                unset($params[$key]);
            }
        }
        return $params;
    }

    // 返回自定义字段tableKey标识或者表名
    public static function getProjectCustomTableKey($managerType, $getTableName = false) {
        $key = "project_value_" . $managerType;
        $getTableName && $key = 'custom_data_' . $key;
        return $key;
    }

    // 返回自定义字段tableKey标识或者表名
    public static function getProjectTaskCustomTableKey($managerType = 1, $getTableName = false) {
        $managerType = 1;
        $key = "project_task_value_" . $managerType;
        $getTableName && $key = 'custom_data_' . $key;
        return $key;
    }

    public static function isProjectCustomTableKey($tableKey)
    {
        return strpos($tableKey, 'project_value_') === 0;
    }

    public static function isProjectTaskCustomTableKey($tableKey)
    {
        return strpos($tableKey, 'project_task_value_') === 0;
    }

    //获取固定菜单的显示状态
    private static function getMobileTabStatus($type, $projectId, $own)
    {
        $menuLists = FormModelingRepository::getCustomTabMenus(self::getProjectCustomTableKey($type), function () use ($own, $projectId) {
            return self::getProjectTabMenus($own);
        }, $projectId);
        $fixedMenuLists = self::getProjectTabMenus($own);
        $menuListsIsShow = Arr::pluck($menuLists, 'isShow', 'key');
        $fixedMenuListKeys = Arr::pluck($fixedMenuLists, 'key', 'key');

        return array_intersect_key($menuListsIsShow, $fixedMenuListKeys);
    }

    private static function getProjectTabMenus($own, $project = null)
    {
        $dataManager = DataManager::getIns();
        $project = $dataManager->getProject();
        $fPIs = ['task_list', 'project_info', 'project_discuss', 'question_list', 'document_list', 'gantt_list', 'appraisal_list', 'log_list'];
        PermissionManager::setDataFunctionPages($project, $dataManager, $fPIs);
        $hasPermissionFPIs = object_get($project, 'function_page_configs', []);
        $result = [];
        isset($hasPermissionFPIs['task_list']) && $result[] = [
            'key'    => 'tasks',
            'isShow' => true,
            'fixed'  => true,
            'view'   => ['tasks'],
            'title'  => trans('project.task'),
        ];
        isset($hasPermissionFPIs['project_info']) && $result[] = [
            'key'    => 'teams',
            'isShow' => true,
            'fixed'  => true,
            'view'   => ['teams'],
            'title'  => trans('project.team'),
        ];
        isset($hasPermissionFPIs['project_discuss']) && $result[] = [
            'key'    => 'discuss',
            'isShow' => true,
            'fixed'  => false,
            'view'   => ['discuss'],
            'title'  => trans('project.discuss'),
        ];
        isset($hasPermissionFPIs['question_list']) && $result[] = [
            'key'    => 'questions',
            'isShow' => true,
            'fixed'  => false,
            'view'   => ['questions'],
            'title'  => trans('project.problem'),
        ];
        isset($hasPermissionFPIs['document_list']) && $result[] = [
            'key'    => 'documents',
            'isShow' => true,
            'fixed'  => false,
            'view'   => ['documents'],
            'title'  => trans('project.file'),
        ];
//        isset($hasPermissionFPIs['gantt_list']) && $result[] = [
//            'key'    => 'gantt',
//            'isShow' => true,
//            'fixed'  => false,
//            'view'   => ['gantt'],
//            'title'  => trans('project.gantt_chart'),
//        ];
        isset($hasPermissionFPIs['project_info']) && $result[] = [
            "key"    => "detail",
            "isShow" => true,
            'fixed'  => true,
            "view"   => ['detail'],
            'title'  => trans('project.detail'),
        ];
        isset($hasPermissionFPIs['appraisal_list']) && $result[] = [
            "key"    => "appraisal",
            "isShow" => true,
            'fixed'  => false,
            "view"   => ['appraisal'],
            'title'  => trans('project.assessment'),
        ];

        isset($hasPermissionFPIs['log_list']) && $result[] = [
            "key"    => "log",
            "isShow" => true,
            'fixed'  => true,
            "view"   => ['log'],
            'title'  => trans('project.log.name'),
        ];

        //是否拥有费用模块权限
        $menuIds = Arr::get($own, 'menus.menu', []);
        if (in_array(38, $menuIds) || in_array(90, $menuIds)) {
            $url = in_array(38, $menuIds) ? 'cost/list' : 'cost/count';
            $result[] = [
                "key"    => "cost",
                "isShow" => true,
                'fixed'  => false,
                "view"   => [$url],
                'title'  => trans('charge.charge'),
            ];
        }

        self::handleCustomTabMenus($project, $own, $result);

        return $result;
    }

    // 解析固定的json参数
    private static function parseFixParam($param, $assoc = true)
    {
        return (new self)->parseParams($param, $assoc); // Todo 未来改进，不要new对象
    }

    /**
     * 用于为数组内的字符串类数据前后拼接字符串
     * @param array|collection $data 数据数组或集合
     * @param string|array $columns 需要处理的字段，必须是字符，否则跳过不处理
     * @param string $append 后面拼接的字符串
     * @param string $prefix 前面拼接的字符串
     */
    private static function spliceStringForColumns(&$data, $columns, $append = '%', $prefix = '')
    {
        if (is_scalar($columns)) {
            $columns = [$columns];
        }
        foreach ($data as $key => $value) {
            foreach ($columns as $column) {
                if (isset($value[$column])) {
                    $data[$key][$column] = $prefix . $value[$column] . $append;
                }
            }
        }
    }

    // 获取下拉框键值对
    private static function getSystemComboBoxFieldValueName($key = 'PROJECT_TYPE') {
        return CacheManager::getOnceArrayCache('system_combobox_' . $key, function () use ($key) {
            $projectTypes = self::getSystemComboBoxFieldRepository()->getSystemComboboxFieldNameAll($key);
            return Arr::pluck($projectTypes, 'field_name', 'field_value');
        });
    }

    // 合并二维数组，主要用于列表数据追加字段，同key才会追加
    private static function twoDimensionArrayMerge(&$data, ...$arrays) {
        foreach ($data as $key => $item) {
            foreach ($arrays as $array) {
                if (isset($array[$key])) {
                    $data[$key] = array_merge($data[$key], $array[$key]);
                }
            }
        }
    }

    public static function handleListParams($params, $unsetSearch = true) {
        self::exceptCustomParams($params);
        $params = self::parseFixParam($params);
        $search = Arr::get($params, 'search');
        if ($unsetSearch) {
            unset($params['search']);
            if ($search && is_array($search)) {
                $params = array_merge($params, $search);
            }
        }

        return $params;
    }

    // 将数组内的某个数组，合并当该数组中
    private static function collapseData(array &$data, $collapseKey = 'search')
    {
        $collapseData = Arr::get($data, $collapseKey);
        unset($data[$collapseKey]);
        if ($collapseData && is_array($collapseData)) {
            $data = array_merge($data, $collapseData);
        }
    }

    #####################其他模块调用
    /**
     * @return FormModelingService
     */
    private static function getFormModelingService(): FormModelingService
    {
        return app(self::formModelingService);
    }

    private static function getFormModelingRepository()
    {
        return app(self::formModelingRepository);
    }

    private static function getCalendarService()
    {
        return app(self::calendarService);
    }

    private static function getSystemComboBoxFieldRepository()
    {
        return app(self::systemComboBoxFieldRepository);
    }

    private static function getUserRepository()
    {
        return app(self::userRepository);
    }

    private static function getSelf()
    {
        return new self();
    }

    /**
     * @return AttachmentService
     */
    private static function getAttachmentService()
    {
        return app(self::attachmentService);
    }

    private static function getUserIds() {
        return CacheManager::getOnceArrayCache('projectServiceGetUserIds', function () {
            return Utils::getUserIds();
        });
    }

    public static function getRoleService() {
        return app(self::roleService);
    }

    public static function getUserService() {
        return app(self::userService);
    }

    public static function getDepartmentDirectorRepository() {
        return app(self::departmentDirectorRepository);
    }

    private static function getAttachments($tableNames, $primaryId, $isBatch = false) {
        return self::getAttachmentService()->getAttachmentIdsByEntityId([
            'entity_table' => $tableNames,
            'entity_id' => $primaryId
        ], $isBatch);
    }

    /**
     * 获取附件
     * @param Model|Collection $data 数据
     * @param string $keyName 字段名
     */
    private static function setAttachments($data, $keyName = 'attachments')
    {
        HelpersManager::toEloquentCollection($data, function ($data) use ($keyName) {
            // 循环获取，暂时都是单个数据获取，没有批量的场景，如果后续有，改进一下即可
            foreach ($data as &$model) {
                $tableName = $model->getTable();
                $primaryId = $model->getKey();
                $attachments = self::getAttachments($tableName, $primaryId);
                $model[$keyName] = $attachments;
            }
            return $data;
        });
    }

    /**
     * 删除关联附件，支持批量
     * @param Model|Collection $data
     */
    private static function deleteAttachments($data)
    {
        HelpersManager::toEloquentCollection($data, function ($data) {
            $model = $data->first();
            $tableName = $model->getTable();
            $primaryKey = $model->getKeyName();
            $destroyIds = $data->pluck($primaryKey)->toArray();
            $delData = [
                "entity_table" => $tableName,
                "entity_id" => ['entity_id' => $destroyIds]
            ];
            self::getAttachmentService()->deleteAttachmentByEntityId($delData);
        });
    }

    /**
     * 同步附件数据
     * @param Model $model 模型
     * @param array $data 数据
     * @param string $key 数据中附件的key
     * @param string $type 同步类型 update|add
     * @throws \Exception
     */
    private static function syncAttachments($model, $data, $key = 'attachments', $type = 'update')
    {
        if (key_exists($key, $data)) {
            $attachments = Arr::get($data, $key, '');
            self::getAttachmentService()->attachmentRelation($model->getTable(), $model->getKey(), $attachments, $type);
        }
    }

    // Todo 需要优化
    public static function checkProjectManagerUnique($data, $managerId): ResponseService
    {
        $checkKey = ['manager_number', 'manager_name']; // 需要检查的字段
        $responseService = ResponseService::getNewIns();
        $checkData = [
            'table_key' => 'project_value_' . Arr::get($data, 'manager_type'),
            'manager_id' => $managerId
        ];
        foreach ($checkKey as $key) {
            $checkData['field_code'] = $key;
            $checkData['value'] = Arr::get($data, $key);
            $res = self::getFormModelingService()->checkFieldsUnique($checkData);
            if ($responseService->setCodeException($res)) {
                return $responseService;
            } else if (!$res) {
                $fieldName = mulit_trans_dynamic("custom_fields_table.field_name." . $checkData['table_key'] . "_" . $key);
//                return ['code' => ['0x016031', 'fields'], 'dynamic' => $fieldName . trans('fields.exist')];
                $responseService->setException('0x016031', 'fields', $fieldName . trans('fields.exist'));
                return $responseService;
            }
        }
        return $responseService;
    }

    /**
     * 自定义字段副表数据入库
     * @param string $customTableKey 自定义字段key
     * @param array $data
     * @param array|Model $project // 用于输入权限数据，不传入则存储
     * @param int $primaryId
     * @param string $type
     * @param string $saveType add|edit
     * @param bool $updateRelation 默认直接更新角色关联数据
     * @throws ResponseException
     */
    private static function auxiliaryTableSave($customTableKey, $data, $project, $primaryId, $type = 'project', $saveType = 'add', $updateRelation = true)
    {
        $responseService = ResponseService::getNewIns();
        $formModelingService = self::getFormModelingService();
        $customTableFields = $formModelingService->listFields([], $customTableKey);
        $customData = array_intersect_key($data, $customTableFields);

        $customData['data_id'] = $primaryId;

        $existCustomData = false;
        $saveType === 'edit' && $existCustomData = $formModelingService->getCustomDataDetail($customTableKey, $primaryId) ? true : false;
        if ($existCustomData) {
            isset($data['outsourceForEdit']) && $customData['outsourceForEdit'] = true;
            $insetCustomDataResult = $formModelingService->editCustomData($customData, $customTableKey, $primaryId);
        } else {
            isset($data['outsource']) && $customData['outsource'] = true;
            $insetCustomDataResult = $formModelingService->addCustomData($customData, $customTableKey);
        }
        $responseService->setCodeException($insetCustomDataResult);
        $responseService->checkException();

        if ($project) {
            $managerId = $project['manager_id'];
            self::setFieldRoleRelation($customData, $type, $primaryId, $managerId, $project['manager_state']); // 自定义字段可关联到角色，就会要设置更新角色更新
        }
        $updateRelation && ProjectService::updateRelation();// 新增状态下需单独存储
    }

    /**
     * 删除副表数据（支持批量）
     * @param int|array $managerTypes 删除的项目的项目类型
     * @param int|array $deleteIds 删除的id
     * @param string $type project|task
     */
    private static function auxiliaryTableDelete($managerTypes, $deleteIds, $type = 'project')
    {
        $managerTypes = HelpersManager::scalarToArray($managerTypes);
        $customTableKeys = [];
        foreach ($managerTypes as $managerType) {
            $type === 'task' && array_push($customTableKeys, self::getProjectTaskCustomTableKey($managerType));
            $type === 'project' && array_push($customTableKeys, self::getProjectCustomTableKey($managerType));
        }
        foreach ($customTableKeys as $customTableKey) {
            $table = 'custom_data_' . $customTableKey;
            try {
                if (is_scalar($deleteIds)) {
                    DB::table($table)->where('data_id', $deleteIds)->delete();
                } elseif (is_array($deleteIds)) {
                    DB::table($table)->whereIn('data_id', $deleteIds)->delete();
                }
            } catch (\Exception $e) {
            }
        }
    }

    // 专门处理导出接口的固定数据
    public static function exportHandle($params, callable $func)
    {
        $apiParams = Arr::get($params, 'api_params');
        $apiParams['page'] = 0;
        $userInfo = $params['user_info'];
        $includeColumns = Arr::get($params, 'export_params.include_columns');
        unset($params['api_params'], $params['export_params']);
        $apiParams = array_merge($params, $apiParams); // 内部有一些特定参数

        $header = self::formatExportHeader($includeColumns);
        $data = $func($apiParams, $header, $userInfo);

        return compact('data', 'header');
    }

    // 新版导出函数处理
    public static function newExportHandle($builder, $params, callable $func)
    {
        $apiParams = Arr::get($params, 'api_params');
        $apiParams['page'] = 0;
        $userInfo = $params['user_info'];
        $includeColumns = Arr::get($params, 'export_params.include_columns');
        unset($params['api_params'], $params['export_params']);
        $apiParams = array_merge($params, $apiParams); // 内部有一些特定参数

        $header = self::formatExportHeader($includeColumns);

        return $func($apiParams, $header, $userInfo, $builder);
    }

    public static function filterNull(&$data, $default = '')
    {
        if (is_array($data)) {
            foreach ($data as $key => $item) {
                is_null(($item)) && $data[$key] = $default;
            }
        }
    }

    // 返回['code' => []]类型的错误
    private static function tryCatchToCode(callable $func)
    {
        try {
            return $func();
        } catch (ResponseException $e) {
            return ['code' => [$e->getLangCode(), $e->getLangModule()], 'dynamic' => $e->getDynamic()];
        } catch (\Exception $exception) {
            return ['code' => ['0x000003', 'common']];
        }
    }

    private static function handleCustomTabMenus($project, $own, &$menus, $isMobile = false) {
        $object = config('project.custom_tab_menus.0');
        $func = config('project.custom_tab_menus.1');
        if (method_exists($object, $func)) {
            $customMenus = $object::$func($project, $own, $isMobile);
            if ($customMenus) {
                $menus = array_merge($menus, $customMenus);
            }
        }
    }
}
