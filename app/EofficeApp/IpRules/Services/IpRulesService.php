<?php

namespace App\EofficeApp\IpRules\Services;

use Request;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\IpRules\Repositories\IpRulesRepository;
use App\EofficeApp\System\Department\Repositories\DepartmentRepository;
use App\EofficeApp\Role\Repositories\RoleRepository;
use App\EofficeApp\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Redis;

/**
 * 访问控制服务
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 */
class IpRulesService extends BaseService {

    /** @var object 访问控制资源库变量 */
    private $ipRulesRepository;

    public function __construct() {
        $this->ipRulesRepository = 'App\EofficeApp\IpRules\Repositories\IpRulesRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->departmentRepository = 'App\EofficeApp\System\Department\Repositories\DepartmentRepository';
        $this->roleRepository = 'App\EofficeApp\Role\Repositories\RoleRepository';
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';

    }


    /**
     * 访问控制列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getIpRulesList($data){
        $result = $this->response(app($this->ipRulesRepository), 'getTotal', 'getIpRulesList', $this->parseParams($data));
        if (isset($result['list']) && !empty($result['list'])) {
            foreach ($result['list'] as $key => $value) {
                $deptName = app($this->departmentRepository)->getDeptNameByIds(explode(',', $value['ip_rules_dept']));
                $roleName = app($this->roleRepository)->getRolesNameByIds(explode(',', $value['ip_rules_role']));
                $userName = app($this->userSystemInfoRepository)->getUsersNameByIds($value['ip_rules_user'], ['include_leave' => false]);
                $result['list'][$key]['ip_control_user'] = implode(',', $userName);
                $result['list'][$key]['ip_control_dept'] = implode(',', $deptName);
                $result['list'][$key]['ip_control_role'] = implode(',', $roleName);
            }
        }
        return $result;
    }

    /**
     * 增加控制
     *
     * @param array   $data
     *
     * @return  int
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addIpRules($data) {
        
        if (!$this->compareIp($data["ip_rules_begin_ip"], $data["ip_rules_end_ip"])){
            return ['code' => ['0x028002', 'iprules']];
        }

        $data["ip_rules_type"] = $data["ip_rules_type"] ?? '';
        $data["ip_rules_to_all"] = isset($data["ip_rules_to_all"])&&($data["ip_rules_to_all"] == 1 ) ? 1: 0;
        $data["ip_rules_role"] = isset($data["ip_rules_role"])&&(!empty($data["ip_rules_role"])) ? $data["ip_rules_role"] : "";
        $data["ip_rules_dept"] = isset($data["ip_rules_dept"])&&(!empty($data["ip_rules_dept"])) ? $data["ip_rules_dept"] : "";
        $data["ip_rules_user"] = isset($data["ip_rules_user"])&&(!empty($data["ip_rules_user"])) ? $data["ip_rules_user"] : "";
        $data["flow_ids"] = isset($data["flow_ids"])&&(!empty($data["flow_ids"])) ? $data["flow_ids"] : "";

        // 控制平台
        $data['control_platform_pc'] = $data['control_platform_pc'] ?? 0;
        $data['control_platform_mobile'] = $data['control_platform_mobile'] ?? 0;
        if ($data["ip_rules_type"] == 2) {
            $data['control_platform_pc'] = 1;
            $data['control_platform_mobile'] =1;
        }
        if (!$data['control_platform_pc'] && !$data['control_platform_mobile']) {
            return ['code' => ['0x028005', 'iprules']];
        }

        if(empty($data["ip_rules_to_all"])){
            if(!($data["ip_rules_role"]||$data["ip_rules_dept"]||$data["ip_rules_user"])){
                return ['code' => ['0x028003', 'iprules']];
            }
        }else{
            $data["ip_rules_to_all"] = 1;
            $data["ip_rules_role"] = $data["ip_rules_dept"] = $data["ip_rules_user"] ="";
        }
        if (Redis::exists('flow_ip_rules')) {
            $accessControl =  Redis::del('flow_ip_rules');
        }

        $ipRulesData = array_intersect_key($data, array_flip(app($this->ipRulesRepository)->getTableColumns()));
        $result = app($this->ipRulesRepository)->insertData($ipRulesData);
        return $result->ip_rules_id;
    }

    /**
     * 访问控制
     *
     * @param  string  $ip1 ip信息
     * @param  string  $ip2 ip信息
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-02-18
     */
    public function compareIp($ip1, $ip2)
    {
        $ip1 = $this->getIpInt($ip1);
        $ip2 = $this->getIpInt($ip2);

        if ($ip1 <= $ip2) {
            return true;
        }

        return false;
    }

    /**
     * 访问控制
     *
     * @param  string  $ip ip信息
     *
     * @return int|string
     *
     * @author qishaobo
     *
     * @since  2016-02-18
     */
    public function getIpInt($ip)
    {
        $ip = ip2long($ip);

        if ($ip < 0) {
            $ip = sprintf("%u", $ip);
        }

        return $ip;
    }


    /**
     * 编辑控制
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function editIpRules($data) {

        if (Redis::exists('flow_ip_rules')) {
            $accessControl =  Redis::del('flow_ip_rules');
        }
        $ipRulesInfo = app($this->ipRulesRepository)->infoIpRules($data['ip_rules_id']);
        if(count($ipRulesInfo) == 0){
            return ['code' => ['0x028004', 'iprules']];
        }

        if (!$this->compareIp($data["ip_rules_begin_ip"], $data["ip_rules_end_ip"])){
            return ['code' => ['0x028002', 'iprules']];
        }

        $data["ip_rules_type"] = $data["ip_rules_type"] ?? '';
        $data["ip_rules_to_all"] = isset($data["ip_rules_to_all"])&&($data["ip_rules_to_all"] ==1) ? 1: 0;
        $data["ip_rules_role"] = isset($data["ip_rules_role"])&&(!empty($data["ip_rules_role"])) ? $data["ip_rules_role"] : "";
        $data["ip_rules_dept"] = isset($data["ip_rules_dept"])&&(!empty($data["ip_rules_dept"])) ? $data["ip_rules_dept"] : "";
        $data["ip_rules_user"] = isset($data["ip_rules_user"])&&(!empty($data["ip_rules_user"])) ? $data["ip_rules_user"] : "";
        $data["flow_ids"] = isset($data["flow_ids"])&&(!empty($data["flow_ids"])) ? $data["flow_ids"] : "";
        // 控制平台
        $data['control_platform_pc'] = $data['control_platform_pc'] ?? 0;
        $data['control_platform_mobile'] = $data['control_platform_mobile'] ?? 0;
        if ($data["ip_rules_type"] == 2) {
            $data['control_platform_pc'] = 1;
            $data['control_platform_mobile'] =1;
        }
        if (!$data['control_platform_pc'] && !$data['control_platform_mobile']) {
            return ['code' => ['0x028005', 'iprules']];
        }

        if($data["ip_rules_to_all"] ==0){
            if(!($data["ip_rules_role"]||$data["ip_rules_dept"]||$data["ip_rules_user"])){
                return ['code' => ['0x028003', 'iprules']];
            }
        }else{
            $data["ip_rules_to_all"] = 1;
            $data["ip_rules_role"] = $data["ip_rules_dept"] = $data["ip_rules_user"] ="";
        }

        $ipRulesData = array_intersect_key($data, array_flip(app($this->ipRulesRepository)->getTableColumns()));
        return app($this->ipRulesRepository)->updateData($ipRulesData, ['ip_rules_id' => $data['ip_rules_id']]);
    }

    /**
     * 删除控制
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function deleteIpRules($data) {
        $destroyIds = explode(",", $data['ip_rules_id']);
        $where = [
            'ip_rules_id' => [$destroyIds, 'in']
        ];
        if (Redis::exists('flow_ip_rules')) {
            $accessControl =  Redis::del('flow_ip_rules');
        }
        return app($this->ipRulesRepository)->deleteByWhere($where);
    }

    /**
     * 获取规则明细
     *
     * @param array $data
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-22
     *
     */
    public function getOneIpRules($data){

         $data = app($this->ipRulesRepository)->infoIpRules($data['ip_rules_id']);
         return $data[0];
    }

     /**
     * 获取访问控制ip范围
     *
     * @param int $type 控制类型
     *
     * @return array
     *
     * @author 齐少博
     *
     * @since 2016-02-18
     */
    public function getAccessControl($type)
    {
        return app($this->ipRulesRepository)->getAccessControl($type);
    }

    /**
     * 访问控制
     *
     * @param  array  $userInfo 用户信息
     * @param  int    $type     控制类型
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-02-18
     */
    public function accessControl($userInfo, $type)
    {
        $accessControl = $this->getAccessControl($type);

        if (empty($accessControl)) {
            return true;
        }

        $userIp  = sprintf("%u", ip2long($this->getClientIp()));

        $ips = [];
        if ($userIp == sprintf("%u", ip2long("127.0.0.1"))) {
            return true;
        }
        // 判断平台是否受访问控制
        $isMobile = app("App\EofficeApp\Auth\Services\AuthService")->isMobile();

        foreach ($accessControl as $k => $row) {
            $inRange = false;

            if ( ($isMobile && $row["control_platform_mobile"]) || (!$isMobile && $row["control_platform_pc"]) ) {
                if ($row["ip_rules_to_all"] == 1) {
                    $inRange = true;
                } else {
                    $deptId = $userInfo["dept_id"];
                    if (!empty($row["ip_rules_dept"]) && strpos($row["ip_rules_dept"], (string)$deptId) !== false) {
                        $inRange = true;
                    }

                    if (!$inRange && !empty($row["ip_rules_role"])) {
                        $ip_rules_role = array_filter(explode(',', $row["ip_rules_role"]));
                        $result = array_diff($userInfo["role_id"], $ip_rules_role);

                        if (empty($result)) {
                            $inRange = true;
                        }
                    }

                    if (!$inRange && !empty($row["ip_rules_user"]) && strpos($row["ip_rules_user"], $userInfo["user_id"]) !== false) {
                        $inRange = true;
                    }
                }
            }

            if ($inRange) {
                $ips[] = [
                    'beginIp' => sprintf("%u", ip2long($row["ip_rules_begin_ip"])),
                    'endIP' => sprintf("%u", ip2long($row["ip_rules_end_ip"]))
                ];
            }
        }

        if (!empty($ips)) {
            $index = false;

            foreach ($ips as $ip) {
                if ($userIp >= $ip['beginIp'] && $userIp <= $ip['endIP']) {
                    $index = true;
                }
            }

            return $index;
        }

        return true;
    }

    private function getClientIp() {
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            //nginx 代理模式下，获取客户端真实IP
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            //客户端的ip
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }

        return $ip;
    }

    /**
     * 流程访问控制
     *
     * @return bool
     *
     * @author wz
     *
     */
    public function accessFlowControl()
    {
        if (Redis::exists('flow_ip_rules')) {
                $accessControl =  Redis::get('flow_ip_rules');
                $accessControl = unserialize( $accessControl);
        } else {
             $accessControl = $this->getAccessControl(2);
             Redis::set('flow_ip_rules' , serialize(  $accessControl ));
        } 
        if (empty($accessControl)) {
            return [];
        }
        $userIp  = sprintf("%u", ip2long($this->getClientIp()));
        if ($userIp == sprintf("%u", ip2long("127.0.0.1"))) {
            return [];
        }
        $notContrlFlowids = [];
        $ContrlFlowids = [];
        foreach ($accessControl as $k => $row) {
                $rowFlowIds = !empty($row['flow_ids']) ? explode(',', $row['flow_ids']) : [];
                if ($userIp < sprintf("%u", ip2long($row["ip_rules_begin_ip"])) || $userIp > sprintf("%u", ip2long($row["ip_rules_end_ip"]))) {
                     $notContrlFlowids =  array_merge($notContrlFlowids , $rowFlowIds);
                } else {
                    $ContrlFlowids = array_merge($ContrlFlowids ,  $rowFlowIds);
                }

        }
        return  array_unique(array_diff($notContrlFlowids,  $ContrlFlowids));
    }
}
