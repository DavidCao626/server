<?php


namespace App\EofficeApp\Salary\Services;



use App\EofficeApp\Salary\Exceptions\SalaryError;
use App\EofficeApp\User\Repositories\UserRepository;
use App\EofficeApp\User\Services\UserService;
use App\Exceptions\ErrorMessage;
use App\EofficeApp\System\Department\Services\DepartmentService;

class SalaryReportSetService extends SalaryBaseService
{

    private $userService;

    private $userRepository;

    public $departmentService;


    public function __construct(
        UserService $userService,
        UserRepository $userRepository,
        DepartmentService $departmentService
    )
    {
        parent::__construct();
        $this->userService = $userService;
        $this->userRepository = $userRepository;
        $this->departmentService = $departmentService;
        $this->personnelFilesRepository = "App\EofficeApp\PersonnelFiles\Repositories\PersonnelFilesRepository";
        $this->salaryReportSetRepository = 'App\EofficeApp\Salary\Repositories\SalaryReportSetRepository';
    }

    /**
     * 获取权限设置列表
     * @param $param
     * @return array
     */
    public function getSalaryReportSetList($param)
    {
        $param = $this->parseParams($param);
        // 传入 [无账号发薪]配置
        $param['pay_without_account'] = $this->payWithoutAccountConfig;
        return $this->response(app($this->salaryReportSetRepository), 'getSetTotal', 'getSetList', $param);
    }

    /**
     * 新增薪资报表查看范围
     * @param $data
     * @return mixed
     */
    public function addSalaryReportSet($data)
    {
        return $this->handleSalarySetData($data);
    }

    /**
     * 编辑权限设置
     * @param $data
     * @return bool
     */
    public function editSalaryReportSet($data)
    {
        return $this->handleSalarySetData($data);
    }

    /**
     * 删除权限设置
     * @param $id
     * @return bool
     */
    public function deleteSalaryReportSet($id)
    {
        $ids = explode(',', $id);
        return app($this->salaryReportSetRepository)->deleteById($ids);
    }

    /**
     * 处理权限设置的数据
     * @param $data
     * @return bool
     */
    public function handleSalarySetData($data)
    {
        // 管理范围
        switch ($data['set_type']) {
            case 1:
                // 指定部门
                if(isset($data['dept_id']) && !empty($data['dept_id'])){
                    $tempData = [
                        'manager_dept' => '',
                        'manager_role' => '',
                        'manager_user' => '',
                        'set_type'     => 1,
                        'dept_id'      => is_array($data['dept_id']) ? implode(',', $data['dept_id']) : $data['dept_id'],
                        'has_children' => isset($data['has_children']) ? $data['has_children'] : 0,
                        'role_id'      => '',
                        'user_id'      => '',
                    ];

                    return $this->handleDataArray($data, $tempData);
                }
                break;
            case 2:
                // 指定角色
                if(isset($data['role_id']) && !empty($data['role_id'])){
                    $tempData = [
                        'manager_dept' => '',
                        'manager_role' => '',
                        'manager_user' => '',
                        'set_type'     => 2,
                        'dept_id'      => '',
                        'has_children' => 0,
                        'role_id'      => is_array($data['role_id']) ? implode(',', $data['role_id']) : $data['role_id'],
                        'user_id'      => '',
                    ];
                    return $this->handleDataArray($data, $tempData);
                }
                break;
            case 3:
                // 指定用户
                if(isset($data['user_id']) && !empty($data['user_id'])){
                    $userId = is_array($data['user_id']) ? implode(',', $data['user_id']) : $data['user_id'];
                    // 有关联的人事id，转换为用户id，自动判断是否无账号发薪
                    // $userId = $this->transPersonnelFileIds(explode(",", $userId)); // 暂不启用此处理
                    // $userId = implode(',', $userId);
                    $tempData = [
                        'manager_dept' => '',
                        'manager_role' => '',
                        'manager_user' => '',
                        'set_type'     => 3,
                        'dept_id'      => '',
                        'has_children' => 0,
                        'role_id'      => '',
                        'user_id'      => $userId,
                    ];

                    return $this->handleDataArray($data, $tempData);
                }
                break;
            case 4:
                // 直接下属
                $tempData = [
                    'manager_dept' => '',
                    'manager_role' => '',
                    'manager_user' => '',
                    'set_type'     => 4,
                    'dept_id'      => '',
                    'has_children' => 0,
                    'role_id'      => '',
                    'user_id'      => '',
                ];

                return $this->handleDataArray($data, $tempData);
                break;
            case 5:
                // 所有下属
                $tempData = [
                    'manager_dept' => '',
                    'manager_role' => '',
                    'manager_user' => '',
                    'set_type'     => 5,
                    'dept_id'      => '',
                    'has_children' => 0,
                    'role_id'      => '',
                    'user_id'      => '',
                ];

                return $this->handleDataArray($data, $tempData);
                break;
            default:
                break;
        }

        return true;
    }

    public function handleDataArray($data, $tempData)
    {
        // [无账号发薪]时，新建权限，默认是[指定用户]类型，特殊处理
        if($this->payWithoutAccountConfig == '1') {
            $permissionStatus = 'personnel';
        } else {
            $permissionStatus = 'common';
        }
        $tempData['permission_status'] = $permissionStatus;
        if(isset($data['manager']['dept_id']) && !empty($data['manager']['dept_id'])){
            foreach ($data['manager']['dept_id'] as $key => $value) {
                $temp                 = $tempData;
                $temp['manager_dept'] = $value;
                $count = app($this->salaryReportSetRepository)->getTotal(['search' => ['manager_dept' => [$value], 'permission_status' => [$permissionStatus]]]);
                if($count > 0){
                    app($this->salaryReportSetRepository)->updateData($temp, ['manager_dept' => $value, 'permission_status' => $permissionStatus]);
                }else{
                    app($this->salaryReportSetRepository)->insertData($temp);
                }
            }
        }
        if(isset($data['manager']['role_id']) && !empty($data['manager']['role_id'])){
            foreach ($data['manager']['role_id'] as $key => $value) {
                $temp                 = $tempData;
                $temp['manager_role'] = $value;
                $count = app($this->salaryReportSetRepository)->getTotal(['search' => ['manager_role' => [$value], 'permission_status' => [$permissionStatus]]]);
                if($count > 0){
                    app($this->salaryReportSetRepository)->updateData($temp, ['manager_role' => $value, 'permission_status' => $permissionStatus]);
                }else{
                    app($this->salaryReportSetRepository)->insertData($temp);
                }
            }
        }
        if(isset($data['manager']['user_id']) && !empty($data['manager']['user_id'])){
            foreach ($data['manager']['user_id'] as $key => $value) {
                $temp                 = $tempData;
                $temp['manager_user'] = $value;
                // 增加对[无账号发薪]的处理
                $count = app($this->salaryReportSetRepository)->getTotal(['search' => ['manager_user' => [$value], 'permission_status' => [$permissionStatus]]]);
                if($count > 0){
                    app($this->salaryReportSetRepository)->updateData($temp, ['manager_user' => $value, 'permission_status' => $permissionStatus]);
                }else{
                    app($this->salaryReportSetRepository)->insertData($temp);
                }
            }
        }

        return true;
    }

    /**
     * 获取当前用户可查看的用户集合
     * [20201123-无账号发薪：权限数据，数据库储存的是'1,admin'这种混合id，此函数返回的时候，已经转换成纯人事档案id:'1,2'这种了，各处用的时候要根据实际情况进行判断/反转换]
     * @param $own
     * @param array $param
     * @return array
     */
    public function getViewUser($own, $param = [])
    {
        $user     = [];
        $userId   = $own['user_id'];
        $userDept = $own['dept_id'];
        $userRole = $own['role_id'];
        $record   = app($this->salaryReportSetRepository)->getSimpleData(['search' => ['manager_dept' => [$userDept]],'pay_without_account'=>$this->payWithoutAccountConfig]);
        if(isset($record[0]) && !empty($record[0])){
            $user = $this->getViewUserByType($record[0], $userId, $param);
        }
        $record = app($this->salaryReportSetRepository)->getSimpleData(['search' => ['manager_role' => [$userRole, 'in']],'pay_without_account'=>$this->payWithoutAccountConfig]);
        if(count($record) > 0){
            foreach ($record as $key => $value) {
                $tempUser = $this->getViewUserByType($value, $userId, $param);
                if(!empty($user)){
                    if(!empty($tempUser)){
                        $user = array_unique(array_merge($user, $tempUser));
                    }
                }else{
                    if(!empty($tempUser)){
                        $user = $tempUser;
                    }
                }
            }
        }
        $record = app($this->salaryReportSetRepository)->getSimpleData(['search' => ['manager_user' => [$userId]],'pay_without_account'=>$this->payWithoutAccountConfig]);
        if(isset($record[0]) && !empty($record[0])){
            $tempUser = $this->getViewUserByType($record[0], $userId, $param);
            if(!empty($user)){
                if(!empty($tempUser)){
                    $user = array_unique(array_merge($user, $tempUser));
                }
            }else{
                if(!empty($tempUser)){
                    $user = $tempUser;
                }
            }
        }
        return $user;
    }

    public function getViewUserByType($data, $userId, $param = [])
    {
        $user = [];
        switch ($data['set_type']) {
            case 1:
                if($data['has_children'] == 1){
                    //获取部门包含子部门的userId
                    $param = [];
                    $belongsSuperior = [];
                    // 数组，收集父部门和子部门
                    $pierceDeptArray = [];
                    $director = explode(",",$data['dept_id']);
                    foreach ($director as $key => $value) {
                        // 包含本体部门id
                        $allChildren = $this->departmentService->allChildren($value);
                        $deptIds = explode(',', $allChildren);
                        $pierceDeptArray = array_merge($pierceDeptArray, $deptIds);
                    }
                    $pierceDeptArray = array_unique($pierceDeptArray);
                    if($this->payWithoutAccountConfig == '1') {
                        $params = [];
                        $params['fields'] = ['id','user_name'];
                        $params['order_by'] = ['id' => 'asc'];
                        $params['include_leave'] = "0";
                        $params['search'] = ['department.dept_id' => [$pierceDeptArray, 'in']];
                        $personnelFiles = app($this->personnelFilesRepository)->getPersonnelFilesList($params);
                        $tempUser = collect($personnelFiles)->pluck('id')->toArray();
                    } else {
                        $tempUser = $this->userRepository->getUserByAllDepartment($pierceDeptArray)->pluck('user_id')->toArray();
                    }
                    $belongsSuperior = array_values(array_unique($tempUser));
                    $user = $belongsSuperior;
                }else{
                    // [无账号发薪]时，设置了对“部门”的权限，应该去人事模块，根据部门，取人事id
                    if($this->payWithoutAccountConfig == '1') {
                        $params = [];
                        $params['fields'] = ['id','user_name'];
                        $params['order_by'] = ['id' => 'asc'];
                        $params['include_leave'] = "0";
                        $params['search'] = ['department.dept_id' => [explode(',', trim($data['dept_id'],',')), 'in']];
                        $personnelFiles = app($this->personnelFilesRepository)->getPersonnelFilesList($params);
                        $user = collect($personnelFiles)->pluck('id')->toArray();
                    } else {
                        $user = $this->userRepository->getUserByDepartment($data['dept_id'])->pluck("user_id")->toArray();
                    }
                }
                break;
            case 2:
                $roleIds = is_array($data['role_id']) ? $data['role_id'] : explode(',', $data['role_id']);
                $user = $this->userRepository->getUserListByRoleId($roleIds)->pluck("user_id")->toArray();
                break;
            case 3:
                $user = empty($data['user_id']) ? [] : explode(',', $data['user_id']);
                // [无账号发薪]时，数据库存的，用户类型的权限，是人事id，直接返回即可
                break;
            case 4:
                $subordinate = $this->userService->getSubordinateArrayByUserId($userId, ['include_leave' => true]);
                $user        = isset($subordinate['id']) ? $subordinate['id'] : [];

                break;
            case 5:
                $subordinate = $this->userService->getSubordinateArrayByUserId($userId, ['all_subordinate' => 1, 'include_leave' => true]);
                $user        = isset($subordinate['id']) ? $subordinate['id'] : [];

                break;
            default:
                break;
        }

        return $user;
    }

    /**
     * 获取权限设置详情
     * @param $id
     * @return array
     */
    public function getSalaryReportSetById($id)
    {
        if ($data = app($this->salaryReportSetRepository)->getDetail($id)) {
            $data            = $data->toArray();
            $data['manager'] = [];

            if($data['manager_dept'] != ''){
                $data['manager']['dept_id'] = explode(',', $data['manager_dept']);
            }
            if($data['manager_role'] != ''){
                $data['manager']['role_id'] = explode(',', $data['manager_role']);
            }
            if($data['manager_user'] != ''){
                $data['manager']['user_id'] = explode(',', $data['manager_user']);
            }
            if($data['dept_id'] != ''){
                $data['dept_id'] = explode(',', $data['dept_id']);
            }
            if($data['role_id'] != ''){
                $data['role_id'] = explode(',', $data['role_id']);
            }
            if($data['user_id'] != ''){
                $userId = explode(',', $data['user_id']);
                // $data['user_id'] = $this->transUserIds($userId); // 暂不启用用户id转换成人事id
                $data['user_id'] = $userId;
            }

            return $data;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 薪酬上报中间列筛选
     * @param $param
     * @return array
     */
    public function salaryViewUserOrgDirector($param)
    {
        $own            = [];
        $own['user_id'] = $param['loginUserInfo']['user_id'];
        $own['dept_id'] = $param['loginUserInfo']['dept_id'];
        $own['role_id'] = $param['loginUserInfo']['role_id'];
        $viewUser       = $this->getViewUser($own);
        // 这里返回的数据进入userservice-userSystemList函数进行查询，作为查询结果返回
        return ['user_id' => $viewUser];
    }

    /**
     * @param $userId
     * @param $own
     * @return bool
     * @throws SalaryError
     */
    public function checkReportUserPermission($userId, $own)
    {
        $viewUser = $this->getViewUser($own);
        // 开启[无账号发薪]时，判断权限这里，把查到的$viewUser转换为人事档案id
        $viewUser = $this->transUserIds($viewUser);
        // 点击树，传的人事档案id（因为已经是人事档案树了）
        if (!in_array($userId, $viewUser)) {
            throw new SalaryError('0x038016');
        }

        return true;
    }




}
