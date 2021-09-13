<?php


namespace App\EofficeApp\Salary\Services;


use App\EofficeApp\Salary\Helpers\SalaryHelpers;
use App\EofficeApp\Salary\Repositories\SalaryAdjustRepository;
use App\EofficeApp\Salary\Repositories\SalaryFieldPersonalDefaultRepository;
use App\EofficeApp\Salary\Repositories\SalaryFieldRepository;
use App\EofficeApp\User\Repositories\UserRepository;
use DB;

class SalaryAdjustService extends SalaryBaseService
{

    public $salaryAdjustRepository;

    private $salaryReportSetService;

    private $userRepository;

    private $salaryFieldRepository;

    public $salaryFieldPersonalDefaultRepository;

    public function __construct(
        SalaryAdjustRepository $salaryAdjustRepository,
        SalaryReportSetService $salaryReportSetService,
        UserRepository $userRepository,
        SalaryFieldRepository $salaryFieldRepository,
        SalaryFieldPersonalDefaultRepository $salaryFieldPersonalDefaultRepository
    )
    {
        parent::__construct();
        $this->salaryAdjustRepository = $salaryAdjustRepository;
        $this->salaryReportSetService = $salaryReportSetService;
        $this->userRepository = $userRepository;
        $this->salaryFieldRepository = $salaryFieldRepository;
        $this->salaryFieldPersonalDefaultRepository = $salaryFieldPersonalDefaultRepository;
        $this->personnelFilesRepository = "App\EofficeApp\PersonnelFiles\Repositories\PersonnelFilesRepository";
        $this->userMenuService = "App\EofficeApp\Menu\Services\UserMenuService";

    }

    /**
     * 获取最新一条薪资调整记录
     * 20201123-废弃，前端已经不再调用
     * @param $userId
     * @param $param
     * @param array $own
     * @return array
     */
    public function getLastSalaryAdjust($userId, $param, $own=[])
    {
        // $param = $this->parseParams($param);
        // $where = ['user_id' => [$userId]];
        // if (isset($param['field_id'])) {
        //     $where['field_id'] = [$param['field_id']];
        // }
        // if (!empty($own)) {
        //     $viewUser = $this->salaryReportSetService->getViewUser($own);
        //     if ($userId != $own['user_id']) {
        //         if (!in_array($userId, $viewUser)) {
        //             return ['code' => ['0x038016', 'salary']];
        //         }
        //     }
        // }

        // return $this->salaryAdjustRepository->getLastSalaryAdjust($where);
        return [];
    }

    /**
     * 新建薪酬调整
     * @param $data
     * @param $own
     * @return bool|mixed
     */
    public function addSalaryAdjust($data, $own)
    {
        // 越权提醒默认值：
        // 调薪失败；您没有部分被调整人的管理权限，请修改后重试。
        $adjustSalaryOverPowerError = ['code' => ['0x038024', 'salary']];
        $users = [];
        if ($data['user_id'] == "all") {
            if($this->payWithoutAccountConfig == '1') {
                $params = [];
                $params['fields'] = ['id','user_name'];
                $params['order_by'] = ['id' => 'asc'];
                $params['include_leave'] = "0";
                $personnelFiles = app($this->personnelFilesRepository)->getPersonnelFilesList($params);
                $users = collect($personnelFiles)->pluck('id')->toArray();
            } else {
                $user  = $this->userRepository->getAllUserIdString();
                $users = explode(',', $user);
            }
            // 选了全体，越权提醒：
            // 全员调薪失败；您没有部分被调整人的管理权限，请修改后重试。
            $adjustSalaryOverPowerError = ['code' => ['0x038025', 'salary']];
        } elseif (is_array($data['user_id'])) {
            $users = $data['user_id'];
        } elseif (is_string($data['user_id'])) {
            $users[] = $data['user_id'];
        }
        // 薪酬权限
        $viewUser = $this->salaryReportSetService->getViewUser($own);
        $diff     = array_diff($users, $viewUser);
        if(!empty($diff)){
            // 越权提醒
            return $adjustSalaryOverPowerError;
        }
        // 转换，存入用户id（无账号发薪，传入的是人事档案id）--20201124-暂不启用
        // $users = $this->transPersonnelFileIds($users);

        $fieldCode = $data['field_code'];
        $field     = $this->salaryFieldRepository->getSalaryItems(['search' => ['field_code' => [$fieldCode]], 'fields' => ['*']]);
        $fieldInfo = $field[0];
        unset($data['field_code']);

        if (!empty($users)) {
            $insertData = $data;

            foreach ($users as $key => $value) {
                $fieldId  = $fieldInfo['field_id'];
                $insertData['field_default_old'] = $this->salaryFieldPersonalDefaultRepository->getUserPersonalDefault($fieldId, $value);
//                $record = $this->getLastSalaryAdjust($value, $param);
//                if (!empty($record)) {
//                    $insertData["field_default_old"] = isset($record['field_default_new']) ? $record['field_default_new'] : 0;
//                }else{
//                    $insertData["field_default_old"] = isset($fieldInfo['field_default']) ? $fieldInfo['field_default'] : 0;
//                }

                $insertData['field_default_new'] = $insertData["field_default_old"] + $insertData["adjust_value"];
                if ($fieldInfo['field_type'] == 1 && $fieldInfo['field_default_set'] != 3) {
                    $insertData['field_default_new'] = SalaryHelpers::valueFormat(
                        $insertData['field_default_new'],
                        $fieldInfo['field_format'],
                        $fieldInfo['field_decimal'],
                        $fieldInfo['over_zero']
                    );
                }
                $insertData['user_id'] = $value;
                $insertData['creator'] = $own['user_id'];

                $this->salaryAdjustRepository->insertData($insertData);
                $personalDefault = [[
                                        'user_id' => $value,
                                        'field_id' => $fieldId,
                                        'default_value' => $insertData['field_default_new']
                                    ]];
                $this->salaryFieldPersonalDefaultRepository->updatePersonalDefaultList($fieldId, $personalDefault);
            }
        }

        return true;
    }

    /**
     * 薪酬调整列表
     * @param $param
     * @param $userId
     * @return array
     */
    public function getSalaryAdjust($param, $userId)
    {
        $page       = isset($param['page']) ? $param['page'] : 1;
        $limit      = isset($param['limit']) ? $param['limit'] : 10;
        $begin      = $limit * ($page - 1);
        $data       = [];
        $param      = $this->parseParams($param);
        // [无账号发薪]处理查询条件里面的user_id，传的是人事档案id，转成用户id
        if(isset($param['search']) && isset($param['search']['user_id']) && isset($param['search']['user_id'][0])) {
            $searchUserId = $param['search']['user_id'][0];
            // $searchUserId = $this->transPersonnelFileIds($searchUserId); // 暂不启用
            $param['search']['user_id'][0] = $searchUserId;
        }
        $adjustList = $this->salaryAdjustRepository->getSalaryAdjust($param);
        $total      = count($adjustList);

        if ($total > 0) {
            $user    = [];
            // 用来处理user_name、creator_name
            $userAll = [];
            // 模糊user集合
            $userArray = [];
            // 是否模糊查询
            $flag = false;
            // 所有user集合
            $userArrayAll = DB::table('user')->select('user_id', 'user_name')->get();
            if (isset($param['search'])) {
                $search = $param['search'];
                if (isset($search['user_name']) && isset($search['user_name'][0])) {
                    $flag      = true;
                    // 如果[无账号发薪]，到人事档案里面查
                    if($this->payWithoutAccountConfig == '1') {
                        $params = ['fields' => ['id', 'user_id', 'user_name'],'search' => ['user_name' => [$search['user_name'][0], 'like']]];
                        $personnelInfo = app($this->personnelFilesRepository)->getPersonnelFilesList($params);
                        $personnelId = collect($personnelInfo)->pluck('id')->toArray();
                        $user = $personnelId;
                    } else {
                        $userArray = DB::table('user')->select('user_id')
                            ->where('user_name', 'like', '%' . $search['user_name'][0] . '%')
                            ->get();
                        if (!empty($userArray)) {
                            foreach ($userArray as $key => $value) {
                                // 模糊查询user_id集合
                                $user[] = $value->user_id;
                            }
                        }
                    }
                }
            }

            if (!empty($userArrayAll)) {
                foreach ($userArrayAll as $key => $value) {
                    $userAll[$value->user_id] = $value->user_name;
                }
            } else {
                return ['list' => [], 'total' => 0];
            }

            if (isset($param['list_type'])) {
                // list_type 传入 mine 的时候，前端是我的薪资菜单-调薪记录
                // 首页-我的主页-薪酬信息-调薪记录，复用了此页面，会传user_id进来
                // 20210126-增加对传入的user_id的权限判断
                if(isset($param['user_id'])){
                    if($param['user_id'] != $userId) {
                        // 用controller传进来的user_id取权限
                        $own = $this->userRepository->getUserDeptIdAndRoleIdByUserId($userId);
                        $own['role_id'] = explode(',', $own['role_id']);
                        $own['user_id'] = $userId;
                        $viewUser = $this->salaryReportSetService->getViewUser($own);
                        // param传入的user_id，不在当前用户的管理范围内
                        if (!in_array($param['user_id'], $viewUser)) {
                            return ['code' => ['0x000006', 'common']];
                        } else {
                            $userId = $param['user_id'];
                        }
                    }
                    // 我的主页，调薪记录，要判断319的菜单权限
                    $menuPermission = app($this->userMenuService)->judgeMenuPermission(319);
                    if ($menuPermission == 'false') {
                        return ['code' => ['0x000006', 'common']];
                    }
                    // 我的主页，调薪记录，要判断319的菜单in_pc属性==1，才可以显示数据-DT201908150006
                    $menuInfo = app($this->userMenuService)->getMenuDetail('319');
                    if(!$menuInfo || !isset($menuInfo['in_pc']) || (isset($menuInfo['in_pc']) && $menuInfo['in_pc'] != '1')) {
                        return ['code' => ['0x038026', 'salary']];
                    }
                }
                // [无账号发薪]时，数据库储存的调薪信息，是人事id，传入的 $userId 不能直接用来逻辑判断，要转换为人事id
                if($this->payWithoutAccountConfig == '1') {
                    $personnelId = $this->transUserIds([$userId]);
                    $userId = $personnelId[0] ?? '';
                }
                // 我的调薪记录
                foreach ($adjustList as $key => $value) {
                    if ($value['user_id'] != $userId || $value['field_id'] == null) {
                        continue;
                    }
                    $userName = isset($userAll[$value['user_id']]) ? $userAll[$value['user_id']] : '';
                    if($userName == '' && isset($value['adjust_has_one_personnel'])) {
                        $userName = $value['adjust_has_one_personnel']['user_name'] ?? '';
                    }
                    $value['user_name'] = $userName;
                    $value['creator_name'] = isset($userAll[$value['creator']]) ? $userAll[$value['creator']] : '';
                    $data[] = $value;
                }

                $total = count($data);
            } else {
                // 获取管理权限
                $own            = $this->userRepository->getUserDeptIdAndRoleIdByUserId($userId);
                $own['role_id'] = explode(',', $own['role_id']);
                $own['user_id'] = $userId;
                $viewUser       = $this->salaryReportSetService->getViewUser($own);
                // 所有薪资调整记录
                if ($flag) {
                    $viewUser = array_intersect($viewUser, $user);
                }
                // $viewUser = $this->transPersonnelFileIds($viewUser); // 暂不启用
                foreach ($adjustList as $key => $value) {
                    if (!in_array($value['user_id'], $viewUser) || $value['field_id'] == null) {
                        continue;
                    }
                    $userName = isset($userAll[$value['user_id']]) ? $userAll[$value['user_id']] : '';
                    if($userName == '' && isset($value['hasPersonnel'])) {
                        // [无账号发薪]处理
                        $userName = $value['hasPersonnel']['user_name'] ?? '';
                        unset($value['hasPersonnel']);
                    }
                    $value['user_name'] = $userName;
                    $value['creator_name'] = isset($userAll[$value['creator']]) ? $userAll[$value['creator']] : '';
                    $data[] = $value;
                }
                $total = count($data);
            }
        }
        $data = array_slice($data, $begin, $limit);
        return ['list' => $data, 'total' => $total];
    }

    /**
     * 调整前薪资
     * @param $data
     * @return array|string
     */
    public function getSalaryAdjustOld($data)
    {
        if(!isset($data['user_id']) || !isset($data['field_id'])){
            return '';
        }
        if(empty($data['user_id']) || empty($data['field_id'])){
            return '';
        }
        // $fieldInfo = $this->salaryFieldRepository->getSalaryItems(['search' => ['field_id' => [$data['field_id']]]]);
        // if(!isset($fieldInfo[0]['field_id']) || !isset($fieldInfo[0]['field_default'])){
        //     return '';
        // }
        // $param  = ['field_id' => $fieldInfo[0]['field_id']];
        // // 获取最新的调整记录
        // $record = $this->getLastSalaryAdjust($data['user_id'], $param);
        $record = $this->salaryFieldPersonalDefaultRepository->getUserPersonalDefault($data['field_id'], $data['user_id']);
        return ['salary_adjust_old' => $record];
    }

    /**
     * 获取一条薪资调整记录
     * @param $adjustId
     * @param $own
     * @return array
     */
    public function getSalaryAdjustInfo($adjustId, $own)
    {
        $param = ['search' => ['adjust_id' => [$adjustId]]];

        $data = $this->salaryAdjustRepository->getSalaryAdjust($param);
        if (!empty($data) && isset($data[0])) {
            $adjustUserId = $data[0]['user_id'] ?? '';
            $ownUserId = $own['user_id'];
            $viewUser = $this->salaryReportSetService->getViewUser($own);
            // $viewUser = $this->transPersonnelFileIds($viewUser); // 暂不启用
            // [无账号发薪]权限判断
            if($this->payWithoutAccountConfig == '1') {
                $adjustUserId = isset($data[0]['hasPersonnel']) && isset($data[0]['hasPersonnel']['user_id']) ? $data[0]['hasPersonnel']['user_id'] : $ownUserId;
            }
            if ($adjustUserId != $ownUserId) {
                if (!in_array($data[0]['user_id'] ?? '', $viewUser)) {
                    return ['code' => [ '0x038016', 'salary']];
                }
            }

            $data[0]['creator_name'] = $this->userRepository->getUserName($data[0]['creator']);
            $userName = $this->userRepository->getUserName($adjustUserId);
            if($userName == '' && isset($data[0]['hasPersonnel'])) {
                // [无账号发薪]处理
                $userName = $data[0]['hasPersonnel']['user_name'] ?? '';
                unset($data[0]['hasPersonnel']);
            }
            $data[0]['adjusted_name'] = $userName;

            return $data[0];
        }

        return [];
    }



}
