<?php
namespace App\EofficeApp\System\Domain\Services;

use App\EofficeApp\Base\BaseService;
use Illuminate\Database\Schema\Blueprint;
use App\Jobs\SyncDomainJob;
use App\Utils\Utils;
use Eoffice;
use Schema;
use Queue;
use DB;

/**
 * @域集成服务类
 *
 * @author niuxiaoke
 */
class DomainService extends BaseService
{
    private $codeInfo;
    private $domainRepository;
    private $domainSyncLogRepository;
    private $userRepository;
    private $userSystemInfoRepository;
    private $userService;
    private $departmentService;

    public function __construct() {
        parent::__construct();

        $this->domainRepository         = 'App\EofficeApp\System\Domain\Repositories\DomainRepository';
        $this->domainSyncLogRepository  = 'App\EofficeApp\System\Domain\Repositories\DomainSyncLogRepository';
        $this->userRepository           = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->userService              = 'App\EofficeApp\User\Services\UserService';
        $this->departmentService        = 'App\EofficeApp\System\Department\Services\DepartmentService';
        $this->codeInfo = [
            '0x0000001' => trans("domain.no_configuration_file").'/ad_sync/config.ini',
            '0x0000002' => trans("domain.no_default_department"),
            '0x0000003' => trans("domain.failed_to_get_default_department_ID"),
            '0x0000004' => trans("domain.default_department_not_exist"),
            '0x0000005' => trans("domain.no_default_role_set"),
            '0x0000006' => trans("domain.failed_to_get_default_role_ID"),
            '0x0000007' => trans("domain.default_role_set_not_exist"),
            '0x0000008' => trans("domain.no_AD_server_address_is_set"),
            '0x0000009' => trans("domain.unable_connect_AD"),
            '0x0000010' => trans("domain.unable_to_login"),
        ];
    }
    /**
     * @测试连接
     * @return bool
     */
    public function testDomainConnect($data)
    {
        if (!isset($data['domain_host']) || $data['domain_host'] == '') {
            return ['code' => ['0x072003', 'domain']];
        }
        // 判断服务器地址格式
        $hostFilter = strpos($data['domain_host'], "ldap://");
        if ($hostFilter !== 0) {
            $data['domain_host'] = "ldap://" . $data['domain_host'];
        }
        // 服务器地址
        $ldapHost = $data['domain_host'];
        if(!function_exists('ldap_connect')){
            return ['code' => ['0x072006', 'domain']];
        }
        // 测试连接服务器
        try {
            $ldap = ldap_connect($ldapHost);

            if ($ldap == false) {
                return ['code' => ['0x072001', 'domain']];
            }
        } catch (\Exception $e) {
            return ['code' => ['0x072001', 'domain']];
        }
        // 域名
        $domainName = $data['domain_name'];
        // 登录AD域用户名
        $ldapUserName = trim($data['domain_user_name']);
        // 登录密码
        $ldapPassword = $data['domain_password'];
        try {
            $connect = ldap_bind($ldap, $ldapUserName . "@" . $domainName, $ldapPassword);
        } catch (\Exception $e) {
            return ['code' => ['0x072002', 'domain']];
        }
    }

    /**
     * @添加域配置
     * @return bool
     */
    public function saveDomain($data)
    {
        $hostFilter = strpos($data['domain_host'], "ldap://");
        if ($hostFilter !== 0) {
            $data['domain_host'] = "ldap://" . $data['domain_host'];
        }
        $handleData = [
            'domain_name'       => $data['domain_name'],
            'domain_host'       => $data['domain_host'],
            'domain_user_name'  => $data['domain_user_name'],
            'domain_password'   => $data['domain_password'],
            'domain_dn'         => $data['domain_dn'],
            // 'dept_id'          => $data['dept_id'],
            'role_id'           => $data['role_id'],
            'domain_scheduling' => $data['domain_scheduling'] ?? '',
            'post_priv'         => $data['post_priv'] ?? 3,
        ];
        if(isset($data['post_dept']) && !empty($data['post_dept'])){
            $handleData['post_dept'] = implode(',', $data['post_dept']);
        }
        if(isset($data['domain_condition']) && !empty($data['domain_condition'])){
            if(strstr($data['domain_condition'], '=')){
                $handleData['domain_condition'] = $data['domain_condition'];
            }else{
                return ['code' => ['0x072005', 'domain']];
            }
        }else{
            $handleData['domain_condition'] = '';
        }
        $record = DB::table('domain')->first();

        if (!empty($record)) {
            return DB::table('domain')->update($handleData);
        } else {
            return app($this->domainRepository)->insertData($handleData);
        }
    }

    /**
     * @获取域配置列表
     * @return array
     */
    public function getDomainInfo()
    {
        $data =  app($this->domainRepository)->getDomainInfo();
        if(isset($data['post_dept']) && !empty($data['post_dept'])){
            $data['post_dept'] = explode(',', $data['post_dept']);
        }
        return $data;
    }

    public function syncDomain($param, $userId)
    {
        $param['user_id'] = $userId;

        // return $this->sync($param);
        $test = $this->testDomainConnect($param);
        if(isset($test['code'])){
            return $test;
        }

        Queue::push(new SyncDomainJob($param));

        return true;
    }

    /**
     * @同步数据
     * @return bool
     */
    public function sync($data)
    {
        $startTime = time();
        // 域名
        $domainName = $data['domain_name'];
        // 服务器地址
        $ldapHost = $data['domain_host'];
        // 判断服务器地址格式
        $hostFilter = strpos($data['domain_host'], "ldap://");
        if ($hostFilter !== 0) {
            $data['domain_host'] = "ldap://" . $data['domain_host'];
        }
        // 登录AD域用户名
        $ldapUserName = trim($data['domain_user_name']);
        // 登录密码
        $ldapPassword = $data['domain_password'];
        // 同步域
        $dn = $data['domain_dn'];

        // 测试连接服务器
        try{
            $ldap = ldap_connect($ldapHost);
        } catch(\Exception $e) {
            return ['code' => ['0x072001', 'domain']];
        }
        // 设置版本，可取中文
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        // 绑定用户登录AD域服务器
        try {
            ldap_bind($ldap, $ldapUserName . "@" . $domainName, $ldapPassword);
        } catch (\Exception $e) {
            $this->addSyncLog('0x0000010', $startTime);
            return ['code' => ['0x072002', 'domain']];
        }
        // 第二个参数表示目录，第三个参数表示筛选
        try {
            $result = ldap_search($ldap, $dn, '(&(objectClass=*)(objectCategory=*))');
            if ($result == false) {
                return ['code' => ['0x072004', 'domain']];
            }
        } catch (\Exception $e) {
            return ['code' => ['0x072004', 'domain']];
        }

        // 获取数据
        $entries = ldap_get_entries($ldap, $result);
        // 获取数据后解除绑定
        ldap_unbind($ldap);

        // 有用字段
        $attributes = array(
            "samaccountname",
            "distinguishedname",
            "objectguid",
            // 'managedby', //部门管理者
            //'directreports', //直接下属
            'manager', //上级经理
        );
        // 过滤条件
        if(isset($data['domain_condition']) && !empty($data['domain_condition'])){
            if(strstr($data['domain_condition'], '=')){
                $exTemp = explode('=', $data['domain_condition']);
                array_push($attributes, strtolower($exTemp[0]));
            }else{
                return ['code' => ['0x072005', 'domain']];
            }
        }

        $userList = [];

        if (isset($entries['count'])) {
            unset($entries['count']);
        }

        foreach ($entries as $value) {
            foreach ($attributes as $v) {
                if (isset($value[$v][0])) {
                    if ($v == 'objectguid') {
                        $temp[$v] = $this->getGUID($this->getBytes($value[$v][0]));
                    } elseif ($v == 'directreports') {
                        $temp[$v] = $value[$v];
                    } else {
                        $temp[$v] = $value[$v][0];
                    }
                } else {
                    $temp[$v] = '';
                }
            }

            if ($temp) {
                if (strpos($value['distinguishedname'][0], 'OU') === 0) {
                    array_unshift($userList, $temp);
                } else {
                    $userList[] = $temp;
                }
            }
        }

        $syncResutlArray = [];
        $userAccounts    = [];
        // $deptId          = $data['dept_id'];
        $roleId = $data['role_id'];
        // $password        = $data['password'];
        $codeContrast = [
            '0x1000000' => trans("domain.new_user_succeeded"),
            '0x1000001' => trans("domain.pirated"),
            '0x1000002' => trans("domain.maximum_authorized_users"),
            '0x1000003' => trans("domain.username_cannot_contain_single_quotes"),
            '0x1000004' => trans("domain.account_exists"),
            '0x1000005' => trans("domain.failed_to_create_new_user_menu"),
            '0x1000006' => trans("domain.set_to_leave"),
            '0x1000007' => trans("domain.department_exists_update"),
            '0x1000008' => trans("domain.new_department_succeeded"),
            '0x1000009' => trans("domain.not_exists_and_delete"),
            '0x1000010' => trans("domain.add_user_failed"),
        ];

        // 检查是否有中间表
        $this->createMiddleTable();

        /*处理userList,获取用户、部门信息 start ****************/

        // 生成对照数据
        $deptContrast = [];
        $allDepts     = [];
        $userGuid     = [];
        $deptGuid     = [];
        $userOrg      = [];

        $i = 1;
        foreach ($userList as $key => $value) {
            $dn     = $value['distinguishedname'];
            $guid   = $value['objectguid'];
            $baseDn = explode(",", $data['domain_dn']);
            if (strpos($dn, 'CN') === false) {
                // 部门
                $dnExplode = explode(",", $dn);

                if (count($dnExplode) > 0) {
                    foreach ($dnExplode as $k => $v) {
                        $temp = explode("=", $v);
                        if (isset($temp[0]) && $temp[0] == 'OU' && isset($temp[1])) {
                            $allDepts[] = $temp[1];
                        }
                    }
                    $ouExplode = explode("=", $dnExplode[0]);
                    // 确定根部门
                    if (count($dnExplode) == count($baseDn)) {
                        // 根部门id为0
                        if (isset($ouExplode[0]) && $ouExplode[0] == 'OU' && isset($ouExplode[1])) {
                            $deptContrast[$dn] = 0;

                        }
                    } else {
                        if (isset($ouExplode[0]) && $ouExplode[0] == 'OU' && isset($ouExplode[1])) {
                            $contrast = DB::table('ad_sync_contrast')->where('guid', $guid)->first();
                            if (!empty($contrast) && isset($contrast->dept_id)) {
                                //在对照表中找到对应的dept_id
                                $deptContrast[$dn] = $contrast->dept_id;
                            } else {
                                // 没有的话取对照表中最大的dept_id +1
                                $maxRecord = DB::table('ad_sync_contrast')->orderBy('dept_id', 'desc')->first();
                                if (!empty($maxRecord) && isset($maxRecord->dept_id)) {
                                    $deptContrast[$dn] = $maxRecord->dept_id + $i;
                                    $i++;
                                } else {
                                    // 对照表为空，则dept_id取1
                                    $deptContrast[$dn] = $i;
                                    $i++;
                                }
                            }
                        }
                    }
                }
                $deptGuid[] = $guid;
            } else {
                $userGuid[] = $guid;
                $userOrg[$guid] = [];

                // 直接下级
                // if($value['directreports'] != ''){
                //     $userOrg[$guid]['directreports'] = [];
                //     foreach($value['directreports'] as $k => $v){
                //         if (strpos($v, 'CN') === false) {
                //             continue;
                //         }
                //         $explode = explode(",", $v);
                //         if(count($explode) > 0){
                //             $cn = explode("=", $explode[0]);
                //             if(count($cn) == 2){
                //                 $userOrg[$guid]['directreports'][] = $this->transEncoding($cn[1], 'UTF-8');
                //             }
                //         }
                //     }
                // }
                // 上级经理
                if($value['manager'] != ''){
                    $explode = explode(",", $value['manager']);
                    if(count($explode) > 0){
                        $cn = explode("=", $explode[0]);
                        if(count($cn) == 2){
                            $userOrg[$guid]['manager'] = $this->transEncoding($cn[1], 'UTF-8');
                        }
                    }

                }
            }
        }
        $allDepts = array_count_values($allDepts); //部门名称出现次数。判断是否有子部门

        // 处理数组，获得插入数据库的部门或用户信息数组
        $dept    = [];
        $user    = [];
        $sort      = max($allDepts); //部门排序
        foreach ($userList as $key => $value) {
            // 账号
            $userAccount    = $value['samaccountname'];
            $guid           = $value['objectguid'];
            $userAccounts[] = $userAccounts;

            $dn = $value['distinguishedname']; // 格式 CN=User01,OU=dept01,OU=test,DC=eoffice,DC=com
            if (strpos($dn, 'CN') === false) {
                // 部门
                $dnExplode = explode(",", $dn);
                $count     = count($dnExplode); //6
                if ($count > 2) {
                    $cnCount = substr_count($dn, 'DC'); //3
                    for ($i = $count - 1; $i >= $count - $cnCount; $i--) {
                        unset($dnExplode[$i]); //5,4,3
                    }

                    $parentIdStr = ''; // 父级结构字符串
                    $parentId    = 0; // 父级id
                    $name        = '';
                    $deptId      = '';
                    $hasChildren = 0;

                    $dnExplode = array_reverse($dnExplode);
                    for ($i = 0; $i < $count - $cnCount; $i++) {
                        $ouExplode = explode("=", $dnExplode[$i]);
                        if (isset($ouExplode[0]) && $ouExplode[0] == 'OU' && isset($ouExplode[1])) {
                            if (!isset($deptContrast[$dn])) {
                            } else {
                                if ($i < ($count - $cnCount - 1)) {
                                    $times   = $this->newstripos($dn, ',', $i + 1);
                                    $deptStr = trim(substr($dn, $times), ',');
                                    if (isset($deptContrast[$deptStr])) {
                                        $parentIdStr .= $deptContrast[$deptStr] . ',';
                                    }

                                    if ($i == ($count - $cnCount - 2)) {
                                        $deptStr = trim(strstr($dn, ','), ',');
                                        //倒数第二个是父级部门
                                        $parentId = isset($deptContrast[$deptStr]) ? $deptContrast[$deptStr] : 0;
                                    }
                                } else {
                                    $deptId = $deptContrast[$dn]; //最后一个是该部门
                                    $name   = $ouExplode[1];
                                }

                            }

                        }
                    }
                    if ($parentIdStr != '') {
                        $parentIdStrEx = explode(',', $parentIdStr);
                        $parentIdStrEx = array_reverse($parentIdStrEx);
                        $parentIdStr   = trim(implode(',', $parentIdStrEx), ',');
                    }
                    $hasChildren = isset($allDepts[$name]) && $allDepts[$name] > 1 ? 1 : 0;
                    if (strpos($name, '.') !== false) {
                        $tempName = $name;
                        $name     = trim(strstr($name, '.'), '.');
                        $tempSort = trim(trim(strstr($tempName, '.', true), '.'));
                        $sort     = $tempSort == '' || !is_numeric($tempSort) ? $sort : $tempSort;
                    }

                    $deptNamePinyin = Utils::convertPy($name);

                    if ($deptId == 0) {
                        continue;
                    }
                    if ($name == '已离职') {
                        continue;
                    }

                    $temp = [
                        'dept_id'       => $deptId,
                        'dept_name'     => $name,
                        'dept_name_py'  => $deptNamePinyin[0],
                        'dept_name_zm'  => $deptNamePinyin[1],
                        'parent_id'     => $parentId == '' ? 0 : $parentId,
                        'arr_parent_id' => $parentIdStr,
                        'has_children'  => $hasChildren,
                        'dept_sort'     => $sort,
                    ];
                    $sort++;

                    $result = $this->handleDept($temp, $guid);
                    array_push($syncResutlArray, [
                        'name'             => $name,
                        'addUserResult'    => $result,
                        'addUserResultStr' => $codeContrast[$result],
                    ]);
                    // $dept[] = $temp;
                }
            } else {
                if(isset($data['domain_condition']) && !empty($data['domain_condition'])){
                    $exTemp = explode('=', $data['domain_condition']);
                    $lowerCondition = strtolower($exTemp[0]);
                    if(!(isset($value[$lowerCondition]) && $value[$lowerCondition] == $exTemp[1])){
                        $record = DB::table('ad_sync_contrast')->where('guid', $guid)->first();
                        if(!empty($record) && isset($record->user_id)){
                            app($this->userService)->userSystemDelete($record->user_id);
                        }
                        continue;
                    }
                }
                // 有CN的表示用户
                $dnExplode = explode(",", $dn);
                if (count($dnExplode) > 0) {
                    // 第一个CN=后的是用户名
                    $cnExplode   = explode("=", $dnExplode[0]);
                    $deptExplode = explode("=", $dnExplode[1]);
                    if (count($cnExplode) == 2 && count($deptExplode) == 2) {
                        $deptStr = trim(strstr($dn, ','), ',');
                        $name    = trim(strstr($deptStr, '.'), '.');
                        if(strpos($name, '已离职') === 0){
                            continue;
                        }
                        $deptId  = isset($deptContrast[$deptStr]) ? $deptContrast[$deptStr] : 0;
                        if ($deptId == 0) {
                            continue;
                        }
                        $userName = $this->transEncoding($cnExplode[1], 'UTF-8');

                        $userData = [
                            'user_accounts'   => $userAccount,
                            'user_name'       => $userName,
                            'dept_id'         => $deptId,
                            'role_id_init'    => $roleId,
                            'password'        => '',
                            'sex'             => 1,
                            'user_status'     => 1,
                            'user_job_number' => $userAccount, //工号暂取账号
                            'attendance_scheduling' => $data['domain_scheduling'] ?? '',
                            'post_priv'       => $data['post_priv'] ?? 3,
                        ];
                        if(isset($data['post_dept']) && !empty($data['post_dept'])){
                            if (is_array($data['post_dept'])) {
                                $postDept = implode(',', $data['post_dept']);
                            } else {
                                $postDept = $data['post_dept'];
                            }
                            $userData['post_dept'] = $postDept;
                        }
                        $result = $this->handleUser($userData, $guid);
                        array_push($syncResutlArray, [
                            // 'userAccount'      => $userAccount,
                            'name'             => $userName,
                            'addUserResult'    => is_array($result) && isset($result['code']) ? $result['code'] : $result,
                            'addUserResultStr' => is_array($result) && isset($result['code']) ? trans($result['code'][1].'.'.$result['code'][0]) : $codeContrast[$result],
                        ]);
                        // $user[] = $userData;
                    }
                }
            }
        }
        // dd($dept, $user);
        /************************ end ****************************/
        $contrast = DB::table('ad_sync_contrast')->get();
        $contrastUser = [];
        if (count($contrast) > 0) {
            foreach ($contrast as $key => $value) {
                if ($value->user_id == '') {
                    //将不在AD域中但是在OA中的部门 做删除操作
                    if (in_array($value->guid, $deptGuid) || $value->guid == '') {
                        continue;
                    }
                    DB::table('ad_sync_contrast')->where('guid', $value->guid)->delete();
                    $deptInfo = app($this->departmentService)->getDeptDetail($value->dept_id);
                    array_push($syncResutlArray, [
                        'name'             => isset($deptInfo->dept_name) ? $deptInfo->dept_name : '',
                        'addUserResult'    => '0x1000006',
                        'addUserResultStr' => $codeContrast['0x1000009'],
                    ]);
                } else {
                    $contrastUser[] = $value->user_id;
                    // 添加上下级
                    if(isset($userOrg[$value->guid])){
                        $org = $userOrg[$value->guid];

                        // if(isset($org['directreports']) && !empty($org['directreports'])){
                        //     foreach ($org['directreports'] as $k => $v) {
                        //         $userTemp = DB::table('user')->where('user_name', $v)->whereNull('deleted_at')->first();
                        //         if(!empty($userTemp)){
                        //             $hasRecord = DB::table('user_superior')->where('user_id', $userTemp->user_id)->where('superior_user_id', $value->user_id)->first();
                        //             if(empty($hasRecord)){
                        //                 $insertData = [
                        //                     'user_id'          => $userTemp->user_id,
                        //                     'superior_user_id' => $value->user_id,
                        //                 ];
                        //                 DB::table('user_superior')->insert($insertData);
                        //             }
                        //         }
                        //     }
                        // }

                        if(isset($org['manager']) && $org['manager'] !=''){
                            $userTemp = DB::table('user')->where('user_name', $org['manager'])->whereNull('deleted_at')->first();
                            if(!empty($userTemp)){
                                DB::table('user_superior')->where('user_id', $value->user_id)->delete();

                                $insertData = [
                                    'user_id'          => $value->user_id,
                                    'superior_user_id' => $userTemp->user_id,
                                ];
                                DB::table('user_superior')->insert($insertData);
                            }
                        }
                    }
                    if (in_array($value->guid, $userGuid)) {
                        continue;
                    }
                    //将不在AD域中但是在OA中的用户 做离职操作
                    DB::table('ad_sync_contrast')->where('guid', $value->guid)->delete();
                    app($this->userSystemInfoRepository)->updateData(['user_status' => 2], ['user_id' => [$value->user_id]]);
                    $userData = DB::table('user')->select('user_name')->where('user_id', $value->user_id)->first();
                    array_push($syncResutlArray, [
                        'name'             => isset($userData->user_name) ? $userData->user_name : '',
                        'addUserResult'    => '0x1000006',
                        'addUserResultStr' => $codeContrast['0x1000006'],
                    ]);
                }
            }
        }

        if (!empty($contrastUser)) {
            $contrastUser[] = ['admin'];
            $deleteUsers = app($this->userRepository)->getAllUserIdString(['search' => ['user_id' => [$contrastUser, 'not_in']]]);
            if (!empty($deleteUsers)) {
                $deleteUsers = explode(',', $deleteUsers);
                // 不在中间表，上次同步之后，系统内手动新建的用户，做离职操作
                foreach ($deleteUsers as $deleteUser) {
                    app($this->userSystemInfoRepository)->updateData(['user_status' => 2], ['user_id' => [$deleteUser]]);
                    $userData = DB::table('user')->select('user_name')->where('user_id', $deleteUser)->first();
                    array_push($syncResutlArray, [
                        'name'             => isset($userData->user_name) ? $userData->user_name : '',
                        'addUserResult'    => '0x1000006',
                        'addUserResultStr' => $codeContrast['0x1000006'],
                    ]);
                }
            }
            
        }

        $this->addSyncLog('0x00000000', $startTime, $syncResutlArray);

        $sendData = [
            'toUser'      => $data['user_id'],
            'remindState' => 'domain.list',
            'remindMark'  => 'domain-complete',
            'sendMethod'  => ['sms'],
            'isHand'      => true,
            'content'     => trans("domain.successfully_synchronized"),
            'stateParams' => [],
        ];
        Eoffice::sendMessage($sendData);

        return true;

    }
    /**
     * @处理同步部门
     * @param $data array 用户信息
     * @param $guid string
     * @return bool
     */
    public function handleDept($data, $guid)
    {
        // 检测部门是否存在
        $dept = DB::table('ad_sync_contrast')->where('guid', $guid)->first();

        if (!empty($dept) && isset($dept->dept_id)) {
            $record = DB::table('department')->where('dept_id', $dept->dept_id)->first();
            if (!empty($record)) {
                // 更新部门信息
                unset($data['dept_id']);
                DB::table('department')->where('dept_id', $dept->dept_id)->update($data);
                return "0x1000007";
            }
        }

        //添加新部门
        $insertData = [
            'dept_id' => $data['dept_id'],
            'guid'    => $guid,
        ];
        $record = DB::table('ad_sync_contrast')->where('dept_id', $data['dept_id'])->first();
        if (!empty($record)) {
            DB::table('ad_sync_contrast')->where('dept_id', $data['dept_id'])->delete();
        }
        DB::table('ad_sync_contrast')->insert($insertData);
        DB::table('department')->insert($data);
        return '0x1000008';
    }

    /**
     * @处理同步用户
     * @param $data array 用户信息
     * @param $guid string
     * @return bool
     */
    public function handleUser($data, $guid)
    {
        // 账号合法性检验
        if (strstr($data['user_accounts'], "\'") != false) {
            return "0x1000003";
        }
        // 检测用户是否存在
        $user = DB::table('ad_sync_contrast')->where('guid', $guid)->first();

        if (!empty($user) && isset($user->user_id)) {
            $record = DB::table('user')->where('user_id', $user->user_id)->whereNull('deleted_at')->first();
            if (!empty($record)) {
                // 更新用户信息
                $data['user_id'] = $user->user_id;
                // 编辑用户时保留工号
                $data['user_job_number'] = $record->user_job_number;
                // 保留用户角色、密码、性别信息
                unset($data['role_id_init']);
                unset($data['password']);
                unset($data['sex']);
                // 编辑用户时不更新排班 和 管理范围
                unset($data['attendance_scheduling']);
                unset($data['post_priv']);
                unset($data['post_dept']);
                app($this->userService)->userSystemEdit($data);
                return "0x1000004";
            }
        }

        // 最大用户数校检........... 0x1000002
        if (!app($this->userService)->checkPcUserNumberWhetherExceed()) {
            return "0x1000002";
        }

        // 添加新用户
        $userId     = app($this->userRepository)->getNextUserIdBeforeCreate();
        $insertData = [
            'user_id' => $userId,
            'guid'    => $guid,
        ];

        $result = app($this->userService)->userSystemCreate($data);
        if (is_object($result)) {
            $record = DB::table('ad_sync_contrast')->where('guid', $guid)->first();
            if ($record) {
                DB::table('ad_sync_contrast')->where('guid', $guid)->delete();
            }
            DB::table('ad_sync_contrast')->insert($insertData);
            return '0x1000000';
        } else {
            if (isset($result['code'])) {
                // 用户已存在
                if ($result['code'][0] == '0x005003') {
                    $existsUser = DB::table('user')->where('user_accounts', $data['user_accounts'])->first();
                    if ($existsUser) {
                        $insertData = [
                            'user_id' => $existsUser->user_id,
                            'guid'    => $guid,
                        ];
                        DB::table('ad_sync_contrast')->insert($insertData);
                    }
                }
                return $result;
            }
            return '0x1000010';
        }

    }

    /**
     * @创建同步日志
     * @param
     * @return bool
     */
    public function addSyncLog($code, $startTime, $syncResutlArray = [])
    {
        $result           = [];
        $result['result'] = $code;

        if ($code == '0x00000000') {
            $result['info'] = $syncResutlArray;
        } else {
            $result['info'] = $this->codeInfo[$code];
        }

        $endTime = time();
        $data    = [
            "start_time"  => date("Y-m-d H:i:s", $startTime),
            "end_time"    => date("Y-m-d H:i:s", $endTime),
            "sync_result" => json_encode($result),
        ];

        return app($this->domainSyncLogRepository)->insertData($data);
    }

    /**
     * @获取同步日志列表
     * @param array
     * @return array
     */
    public function getSyncLogs($param = [])
    {
        $param             = $this->parseParams($param);
        $param['order_by'] = ['log_id' => 'DESC'];

        return $this->response(app($this->domainSyncLogRepository), 'getSyncLogsTotal', 'getSyncLogs', $param);
    }

    /**
     * @同步详情
     * @return array
     */
    public function getSyncLogDetail($param, $logId)
    {
        $page  = isset($param['page']) ? $param['page'] : 1;
        $limit = isset($param['limit']) ? $param['limit'] : 10;
        $begin = $limit * ($page - 1);

        $result = app($this->domainSyncLogRepository)->getSyncLogByLogId($logId);

        if ($result) {
            $syncResult = json_decode($result->sync_result, true);

            if (isset($syncResult['result']) && $syncResult['result'] == "0x00000000") {
                if (!empty($syncResult['info'])) {
                    $syncList = array_slice($syncResult['info'], $begin, $limit);
                    $total    = count($syncResult['info']);
                    return ['list' => $syncList, 'total' => $total];
                }
            }
        }

        return [];
    }

    /**
     * @获取同步结果
     * @return str
     */
    public function getSyncResult($logId)
    {
        $code   = "0x00000000";
        $result = app($this->domainSyncLogRepository)->getSyncLogByLogId($logId);
        $num    = [
            [
                'type'   => trans("domain.user"),
                'add'    => 0,
                'update' => 0,
                'delete' => 0,
            ],
            [
                'type'   => trans("domain.department"),
                'add'    => 0,
                'update' => 0,
                'delete' => 0,
            ],
        ];

        if ($result) {
            $syncResult = json_decode($result->sync_result, true);

            if (isset($syncResult['result']) && $syncResult['result'] != "0x00000000") {
                $code = $syncResult['result'];
            }

            if (isset($syncResult['info']) && is_array($syncResult['info']) && count($syncResult['info']) > 0) {
                /*
                '0x1000000' => '新建用户成功',
                '0x1000001' => '盗版OA',
                '0x1000002' => '已经达到系统的最大授权用户数,无法添加用户',
                '0x1000003' => '用户名中不能包含单引号',
                '0x1000004' => '账号存在,更新信息',
                '0x1000005' => '创建新用户菜单失败',
                '0x1000006' => '设为离职',
                '0x1000007' => '部门存在,更新信息',
                '0x1000008' => '新建部门成功',
                '0x1000009' => 'AD域不存在该部门,已删除',
                 */
                foreach ($syncResult['info'] as $key => $value) {
                    switch ($value['addUserResult']) {
                        case '0x1000000':
                            $num[0]['add'] += 1;
                            break;
                        case '0x1000004':
                            $num[0]['update'] += 1;
                            break;
                        case '0x1000006':
                            $num[0]['delete'] += 1;
                            break;
                        case '0x1000007':
                            $num[1]['update'] += 1;
                            break;
                        case '0x1000008':
                            $num[1]['add'] += 1;
                            break;
                        case '0x1000009':
                            $num[1]['delete'] += 1;
                            break;
                        default:
                            # code...
                            break;
                    }
                }
            }
        }

        $str = isset($this->codeInfo[$code]) ? $this->codeInfo[$code] : '';

        return ['syncCode' => $code, 'handleUserResult' => $str, 'num' => $num];
    }

    /**
     * @获取默认域
     * @return str
     */
    public function getDefaultDomain()
    {
        $where  = ['is_validate' => [1]];
        $domain = app($this->domainRepository)->getDataByWhere($where)->toArray();
        if (!empty($domain) && isset($domain[0])) {
            return $domain[0];
        } else {
            return [];
        }
    }

    public function getGUID($GUID)
    {
        $strGuid = "{";
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[3]);
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[2]);
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[1]);
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[0]);
        $strGuid = $strGuid . "-";
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[5]);
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[4]);
        $strGuid = $strGuid . "-";
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[7]);
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[6]);
        $strGuid = $strGuid . "-";
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[8]);
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[9]);
        $strGuid = $strGuid . "-";
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[10]);
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[11]);
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[12]);
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[13]);
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[14]);
        $strGuid = $strGuid . $this->AddLeadingZero((int) $GUID[15]);
        $strGuid = $strGuid . "}";

        return $strGuid;
    }
    public function AddLeadingZero($k)
    {
        if ($k >= 48 && $k <= 57) {
            return chr($k);
        } else if ($k >= 65 && $k <= 90) {
            return chr($k);
        } else if ($k >= 97 && $k <= 122) {
            return chr($k);
        }
        return $k;
    }
    public function getBytes($string)
    {
        $bytes = array();
        for ($i = 0; $i < strlen($string); $i++) {
            $bytes[] = ord($string[$i]);
        }
        return $bytes;
    }

    public function newstripos($str, $find, $count, $offset = 0)
    {
        $pos = stripos($str, $find, $offset);
        $count--;
        if ($count > 0 && $pos !== false) {
            $pos = $this->newstripos($str, $find, $count, $pos + 1);
        }
        return $pos;
    }

    /**
     * 字符串编码转换
     * @param  [string] $string [要转换的内容]
     * @param  [string] $target [要转换的格式]
     * @return [string]         [description]
     */
    private function transEncoding($string, $target)
    {
        $encoding = mb_detect_encoding($string, 'auto');
        return mb_convert_encoding($string, $target, $encoding);
    }

    private function createMiddleTable()
    {
        if (!Schema::hasTable('ad_sync_contrast')) {
            Schema::create('ad_sync_contrast', function (Blueprint $table) {
                $table->integer('dept_id')->nullable();
                $table->string('user_id', 100)->nullable();
                $table->string('guid')->nullable();
            });
            // 取admin的部门信息，插入到对照表中，其他部门删除
            $param     = ['search' => ['user_id' => ['admin']]];
            $adminInfo = app($this->userRepository)->getUserAllData($param)->toArray();
            $deptId    = $adminInfo[0]['user_has_one_system_info']['dept_id'];

            $param = [
                'include_leave' => 1,
                'with_trashed'  => 1,
            ];
            $user      = app($this->userService)->getAllUserIdString($param);
            $userArray = explode(',', $user);
            if (!empty($userArray)) {
                foreach ($userArray as $key => $value) {
                    if ($value == 'admin') {
                        continue;
                    }
                    app($this->userService)->userSystemDelete($value);
                }
            }

            DB::table('department')->where('dept_id', '!=', $deptId)->delete();
            DB::table('ad_sync_contrast')->insert(['dept_id' => $deptId]);
        }
    }

    public function deleteSyncRecord($logId)
    {
        return app($this->domainSyncLogRepository)->deleteById($logId);
    }

}
