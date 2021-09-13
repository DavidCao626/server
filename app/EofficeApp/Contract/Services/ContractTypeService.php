<?php
namespace App\EofficeApp\Contract\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Contract\Repositories\ContractOrderRepository;
use App\EofficeApp\Contract\Repositories\ContractRemindRepository;
use App\EofficeApp\Contract\Repositories\ContractTypeMonitorPermissionRepository;
use App\EofficeApp\Contract\Repositories\ContractTypeRepository;
use App\EofficeApp\Contract\Repositories\ContractProjectRepository;
use App\EofficeApp\Contract\Repositories\ContractProjectProjectRepository;
use App\EofficeApp\Contract\Repositories\ContractFlowRepository;
use App\EofficeApp\Contract\Repositories\ContractShareRepository;
use App\EofficeApp\Contract\Repositories\ContractRepository;
use App\EofficeApp\Contract\Services\ContractService;

use DB;
use Illuminate\Support\Facades\Redis;

/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractTypeService extends BaseService
{

    const CUSTOM_TABLE_KEY    = 'contract_t';
    const CONTRACT_PROJECT_TYPE = "CONTRACT_PROJECT_TYPE";


    private $contractRepository;
    private $contractOrderRepository;
    private $contractRemindRepository;
    private $contractTypeRepository;
    private $contractTypePermissionRepository;
    private $contractTypeMonitorPermissionRepository;
    private $contractService;

    public function __construct()
    {
        parent::__construct();
        $this->contractRepository        = 'App\EofficeApp\Contract\Repositories\ContractRepository';
        $this->contractOrderRepository   = 'App\EofficeApp\Contract\Repositories\ContractOrderRepository';
        $this->contractRemindRepository  = 'App\EofficeApp\Contract\Repositories\ContractRemindRepository';
        $this->contractTypeRepository    = 'App\EofficeApp\Contract\Repositories\ContractTypeRepository';
        $this->contractTypePermissionRepository = 'App\EofficeApp\Contract\Repositories\ContractTypePermissionRepository';
        $this->contractTypeMonitorPermissionRepository = 'App\EofficeApp\Contract\Repositories\ContractTypeMonitorPermissionRepository';
        $this->contractService = 'App\EofficeApp\Contract\Services\ContractService';
        $this->userRepository               = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->departmentRepository			= 'App\EofficeApp\System\Department\Repositories\DepartmentRepository';
        $this->roleRepository            = 'App\EofficeApp\Role\Repositories\RoleRepository';
    }


    public function setRelationFields($id,$input,$own){
        $data = $this->parseRelationFields($input);
        return ContractTypeRepository::setRelationFields($id,$data);
    }

    public function setDataPermission($id,$input,$own){
        $data = $this->parseRelationData($input);
        if(ContractTypeRepository::getPrivilegeDetail($id)){
            $data['updated_at'] = date('Y-m-d H:i:s');
            $result = ContractTypeRepository::upDataPermission($id,$data);
        }else{
            $data['type_id'] = $id;
            $result = app($this->contractTypePermissionRepository)->insertData($data);
        }
        return $result;
    }

    public function parseRelationData($input){
        $parseData = [
            'all_privileges' => $this->defaultParams($input,'all_privileges',0),
            'data_view' => $this->defaultParams($input,'data_view',0),
            'data_edit' => $this->defaultParams($input,'data_edit',0),
            'data_delete' => $this->defaultParams($input,'data_delete',0),
            'data_user_id' => $this->defaultParams($input,'data_user_id',0),
            'data_share' => $this->defaultParams($input,'data_share',0),
            'label_basic_info' => json_encode($input['label_basic_info']),
            'label_text' => json_encode($input['label_text']),
            'label_child_contract' => json_encode($input['label_child_contract']),
            'label_project' => json_encode($input['label_project']),
            'label_order' => json_encode($input['label_order']),
            'label_run' => json_encode($input['label_run']),
            'label_remind' => json_encode($input['label_remind']),
            'label_attachment' => json_encode($input['label_attachment']),

        ];
        return $parseData;
    }


    public function parseRelationFields($input,$type = 'fields'){
        if(empty($input['type_id'])){
            return ['code' => ['type_not_empty','contract']];
        }

        if(!is_array($input['type_id'])){
            $input['type_id'] = explode(',',$input['type_id']);
        }
        $parseData = [
            'type_id' => (isset($input['type_id']) && !empty($input['type_id'])) ? implode(',', (array) $input['type_id']) : '',
            'all_privileges' => $this->defaultParams($input,'all_privileges',0),
            'data_view' => $this->defaultParams($input,'data_view',0),
            'data_edit' => $this->defaultParams($input,'data_edit',0),
            'data_delete' => $this->defaultParams($input,'data_delete',0),
            'data_user_id' => $this->defaultParams($input,'data_user_id',0),
            'data_share' => $this->defaultParams($input,'data_share',0),
            'label_basic_info' => $input['label_basic_info'] ? json_encode($input['label_basic_info']) : '',
            'label_text' => $input['label_text'] ? json_encode($input['label_text']) : '',
            'label_child_contract' => $input['label_child_contract'] ? json_encode($input['label_child_contract']) : '',
            'label_project' => $input['label_project'] ? json_encode($input['label_project']) : '',
            'label_order' => $input['label_order'] ? json_encode($input['label_order']) : '',
            'label_run' => $input['label_run'] ? json_encode($input['label_run']) : '',
            'label_remind' => $input['label_remind'] ? json_encode($input['label_remind']): '',
            'label_attachment' => $input['label_attachment'] ? json_encode($input['label_attachment']): '',
            'label_custom' => $input['label_custom'] ? json_encode($input['label_custom']): '',
        ];
        if($type == 'fields'){
            $parseData['relation_fields'] = (isset($input['relation_fields']) && $input['relation_fields']) ? $input['relation_fields'] : '';
            $parseData['own'] = (isset($input['own']) && $input['own']) ? $input['own'] : 0;
            $parseData['superior'] = (isset($input['superior']) && $input['superior']) ? $input['superior'] : 0;
            $parseData['all_superior'] = (isset($input['all_superior']) && $input['all_superior']) ? $input['all_superior'] : 0;
            $parseData['department_header'] = (isset($input['department_header']) && $input['department_header']) ? $input['department_header'] : 0;
            $parseData['department_person'] = (isset($input['department_person']) && $input['department_person']) ? $input['department_person'] : 0;
            $parseData['role_person'] = (isset($input['role_person']) && $input['role_person']) ? $input['role_person'] : 0;
        }else{
            $parseData['all_user'] = (isset($input['all_user']) && $input['all_user']) ? $input['all_user'] : 0 ;
            if(isset($input['user_ids'])){
                if($input['user_ids']){
                    $parseData['user_ids'] = is_array($input['user_ids']) ? implode(',', $input['user_ids']) : $input['user_ids'];
                }else{
                    $parseData['user_ids'] = '';
                }
            }
            if(isset($input['dept_ids'])){
                if($input['dept_ids']){
                    $parseData['dept_ids'] = is_array($input['dept_ids']) ? implode(',', $input['dept_ids']) : $input['dept_ids'];
                }else{
                    $parseData['dept_ids'] = '';
                }
            }

            if(isset($input['role_ids'])){
                if($input['role_ids']){
                    $parseData['role_ids'] = is_array($input['role_ids']) ? implode(',', $input['role_ids']) : $input['role_ids'];
                }else{
                    $parseData['role_ids'] = '';
                }
            }
        }
        return $parseData;
    }

    private function defaultParams($input,$fields,$default = 0){
        if($input){
            if(isset($input[$fields]) && $input[$fields]){
                return 1;
            }else{
                return $default;
            }
        }
    }

    public function getPermissionLists($params = [],$sign = 'fields'){
        if($sign == 'fields'){
            $result = app($this->contractTypePermissionRepository)->groupList($params);
        }else{
            $result = app($this->contractTypeMonitorPermissionRepository)->groupList($params);

        }
        if(empty($result)){
            return [];
        }
        foreach ($result as $key => $vo){
            foreach (ContractService::$labelName as $value){
                if(isset($vo[$value])){
                    $result[$key][$value] = $vo[$value] ? json_decode($vo[$value], 1): '';
                }
                $result[$key]['label_custom'] = (isset($vo['label_custom']) && $vo['label_custom']) ? json_decode($vo['label_custom'], 1) : [];
            }
        }
        return $result;
    }

    public function addGroup($params,$own){
        $data = $this->parseRelationFields($params);
        return app($this->contractTypePermissionRepository)->insertData($data);
    }

    public function groupList($params){
        $result = $this->response(app($this->contractTypePermissionRepository), 'getTotal', 'groupList', $params);
        if (!isset($result['list']) || empty($result['list'])) {
            return $result;
        }
        $lists = $result['list'];
        $total = $result['total'];
        $menus = app($this->contractService)->menus(0,own());
        $menusSet = [];
        if($menus){
            foreach ($menus as $menu){
                $menusSet[$menu['key']] = $menu['isShow'] ?? 0;
            }
        }
        if($lists){
            $typeData = ContractTypeRepository::getTypeNameById();
            $type_names = [];
            foreach ($typeData as $name){
                $type_names[$name->id] = $name->name;
            }
            $userArray = [];
            if($fieldsList = app($this->contractService)->userSelector([],own())){
                array_map(function ($v) use(&$userArray){
                    return $userArray[$v['field']] = $v['name'];
                },$fieldsList);
            };
            foreach ($lists as $key => $vo){
                $type_ids = explode(',',$vo['type_id']);
                $names = '';
                foreach ($type_ids as $id){
                    $names .= (isset($type_names[$id]) ? $type_names[$id] : '') .' |';
                }
                $lists[$key]['type_name'] = trim($names,',');
                foreach (ContractService::$labelName as $item){
                    $lists[$key][$item] = json_decode($vo[$item],1);
                }

                $lists[$key]['label_custom'] = $vo['label_custom'] ? json_decode($vo['label_custom'],1) : [];

                $lists[$key]['field_name'] = isset($userArray[$vo['relation_fields']]) ? $userArray[$vo['relation_fields']] : '';
            }
        }
        return ['total' => $total, 'list' => $lists,'menus' =>$menusSet];
    }

    public function groupDetail($id,$input, $own){
        if(!$data = app($this->contractTypePermissionRepository)->getDetail($id)){
            return ['code' => ['no_data','common']];
        };
//        if($data['type_id'] == 'all'){
//            $data['type_id'] = array_column(app($this->contractTypeRepository)->getLists([ 'fields' => ['id']])->toArray(),'id');
//        }
        foreach (ContractService::$labelName as $item){
            if(isset($data[$item])){
                $data[$item] = json_decode($data[$item],1);
            }
        }
        $data['label_custom'] = $data['label_custom'] ? json_decode($data['label_custom'],1) : '';
        return $data;
    }

    public function groupEdit($id,$input, $own){
        if(!$data = app($this->contractTypePermissionRepository)->getDetail($id)){
            return ['code' => ['no_data','common']];
        };
        $data = $this->parseRelationFields($input,'fields');
        return app($this->contractTypePermissionRepository)->updateData($data,['id' => $id]);
    }

    public function groupDelete($id,$input, $own){
        if(!$data = app($this->contractTypePermissionRepository)->getDetail($id)){
            return ['code' => ['no_data','common']];
        };
        return app($this->contractTypePermissionRepository)->reallyDeleteByWhere(['id'=>[$id]]);
    }

    // 根据当前分类获取对应下的权限
    public function getPermissionByType($type_id){
        $params = [
            'page' => 0,
            'type_id' => $type_id
        ];
        $lists = app($this->contractTypePermissionRepository)->groupList($params);
        foreach ($lists as $key => $vo){
            foreach (ContractService::$labelName as $item){
                $lists[$key][$item] = $vo[$item] ? json_decode($vo[$item],1) : '';
            }
        }
        return $lists;
    }

    public function monitorGroupList($params){

        $result = $this->response(app($this->contractTypeMonitorPermissionRepository), 'getTotal', 'groupList', $params);
        if (!isset($result['list']) || empty($result['list'])) {
            return $result;
        }

        $lists = $result['list'];
        $total = $result['total'];
        $menus = app($this->contractService)->menus(0,own());
        $menusSet = [];
        if($menus){
            foreach ($menus as $menu){
                $menusSet[$menu['key']] = $menu['isShow'] ?? 0;
            }
        }
        if($lists){
            $typeData = ContractTypeRepository::getTypeNameById();
            $type_names = [];
            foreach ($typeData as $name){
                $type_names[$name->id] = $name->name;
            }
            foreach ($lists as $key => $vo){
                if($vo['type_id'] != 'all'){
                    $type_ids = explode(',',$vo['type_id']);
                    $names = '';
                    foreach ($type_ids as $id){
                        $names .= (isset($type_names[$id]) ? $type_names[$id] : '') .',';
                    }
                    $lists[$key]['type_name'] = trim($names,',');
                }
                // 默认显示前5位人员
                if($vo['user_ids']){
                    $user_ids = explode(',',$vo['user_ids']);
                    $user_names = '';
//                    if(count($user_ids) > 5){
//                        $user_ids = array_slice($user_ids,0,5);
//                    }
                    if($data = app($this->userRepository)->getUserNames($user_ids)){
                        $user_names = array_column($data->toArray(),'user_name');
                    };
//                    $lists[$key]['user_names'] = $user_names ? implode('|',$user_names) : '';
                    $lists[$key]['user_names'] = $user_names ? $user_names : '';
                    $lists[$key]['user_count'] = count($user_ids);
//                    if(count($user_names) >= 5){
//                        $lists[$key]['user_names'] .= '，等人员';
//                    }
                }
                // 默认显示前5个部门
                if($vo['dept_ids']){
                    $dept_ids = explode(',',$vo['dept_ids']);
                    $dept_names = '';
//                    if(count($dept_ids) > 5){
//                        $dept_ids = array_slice($dept_ids,0,5);
//                    }
                    $params = [
                        'search' => ['dept_id' => [$dept_ids,'in']],
                        'fields' => ['dept_name']
                    ];
                    if($deptDatas = app($this->departmentRepository)->listDept($params)){
                        $dept_names = array_column($deptDatas,'dept_name');
                    };

//                    $lists[$key]['dept_names'] = $dept_names ? implode('|',$dept_names) : '';
                    $lists[$key]['dept_names'] = $dept_names ? $dept_names : '';
                    $lists[$key]['dept_count'] = count($dept_ids);
//                    if(count($dept_names) >= 5){
//                        $lists[$key]['dept_names'] .= '，等部门';
//                    }
                }
                // 默认显示前5个角色
                if($vo['role_ids']){
                    $role_ids = explode(',',$vo['role_ids']);
                    $role_names = '';
//                    if(count($role_ids) > 5){
//                        $role_ids = array_slice($role_ids,0,5);
//                    }
                    $params = [
                        'search' => ['role_id' => [$role_ids,'in']],
                        'fields' => ['role_name']
                    ];
                    if($roleDatas = app($this->roleRepository)->getRoleList($params)){
                        $role_names = array_column($roleDatas,'role_name');
                    };
//                    $lists[$key]['role_names'] = $role_names ? implode('|',$role_names) : '';
                    $lists[$key]['role_names'] = $role_names ? $role_names : '';
                    $lists[$key]['role_count'] = count($role_ids);
//                    if(count($role_names) >= 5){
//                        $lists[$key]['role_names'] .= '，等角色';
//                    }
                }

                foreach (ContractService::$labelName as $item){
                    $lists[$key][$item] = json_decode($vo[$item],1);
                }
                $lists[$key]['label_custom'] = $vo['label_custom'] ? json_decode($vo['label_custom'],1) : [];
            }
        }
        return ['total' => $total, 'list' => $lists,'menus' => $menusSet];
    }

    public function monitorGroupDetail($id,$input, $own){
        if(!$data = app($this->contractTypeMonitorPermissionRepository)->getDetail($id)){
            return ['code' => ['no_data','common']];
        };

//        if($data['type_id'] == 'all'){
//            $data['type_id'] = array_column(app($this->contractTypeRepository)->getLists([ 'fields' => ['id']])->toArray(),'id');
//        }
        foreach (ContractService::$labelName as $item){
            if(isset($data[$item])){
                $data[$item] = json_decode($data[$item],1);
            }
        }
        $data['label_custom'] = $data['label_custom'] ? json_decode($data['label_custom'],1) : '';
        return $data;
    }

    public function monitorGroupEdit($id,$input, $own){
        if(!$data = app($this->contractTypeMonitorPermissionRepository)->getDetail($id)){
            return ['code' => ['no_data','common']];
        };
        $data = $this->parseRelationFields($input,'user');
        return app($this->contractTypeMonitorPermissionRepository)->updateData($data,['id' => $id]);
    }

    public function monitorAddGroup($params){
        $data = $this->parseRelationFields($params,'user');
        return app($this->contractTypeMonitorPermissionRepository)->insertData($data);
    }

    public function monitorGroupDelete($id,$input, $own){
        if(!$data = app($this->contractTypeMonitorPermissionRepository)->getDetail($id)){
            return ['code' => ['no_data','common']];
        };
        return app($this->contractTypeMonitorPermissionRepository)->reallyDeleteByWhere(['id'=>[$id]]);
    }
}
