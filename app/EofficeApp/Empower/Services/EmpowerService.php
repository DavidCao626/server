<?php

namespace App\EofficeApp\Empower\Services;

use Queue;
use App\Jobs\ImportExportJob;
use App\EofficeApp\Base\BaseService;
use Cache;
use Lang;

/**
 * 授权Service类:提供授权模块相关服务
 *
 * @author qishaobo
 *
 * @since  2017-03-16 创建
 */
class EmpowerService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->register = 'App\Utils\Register';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->menuRepository = 'App\EofficeApp\Menu\Repositories\MenuRepository';
        $this->versionRepository = 'App\EofficeApp\Empower\Repositories\VersionRepository';
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->moduleVerifyAuthRepository = 'App\EofficeApp\Empower\Repositories\ModuleVerifyAuthRepository';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->importExportService = 'App\EofficeApp\ImportExport\Services\ImportExportService';
    }

    /**
     * 查询电脑端授权
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function getPcEmpower($type)
    {
        $checkIsPcRegistered = isPcRegistered();
        if (!$checkIsPcRegistered) {
            return $this->getTrialVersion($type);
        }
        if (is_array($checkIsPcRegistered) && isset($checkIsPcRegistered['code'])) {
            return $checkIsPcRegistered;
        }
        return app($this->register)->parseRegFileStr('pc');
    }

    /**
     * 查询手机端授权
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function getMobileEmpower($type='')
    {
        $checkIsMobileRegistered = isMobileRegistered();
        if (!$checkIsMobileRegistered) {
            return $this->getTrialVersion($type, 'mobile');
        }
        if (is_array($checkIsMobileRegistered) && isset($checkIsMobileRegistered['code'])) {
            return $checkIsMobileRegistered;
        }
        return app($this->register)->parseRegFileStr('mobile');
    }

    /**
     * 检查手机版是否有效授权(不需要提示信息的)
     *
     * @return boolean
     *
     * @author 缪晨晨
     *
     * @since  2018-09-19
     */
    public function checkMobileEmpowerAvailability()
    {
        $data = $this->getMobileEmpower(0);

        if (empty($data)) {
            return false;
        }

        if (isset($data['code'])) {
            return false;
        }

        $check = ($data['expireDdate'] === 1) || (time() < strtotime($data['expireDdate'])) ? true : false;

        if (!$check) {
            return false;
        }

        return true;
    }

    /**
     * 检查手机授权和手机访问是否允许
     *
     * @return boolean
     *
     * @author 缪晨晨
     *
     * @since  2017-06-06
     */
    public function checkMobileEmpowerAndWapAllow($userId)
    {
        $data = $this->getMobileEmpower(0);

        if (empty($data)) {
            return ['code' => ['no_info', 'register']];
        }

        if (isset($data['code'])) {
            return $data;
        }

        $check = ($data['expireDdate'] === 1) || (time() < strtotime($data['expireDdate'])) ? true : false;

        if (!$check) {
            return ['code' => ['mobile_is_expired', 'register']];
        }

        $check = $this->checkUserNumber($data['mobileUserNumber'], 'mobile');

        if (!$check) {
            return ['code' => ['mobile_number_exceed', 'register']];
        }

        if(!app($this->userSystemInfoRepository)->checkWapAllow($userId)) {
            return ['code' => ['not_mobile_empower_user', 'register']];
        }
        return true;
    }
    public function getEmpowerInfo($userId)
    {
        $allowMobile = $this->checkMobileEmpowerAndWapAllow($userId);

        return [
            'mobile_empower_check' => isset($allowMobile['code']) ? false : true,
            'mobile_empower_info' => $this->getMobileEmpower(0),
            'pc_empower_info' => $this->getPcEmpower(0),
        ];
    }
    /**
     * 导出授权
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function exportEmpower($param)
    {
        $data = [
            'module' => 'register',
        ];
        $fileInfo = app($this->importExportService)->exportJobHandleExport($data);
        if ($fileInfo) {
            return response()->download($fileInfo, basename($fileInfo));
        }
        return '';
    }

    /**
     * 导出授权
     *
     * @param  array $param  查询条件
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    function getEmpowerData($param)
    {
        $header = [
            'version'          => trans('register.system_version'),
            'empowerName'      => trans('register.authorization_name'),
            'pcUserNumber'     => trans('register.pc_user_number'),
            'mobileUserNumber' => trans('register.number_of_mobile_users'),
            'machineCode'      => trans('register.machine_code'),
        ];

        $pcEmpowerInfo = $this->getPcEmpower(0);
        $mobileEmpowerInfo = $this->getMobileEmpower(0);
        $empowerName = '';
        if (!empty($pcEmpowerInfo['empowerName'])) {
            $empowerName = $pcEmpowerInfo['empowerName'];
        } else if (!empty($mobileEmpowerInfo['empowerName'])) {
            $empowerName = $mobileEmpowerInfo['empowerName'];
        }

        $data = [
            [
                'version'          => $this->getSystemVersion(),
                'empowerName'      => $empowerName,
                'pcUserNumber'     => $pcEmpowerInfo['pcUserNumber'] ?? '',
                'mobileUserNumber' => $mobileEmpowerInfo['mobileUserNumber'] ?? '',
                'machineCode'      => $this->getMachineCode()
            ]
        ];

        return compact('header', 'data');
    }

    /**
     * 导入授权
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function importEmpower($param)
    {
        if (empty($param['file'])) {
            return ['code' => ['no_file', 'register']];
        }

        $fileInfo = app($this->attachmentService)->getOneAttachmentById($param['file']);
        if (empty($fileInfo) || !isset($fileInfo['temp_src_file']) || !is_file($fileInfo['temp_src_file'])) {
            return ['code' => ['no_file', 'register']];
        }
        $param['file'] = $fileInfo['temp_src_file'];
        $content = file_get_contents($param['file']);

        $data = app($this->register)->parseEmpowerFile($content, $param['type']);

        if (isset($data['code'])) {
            return $data;
        }

        $mac = app($this->register)->getMachineCode();
        if (isset($mac['code'])) {
            return $mac;
        }

        if(!in_array($data['machineCode'], $mac)){
           return ['code' => ['mac_error', 'register']];
        }

        $saveResult = app($this->register)->saveEmpowerInfo($data, $content, $param['type']);
        // chmod可能的报错处理
        if(is_array($saveResult) && isset($saveResult['code'])) {
            return $saveResult;
        }
        Cache::forget('empower_user_number_'.$param['type']);
        if ($param['type'] == 'pc' && isset($param['pcUserNumber'])) {
            Cache::forever('empower_user_number_'.$param['type'], $param['pcUserNumber']);
        } elseif ($param['type'] == 'mobile' && isset($param['mobileUserNumber'])) {
            Cache::forever('empower_user_number_'.$param['type'], $param['mobileUserNumber']);
        }

        // 清除用户角色缓存、未授权菜单集合的缓存
        Cache::forget('no_permission_menus');
        $params = array(
                'search' => [
                    'user_accounts' => ['', '!=']
                ]
            );
        $userList = app($this->userRepository)->getAllUsers($params);
        if (!empty($userList)) {
            foreach ($userList as $key => $value) {
                if ($value->user_id) {
                    Cache::forget('user_role_' . $value->user_id);
                }
            }
        }
        // 清除模块授权信息缓存
        ecache('Empower:EmpowerModuleInfo')->clear();

        $data['empowerName'] = iconv('GBK', 'UTF-8', $data['empowerName']);
	if (!empty($data['expireDdate'])) {
            $data['dateInterval'] = app($this->register)->getDateInterval($data['expireDdate']);
        }
        // 将系统授权信息写入文件，在bin目录下
        $binDir = get_bin_dir();
        if (is_dir($binDir)) {
            $empowerInfoFile = $binDir . 'empower.info';
            if (is_file($empowerInfoFile)) {
                // chmod($empowerInfoFile, 0777);
                // 替换为会报错的file验证
                $dirPermission = verify_file_permission($empowerInfoFile);
                if(is_array($dirPermission) && isset($dirPermission['code'])) {
                    return $dirPermission;
                }
            }
            // chmod($binDir, 0777);
            // 替换为会报错的dir验证
            $dirPermission = verify_dir_permission($binDir);
            if(is_array($dirPermission) && isset($dirPermission['code'])) {
                return $dirPermission;
            }
            if (file_exists($empowerInfoFile)) {
                $empowerInfo = file_get_contents($empowerInfoFile);
                $empowerInfo = json_decode($empowerInfo, true);
                $empowerInfo[$param['type'] . '_empower_info'] = $data;
                file_put_contents($empowerInfoFile, json_encode($empowerInfo));
            } else {
                file_put_contents($empowerInfoFile, json_encode([$param['type'] . '_empower_info' => $data]));
            }
        }

        return $data;
    }

    /**
     * 检查授权人数
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function checkUserNumber($userNumber, $type="pc")
    {
        // if(Cache::has('empower_user_number_' . $type)){
        //     $sysUserNumber = Cache::get('empower_user_number_' . $type);
        // } else {
            if($type == 'pc') {
                $where = [
                    'user_system_info' => [
                        'user_status' => [[0,2], 'not_in']
                    ]
                ];
            }elseif($type == 'mobile') {
                $where = [
                    'user_system_info' => [
                        'user_status' => [[0,2], 'not_in'],
                        'wap_allow'   => [1]
                    ]
                ];
            }
            $sysUserNumber = app($this->userRepository)->getUserNumber($where);

            // Cache::forever('empower_user_number_' . $type, $sysUserNumber);
        // }

        return $sysUserNumber <= $userNumber ? true : false;
    }

    /**
     * 检查手机端授权人数
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function checkMobileUserNumber($userNumber)
    {
        $where = [
            'user_system_info' => [
                'user_status' => [[0,2], 'not_in'],
                'wap_allow'   => [1]
            ]
        ];

        $sysUserNumber = app($this->userRepository)->getUserNumber($where);

        return $sysUserNumber <= $userNumber ? true : false;
    }

    /**
     * 查询电脑端授权
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function checkPcEmpower()
    {
    	$data = $this->getPcEmpower(0);

        if (empty($data)) {
            return ['code' => ['no_info', 'register']];
        }

        if (isset($data['code'])) {
            return $data;
        }

        $isRegister = isset($data['empowerName']) && $data['empowerName'] == '试用' ? false : true;

        $check = ($data['expireDdate'] === 1) || (time() < strtotime($data['expireDdate'])) ? true : false;

        if (!$check) {
            $info = $isRegister ? 'is_expired' : 'trial_is_expired';
            return ['code' => [$info, 'register']];
        }

        $check = $this->checkUserNumber($data['pcUserNumber'], 'pc');

        if (!$check) {
            $info = $isRegister ? 'number_exceed' : 'trial_number_exceed';
            return ['code' => [$info, 'register']];
        }

        return true;
    }

    /**
     * 查询手机端授权
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function checkMobileEmpower()
    {
        $data = $this->getMobileEmpower(0);

        if (empty($data)) {
            return ['code' => ['no_info', 'register']];
        }

        if (isset($data['code'])) {
            return $data;
        }

        $isRegister = isMobileRegistered();

        $check = ($data['expireDdate'] === 1) || (time() < strtotime($data['expireDdate'])) ? true : false;

        if (!$check) {
            return ['code' => ['mobile_is_expired', 'register']];
        }

        $check = $this->checkUserNumber($data['mobileUserNumber'], 'mobile');

        if (!$check) {
            return ['code' => ['number_exceed', 'register']];
        }

        return true;
    }

    /**
     * 查询新建用户是否超过授权用户
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function checkAddUser()
    {
        $data = $this->getEmpower(0);

        if (empty($data)) {
            return ['code' => ['no_info', 'register']];
        }

        if (isset($data['code'])) {
            return $data;
        }

        $check = $this->checkUserNumber($data['userNumber']);

        return $check;
    }

    /**
     * 获取系统版本
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function getSystemVersion()
    {
        $data = app($this->versionRepository)->getVersion();
        if (!empty($data)) {
            return $data[0]['ver'];
        }

        return '';
    }

    /**
     * 获取机器码
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function getMachineCode()
    {
        $mac = app($this->register)->getMachineCode(1);
        return $mac;
    }

    /**
     * 获取试用信息
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function getTrialVersion($register = 0, $type = 'pc')
    {
        $regdate = app($this->register)->registerDate();
        $config = config('eoffice.trialVersion');

        $mac = app($this->register)->getMachineCode(1);
        if (isset($mac['code'])) {
            $mac = '';
        }

        $data = array();

        if (empty($regdate)) {
            return $data;
        }
        if ($type == 'pc') {
            $data = [
                'version'          => $this->getSystemVersion(),
                'empowerName'      => $register == 1 ? '' : '试用',
                'machineCode'      => $mac,
                'pcUserNumber'     => $register == 1 ? '' : $config['pcUserNumber'],
                'expireDdate'      => date('Y-m-d', strtotime($regdate)+$config['probation']*86400),
                'pcTrialDays'      => isset($config['probation']) ? $config['probation'] : '30'
            ];
        } elseif ($type == 'mobile') {
            $data = [
                'version'          => $this->getSystemVersion(),
                'empowerName'      => $register == 1 ? '' : '试用',
                'machineCode'      => $mac,
                'mobileUserNumber' => $register == 1 ? '' : $config['pcUserNumber'],
                'expireDdate'      => date('Y-m-d', strtotime($regdate)+$config['probation']*86400),
                'mobileTrialDays'  => isset($config['probation']) ? $config['probation'] : '30'
            ];
        }
        if (!empty($data['expireDdate'])) {
            $data['dateInterval'] = app($this->register)->getDateInterval($data['expireDdate']);
        }
        return $data;
    }

    /**
     * 获取有权限的系统模块
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-17
     */
    public function getPermissionModules()
    {
        return ecache('Empower:EmpowerModuleInfo')->get();
    }

    /**
     * 获取模块授权信息
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-17
     */
    public function getModuleEmpower()
    {
        $checkEmpower = $this->checkPcEmpower();
        if (isset($checkEmpower['code'])) {
            $modules = [];
            $firstLogin = get_system_param('first_login', 0);
            if ($firstLogin == '0') {
                $search = [
                    'menu_id' => ['1000', '<'],
                    'multiSearch'  => [
                        'menu_parent' => ['0'],
                        'multiSearch'  => [
                            'menu_parent' => ['280'],
                            'menu_id' => ['281', '!='],
                        ],
                        '__relation__' => 'or',
                    ],
                    '__relation__' => 'and',
                ];
                $modules = app($this->menuRepository)->getMenuByWhere($search, ['menu_id', 'menu_name']);
                if (!empty($modules) && is_array($modules)) {
                    foreach ($modules as $key => $value) {
                        $modules[$key]['menu_name']   = trans_dynamic("menu.menu_name.menu_" . $value['menu_id']);
                        $modules[$key]['empower'] = 'trial';
                    }
                }
            }
            return $modules;
        }

        $search = [
            'menu_id' => ['1000', '<'],
            'multiSearch'  => [
                'menu_parent' => ['0'],
                'multiSearch'  => [
                    'menu_parent' => ['280'],
                    'menu_id' => ['281', '!='],
                ],
                '__relation__' => 'or',
            ],
            '__relation__' => 'and',
        ];

        $modules = app($this->menuRepository)->getMenuByWhere($search, ['menu_id', 'menu_name']);

        $isRegister      = isPcRegistered() ? true : false;
        // 模块授权信息
        $empowerModule   = app($this->register)->getModuleEmpower();
        // 是否永久授权
        $isPermanentFlag = app($this->register)->isPermanentUser();

        // 系统授权无限期的 模块授权未授权的
        if ($isPermanentFlag) {
            if (isset($empowerModule['code'])) {
                foreach ($modules as $k => $module) {
                    $modules[$k]['menu_name']   = trans_dynamic("menu.menu_name.menu_" . $module['menu_id']);
                    $modules[$k]['empower'] = 'no_empower';
                }
                return $modules;
            }
        } else {
            $pcRegisterInfo = $this->getPcEmpower(0);
            $pcTrialDate    = '';
            if (isset($pcRegisterInfo['expireDdate'])) {
                $pcTrialDate = $pcRegisterInfo['expireDdate'];
            }
        }

        // 系统授权了 模块授权未授权的 返回模块授权试用状态和试用时间
        if (!$isPermanentFlag && $isRegister && isset($empowerModule['code'])) {
            foreach ($modules as $k => $module) {
                $modules[$k]['menu_name']   = trans_dynamic("menu.menu_name.menu_" . $module['menu_id']);
                $modules[$k]['empower']    = 'trial';
                $modules[$k]['trial_date'] = $pcTrialDate;
                if (!empty($pcTrialDate)) {
                    $modules[$k]['dateInterval'] = app($this->register)->getDateInterval($pcTrialDate);
                }
            }
            return $modules;
        }

        // 以下是系统授权了且模块授权了的情况
        $moduleInfor = isset($empowerModule['moduleInfor']) ? $empowerModule['moduleInfor'] : $empowerModule;

        $date = date('Y-m-d');

        foreach ($modules as $k => $module) {
            $modules[$k]['menu_name']   = trans_dynamic("menu.menu_name.menu_" . $module['menu_id']);
            if (!$isRegister || !$isPermanentFlag) {
                $modules[$k]['empower'] = 'trial';
                $modules[$k]['trial_date'] = $pcTrialDate;
                if (!empty($modules[$k]['trial_date'])) {
                    $modules[$k]['dateInterval'] = app($this->register)->getDateInterval($modules[$k]['trial_date']);
                }
                continue;
            }

            if (empty($moduleInfor) || !isset($moduleInfor[$module['menu_id']])) {
                $modules[$k]['empower'] = 'no_empower';
                continue;
            }

            if ($moduleInfor[$module['menu_id']] != '') {
                $enpowerDate = $moduleInfor[$module['menu_id']];

                if (strpos($enpowerDate, '-') === false) {
                    $time = strtotime("+{$enpowerDate} months");
                    $enpowerDate = date('Y-m-d', $time);
                }

                if ($date > $enpowerDate) {
                    $modules[$k]['empower'] = 'out_trial';
                    $modules[$k]['trial_date'] = $enpowerDate;
                } else {
                    $modules[$k]['empower'] = 'in_trial';
                    $modules[$k]['trial_date'] = $enpowerDate;
                }
            } else {
                $modules[$k]['empower'] = 'is_empower';
            }

            if (isset($modules[$k]['trial_date']) && !empty($modules[$k]['trial_date'])) {
                $modules[$k]['dateInterval'] = app($this->register)->getDateInterval($modules[$k]['trial_date']);
            }
        }

        return $modules;
    }

    /**
     * 添加模块授权信息
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-17
     */
    public function addModuleEmpower($param)
    {
        if (empty($param['attachment_id'])) {
            return ['code' => ['no_file', 'register']];
        }

        $fileInfo = app($this->attachmentService)->getOneAttachmentById($param['attachment_id']);
        if (empty($fileInfo) || !isset($fileInfo['temp_src_file']) || !is_file($fileInfo['temp_src_file'])) {
            return ['code' => ['no_file', 'register']];
        }

        $mac = app($this->register)->getMachineCode();
        if (isset($mac['code'])) {
            return $mac;
        }

        $verifyFileNameArray = array();
        $correctMac = '';
        if (!empty($mac)) {
            foreach ($mac as $key => $value) {
                $tempFileName = md5($value.'eoffice9731').'.evf';
                if ($param['attachment_name'] == $tempFileName) {
                    $correctMac = $value;
                }
                $verifyFileNameArray[] = $tempFileName;
            }
        }

        $mac = $correctMac;

        if (!in_array($param['attachment_name'], $verifyFileNameArray) || empty($mac)) {
            return ['code' => ['module_name_error', 'register']];
        }

        $sha1file = sha1_file($fileInfo['temp_src_file']);
        $where = [
            'machine_code' => $mac,
            'shafile' => $sha1file
        ];

        $data = app($this->moduleVerifyAuthRepository)->getModulesVerify($where);

        if (!empty($data)) {
            return ['code' => ['have_used', 'register']];
        }

        $moduleVerifyInfor = json_decode(authCode(file_get_contents($fileInfo['temp_src_file'])), true);

        $verifyMode = 'append';
        $moduleInfor = $moduleVerifyInfor;

        if ($moduleVerifyInfor['verifyMode']) {
            $verifyMode = $moduleVerifyInfor['verifyMode'];
            $moduleInfor = $moduleVerifyInfor['moduleInfor'];
        }

        //转化试用日期  试用1个月 试用3个月  转化日期  从授权日开始
        if (is_array($moduleInfor)) {
            foreach ($moduleInfor as $key => $value) {
                $moduleInfor[$key] = empty($value) ? '' : date('Y-m-d', strtotime('+' . $value . ' month'));
            }
        }

        $isSave = app($this->register)->saveEmpowerModule($moduleInfor, $param['attachment_name'], $verifyMode);
        // chmod可能的报错处理
        if(is_array($isSave) && isset($isSave['code'])) {
            return $isSave;
        }

        if ($isSave) {
            $data = [
                'machine_code' => $mac,
                'shafile' => $sha1file,
                'ip' => getClientIp(),
            ];

            app($this->moduleVerifyAuthRepository)->insertData($data);

            // 清除用户角色缓存、未授权菜单集合的缓存
            Cache::forget('no_permission_menus');
            $params = array(
                    'search' => [
                        'user_accounts' => ['', '!=']
                    ]
                );
            $userList = app($this->userRepository)->getAllUsers($params);
            if (!empty($userList)) {
                foreach ($userList as $key => $value) {
                    if ($value->user_id) {
                        Cache::forget('user_role_' . $value->user_id);
                    }
                }
            }

            // 清除模块授权信息缓存
            ecache('Empower:EmpowerModuleInfo')->clear();

            // 将模块授权信息写入文件，在bin目录下
            $moduleEmpowerInfo = $this->getModuleEmpower();
            $binDir = get_bin_dir();
            if (is_dir($binDir)) {
                $empowerInfoFile = $binDir . 'empower.info';
                // chmod($binDir, 0777);
                // 替换为会报错的dir验证
                $dirPermission = verify_dir_permission($binDir);
                if(is_array($dirPermission) && isset($dirPermission['code'])) {
                    return $dirPermission;
                }
                if (is_file($empowerInfoFile)) {
                    // chmod($empowerInfoFile, 0777);
                    // 替换为会报错的file验证
                    $dirPermission = verify_file_permission($empowerInfoFile);
                    if(is_array($dirPermission) && isset($dirPermission['code'])) {
                        return $dirPermission;
                    }
                }
                if (file_exists($empowerInfoFile)) {
                    $empowerInfo = file_get_contents($empowerInfoFile);
                    $empowerInfo = json_decode($empowerInfo, true);
                    $empowerInfo['module_empower_info'] = $moduleEmpowerInfo;
                    file_put_contents($empowerInfoFile, json_encode($empowerInfo));
                } else {
                    file_put_contents($empowerInfoFile, json_encode(['module_empower_info' => $moduleEmpowerInfo]));
                }
            }

            return true;
        }

        return ['code' => ['module_empower_error', 'register']];
    }

    /**
     * 检查某个模块是否授权过期
     *
     * @param int $moduleId
     *
     * @return 0:未授权或授权过期；1:已授权或试用中
     */
    public function checkModuleWhetherExpired($moduleId)
    {
        if (empty($moduleId) || !is_int($moduleId)) {
            return 0;
        } else {
            //获取有权限的模块菜单
            $empowerMenuId = ecache('Empower:EmpowerModuleInfo')->get();
            if (empty($empowerMenuId) || !is_array($empowerMenuId) || !in_array($moduleId, $empowerMenuId)) {
                return 0;
            } else {
                return 1;
            }
        }
    }

    // 生成或更新授权信息文件
    public function addOrUpdateEmpowerInfoFile()
    {
        $pcEmpowerInfo = $this->getPcEmpower(0);
        $mobileEmpowerInfo = $this->getMobileEmpower(0);
        $moduleEmpowerInfo = $this->getModuleEmpower();
        $binDir = get_bin_dir();
        if (!is_dir($binDir)) {
            dir_make($binDir, 0777);
        }
        $fileName = $binDir . 'empower.info';
        // 20200528-丁鹏-部署时给bin赋权777，不再手动赋权
        // chmod($binDir, 0777);
        $empowerInfo = [
            'pc_empower_info'     => $pcEmpowerInfo,
            'mobile_empower_info' => $mobileEmpowerInfo,
            'module_empower_info' => $moduleEmpowerInfo
        ];
        if (is_file($fileName)) {
            // 20200528-丁鹏-部署时给bin/empower.info赋权777，不再手动赋权
            // chmod($fileName, 0777);
        }
        file_put_contents($fileName, json_encode($empowerInfo));
    }
    //
    public function getEmpowerPlatform() {
        return envOverload('CASE_PLATFORM') ? 1 : 0;
    }
}
