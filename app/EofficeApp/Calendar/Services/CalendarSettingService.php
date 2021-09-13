<?php
namespace App\EofficeApp\Calendar\Services;

/**
 * @日程模块服务类
 */
class CalendarSettingService extends CalendarBaseService 
{

    private $calendarRepository;
    private $calendarSetRepository;
    private $calendarAttentionGroupRepository;
    private $calendarDefaultValueRepository;
    private $calendarJoinModuleConfigRepository;
    private $calendarTypeRepository;
    private $calendarPurviewRepository;
    private $calendarSharePurviewManageUserRepository;
    private $langService;
    private $userSystemInfoRepository;
    private $userService;
    private $departmentService;
    private $roleService;
    private $moduleConfig = [        
        1 => 'flow',
        44 => 'customer',
        530 => 'task',
        700 => 'meeting',
        160 => 'project',
        600 => 'car',
        82 => 'performance',
    ];
    /**
     * @注册日程相关的资源库对象
     * @param \App\EofficeApp\Repositories\CalendarRepository $calendarRepository
     */
    public function __construct() {
        parent::__construct();
        $this->calendarRepository = 'App\EofficeApp\Calendar\Repositories\CalendarRepository';
        $this->calendarAttentionGroupRepository = 'App\EofficeApp\Calendar\Repositories\CalendarAttentionGroupRepository';
        $this->calendarSetRepository = 'App\EofficeApp\Calendar\Repositories\CalendarSetRepository';
        $this->calendarPurviewRepository = 'App\EofficeApp\Calendar\Repositories\CalendarSharePurviewRepository';

        $this->calendarDefaultValueRepository = 'App\EofficeApp\Calendar\Repositories\CalendarDefaultValueRepository';
        $this->calendarSharePurviewManageUserRepository = 'App\EofficeApp\Calendar\Repositories\CalendarSharePurviewManageUserRepository';
        $this->calendarSharePurviewUserRepository = 'App\EofficeApp\Calendar\Repositories\CalendarSharePurviewUserRepository';
        $this->calendarSharePurviewRoleRepository = 'App\EofficeApp\Calendar\Repositories\CalendarSharePurviewRoleRepository';
        $this->calendarSharePurviewDeptRepository = 'App\EofficeApp\Calendar\Repositories\CalendarSharePurviewDeptRepository';
        
        $this->calendarJoinModuleConfigRepository = 'App\EofficeApp\Calendar\Repositories\CalendarJoinModuleConfigRepository';
        $this->calendarTypeRepository = 'App\EofficeApp\Calendar\Repositories\CalendarTypeRepository';
        $this->calendarOuterRepository = 'App\EofficeApp\Calendar\Repositories\CalendarOuterRepository';
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->departmentService = 'App\EofficeApp\System\Department\Services\DepartmentService';
        $this->roleService = 'App\EofficeApp\Role\Services\RoleService';
    }

    /**
     * 获取日程设置信息
     * @param  [type] $key [description]
     * @return [type]       [description]
     */
    public function getBaseSetInfo($key = null) 
    {
        $list = app($this->calendarSetRepository)->getCalendarSetInfo($key);

        $map = $list->mapWithKeys(function($item) {
            return [$item->calendar_set_key => $item->calendar_set_value];
        });

        return $key ? $map[$key] : $map;
    }

    /**
     * 日程设置
     * @param [type] $data [description]
     */
    public function setBaseSetInfo($data) 
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $item = ['calendar_set_key' => $key, 'calendar_set_value' => $value];
                app($this->calendarSetRepository)->updateSetData($item, ['calendar_set_key' => [$key]]);
            }
        }
        return true;
    }
    public function getJoinModule() 
    {
        $allConfig = app($this->calendarJoinModuleConfigRepository)->getAllModuleConfig();
        $modules = [];
        foreach ($this->moduleConfig as $moduleId => $code) {
            $module = [
                'module_id' => $moduleId, 
                'module_name' => trans('calendar.'. $code),
                'module_type' => $code,
                'children' => []
            ];
            foreach ($allConfig as $item) {
                if($item->module_id == $moduleId) {
                    $item->from_name = trans('calendar.' . str_replace('-', '_', $item->from));
                    $module['children'][] = $item;
                }
            }
            $modules[] = $module;
        }
        return $modules;
    }
    public function getJoinModuleConfig() 
    {
        $allConfig = app($this->calendarJoinModuleConfigRepository)->getAllModuleConfig();
        
        return $allConfig;
    }
    public function setJoinModuleConfig($data)
    {
        $updateData = [
            'allow_manual_join' => $data['allow_manual_join'] ?? 0,
            'allow_auto_join' => $data['allow_auto_join'] ?? 0,
            'calendar_type_id' => $data['calendar_type_id'] ?? 1,
        ];
        return app($this->calendarJoinModuleConfigRepository)->updateData($updateData, ['module_id' =>[$data['module_id']], 'from' => [$data['from']]]);
    }
    public function getModuleConfigByFrom($from)
    {
        return app($this->calendarJoinModuleConfigRepository)->getModuleConfigByFrom($from);
    }
    public function getDefalutValueByTypeId($typeId)
    {
        $defaultValue = app($this->calendarDefaultValueRepository)->getDefalutValueByTypeId($typeId);
        if($defaultValue) {
            $defaultValue->remind_config = json_decode($defaultValue->remind_config);
        }
        return $defaultValue;
    }
    public function setDefalutValueByTypeId($typeId, $data)
    {
        $data['remind_config'] = json_encode($data['remind_config']);
        return app($this->calendarDefaultValueRepository)->updateData($data, ['type_id' => $typeId]);
    }
    public function getOuterBySourceId($sourceId, $sourceFrom) 
    {
        $caelndarRealtion = app($this->calendarOuterRepository)->getOuterBySourceId($sourceId, $sourceFrom);
        return $caelndarRealtion[0] ?? [];
    }
    public function getCalendarType($param = []) 
    {
        $result = app($this->calendarTypeRepository)->getAllCalendarType($this->parseParams($param));
        if ($result) {
            foreach ($result as $item) {
                $typeName = $item->type_name;
                $item->type_name = mulit_trans_dynamic('calendar_type.type_name.'. $typeName);
                $item->type_name_lang = app($this->langService)->transEffectLangs('calendar_type.type_name.' . $typeName, true);
            }
        }
        return $result;
    }
    public function getCalendarTypeMap()
    {
        return $this->getCalendarType()->mapWithKeys(function($type){
            return [$type->type_id => $type];
        });
    }
    public function getDefaultCalendarType()
    {
        $result = app($this->calendarTypeRepository)->getDefalutCalendarType();
        $result->type_name = mulit_trans_dynamic('calendar_type.type_name.'. $result->type_name);
        return $result;
    }
    public function setDefaultCalendarType($typeId) 
    {
        $calendarTypeRepository = app($this->calendarTypeRepository);
        $calendarTypeRepository->updateData(['is_default' => 0], ['is_default' => 1]);
        $calendarTypeRepository->updateData(['is_default' => 1], ['type_id' => $typeId]);
        return true;
    }
    public function setCalendarType($typeId, $data)
    {
        app($this->calendarTypeRepository)->updateData(['mark_color' => $data['type']['mark_color']], ['type_id' => $typeId]);
        $this->editTypeName($data['type'], $typeId);
        
        $this->setDefalutValueByTypeId($typeId, $data['default_value']);
        
        return true;
    }
    public function setCalendarTypeSort($data) 
    {
        if(!empty($data)) {
            return array_walk($data, function($item) {
                return app($this->calendarTypeRepository)->updateData(['sort' => $item['sort']], ['type_id' => $item['type_id']]);
            });
        }
        return false;
    }
    public function addCalendarType($data)
    {
        $data['sort'] = 0;
        $data['is_default'] = 0;
        $result = app($this->calendarTypeRepository)->insertData($data);
        if($result) {
            $typeId = $result->type_id;
            app($this->calendarTypeRepository)->updateData(['type_name' => 'type_name_' . $typeId], ['type_id' => $typeId]);
            $this->editTypeName($data, $typeId);
            $defaultConfig = [
                'type_id' => $typeId, 
                'calendar_level' => 0,
                'remind_config' => json_encode([
                    'allow_remind' => 1,
                    'remind_now' => 1,
                    'start_remind' => [
                        'allow' => 1,
                        'hours' => 0,
                        'minutes' => 5
                    ],
                    'end_remind' => [
                        'allow' => 1,
                        'hours' => 0,
                        'minutes' => 5
                    ]
                ])
            ];
            app($this->calendarDefaultValueRepository)->insertData($defaultConfig);
        }
        return $result;
    }
    public function deleteCalendarType($typeId)
    {
        if(app($this->calendarTypeRepository)->deleteById($typeId)) {
            app($this->calendarDefaultValueRepository)->deleteByWhere(['type_id' => [$typeId]]);
            app($this->calendarJoinModuleConfigRepository)->updateData(['calendar_type_id' => 0], ['calendar_type_id' =>[$typeId]]);
            
            return true;
        }
        return false;
    }

    private function editTypeName($data, $typeId) 
    {
        $typeNameKey = 'type_name_' . $typeId;
        $langService = app($this->langService);
        if(isset($data['type_name_lang']) && !empty($data['type_name_lang'])){
            foreach ($data['type_name_lang'] as $locale => $langValue) {
                $langService->addDynamicLang(['table' => 'calendar_type', 'column' => 'type_name', 'lang_key' => $typeNameKey, 'lang_value' => $langValue], $locale);
            }
        } else {
            $langService->addDynamicLang(['table' => 'calendar_type', 'column' => 'type_name', 'lang_key' => $typeNameKey, 'lang_value' => $data['type_name']]);
        }
        return true;
    }

    public function getShareGroup($params)
    {
        $groupCount = app($this->calendarPurviewRepository)->getShareGroupCount();
        if (!$groupCount) {
            return ['list' => [], 'count' => 0];
        }
        $groupCount = app($this->calendarPurviewRepository)->getShareGrouplist($params);
        
    }
    public function addCalendarPurview($data)
    {
        $data = $this->parseParams($data);
        if (!$data) {
            return['code' => ['0x000003', 'calendar']];
        }
        // 主表数据插入
        $purviewData = [
            'group_name' => $data['group_name'] ?? '',
            'remark' => $data['remark'] ?? '',
            'allow_scope' => $data['allow_scope'] ?? 0
        ];
        if (isset($data['id'])) {
            $where = [
                'id'      => [$data['id']]
            ];
            $result = app($this->calendarPurviewRepository)->updateData($purviewData, $where);
            $id = $data['id'];
        } else {
            $result = app($this->calendarPurviewRepository)->insertData($purviewData);
            $id = $result->id ?? '';
        }
        if ($id) {
            // 是否为全体人员, 解析全体人员数据
            if ($purviewData['allow_scope'] == 1) {
                $data = $this->toParseAllowScope($data);
            }
            // 更新数据关联表
            $this->addCalendarPurviewManageUser($data, $id);
            $this->addCalendarPurviewUser($data, $id);
            $this->addCalendarPurviewDept($data, $id);
            $this->addCalendarPurviewRole($data, $id);
        }
        return $result;
    }

    private function toParseAllowScope($data)
    {
        //获取全部人员, 部门, 角色
        $userId = app($this->userService)->getAllUserIdString();
        $deptIdArr = app($this->departmentService)->getAllDeptId();
        $roleId = app($this->roleService)->getAllRoleIds();
        $deptId = array_column($deptIdArr, 'dept_id');
        $purview = json_decode($data['purview'], true);
        $purview['user_id'] = explode(',', $userId);
        $purview['dept_id'] = $deptId;
        $purview['role_id'] = $roleId;
        $data['purview'] = json_encode($purview);
        return $data;
    }
    public function addCalendarPurviewManageUser($data, $id)
    {
        // 先删除
        app($this->calendarSharePurviewManageUserRepository)->deleteByWhere(['group_id' => [$id, '=']]);
        $manageUser = $this->parseInsertData($data, 'manager');
        if ($manageUser) {
            $insertData = [];
            foreach ($manageUser as $k => $v) {
                $insertData[$k] = [
                    'user_id' => $v,
                    'group_id' => $id
                ];
            }
            return app($this->calendarSharePurviewManageUserRepository)->insertMultipleData($insertData);
        }
    }
    public function addCalendarPurviewUser($data, $id)
    {
        app($this->calendarSharePurviewUserRepository)->deleteByWhere(['group_id' => [$id, '=']]);
        $user = $this->parseInsertData($data, 'user_id');
        if ($user == 'all') {
            $user = explode(',', app($this->userService)->getAllUserIdString());
        }
        if ($user) {
            $insertData = [];
            foreach ($user as $k => $v) {
                $insertData[$k] = [
                    'user_id' => $v,
                    'group_id' => $id
                ];
            }
            return app($this->calendarSharePurviewUserRepository)->insertMultipleData($insertData);
        }
    }
    public function addCalendarPurviewDept($data, $id)
    {
        app($this->calendarSharePurviewDeptRepository)->deleteByWhere(['group_id' => [$id, '=']]);
        $dept = $this->parseInsertData($data, 'dept_id');
        if ($dept == 'all') {
            $deptIdArr = app($this->departmentService)->getAllDeptId();
            $dept = array_column($deptIdArr, 'dept_id');
        }
        if ($dept) {
            $insertData = [];
            foreach ($dept as $k => $v) {
                $insertData[$k] = [
                    'dept_id' => $v,
                    'group_id' => $id
                ];
            }
            return app($this->calendarSharePurviewDeptRepository)->insertMultipleData($insertData);
        }
    }
    public function addCalendarPurviewRole($data, $id)
    {
        app($this->calendarSharePurviewRoleRepository)->deleteByWhere(['group_id' => [$id, '=']]);
        $role = $this->parseInsertData($data, 'role_id');
        if ($role == 'all') {
            $role = app($this->roleService)->getAllRoleIds();
        }
        if ($role) {
            $insertData = [];
            foreach ($role as $k => $v) {
                $insertData[$k] = [
                    'role_id' => $v,
                    'group_id' => $id
                ];
            }
            return app($this->calendarSharePurviewRoleRepository)->insertMultipleData($insertData);
        }
    }
    private function parseInsertData($data, $key)
    {
        $return= json_decode($data['purview'], true);
        return $return[$key];
    }
    public function getCalendarPurview($param)
    {
        return  $this->response(app($this->calendarPurviewRepository), 'getCalendarPurviewCount', 'getCalendarPurviewList', $this->parseParams($param));
    }
    public function getCalendarPurviewDetail($id)
    {
        if (!$id) {
            return['code' => ['0x000003', 'common']];
        }
        $result = app($this->calendarPurviewRepository)->getCalendarPurviewDetail($id);
        $return = $result[0] ?? [];
        if ($return) {
            $return['manager'] = $return['calendar_purview_has_many_manage_user'] ? array_column($return['calendar_purview_has_many_manage_user'], 'user_id') : [];
            $return['user_id'] = $return['calendar_purview_has_many_user'] ? array_column($return['calendar_purview_has_many_user'], 'user_id') : [];
            $return['role_id'] = $return['calendar_purview_has_many_role'] ? array_column($return['calendar_purview_has_many_role'], 'role_id') : '';
            $return['dept_id'] = $return['calendar_purview_has_many_dept'] ? array_column($return['calendar_purview_has_many_dept'], 'dept_id') : '';
            unset($return['calendar_purview_has_many_manage_user'], $return['calendar_purview_has_many_user'], $return['calendar_purview_has_many_role'], $return['calendar_purview_has_many_dept']);
        }
        return $return;
    }
    public function getMyCalendarPurview($param, $own)
    {
        $shareGroup = app($this->calendarPurviewRepository)->getMyCalendarPurview($param, $own);
        if ($shareGroup) {
            foreach ($shareGroup as $key => $value) {
                if (isset($value['calendar_purview_has_many_manage_user']) && !empty($value['calendar_purview_has_many_manage_user'])) {
                        $data[$key]['has_children'] = 1;
                    foreach ($value['calendar_purview_has_many_manage_user'] as $k => $v) {
                        $shareGroup[$key]['users'][$k]['user_id'] = $v['user_id'];
                        $shareGroup[$key]['users'][$k]['user_name'] = get_user_simple_attr($v['user_id']);
                        $dept = app($this->userSystemInfoRepository)->getDeptIdByUserId($v['user_id']);
                        $shareGroup[$key]['users'][$k]['dept_name'] = isset($dept[0]['dept_name']) ? $dept[0]['dept_name'] : '';
                        $shareGroup[$key]['users'][$k]['from'] = '3';
                        // $shareGroup[$key]['users'][]['is_read'] = $v['is_read'];
                    }
                }
                unset($value['calendar_purview_has_many_manage_user']);
            }
        }
        return $shareGroup;
    }

    public function deleteCalendarPurview($id)
    {
        if (!$id) {
            return ['code' => ['0x000003', 'common']];
        }
        $where = ['group_id' => [$id, '=']];
        if (app($this->calendarPurviewRepository)->deleteByWhere(['id' => [$id, '=']])) {
            app($this->calendarSharePurviewRoleRepository)->deleteByWhere($where);
            app($this->calendarSharePurviewDeptRepository)->deleteByWhere($where);
            app($this->calendarSharePurviewUserRepository)->deleteByWhere($where);
            app($this->calendarSharePurviewManageUserRepository)->deleteByWhere($where);
        }
        return true;
    }
}
