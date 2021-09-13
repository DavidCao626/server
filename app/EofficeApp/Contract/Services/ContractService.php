<?php
namespace App\EofficeApp\Contract\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Contract\Repositories\ContractOrderRepository;
use App\EofficeApp\Contract\Repositories\ContractRemindRepository;
use App\EofficeApp\Contract\Repositories\ContractTypeRepository;
use App\EofficeApp\Contract\Repositories\ContractProjectRepository;
use App\EofficeApp\Contract\Repositories\ContractProjectProjectRepository;
use App\EofficeApp\Contract\Repositories\ContractFlowRepository;
use App\EofficeApp\Contract\Repositories\ContractShareRepository;
use App\EofficeApp\Contract\Repositories\ContractRepository;
use App\EofficeApp\FormModeling\Repositories\FormModelingRepository;
use App\EofficeApp\User\Services\UserService;

use DB;
use Illuminate\Support\Facades\Redis;
use Eoffice;
use Illuminate\Support\Arr;
use App\EofficeApp\LogCenter\Facades\LogCenter;

/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractService extends BaseService
{

    const SELECT_FIELD          = 1001;
    const PAY_WAY_SELECT_FIELD  = 1002;
    const PAY_TYPE_SELECT_FIELD = 1101;
    const CUSTOM_TABLE_KEY    = 'contract_t';

    const CONTRACT_PROJECT_TYPE = "CONTRACT_PROJECT_TYPE";

    private static $focusFields = ['number','title','target_name','money','a_user','b_user','a_address','b_address',
        'a_linkman','b_linkman','a_phone','b_phone','a_sign','b_sign','a_sign_time','b_sign_time','remarks',
        'content'];

    public static $labelName = ['label_basic_info','label_text','label_child_contract','label_project','label_order','label_run','label_remind','label_attachment'];

    private static $customerSelectFieldValue = ['type_id'];

    private static $contractNumberRedisKey = 'contract:contract_number_';

    private $contractRepository;
    private $contractOrderRepository;
    private $contractRemindRepository;
    private $contractProjectRepository;
    private $contractFlowRepository;
    private $contractTypeRepository;
    private $attachmentService;
    private $userMenuService;
    private $formModelingService;
    private $systemComboboxFieldRepository;
    private $contractTypeService;
    private $userService;
    private $contractTypePermissionRepository;
    private $contractTypeMonitorPermissionRepository;

    public function __construct()
    {
        parent::__construct();
        $this->contractRepository        = 'App\EofficeApp\Contract\Repositories\ContractRepository';
        $this->contractOrderRepository   = 'App\EofficeApp\Contract\Repositories\ContractOrderRepository';
        $this->contractRemindRepository  = 'App\EofficeApp\Contract\Repositories\ContractRemindRepository';
        $this->contractProjectRepository = 'App\EofficeApp\Contract\Repositories\ContractProjectRepository';
        $this->contractProjectProjectRepository = 'App\EofficeApp\Contract\Repositories\contractProjectProjectRepository';
        $this->contractFlowRepository    = 'App\EofficeApp\Contract\Repositories\ContractFlowRepository';
        $this->attachmentService         = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->systemComboboxService     = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->userMenuService           = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->contractTypeRepository    = 'App\EofficeApp\Contract\Repositories\ContractTypeRepository';
        $this->formModelingService       = 'App\EofficeApp\FormModeling\Services\FormModelingService';
        $this->userRepository            = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->systemComboboxFieldRepository = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxFieldRepository';
        $this->contractTypeService       = 'App\EofficeApp\Contract\Services\ContractTypeService';
        $this->userService               = 'App\EofficeApp\User\Services\UserService';
        $this->contractTypePermissionRepository = 'App\EofficeApp\Contract\Repositories\ContractTypePermissionRepository';
        $this->departmentDirectorRepository = "App\EofficeApp\System\Department\Repositories\DepartmentDirectorRepository";
        $this->contractTypeMonitorPermissionRepository = 'App\EofficeApp\Contract\Repositories\ContractTypeMonitorPermissionRepository';
        $this->logService                    = 'App\EofficeApp\System\Log\Services\LogService';
    }

    public function  contractLists($input, $own)
    {
        $params = $this->parseParams($input);
        list($search,$user_arr,$monitor) = $this->parseDatas($params,$own);
        if(!$search){
            return [];
        }
        $params['search'] = $search;
        $result = app($this->formModelingService)->getCustomDataLists($params, self::CUSTOM_TABLE_KEY, $own);
        if($result && isset($result['list'])){
            $type_ids = array_unique(array_column($result['list'],'raw_type_id'));
            $param = ['type_id' => $type_ids];
            $fieldsGroupLists = app($this->contractTypeService)->getPermissionLists($param,'fields');
            $monitorGroupLists = app($this->contractTypeService)->getPermissionLists($param,'monitor');
            // 根据id判断
            foreach ($result['list'] as $key => $vo){
                $result['list'][$key]->button = $this->getButtonPermission($vo,$user_arr,$own,$fieldsGroupLists,$monitorGroupLists);
            }
        }
        return $result;
    }

    // 我的合同列表
    public function contractMyLists($input, $own){
        $params = $this->parseParams($input);
        $ids = [];
        $multiSearchs = [];
        $result = isset($params['search']) ? $params['search'] : [];
        unset($result['user_id']);
        // 获取分享合同id
        $share_ids = self::getShareListId($own);
        // 跟进人不自己的
        $other_share_ids = ContractRepository::getOtherShareIds($share_ids,$own);

        if(isset($params['share'])){
            // 直接返回他人分享的合同
            $params['search'] = ['id' => [$share_ids,'in']];
            $result = app($this->formModelingService)->getCustomDataLists($params, self::CUSTOM_TABLE_KEY, $own);
            if($result && isset($result['list'])){
                $button = ['data_user_id'=>0,'data_edit'=>0,'data_delete'=>0,'data_share' => 0];
                foreach ($result['list'] as $key => $vo){
                    $result['list'][$key]->isShare = true;
                    $result['list'][$key]->button = $button;
                }
            }
            return $result;
        }
        if(!isset($params['follow'])){
            $ids = array_merge($ids,$share_ids);
        }

        // 获取草稿状态是自己创建的合同id
        if(isset($params['orSearch'])){
            $orSearch = json_decode($params['orSearch'],1);
            // 如果是我跟进的
            if(isset($params['follow'])){
                $orSearch['user_id'] = [$own['user_id'],'like'];
            }
            $orIds = app($this->contractRepository)->searchIds($orSearch);
            $ids = array_merge($ids,$orIds);
            unset($params['orSearch']);
        }

        // 获取有权限组的的id
        list($contractIds,$user_arr,$monitor) = $this->getHasPermission($own);
        $user_id_other = '';
        if(isset($result['user_id_other'])){
            $user_id_other = $result['user_id_other'];
            unset($result['user_id_other']);
        }
        // 获取我是跟进人的合同id

        $param = array_merge($result,['id' => [$contractIds, 'in']],['user_id'=>[$own['user_id'],'like']]);
        $contractIds = app($this->contractRepository)->searchIds($param);
        $ids = array_merge($ids,$contractIds);
        if(isset($result['id'])){
            if(is_array($result['id'][0])){
                $ids = array_intersect($ids,$result['id'][0]);
            }else{
                $ids = array_intersect($ids,[$result['id'][0]]);
            }
        }
        $result = array_merge($result,['id' => [$ids, 'in']]);
        if($user_id_other){
            $result['user_id'] = $user_id_other;
        }else{
            unset($result['user_id']);
        }
        $params['search'] = $result;
        $result = app($this->formModelingService)->getCustomDataLists($params, self::CUSTOM_TABLE_KEY, $own);

        if($result && isset($result['list'])){
            $type_ids = array_unique(array_column($result['list'],'raw_type_id'));
            $param = [
                'type_id' => $type_ids,
                'fields' => ['*']
            ];
            $fieldsGroupLists = app($this->contractTypeService)->getPermissionLists($param,'fields');
            $monitorGroupLists = app($this->contractTypeService)->getPermissionLists($param,'monitor');
            // 根据id判断
            foreach ($result['list'] as $key => $vo){
                $result['list'][$key]->button = $this->getButtonPermission($vo,$user_arr,$own,$fieldsGroupLists,$monitorGroupLists);
                if(in_array($vo->id,$other_share_ids)){
                    $result['list'][$key]->isShare = true;
                }
            }
        }
        return $result;
    }

    public function parseDatas($params,$own){
        if (isset($params['search']['multiSearch'])) {
            $multiSearchs = $params['search']['multiSearch'];
            unset($params['search']['multiSearch']);
        }
        $result = isset($params['search']) ? $params['search'] : [];
       
        $recycle = (isset($result['recycle_status']) && in_array(1,$result['recycle_status'])) ? 1 : 0;

        list($ids,$user_arr,$monitor) = $this->getHasPermission($own,[],$recycle);
        if(empty($ids)){
            return [[],[],[]];
        }

        if(!isset($result['recycle_status'])){
            $result['recycle_status'] = [0];
        }

        if (isset($params['showAll'])) {
            unset($params['showAll']);
        }
        if (!empty($multiSearchs)) {

            $nIds = app($this->contractRepository)->multiSearchIds($multiSearchs,$result);
            $nIds = array_intersect($nIds,$ids);
            $result['id'] = [$nIds, 'in'];
        }else{
            // 处理合同选择器，单选，多选,过滤出没有权限的合同
            if(isset($result['id']) && $result['id']){
                $ids = (is_array($result['id'][0])) ? array_intersect($result['id'][0],$ids) : array_intersect([$result['id'][0]],$ids);
            }
            $result['id'] = [$ids, 'in'];
        }
        return [$result,$user_arr,$monitor];
    }

    public function getButtonPermission($data,$user_arr,$own,$fieldsGroupLists,$monitorGroupLists){
        $edit = $delete = $changeUser = $share = 0;
        if($fieldsGroupLists){
            // 先根据数据权限组判断操作权限
            foreach ($fieldsGroupLists as $key => $vo){
                if($vo['relation_fields']){
                    $pril = isset($user_arr[$vo['id']]) ? $user_arr[$vo['id']] : [];
                    if($vo['own'] || $vo['superior'] || $vo['all_superior'] || $vo['department_header'] ||
                        $vo['department_person'] || $vo['role_person']){
                        $exists = array_intersect(explode(',',$data->{'raw_'.$vo['relation_fields']}),$pril);
                        $exists_type = false;
                        if($vo['type_id'] == 'all'){
                            $exists_type = true;
                        }else{
                            $type_ids = explode(',',$vo['type_id']);
                            if(in_array($data->raw_type_id,$type_ids)){
                                $exists_type = true;
                            };
                        }
                        if($exists && $exists_type){
                            if($vo['all_privileges']){
                                $changeUser = $edit = $delete = $share = 1;
                            }
                            if($vo['data_edit']){
                                $edit = 1;
                            }
                            if($vo['data_delete']){
                                $delete = 1;
                            }
                            if($vo['data_user_id']){
                                $changeUser = 1;
                            }
                            if($vo['data_share']){
                                $share = 1;
                            }
                        }
                    }
                }
            }
        };
        // 再根据监控权限组判断操作权限
        if($monitorGroupLists){
            foreach ($monitorGroupLists as $ks => $item){
                $exists_type = false;
                if($item['type_id'] == 'all'){
                    $exists_type = true;
                }else{
                    $type_ids = explode(',',$item['type_id']);
                    if(in_array($data->raw_type_id,$type_ids)){
                        $exists_type = true;
                    };
                }
                // 如果在对应的选择分类内
                if($exists_type){
                    if($item['all_user']){
                        if($item['all_privileges']){
                            $changeUser = $edit = $delete = $share = 1;
                        }
                        if($item['data_edit']){
                            $edit = 1;
                        }
                        if($item['data_delete']){
                            $delete = 1;
                        }
                        if($item['data_user_id']){
                            $changeUser = 1;
                        }
                        if($item['data_share']){
                            $share = 1;
                        }
                    }else{
                        if($item['user_ids']){
                            if(in_array($own['user_id'],explode(',',$item['user_ids']))){
                                if($item['all_privileges']){
                                    $changeUser = $edit = $delete = $share = 1;
                                }
                                if($item['data_edit']){
                                    $edit = 1;
                                }
                                if($item['data_delete']){
                                    $delete = 1;
                                }
                                if($item['data_user_id']){
                                    $changeUser = 1;
                                }
                                if($item['data_share']){
                                    $share = 1;
                                }
                            }
                        }
                        if($item['dept_ids']){
                            if(in_array($own['dept_id'],explode(',',$item['dept_ids']))){
                                if($item['all_privileges']){
                                    $changeUser = $edit = $delete = $share = 1;
                                }
                                if($item['data_edit']){
                                    $edit = 1;
                                }
                                if($item['data_delete']){
                                    $delete = 1;
                                }
                                if($item['data_user_id']){
                                    $changeUser = 1;
                                }
                                if($item['data_share']){
                                    $share = 1;
                                }
                            }
                        }
                        if($item['role_ids']){
                            if(array_intersect($own['role_id'],explode(',',$item['role_ids']))){
                                if($item['all_privileges']){
                                    $changeUser = $edit = $delete = $share = 1;
                                }
                                if($item['data_edit']){
                                    $edit = 1;
                                }
                                if($item['data_delete']){
                                    $delete = 1;
                                }
                                if($item['data_user_id']){
                                    $changeUser = 1;
                                }
                                if($item['data_share']){
                                    $share = 1;
                                }
                            }
                        }
                    }
                }

            }
        }
        return ['data_user_id'=>$changeUser,'data_edit'=>$edit,'data_delete'=>$delete,'data_share' => $share];
    }

    public function getTypePermission($type_ids){
        $param = [
            'type_id' => $type_ids,
        ];
        $fieldsGroupLists = app($this->contractTypeService)->getPermissionLists($param,'fields');

        $monitorGroupLists = app($this->contractTypeService)->getPermissionLists($param,'monitor');
        // 将两个数组加到一起，统一处理数据操作权限
        $result = array_merge_recursive($fieldsGroupLists,$monitorGroupLists);
        $changeUserTypeIds = $editTypeIds = $deleteTypeIds = $viewTypeIds = $shareTypeIds = [];
        foreach ($result as $key => $vo){
            $type_id = ($vo['type_id'] == 'all') ? array_column(app($this->contractTypeRepository)->getLists([ 'fields' => ['id']])->toArray(),'id') :
                array_map('intval', explode(',', $vo['type_id']));
            if($vo['all_privileges']){
                $changeUserTypeIds = array_merge($changeUserTypeIds,$type_id);
                $editTypeIds = array_merge($editTypeIds,$type_id);
                $deleteTypeIds = array_merge($deleteTypeIds,$type_id);
                $viewTypeIds = array_merge($viewTypeIds,$type_id);
                $shareTypeIds = array_merge($shareTypeIds,$type_id);
                continue;
            }
            if($vo['data_user_id']){
                $changeUserTypeIds = array_merge($changeUserTypeIds,$type_id);
            }
            if($vo['data_edit']){
                $editTypeIds = array_merge($editTypeIds,$type_id);
            }
            if($vo['data_delete']){
                $deleteTypeIds = array_merge($deleteTypeIds,$type_id);
            }
            if($vo['data_view']){
                $viewTypeIds = array_merge($viewTypeIds,$type_id);
            }
            if($vo['data_share']){
                $shareTypeIds = array_merge($shareTypeIds,$type_id);
            }
        }
        $changeUserTypeIds = array_unique($changeUserTypeIds);
        $editTypeIds = array_unique($editTypeIds);
        $deleteTypeIds = array_unique($deleteTypeIds);
        $viewTypeIds = array_unique($viewTypeIds);
        $shareTypeIds = array_unique($shareTypeIds);
        sort($changeUserTypeIds);
        sort($editTypeIds);
        sort($deleteTypeIds);
        sort($viewTypeIds);
        sort($shareTypeIds);
        return [$changeUserTypeIds,$editTypeIds,$deleteTypeIds,$viewTypeIds,$shareTypeIds];
    }

    public function getShareIds($own){
        // 获取分享合同id
        $share_ids = self::getShareListId($own);
        // 去除跟进人是自己的
        $share_ids = ContractRepository::getOtherShareIds($share_ids,$own);
        return $share_ids;
    }

    private function validateStoreContract(&$contract, $contractChilds)
    {
        if (isset($contract['title']) && !$contract['title']) {
            return ['code' => ['0x066005', 'contract']];
        }
        if (isset($contract['type_id']) && !$contract['type_id']) {
            return ['code' => ['0x066008', 'contract']];
        }
        if (isset($contract['id']) && $contract['id'] && in_array($contract['id'], $contractChilds)) {
            return ['code' => ['0x066006', 'contract']];
        }
        if (isset($contract['main_id']) && $contract['main_id']) {
            if(in_array($contract['main_id'], $contractChilds)){
                return ['code' => ['0x066006', 'contract']];
            }
            // 主合同只能是正式合同，非草稿合同
            if($res = app($this->contractRepository)->getDetail($contract['main_id'])){
                if($res['status'] != 1){
                    return ['code' => ['err_contract_status', 'contract']];
                }
            };
        }
        if (isset($contract['money'])) {
            $contract['money']    = round($contract['money'], 2);
            $contract['money_cp'] = $contract['money'] ?? 0;
            if($length = $this->sctonum($contract['money'],2)){
                if($nums = explode('.',$length)){
                    if(strlen($nums[0]) > 18){
                        return ['code' => ['error_money_length', 'contract']];
                    };
                };
            };
        }
        return true;
    }



    /**
     * @param $num        科学计数法字符串  如 2.1E-5
     * @param int $double 小数点保留位数 默认5位
     * @return string
     */
    private  function sctonum($num, $double = 5){
        if(false !== stripos($num, "e")){
            $a = explode("e",strtolower($num));
            return bcmul($a[0], bcpow(10, $a[1], $double), $double);
        }
        return '';
    }


    /**
     * 添加合同
     * @param  array $input
     * @param  int $user_id
     * @return object
     */
    public function storeContract($input, $user_id = '')
    {
        $contract            = isset($input['contract']) ? $input['contract'] : [];
        $contractProject     = isset($input['contractProject']) ? $input['contractProject'] : [];
        $contractOrder       = isset($input['contractOrder']) ? $input['contractOrder'] : [];
        $contractRemind      = isset($input['contractRemind']) ? $input['contractRemind'] : [];
        $contractFlow        = isset($input['contractFlow']) ? $input['contractFlow'] : [];
        $contractAttachments = isset($input['contractAttachments']) ? $input['contractAttachments'] : [];
        $contractChilds      = isset($input['childContracts']) ? $input['childContracts'] : [];
        $validateResult      = $this->validateStoreContract($contract, $contractChilds);
        if (isset($validateResult['code'])) {
            return $validateResult;
        }
        if (isset($contract['attachments'])) {
            $attachments = $contract['attachments'];
            unset($contract['attachments']);
        }

        $number = isset($contract['number']) ? $contract['number'] : '';
        $flag = ($number == '') ? true : false;
        if (!$contract['number'] = $this->checkNumber($number)) {
            return ['code' => ['0x066003', 'contract']];
        }
        if(!empty($contract['user_id'])){
            if(is_array($contract['user_id'])){
                $contract['user_id'] = implode(',',$contract['user_id']);
            }
        }else{
            $contract['user_id'] = $user_id;
        }
        if(!empty($contract['child_contracts'])){
            $contractChilds = $contract['child_contracts'];
        }
        $result              = app($this->formModelingService)->addCustomData($contract, 'contract_t');
        if (!$result) {
            return ['code' => ['0x000006', 'common']];
        }
        if (isset($result['code'])) {
            if($result['code'][0] == '0x016031'){
                if($flag){
                    if (Redis::exists(self::$contractNumberRedisKey . date('Ymd', time()))) {
                        Redis::decr(self::$contractNumberRedisKey . date('Ymd', time()));
                    }
                }
            }
            return $result;
        }
        if (!empty($attachments)) {
            $this->updateAttachments($result, $attachments);
        }

        $updateData = [];
        if (isset($contract['content']) && !empty($contract['content'])) {
            $updateData['content'] = $contract['content'];
        }
        if (isset($contract['status'])) {
            if($contract['status'] === ''){
                $updateData['status'] = 1;
            }else{
                $updateData['status'] = $contract['status'];
            }
        }
        if (!empty($updateData)) {
            DB::table('contract_t')->where('id', $result)->update($updateData);
        }

        // 结算记录
        if($contractProject){
            $contractProject['contract_t_id'] = $result;
            app($this->formModelingService)->addCustomData($contractProject, 'contract_t_project');
        }
//        // 订单记录
        if($contractOrder){
            $contractOrder['contract_t_id'] = $result;
            app($this->formModelingService)->addCustomData($contractOrder, 'contract_t_order');
        }
//        // 提醒记录
        if($contractRemind){
            $contractRemind['contract_t_id'] = $result;
            app($this->formModelingService)->addCustomData($contractRemind, 'contract_t_remind');
        }
        if (!empty($attachments)) {
            $this->updateAttachments($result, $attachments);
        }
        // 子合同
        if (!empty($contractChilds)) {
            DB::table('contract_t')->whereIn('id', $contractChilds)->where('main_id', 0)->update([
                'main_id' => $result,
            ]);
        }
        app($this->contractFlowRepository)->insertManyData($contractFlow, $result);
        return $result;
    }

    public function storeProject($table,$input, $own){
        if($table == 'contract_t_project'){
            $result = app($this->formModelingService)->addCustomData($input, 'contract_t_project');
        }else if($table == 'contract_t_order'){
            $result = app($this->formModelingService)->addCustomData($input, 'contract_t_order');
        }else if($table == 'contract_t_remind'){
            $result = app($this->formModelingService)->addCustomData($input, 'contract_t_remind');
        }
        return $result;
    }
    private function updateAttachments($id, $attachmentIds)
    {
        $table       = 'attachment_relataion_contract_t';
        $originLists = DB::table($table)->select(['attachment_id', 'entity_column'])->where('entity_id', $id)->get();
        $originIds   = $newIds   = [];
        if (!$originLists->isEmpty()) {
            foreach ($originLists as $key => $item) {
                if (!$item->entity_column) {
                    $originIds[] = $item->attachment_id;
                }
            }
        }
        $deleteIds = array_diff($originIds, $attachmentIds);
        $newIds    = array_diff($attachmentIds, $originIds);
        if (!empty($deleteIds)) {
            DB::table($table)->where('entity_id', $id)->whereIn('attachment_id', $deleteIds)->delete();
        }
        if (empty($newIds)) {
            return true;
        }
        $insertData = [];
        foreach ($newIds as $newId) {
            $insertData[] = [
                'entity_id'     => $id,
                'attachment_id' => $newId,
            ];
        }
        return DB::table($table)->insert($insertData);
    }

    private function checkNumber($number, $id = '')
    {
        while (!$number) {
            $number   = 'HT' . date('Ymd', time()) . $this->getNumber();
            $tempList = DB::table('contract_t')->where('number', $number)->whereNull('deleted_at')->first();
            if (empty($tempList)) {
                break;
            }
            $number = '';
        }
        $query = DB::table('contract_t')->where('number', $number);
        if ($id) {
            $query = $query->where('id', '!=', $id);
        }
        $tempList = $query->whereNull('deleted_at')->first();
        if (!empty($tempList)) {
            return false;
        }
        return $number;
    }

    /**
     * 更新合同信息
     * @param  int $id    合同id
     * @param  array $input 数据
     * @return boolean
     */
    public function updateContract($id, $input, $own)
    {
        $list = app($this->contractRepository)->getDetail($id);
        if (empty($list)) {
            return ['code' => ['0x000006', 'common']];
        }

        $contract        = isset($input['contract']) ? $input['contract'] : [];
        $contractProject = isset($input['contractProject']) ? $input['contractProject'] : [];
        $contractOrder   = isset($input['contractOrder']) ? $input['contractOrder'] : [];
        $contractRemind  = isset($input['contractRemind']) ? $input['contractRemind'] : [];
        $contractFlow    = isset($input['contractFlow']) ? $input['contractFlow'] : [];
        $contractChilds  = isset($input['childContracts']) ? $input['childContracts'] : [];
        $basiEdit = $textEdit = $childEdit = $projectEdit = $orderEdit = $runEdit = $remindEdit = $attachmentEdit = $attachmentDownload = true;
        // 非草稿合同走权限
        if(!(!$list->status && $list->creator && $list->creator == $own['user_id'])){
            // 验证是否有查看权限
            list($ids,$user_arr,$monitor) = $this->getHasPermission($own,['type_id'=>[$list->type_id]]);
            if(!$ids || !in_array($id,$ids)){
                return ['code' => ['0x000017', 'common']];

            }
            // 标签编辑权限
            list(
                $basiEdit,
                $textEdit,
                $childEdit,
                $projectEdit,
                $orderEdit,
                $runEdit,
                $remindEdit,
                $attachmentEdit,
                $attachmentDownload
                ) = $this->groupPermission($list,'edit',$user_arr,$own);
        }
        if(isset($contract['number'])){
            $number          = $contract['number'] ?? '';
            if (!$contract['number'] = $this->checkNumber($number, $id)) {
                return ['code' => ['0x066003', 'contract']];
            }
        }

        if(isset($contract['user_id']) && !$contract['user_id']){
            return ['code' => ['user_not_empty', 'contract']];
        }

        $validateResult = $this->validateStoreContract($contract, $contractChilds);
        if (isset($validateResult['code'])) {
            return $validateResult;
        }
        // 验证附件编辑权限
        if($attachmentEdit){
            if(isset($contract['attachments'])){
                // 更新附件
                $attachments = $contract['attachments'] ?? [];
                $this->updateAttachments($id, $attachments);
            }
        }
        /**
         * 验证基本信息编辑权限
         */
        if($basiEdit){
            unset($contract['id']);
            unset($contract['attachments']);
            if ($updateResult = app($this->formModelingService)->editCustomData($contract, 'contract_t', $id)) {
                if(isset($updateResult['code'])){
                    return $updateResult;
                }
                $this->saveUpdateLogs($list->toArray(),$contract,$own['user_id']);
            }
        }else{
            if(isset($contract['outsourceForEdit'])){
                return ['code' => ['not_to_edit', 'contract']];
            }
        }

        /**
         * 验证合同正文编辑权限
         */
        $newDatas = [];
        if($textEdit){
            if(isset($contract['content'])){
                if (isset($list['content']) && $list['content'] !== $contract['content']) {
                    $newDatas['content'] = $contract['content'];
                }
            }
        }

        if($list['status'] != 1 && isset($contract['status'])){
            if (isset($list['status'])  && $list['status'] != $contract['status']) {
                $newDatas['status'] = $contract['status'];
            }
        }
        if (!empty($newDatas)) {
            DB::table('contract_t')->where('id', $id)->update($newDatas);
        }

        /**
         * 验证子合同的编辑权限
         */
        if($childEdit){
            isset($input['childContracts']) && app($this->contractRepository)->refreshChilds($id, $contractChilds);
        }


        /**
         * 验证结算情况的编辑权限
         */
        if($projectEdit){
            // 更新结算表
            if($contractProject){
                if(isset($contractProject['project_id']) && $contractProject['project_id']){
                    $updateResult = app($this->formModelingService)->editCustomData($contractProject, 'contract_t_project', $contractProject['project_id']);

                }else if($contractProject['project']){
                    $contractProject['contract_t_id'] = $id;
                    $updateResult = app($this->formModelingService)->addCustomData($contractProject, 'contract_t_project');
                }
                if(isset($updateResult['code'])){
                    return $updateResult;
                }
            }
        }

        /**
         * 验证订单的编辑权限
         */
        if($orderEdit){
            // 更新订单表
            if($contractOrder){
                if(isset($contractOrder['order_id']) && $contractOrder['order_id']){
                    $updateResult = app($this->formModelingService)->editCustomData($contractOrder, 'contract_t_order', $contractOrder['order_id']);
                }else if($contractOrder['order']){
                    $contractOrder['contract_t_id'] = $id;
                    $updateResult = app($this->formModelingService)->addCustomData($contractOrder, 'contract_t_order');
                }
                if(isset($updateResult['code'])){
                    return $updateResult;
                }
            }
        }

        /**
         * 验证提醒计划的编辑权限
         */
        if($remindEdit){
            // 更新提醒计划表
            if($contractRemind){
                if(isset($contractRemind['remind_id']) && $contractRemind['remind_id']){
                    $updateResult = app($this->formModelingService)->editCustomData($contractRemind, 'contract_t_remind', $contractRemind['remind_id']);
                }else if($contractRemind['remind']){
                    $contractRemind['contract_t_id'] = $id;
                    $updateResult = app($this->formModelingService)->addCustomData($contractRemind, 'contract_t_remind');
                }
                if(isset($updateResult['code'])){
                    return $updateResult;
                }
            }
        }

        /**
         * 验证相关流程的编辑权限
         */
        if($runEdit){
//            $deleteFlows = isset($input['isMobile']) ? false : true;
            isset($input['contractFlow']) && app($this->contractFlowRepository)->updateContract($id, $contractFlow, true);
        }
        return true;
    }

    /**
     * 删除合同
     * @param  int $id 合同id
     * @return boolean
     */
    public function deleteContract($id, $own)
    {
        $ids = array_filter(explode(',', $id));
        $validate = app($this->contractRepository)->checkDelete($ids, true);
        if (isset($validate['code'])) {
            return $validate;
        }
        $tempData = [];
        foreach ($ids as $id){
            $contractObj = app($this->contractRepository)->getDetail($id);
            $tempData[$contractObj->id] = $contractObj->title;
        }
        app($this->contractRepository)->deleteByWhere(['id' => [$ids, 'in']]);
        app($this->contractProjectRepository)->deleteByWhere(['contract_t_id' => [$ids, 'in']]);
        app($this->contractOrderRepository)->deleteByWhere(['contract_t_id' => [$ids, 'in']]);
        app($this->contractRemindRepository)->deleteByWhere(['contract_t_id' => [$ids, 'in']]);
        app($this->contractFlowRepository)->deleteByWhere(['contract_t_id' => [$ids, 'in']]);
        $identify = 'contract.contract_info.destroy';
        if($ids && $tempData){
            foreach ($ids as $key => $vo){
                $logContent = $tempData[$vo] ?? '';
                self::saveLogs($vo,trans('contract.recycle_contract').'：'.$logContent,$own['user_id'],$identify,$logContent);
            }
        }
        return true;
    }

    /**
     * 加入回收站
     * @param  int $id 合同id
     * @return boolean
     */
    public function recycleContract($id, $own)
    {
        $ids = explode(',',trim($id,','));

        $validate = app($this->contractRepository)->checkDelete($ids);
        if (isset($validate['code'])) {
            return $validate;
        }

        $tempData = [];
        // 获取修改合同的数据
        $params = [
            'search' => ['id' => [$ids,'in']],
            'recycle_status' => [0]
        ];
        $result = app($this->formModelingService)->getCustomDataLists($params, self::CUSTOM_TABLE_KEY, $own);
        if(empty($result['list'])){
            return ['code' => ['already_delete', 'contract']];
        }
        $type_ids = array_column($result['list'],'raw_type_id');
        list($contractIds,$user_arr,$monitor) = $this->getHasPermission($own,['type_id'=>$type_ids]);
        // 获取删除合同所属的权限组
        $param = ['type_id' => $type_ids];
        $fieldsGroupLists = app($this->contractTypeService)->getPermissionLists($param,'fields');
        $monitorGroupLists = app($this->contractTypeService)->getPermissionLists($param,'monitor');

        try{
            foreach ($result['list'] as $vo){
                // 草稿合同不验证权限(创建人是本人),直接可以删除，不进回收站
                if(!$vo->raw_status && $vo->raw_creator && $vo->raw_creator == $own['user_id']){
                    app($this->contractRepository)->updateData(['recycle_status' => 1], ['id' => [[$vo->id], 'in']]);
                    $this->deleteContract($vo->id,$own);
                    $tempData[$vo->id] = $vo->title;
                    continue;
                }
                // 验证是否有查看权限
                if(!$contractIds || !in_array($vo->id,$contractIds)){
                    return ['code' => ['0x000017', 'common']];
                }
                $flag = $this->getButtonPermission($vo,$user_arr,$own,$fieldsGroupLists,$monitorGroupLists);
                if(!$flag || ($flag && !$flag['data_delete'])){
                    return ['code' => ['not_to_delete', 'contract']];
                }
                $tempData[$vo->id] = $vo->title;
            }
        }catch (\Exception $e){
            return false;
        }

        $data = ['recycle_status' => 1];
        $result = app($this->contractRepository)->updateData($data, ['id' => [$ids, 'in']]);
        $identify = 'contract.contract_info.delete';
        if($ids){
            foreach ($ids as $key => $vo){
                $logContent = $tempData[$vo] ?? '';
                self::saveLogs($vo,trans('contract.delete_contract').'：'.$logContent,$own['user_id'],$identify,$logContent);
            }
        }
        return $result;
    }

    /**
     * 恢复
     * @param  int $id 合同id
     * @return boolean
     */
    public function recoverContract($id,$input)
    {
        $ids = explode(',',trim($id,','));
        $sting_title = '';
        $unique = false;
        // 判断是否需要唯一性检查
        $result = contractRepository::checkUnique(['field_table_key'=>'contract_t','field_code'=>'title']);
        if(!$result){
            return false;
        }
        $field_options = json_decode($result->field_options,true);

        if(isset($field_options['validate']['unique'])){
            $unique = true;
        }
        try{
            foreach ($ids as $id){
                $list = app($this->contractRepository)->getDetail($id);
                if (empty($list) || !$list->recycle_status) {
                    return ['code' => ['0x000006', 'common']];
                }
                $title = $input ? $input['title'] : $list->title;
                $tempData[$id] = $list->title;
                if($unique){
                    if($data = contractRepository::checkRepeatTitle($title,$id)){
                        $sting_title .= $data->title .',';
                    };
                }
            }
        }catch (\Exception $e){
            return false;
        }
        if($sting_title){
            return trim($sting_title,',');
        }
        $data = ['recycle_status' => 0];
        if($input){
            $data['title'] = $input['title'];
        }
        $result = app($this->contractRepository)->updateData($data, ['id' => [$ids, 'in']]);
        $identify = 'contract.contract_info.recover';
        foreach ($ids as $key =>$vo){
            $logContent = $tempData[$vo] ?? '';
            self::saveLogs($vo,trans('contract.recover_contract').'：'.$logContent,own()['user_id'],$identify,$logContent);
        }
        return $result;

    }

    /**
     * 改变跟进人
     * @param  int $id      合同id
     * @param  array $data
     * @return boolean
     */
    public function modifyRelation($id, $data, $own)
    {
        $ids = explode(',',trim($id,','));
        $upData['user_id'] = implode(',',$data['user_id']);

        $params = [
            'search' => ['id' => [$ids,'in']],
            'recycle_status' => [0]
        ];
        $result = app($this->formModelingService)->getCustomDataLists($params, self::CUSTOM_TABLE_KEY, $own);
        if(empty($result['list'])){
            return ['code' => ['0x000017', 'common']];
        }
        $type_ids = array_column($result['list'],'raw_type_id');

        list($contractIds,$user_arr,$monitor) = $this->getHasPermission($own,['type_id'=>$type_ids]);
        $param = ['type_id' => $type_ids];
        $fieldsGroupLists = app($this->contractTypeService)->getPermissionLists($param,'fields');
        $monitorGroupLists = app($this->contractTypeService)->getPermissionLists($param,'monitor');
        $tempName = [];
        foreach ($result['list'] as $vo){
            if (empty($vo) || $vo->recycle_status) {
                return ['code' => ['0x000006', 'common']];
            }
            // 验证是否有查看权限
            if(!$contractIds || !in_array($vo->id,$contractIds)){
                return ['code' => ['0x000017', 'common']];
            }
            $flag = $this->getButtonPermission($vo,$user_arr,$own,$fieldsGroupLists,$monitorGroupLists);
            if(!$flag || ($flag && !$flag['data_user_id'])){
                return ['code' => ['0x000017', 'common']];
            }
            $oldUserId = explode(',',$vo->raw_user_id);
            // 跟进人有变化才进行记录
            if(array_diff($oldUserId,$data['user_id']) || array_diff($data['user_id'],$oldUserId)){
                $tempName[$vo->id] = ['new_user_id' => get_user_simple_attr($upData['user_id']),'old_user_id'=>get_user_simple_attr($oldUserId), 'title' => $vo->title];
            }
        }
        contractRepository::batchUpdateData($ids,$upData);
        $identify = 'contract.contract_info.edit';
        if($tempName){
            foreach ($tempName as $ks => $item){
                $logContent = trans('contract.edit_contract').'：'. $item['title'] .' '.trans('contract.follow_up_person').'：'. $item['old_user_id'] ."-><span style='color:#E46D0A;'>" . $item['new_user_id'] . "</span>";;
                self::saveLogs($ks,$logContent,$own['user_id'],$identify,$item['title']);
            }
        }
        return true;
    }

    private function hasPermission($list, $own)
    {
        $listId = self::getShareListId($own);
        $userId = isset($own['user_id']) ? $own['user_id'] : 0;
        $list->user_id = is_array($list->user_id) ? $list->user_id : explode(',',$list->user_id);
        $hasMenuArr = app($this->userMenuService)->getUserMenus($userId);
        $typeIds = app($this->contractTypeRepository)->getHasPermissionIdLists($own);
        if (in_array(151, $hasMenuArr['menu']) && (in_array($list->type_id, $typeIds)) || in_array($list->id,$listId)) {
            return true;
        } else if (in_array(152, $hasMenuArr['menu']) && (in_array($userId, $list->user_id) || in_array($list->id,$listId))) {
            return true;
        }
        return false;
    }


    public function getContractPermission($data,$own,$label){

        // 获取分享id集合
        $listId = self::getShareListId($own);
        $userId = isset($own['user_id']) ? $own['user_id'] : 0;
        $hasMenuArr = app($this->userMenuService)->getUserMenus($userId);
        // 合同管理/我的合同菜单权限
        if(array_intersect([151,152],$hasMenuArr['menu'])){
            if($permission = $this->getTypePermissionDetail($data->type_id)){
                if($permission['all_privileges'] || $permission['data_view'] || in_array($data->id,$listId)){
                    return true;
                }

            }

        }
        return ['code' => ['0x000017', 'common']];




        if($permission['all_privileges']){
            return true;
        }
        if(!$permission['data_view']){
            return ['code' => ['0x000017', 'common']];
        }

        switch ($label){
            case 'basic_info':
                if(!$permission['label_basic_info']){
                    // 无权限
                    return ['code' => ['0x000017', 'common']];
                }
                break;
            case 'project':
                if(!$permission['label_project']['view']){
                    // 无权限
                    return ['code' => ['0x000017', 'common']];
                }
                break;
            case 'order':
                if(!$permission['label_order']['view']){
                    // 无权限
                    return ['code' => ['0x000017', 'common']];
                }
                break;
            default:
                break;
        }



        return true;
    }

    /**
     * 详情
     * @param  int $id 合同id
     * @return array
     */
    public function showContract($id, $own,$input = [])
    {
        $hasMenuArr = app($this->userMenuService)->getUserMenus($own['user_id']);
        if(!array_intersect([151,152],$hasMenuArr['menu'])){
            return ['code' => ['error_view', 'contract']];
        }
        if (!$contractObj = app($this->contractRepository)->getDetail($id)) {
            return ['code' => ['already_delete', 'contract']];
        }
        $basicView = $textView = $childView = $projectView = $orderView = $runView = $remindView = $attachmentView = $attachmentDownload = 1;
        $basicEdit = $textEdit = $childEdit = $projectEdit = $orderEdit = $runEdit = $remindEdit = $attachmentEdit = 1;
        // 草稿合同不走权限
        // 分享合同也不走权限
        $share_ids = self::getShareListId($own);
        $recycle = false;
        if(!(!$contractObj->status && $contractObj->creator && $contractObj->creator == $own['user_id'])){
            // 验证是否有查看权限
            if($contractObj->recycle_status){
                $recycle = true;
            }
            list($ids,$user_arr,$monitor) = $this->getHasPermission($own,['type_id'=>[$contractObj->type_id]],$recycle);
            if(!in_array($id,$share_ids)){
                if(!$ids || !in_array($id,$ids)){
                    return ['code' => ['0x000017', 'common']];
                }
            }
            // 标签查看权限
            list(
                $basicView,
                $textView,
                $childView,
                $projectView,
                $orderView,
                $runView,
                $remindView,
                $attachmentView,
                $attachmentDownload
                ) = $this->groupPermission($contractObj,'view',$user_arr,$own);
            // 标签编辑权限
            list(
                $basicEdit,
                $textEdit,
                $childEdit,
                $projectEdit,
                $orderEdit,
                $runEdit,
                $remindEdit,
                $attachmentEdit,
                $attachmentEditDownload
                ) = $this->groupPermission($contractObj,'edit',$user_arr,$own);
        }


        $customResult                 = app($this->formModelingService)->getCustomDataDetail(self::CUSTOM_TABLE_KEY, $id, $own);
        $result                       = $contractObj->toArray();
        $result                       = array_merge($result, (array) $customResult);
        /*
        $result['contract_type_name'] = $typeData ? $typeData->name : '';
        $result['contract_user_name'] = DB::table('user')->where('user_id', $result['user_id'])->value('user_name');
        */
        $result['contract_status']    = isset($result['status']) && $result['status'] ? trans('contract.has_submit') : trans('contract.the_draft');
        $result['flows']              = ContractFlowRepository::getRunIds($id);
        $project = $order = $remind = [];
        $result['permission'] = [
            'label_basic_info' => ['view'=>$basicView,'edit'=>$basicEdit],
            'label_text' => ['view'=>$textView,'edit'=>$textEdit],
            'label_child_contract' => ['view'=>$childView,'edit'=>$childEdit],
            'label_project' => ['view'=>$projectView,'edit'=>$projectEdit],
            'label_order' => ['view'=>$orderView,'edit'=>$orderEdit],
            'label_run' => ['view'=>$runView,'edit'=>$runEdit],
            'label_remind' => ['view'=>$remindView,'edit'=>$remindEdit],
            'label_attachment' => ['view'=>$attachmentView,'edit'=>$attachmentEdit,'download' =>$attachmentDownload]
        ];
        // 获取表对应的project_id
//        if($projectData = DB::table('contract_t_project')->where('contract_t_id',$id)->first()){
//            // 结算情况获取
//            $project = app($this->formModelingService)->getCustomDataDetail('contract_t_project', $projectData->project_id);
//        }
        // 解析打款方式 和 款项类别

        $result['projects'] = $project ? $project : null;
        if($project){
            app($this->contractProjectRepository)->parseResults(app($this->systemComboboxService), $result['projects']['project']);
        }
        // 获取表对应的order_id
//        if($orderData = DB::table('contract_t_order')->where('contract_t_id',$id)->first()){
//            // 订单获取
//            $order = app($this->formModelingService)->getCustomDataDetail('contract_t_order', $orderData->order_id);
//        }
        $result['orders'] = $order ? $order : null;

        // 获取表对应的remind_id
//        if($remindData = DB::table('contract_t_remind')->where('contract_t_id',$id)->first()){
//            // 订单获取
//            $remind = app($this->formModelingService)->getCustomDataDetail('contract_t_remind', $remindData->remind_id);
//        }
        $result['reminds'] = $remind ? $remind : null;

        $result['main_id']  = $result['main_id'] == 0 ? '' : $result['main_id'];
        $result['user_id']  = $result['user_id'] ? (is_array($result['user_id']) ? $result['user_id'] : explode(',',$result['user_id'])) : [];

        $result['attachments'] = $this->getContractAttachments($id);
        // 子合同
        $childContracts = app($this->contractRepository)->getChildLists($id);
        $result['childContracts'] = array_column($childContracts->toArray(),'id');
        if(in_array($id,$share_ids)){
            $result['isShare'] = true;
        }
        if($input && isset($input['label'])){
            return $result;
        }
        $identify = 'contract.contract_info.view';
        self::saveLogs($id,trans('contract.view_contract').'：'.$result['title'],$own['user_id'],$identify,$result['title']);
        return $result;
    }

    public function contractStatistics($input,$id, $own){
        if (!$contractObj = app($this->contractRepository)->getDetail($id)) {
            return ['code' => ['already_delete', 'contract']];
        }

//        if(!$data = DB::table('contract_t_project')->where('contract_t_id',$id)->first()){
//            $contractObj->projects = [
//                'contract_t_id' => $id,
//                'project' => [],
//            ];
//            return $contractObj;
//        };
//        $project = app($this->formModelingService)->getCustomDataDetail('contract_t_project', $data->id);
//        if(isset($project['code'])){
//            return $project;
//        }
        $project = app($this->contractProjectRepository)->lists([$id]);
        $result                = $contractObj->toArray();
        $result['attachments'] = $this->getContractAttachments($id);
        $typeData = ContractTypeRepository::getTypeNameById($result['type_id'] ? $result['type_id'] : 0);
        $result['contract_type_name'] = $typeData ? $typeData->name : '';

        $result['contract_user_name'] = DB::table('user')->where('user_id', $result['user_id'])->value('user_name');
        $result['contract_status']    = isset($result['status']) && $result['status' ] ? trans('contract.has_submit') : trans('contract.the_draft');

        // 解析打款方式 和 款项类别
        $result['projects'] = $project ? $project : [];
        app($this->contractProjectRepository)->parseResults(app($this->systemComboboxService), $result['projects']);

        // 子合同
        $result['childContracts'] = app($this->contractRepository)->getChildLists($id);
//        $childProjects= [];
//        if($result['childContracts']){
//            $ids = array_column($result['childContracts']->toArray(),'id');
//            if($projectIds = DB::table('contract_t_project')->where('contract_t_id',$ids)->pluck('project_id')->toArray()){
//                foreach ($projectIds as $projectId){
//                    $project = app($this->formModelingService)->getCustomDataDetail('contract_t_project', $data->project_id);
//                    if(!isset($project['code'])){
//                        $childProjects[] = $project;
//                    }
//                }
//            };
//        }
//        $result['projects'] = array_merge($result['projects'],$childProjects);
        return $result;
    }


    public function contractOrder($input,$id, $own){
        if (!$contractObj = app($this->contractRepository)->getDetail($id)) {
            return ['code' => ['already_delete', 'contract']];
        }

        if(!$data = DB::table('contract_t_order')->where('contract_t_id',$id)->first()){
            return [];
        }
        $order = app($this->formModelingService)->getCustomDataDetail('contract_t_order', $data->order_id);
        if(isset($order['code'])){
            return $order;
        }
        $result                = $contractObj->toArray();
        $result['orders']     = $order;
        return $result;
    }

    public function contractRemind($input,$id, $own){
        if (!$contractObj = app($this->contractRepository)->getDetail($id)) {
            return ['code' => ['already_delete', 'contract']];
        }
        if(!$data = DB::table('contract_t_remind')->where('contract_t_id',$id)->first()){
            return [];
        }
        $remind = app($this->formModelingService)->getCustomDataDetail('contract_t_remind', $data->remind_id);
        if(isset($remind['code'])){
            return $remind;
        }
        $result                = $contractObj->toArray();
        $result['reminds']     = $remind;
        return $result;
    }


    public function groupPermission($contractObj,$type = 'edit',$user_arr = [],$own){
        $param = [
            'type_id' => [$contractObj->type_id],
        ];
        $fieldsGroupLists = app($this->contractTypeService)->getPermissionLists($param,'fields');
        $monitorGroupLists = app($this->contractTypeService)->getPermissionLists($param,'monitor');
        $result = array_merge_recursive($fieldsGroupLists,$monitorGroupLists);

        switch ($type){
            case 'edit':
            case 'view':
                return $this->parseViewOrEdit($contractObj,$fieldsGroupLists,$monitorGroupLists,$user_arr,$type,$own);
                break;
            case 'data_delete';
                return $this->parseDelete($result,$type);
                break;
            case 'data_user_id':
                return $this->parseDelete($result,$type);
                break;
            case 'data_share':
                return $this->parseDelete($result,$type);
                break;
        }
    }

    public function parseDelete($groupData,$type){
        $result = false;
        foreach ($groupData as $item){
            if($item['all_privileges'] || $item[$type]){
                $result = true;
                return [$result];
            }
        }
        return [$result];
    }

    public function parseViewOrEdit($contractObj,$fieldsGroupLists,$monitorGroupLists,$user_arr,$type,$own){
        $basic = $text = $attachment = $child = $project = $order = $remind = $run = $attachmentLoad = 0;
        $standardFalse = [0,0,0,0,0,0,0,0,0];
        $standardTrue = [1,1,1,1,1,1,1,1,1,'all'];
        $editFlag = 0;
        $label_custom = [];
        // 验证数据权限下的标签权限
        if($fieldsGroupLists){
            foreach ($fieldsGroupLists as $vo){
                if($vo['data_edit']){
                    $editFlag = 1;
                }
                // 验证是否有查看权限
                $pril = isset($user_arr[$vo['id']]) ? $user_arr[$vo['id']] : [];
                if($vo['own'] || $vo['superior'] || $vo['all_superior'] || $vo['department_header'] || $vo['department_person'] || $vo['role_person'])
                {
                    // 是否是当前权限组内人员
                    $exists = array_intersect(explode(',',$contractObj->{$vo['relation_fields']}),$pril);
                    // 是否是他人分享的合同
//                    $share_ids = $this->getShareIds($own);
//                    $exists_share = in_array($contractObj->id,$share_ids) ? true : false;

                    $exists_type = false;
                    if($vo['type_id'] == 'all'){
                        $exists_type = true;
                    }else{
                        $type_ids = explode(',',$vo['type_id']);
                        if(in_array($contractObj->type_id,$type_ids)){
                            $exists_type = true;
                        };
                    }
                    //既是该分组下的管理人，同时具有当前分类的的权限
                    if($exists && $exists_type){
                        if($vo['all_privileges']){
                            return $standardTrue;
                        }
                        if(isset($vo['data_'.$type]) && $vo['data_'.$type]){
                            if($vo['label_basic_info'][$type]) $basic = 1;
                            if($vo['label_text'][$type]) $text = 1;
                            if($vo['label_child_contract'][$type]) $child = 1;
                            if($vo['label_project'][$type]) $project = 1;
                            if($vo['label_order'][$type]) $order = 1;
                            if($vo['label_run'][$type]) $run = 1;
                            if($vo['label_remind'][$type]) $remind = 1;
                            if($vo['label_attachment'][$type]) $attachment = 1;
                            if($vo['label_attachment']['download']) $attachmentLoad = 1;
                            if($vo['label_custom']){
                                $menuKey = [];
                                foreach ($vo['label_custom'] as $k1 => $v1){
                                    if($v1){
                                        $menuKey[] = $k1;
                                    }
                                }
//                                $menuKey = array_keys($vo['label_custom']);
                                $label_custom = array_merge($label_custom,$menuKey);
                            }
                        }
                        if(isset($vo['data_view']) && $vo['data_view']){
                            if($vo['label_attachment']['download']) $attachmentLoad = 1;
                        }
                    }
                }
            }
        }
        // 验证监控权限下的标签权限
        if($monitorGroupLists){
            foreach ($monitorGroupLists as $item){
                $exists_type = false;
                if($item['type_id'] == 'all'){
                    $exists_type = true;
                }else{
                    $type_ids = explode(',',$item['type_id']);
                    if(in_array($contractObj->type_id,$type_ids)){
                        $exists_type = true;
                    };
                }
                if($exists_type){
                    if($item['data_edit']){
                        $editFlag = 1;
                    }
                    if($item['all_user']){
                        if($item['all_privileges']){
                            return $standardTrue;
                        }
                        if(isset($item['data_'.$type]) && $item['data_'.$type]){
                            if($item['label_basic_info'][$type]) $basic = 1;
                            if($item['label_text'][$type]) $text = 1;
                            if($item['label_child_contract'][$type]) $child = 1;
                            if($item['label_project'][$type]) $project = 1;
                            if($item['label_order'][$type]) $order = 1;
                            if($item['label_run'][$type]) $run = 1;
                            if($item['label_remind'][$type]) $remind = 1;
                            if($item['label_attachment'][$type]) $attachment = 1;
                            if($item['label_attachment']['download']) $attachmentLoad = 1;
                            if($item['label_custom']){
                                $menuKey = [];
                                foreach ($item['label_custom'] as $k2 => $v2){
                                    if($v2){
                                        $menuKey[] = $k2;
                                    }
                                }
//                                $menuKey = array_keys($item['label_custom']);

                                $label_custom = array_merge($label_custom,$menuKey);
                            }
                        }
                        if(isset($item['data_view']) && $item['data_view']){
                            if($item['label_attachment']['download']) $attachmentLoad = 1;
                        }
                    }
                    if($item['user_ids']){
                        if(in_array($own['user_id'],explode(',',$item['user_ids']))){
                            if($item['all_privileges']){
                                return $standardTrue;
                            }
                            if(isset($item['data_'.$type]) && $item['data_'.$type]){
                                if($item['label_basic_info'][$type]) $basic = 1;
                                if($item['label_text'][$type]) $text = 1;
                                if($item['label_child_contract'][$type]) $child = 1;
                                if($item['label_project'][$type]) $project = 1;
                                if($item['label_order'][$type]) $order = 1;
                                if($item['label_run'][$type]) $run = 1;
                                if($item['label_remind'][$type]) $remind = 1;
                                if($item['label_attachment'][$type]) $attachment = 1;
                                if($item['label_attachment']['download']) $attachmentLoad = 1;
                                if($item['label_custom']){
                                    $menuKey = [];
                                    foreach ($item['label_custom'] as $k3 => $v3){
                                        if($v3){
                                            $menuKey[] = $k3;
                                        }
                                    }
//                                    $menuKey = array_keys($item['label_custom']);
                                    $label_custom = array_merge($label_custom,$menuKey);
                                }
                            }
                            if(isset($item['data_view']) && $item['data_view']){
                                if($item['label_attachment']['download']) $attachmentLoad = 1;
                            }
                        }
                    }
                    if($item['dept_ids']){
                        if(in_array($own['dept_id'],explode(',',$item['dept_ids']))){
                            if($item['all_privileges']){
                                return $standardTrue;
                            }
                            if(isset($item['data_'.$type]) && $item['data_'.$type]){
                                if($item['label_basic_info'][$type]) $basic = 1;
                                if($item['label_text'][$type]) $text = 1;
                                if($item['label_child_contract'][$type]) $child = 1;
                                if($item['label_project'][$type]) $project = 1;
                                if($item['label_order'][$type]) $order = 1;
                                if($item['label_run'][$type]) $run = 1;
                                if($item['label_remind'][$type]) $remind = 1;
                                if($item['label_attachment'][$type]) $attachment = 1;
                                if($item['label_attachment']['download']) $attachmentLoad = 1;
                                if($item['label_custom']){
                                    $menuKey = [];
                                    foreach ($item['label_custom'] as $k4 => $v4){
                                        if($v4){
                                            $menuKey[] = $k4;
                                        }
                                    }
//                                    $menuKey = array_keys($item['label_custom']);
                                    $label_custom = array_merge($label_custom,$menuKey);
                                }
                            }
                            if(isset($item['data_view']) && $item['data_view']){
                                if($item['label_attachment']['download']) $attachmentLoad = 1;
                            }

                        }
                    }
                    if($item['role_ids']){
                        if(array_intersect($own['role_id'],explode(',',$item['role_ids']))){
                            if($item['all_privileges']){
                                return $standardTrue;
                            }
                            if(isset($item['data_'.$type]) && $item['data_'.$type]){
                                if($item['label_basic_info'][$type]) $basic = 1;
                                if($item['label_text'][$type]) $text = 1;
                                if($item['label_child_contract'][$type]) $child = 1;
                                if($item['label_project'][$type]) $project = 1;
                                if($item['label_order'][$type]) $order = 1;
                                if($item['label_run'][$type]) $run = 1;
                                if($item['label_remind'][$type]) $remind = 1;
                                if($item['label_attachment'][$type]) $attachment = 1;
                                if($item['label_attachment']['download']) $attachmentLoad = 1;
                                if($item['label_custom']){
                                    $menuKey = [];
                                    foreach ($item['label_custom'] as $k5 => $v5){
                                        if($v5){
                                            $menuKey[] = $k5;
                                        }
                                    }
//                                    $menuKey = array_keys($item['label_custom']);
                                    $label_custom = array_merge($label_custom,$menuKey);
                                }
                            }
                            if(isset($item['data_view']) && $item['data_view']){
                                if($item['label_attachment']['download']) $attachmentLoad = 1;
                            }
                        }
                    }
                }
            }
        }

        // 如果全部权限组都没有设置编辑权限，则全部返回 false
        if(!$editFlag && $type == 'edit'){
            return $standardFalse;
        }
        // 返回对应标签下的编辑/查看权限
        return [$basic,$text,$child,$project,$order,$run,$remind,$attachment,$attachmentLoad,$label_custom];
    }




    private function getProjects($id){
        $projects = app($this->contractProjectRepository)->getProjects($id);
        return $projects;
    }
    private function getContractAttachments($id)
    {
        $result        = [];
        $attachmentIds = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'contract_t', 'entity_id' => $id]);
        if (empty($attachmentIds)) {
            return $result;
        }
        $lists = DB::table('attachment_relataion_contract_t')->select(['attachment_id', 'entity_column'])->where('entity_id', $id)->get();
        if ($lists->isEmpty()) {
            return $result;
        }
        foreach ($lists as $key => $item) {
            if ($item->entity_column) {
                continue;
            }
            if (in_array($item->attachment_id, $attachmentIds)) {
                $result[] = $item->attachment_id;
            }
        }
        return $result;
    }

    public function getSimpleFlowRun($runId)
    {
        if (empty($runId)) {
            return false;
        }
        $query = DB::table('flow_run')->select(['run_name', 'run_id', 'flow_id']);
        if (is_array($runId)) {
            $list = $query->whereIn('run_id', $runId)->get();
            if (count($list) > 0) {
                $runName = [];
                foreach ($list as $item) {
                    $runName[$item->run_id] = $item;
                }
                return $runName;
            }
        } else {
            return $query->where('run_id', $runId)->first();
        }

        return false;
    }

    /**
     * 合同提醒
     */
    public function getContractRemind()
    {
        $param = [
            'page'   => false,
            'search' => [
                'remind_date' => [date('Y-m-d', time())],
//                 'recycle_status' => [0]
            ],
        ];

        $result   = app($this->contractRemindRepository)->getContractRemindLists($param);
//        $result   = ContractRemindRepository::getRimindList($param['search']);
        $messages = [];
        if ($result) {
            $result = $result->toArray();
            $contractRecycleIds = $contractIds = [];
            foreach ($result as $key => $value) {
                $contractIds[] = $value['contract_t_id'];
            }
            $recycleLists = DB::table('contract_t')->whereIn('id', $contractIds)->where('recycle_status', 1)->orWhere('status', 0)->get();
            if (!$recycleLists->isEmpty()) {
                foreach ($recycleLists as $key => $value) {
                    $contractRecycleIds[] = $value->id;
                }
            }

            foreach ($result as $key => $value) {
                // 过滤已经删除的合同
                if (!empty($contractRecycleIds) && in_array($value['contract_t_id'], $contractRecycleIds)) {
                    continue;
                }
                $messages[] = [
                    'remindMark'   => 'contract-manager',
                    'toUser'       => $value['user_id'],
                    'contentParam' => [
                        'contractTitle'   => $value['contract']['title'],
                        'contractRemarks' => $value['content'],
                    ],
                    'stateParams'  => ['contract_id' => $value['contract_t_id']],
                ];
            }
        }
        return $messages;
    }

    /**
     * 获取合同编号
     * @return int
     */
    public function getNumber()
    {
        $result = '';
        $date   = date('Ymd', time());
        if (Redis::exists(self::$contractNumberRedisKey . $date)) {
            $result = Redis::incr(self::$contractNumberRedisKey . $date);
        } else {
            $max_number = DB::table('contract_t')->where('id', DB::table('contract_t')->max('id'))->value('number');
            $prv_date   = '';
            if ($max_number) {
                $prv_date    = substr($max_number, -12, -4);
                $temp_number = (int) substr($max_number, -4);
            }
            if ($date == $prv_date) {
                Redis::setnx(self::$contractNumberRedisKey . $date, $temp_number);
            } else {
                Redis::setnx(self::$contractNumberRedisKey . $date, '0');
            }
            $result = Redis::incr(self::$contractNumberRedisKey . $date);
        }
        return str_pad($result, 4, "0", STR_PAD_LEFT);
    }
    /**
     * 根据合同ID获取合同
     * @param type $contractIds
     * @param type $fields
     * @param type $withTrashed
     * @param type $return
     * @return type
     */
    public function getContractsByIds($contractIds, $fields = ['*'], $withTrashed = false, $returnArray = false)
    {
        $contracts = app($this->contractRepository)->getContractsByIds($contractIds, $fields, $withTrashed);

        return $returnArray ? $contracts->toArray() : $contracts;
    }

    /**
     * 外发新增合同
     * @param  array $data
     * @return array
     */
    public function flowSendStore(array $data)
    {
        // 勾选流程id对应
        if (isset($data['flowInfo']['run_id'])) {
            $runid = $data['flowInfo']['run_id'];
            unset($data['flowInfo']);
            $input['contractFlow'] = [$runid];
        }
        $own = own() ? own() : [];
        $input['contract'] = isset($data['data']) ? $data['data'] : [];
        $userId = $data['current_user_id'] ?? $own['user_id'];
        // 外发过来合同默认是正常提交合同
        if(!isset($input['contract']['status'])){
            $input['contract']['status'] = 1;
        }
        // 判断主合同是否是自己有权限的合同
        if(isset($input['contract']['main_id']) && $input['contract']['main_id']){

            $mainData = app($this->contractRepository)->getDetail($input['contract']['main_id']);
            if(!$mainData){
                return ['code' => ['deleted_main_contract', 'contract']];
            }
            if($mainData['status'] != 1){
                return ['code' => ['err_contract_status', 'contract']];
            }
            if($mainData['recycle_status'] == 1){
                return ['code' => ['err_contract_recycle_status', 'contract']];
            }

            $info = app($this->userRepository)->getUserAllData($userId)->toArray();
            if($info){
                $role_ids = [];
                foreach ($info['user_has_many_role'] as $key => $vo) {
                    $role_ids[] = $vo['role_id'];
                }
                $own = [
                    'user_id' => $userId,
                    'dept_id' => $info['user_has_one_system_info']['dept_id'],
                    'role_id' => $role_ids,
                ];
            }



            list($ids,$user_arr,$monitor) = $this->getHasPermission($own);
            if(!in_array($input['contract']['main_id'],$ids)){
                return ['code' => ['no_private_contract', 'contract']];
            }
        }
        $input['contract']['creator'] = (isset($input['contract']['creator']) && $input['contract']['creator']) ? $input['contract']['creator'] : $userId;
        $result = $this->storeContract($input, $userId);
        if (isset($result['code'])) {
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'contract_t',
                    'field_to' => 'id',
                    'id_to' => $result
                ]
            ]
        ];
    }

    public function typeLists(array $input)
    {
        $param = $this->parseParams($input);
        if(!isset($param['sign'])){
            $param['limit'] = '100';
        }
        $total = app($this->contractTypeRepository)->getTotal($param);
        $lists = app($this->contractTypeRepository)->getLists($param);

        if($lists){
            $userArray = [];
            if($fieldsList = $this->userSelector([],own())){
                array_map(function ($v) use(&$userArray){
                    return $userArray[$v['field']] = $v['name'];
                },$fieldsList);
            };
            foreach ($lists as $key => $vo){
                if($lists[$key]->permissions){
                    foreach (self::$labelName as $item){
                        $lists[$key]->permissions->$item = json_decode($vo->permissions[$item],1);
                    }
                }
                $lists[$key]->field_name = isset($userArray[$vo->relation_fields]) ? $userArray[$vo->relation_fields] : '';
            }
        }
        return ['total' => $total, 'list' => $lists];
    }

    public function myTypeLists(array $input, $own)
    {
        $searchs = [];
        if (isset($input['search'])) {
            $searchs = json_decode($input['search'], true);
        }
        $hasPermissionIds = app($this->contractTypeRepository)->getHasPermissionIdLists($own);
        $input['search']  = json_encode($searchs + ['id' => [$hasPermissionIds, 'in']]);
        return $this->typeLists($input);
    }

    public function typeStore(array $input)
    {
        if (!$data = $this->typeValidate($input)) {
            return ['code' => ['0x000001', 'common']];
        }
        $result = app($this->contractTypeRepository)->insertData($data);
        if(isset($result['code'])){
            return $result;
        }
        return true;
    }

    public function parseType(&$result,$label_edit) : void {
        $result = array_merge($result,$label_edit);
        unset($result['label_edit']);
    }

    public function typeShow(int $id)
    {
        if($result = app($this->contractTypeRepository)->getDetail($id)){
            $result = $result->toArray();
            $permission = $this->getTypePermissionDetail($id);
            if($permission){
                $result['permission'] = $permission ? $permission : [];
            }
            return $result;
        }
        return [];
    }

    public function typeUpdate(int $id, array $input)
    {
        if (!$data = $this->typeValidate($input)) {
            return ['code' => ['0x000001', 'common']];
        }
        return app($this->contractTypeRepository)->updateData($data, ['id' => $id]);
    }

    public function typeDestory(int $id)
    {
        if ($validate = ContractRepository::existContractTypeId($id)) {
            return ['code' => ['0x066019', 'contract']];
        }
        app($this->contractTypeRepository)->deleteById($id);
        // 检测当前分类下的数据权限分组，更新对应的type_id
        if($dataGroupLists = app($this->contractTypePermissionRepository)->getLists(['type_id'=>[$id]])){

            foreach ($dataGroupLists as $key => $vo){
                if($vo['type_id'] != 'all'){
                    $typeArr = explode(',',$vo['type_id']);
                    if($type_id = array_diff($typeArr,[$id])){
                        app($this->contractTypePermissionRepository)->updateData(['type_id'=>implode(',',$type_id)],['id' => $vo['id']]);
                    }else {
                        app($this->contractTypePermissionRepository)->reallyDeleteByWhere(['id'=>[$vo['id']]]);
                    }
                }
            }
        }
        // 检测当前分类下的监控权限分组，更新对应的type_id
        if($monitorGroupLists = app($this->contractTypeMonitorPermissionRepository)->groupList(['type_id'=>[$id]])){
            foreach ($monitorGroupLists as $ks => $item){
                if($item['type_id'] != 'all'){
                    $typeArr = explode(',',$item['type_id']);
                    $type_id = array_diff($typeArr,[$id]);
                    if($type_id){
                        app($this->contractTypeMonitorPermissionRepository)->updateData(['type_id'=>implode(',',$type_id)],['id' => $item['id']]);
                    }else{
                        // 如果为空，则直接删除
                        app($this->contractTypeMonitorPermissionRepository)->reallyDeleteByWhere(['id'=>[$item['id']]]);
                    }
                }
            }
        }
        if($permission = $this->getTypePermissionDetail($id)){
            // 删除对应分类下的权限分组数据
            return app($this->contractTypePermissionRepository)->deleteById($permission['id']);
        }

    }

    private function typeValidate(array $input)
    {
        $name     = isset($input['name']) ? strval($input['name']) : '';
        $number   = isset($input['number']) ? intval($input['number']) : 1;
        $all_user = isset($input['all_user']) ? strval($input['all_user']) : 0;

        $dept_ids = (isset($input['dept_ids']) && !empty($input['dept_ids'])) ? implode(',', $input['dept_ids']) : '';
        $user_ids = (isset($input['user_ids']) && !empty($input['user_ids'])) ? implode(',', (array) $input['user_ids']) : '';
        $role_ids = (isset($input['role_ids']) && !empty($input['role_ids'])) ? implode(',', (array) $input['role_ids']) : '';


        if (!$name) {
            return false;
        }
        $data = [
            'name'     => $name,
            'number'   => $number,
            'all_user' => $all_user,
            'dept_ids' => $dept_ids,
            'user_ids' => $user_ids,
            'role_ids' => $role_ids,
        ];
        return $data;
    }

    public function filterLists(array &$params,$own)
    {
        $result = $params['search'] ?? [];
        if(!$own){
            return [];
        }
        // 跟进人为多个时查询
        if(isset($result['type']) && $result['type'] == 'mine'){
            if(isset($result['user_id']) && isset($result['user_id'][0]) && $result['user_id'][0] != $own['user_id']){
                return ['whereRaw' => ['FIND_IN_SET(?,user_id)',[$own['user_id']]]]; // 此处应该已废弃，暂未找到调用地方，若出现问题在修改
            };
        }
        /*
        if (isset($params['search']['multiSearch'])) {
            $multiSearchs = $params['search']['multiSearch'];
            unset($params['search']['multiSearch']);
        }
        $result = isset($params['search']) ? $params['search'] : [];
        if(!isset($result['recycle_status'])){
            $result['recycle_status'] = [0];
        }
        if (!isset($result['type']) && !isset($params['showAll'])) {
            $result['type_id'] = [app($this->contractTypeRepository)->getHasPermissionIdLists($own), 'in'];
        }
        if (isset($params['showAll'])) {
            unset($params['showAll']);
        }
        if (!empty($multiSearchs) && !isset($result['id'])) {
            $nIds = app($this->contractRepository)->multiSearchIds($multiSearchs);
            $result['id'] = [$nIds, 'in'];
        }

	if(isset($result['user_id']) && isset($result['user_id'][0]) && is_array($result['user_id'][0])){
            if($result['user_id'][0] != $own['user_id']){
                $result = [];
            }
        }
        */
        return [];
    }

    public function getShareListId($own){
        $contract_ids = ContractShareRepository::getShareDeptContractIds($own['dept_id']);
        $contract_ids = array_merge($contract_ids,ContractShareRepository::getShareRoleContractIds($own['role_id']));
        $contract_ids = array_unique(array_merge($contract_ids,ContractShareRepository::getShareUserContractIds($own['user_id'])));
        sort($contract_ids);
        return $contract_ids ?: [];
    }

    public function exportSettlement(array $param)
    {
        $own         = $param['user_info'];
        if(isset($param['include']) && $param['include'] == 1){
            $contractId = $param['contract_t_id'];
            $childLists = app($this->contractRepository)->getChildLists($contractId);
            if(!empty($childLists)){
                $id = array_column($childLists->toarray(),'id');
                list($ids,$user_arr,$monitor) = $this->getHasPermission($own);
                if($id = array_intersect($id,$ids)){
                    $param['search']['contract_t_id'][0] = array_merge($param['search']['contract_t_id'][0],$id);
                }

            }
        }
        return app($this->formModelingService)->exportFields('contract_t_project', $param, $own, trans('contract.contract_project_export_template'));
        $header = [
            "number"       => ['data' => trans("contract.contract_number"), 'style' => ['width' => '15']],
            'title'        => ['data' => trans("contract.title"), 'style' => ['width' => '35']],
            "main_money"   => ['data' => trans("contract.contract_money"), 'style' => ['width' => '15']],
            "type"         => ['data' => trans("contract.type"), 'style' => ['width' => '15']],
            "pay_type"     => ['data' => trans("contract.pay_type"), 'style' => ['width' => '15']],
            // "proportion"   => ['data' => trans("contract.proportion"), 'style' => ['width' => '15']],
            "money"        => ['data' => trans("contract.money"), 'style' => ['width' => '15']],
            "pay_way_name" => ['data' => trans("contract.pay_way_name"), 'style' => ['width' => '15']],
            "pay_account"  => ['data' => trans("contract.pay_account"), 'style' => ['width' => '15']],
            "pay_time"     => ['data' => trans("contract.pay_time"), 'style' => ['width' => '15']],
            "invoice_time" => ['data' => trans("contract.invoice_time"), 'style' => ['width' => '15']],
            "flows"        => ['data' => trans("contract.flows"), 'style' => ['width' => '55']],
            "remarks"      => ['data' => trans("contract.remarks"), 'style' => ['width' => '55']],
        ];
        $data         = [];
        $contractId   = $param['contract_id'] ?? 0;
        $contractList = app($this->contractRepository)->getDetail($contractId);
        if (!$contractId || !$contractList) {
            return compact('header', 'data');
        }
        $contractTitleId = [
            $contractId => [
                'title'  => $contractList->title,
                'money'  => $contractList->money,
                'number' => $contractList->number,
            ],
        ];
        $contractIds = [$contractId];
        // 导出子合同
        $childsEx = isset($param['showChilds']) ? (bool) $param['showChilds'] : false;
        list($ids,$user_arr,$monitor) = $this->getHasPermission($own);
        $childids = [];
        if ($childsEx) {
            $childLists = app($this->contractRepository)->getChildLists($contractId);
            if (!$childLists->isEmpty()) {
                foreach ($childLists as $key => $item) {
                    if(in_array($item['id'],$ids)){
                        $childids[] = $item->id;
//                        array_push($contractIds, $item->id);
                        $contractTitleId[$item->id] = [
                            'title'  => $item->title,
                            'money'  => $item->money,
                            'number' => $item->number,
                        ];
                    }
                }
            }
        }
//        $contractData = DB::table('contract_t_project')->where('contract_t_id',$contractId)->first();
//        if(!$contractData){
//            return [];
//        }
//        $searchs = [
//            'search' => [
//                'project_id' => [[$contractData->project_id],'in']
//            ]
//        ];

//        $lists = app($this->contractProjectProjectRepository)->getProjectLists($searchs);

//        $project = app($this->formModelingService)->getCustomDataDetail('contract_t_project', $contractData->project_id);
//        if($lists){
//            foreach ($lists as $key => $vo){
//                $lists[$key]['contract_t_id'] = $contractId;
//            }
//        }
        $lists = app($this->contractProjectRepository)->lists($contractIds);

        if($childids){
            $projectIdLists = DB::table('contract_t_project')->select(['project_id','contract_t_id'])->whereIn('contract_t_id',$childids)->get()->toArray();
            $childlists = [];
            if($projectIdLists){
                $project_id = [];
                foreach ($projectIdLists as $vo){
                    $project_id[] = $vo->project_id;
                    $contract_id[$vo->project_id] = $vo->contract_t_id;
                }
                $search = [
                    'search' => [
                        'project_id' => [$project_id,'in']
                    ]
                ];
                $childlists = app($this->contractProjectProjectRepository)->getProjectLists($search);

            }
            if($childlists){
                foreach ($childlists as $ke => $item){
                    $childlists[$ke]['contract_t_id'] = $contract_id[$item['project_id']];
                }
                $lists = array_merge($lists,$childlists);

            };
        }
        if (!$lists) {
            return compact('header', 'data');
        }
        $payWaySelects = [];
        $runIds        = [];
        foreach ($lists as $key => $list) {
            $list = (array) $list;
            $tempRunIds = [];
            if ($list['run_id']) {
                $tempRunIds = explode(',', $list['run_id']);
                $runIds     = array_merge($runIds, $tempRunIds);
            }
            $data[] = [
                "number"       => $contractTitleId[$list['contract_t_id']]['number'] ?? '',
                "title"        => $contractTitleId[$list['contract_t_id']]['title'] ?? '',
                "main_money"   => $contractTitleId[$list['contract_t_id']]['money'] ?? '',
                "type"         => intval($list['type']) === 1 ? '支出' : '收入',
                "pay_type"     => app($this->systemComboboxService)->parseCombobox(self::PAY_TYPE_SELECT_FIELD, $list['pay_type']),
                "money"        => $list['money'],
                "pay_way_name" => app($this->systemComboboxService)->parseCombobox(self::PAY_WAY_SELECT_FIELD, $list['pay_way']),
                "pay_account"  => $list['pay_account'],
                "pay_time"     => strtotime($list['pay_time']) > 0 ? $list['pay_time'] : '',
                "invoice_time" => strtotime($list['invoice_time']) > 0 ? $list['invoice_time'] : '',
                "flows"        => $tempRunIds,
                "remarks"      => $list['remarks'],
            ];
        }
        if (!empty($runIds)) {
            $flowLists = $this->getSimpleFlowRun($runIds);
            if (!empty($flowLists)) {
                foreach ($data as $key => $dataList) {
                    $tempV = '';
                    array_map(function ($item) use ($flowLists, &$tempV) {
                        if (isset($flowLists[$item])) {
                            $tempV .= $flowLists[$item]->run_name . ',';
                        }
                    }, $dataList['flows']);
                    $data[$key]['flows'] = $tempV;
                }
            }
        }
        return compact('header', 'data');
    }

    public function exportStatistical(array $param)
    {
        $header = [
            'title'               => ['data' => trans("contract.title"), 'style' => ['width' => '35']],
            "net_profit"          => ['data' => trans("contract.net_profit")],
            "total_revenue"       => ['data' => trans("contract.total_revenue")],
            "open_total_revenue"  => ['data' => trans("contract.open_total_revenue")],
            "total_spending"      => ['data' => trans("contract.total_spending")],
            "open_total_spending" => ['data' => trans("contract.open_total_spending")],
        ];
        $data         = $payWaysCount         = [];
        $contractId   = $param['contract_id'] ?? 0;
        $contractList = app($this->contractRepository)->getDetail($contractId);
        if (!$contractId || empty($contractList)) {
            return compact('header', 'data');
        }

        $contractIds     = [$contractId];
        $contractTitleId = [
            $contractId => $contractList->title,
        ];
        // 导出子合同
        $childsEx = isset($param['showChilds']) ? (bool) $param['showChilds'] : false;
        if ($childsEx) {
            $childLists = app($this->contractRepository)->getChildLists($contractId);
            if (!$childLists->isEmpty()) {
                foreach ($childLists as $key => $item) {
                    array_push($contractIds, $item->id);
                    $contractTitleId[$item->id] = $item->title;
                }
            }
        }
        foreach ($contractIds as $contractId) {
            $dataDetail   = $projectLists = [];
//            $projectData = DB::table('contract_t_project')->where('contract_t_id',$contractId)->first();
//            if($projectData){
//                $search = [
//                    'search' => [
//                        'project_id' => [[$projectData->project_id],'in']
//                    ]
//                ];
//                $projectLists = app($this->contractProjectProjectRepository)->getProjectLists($search);
//            }

            $projectLists = app($this->contractProjectRepository)->lists([$contractId]);
            $totalRevenue = $openTotaRevenue = $totalSpending = $openTotalSpending = 0;
            if ($projectLists) {
                $payWayLists = app($this->systemComboboxService)->getAllFields(self::PAY_TYPE_SELECT_FIELD);
                if (isset($payWayLists['list']) && !empty($payWayLists['list'])) {
                    foreach ($payWayLists['list'] as $index => $item) {
                        $header[$item['field_value'] . '_count']       = ['data' => $item['field_name']];
                        $payWaysCount[$item['field_value'] . '_count'] = 0;
                    }
                }
                foreach ($projectLists as $key => $list) {
                    $tempKey = intval($list['type']) === 1 ? 'type' . $list['type'] : 'type_' . $list['type'];
                    if (!isset($dataDetail[$tempKey])) {
                        $dataDetail[$tempKey] = 0;
                    }
                    $tempMoney = floatval($list['money']);
                    if (intval($list['type']) === 1) {
                        if (check_date_valid($list['pay_time'])) {
                            $totalSpending += $tempMoney;
                            $dataDetail[$tempKey] += -$tempMoney;
                            if (isset($payWaysCount[$list['pay_type'] . '_count'])) {
                                $payWaysCount[$list['pay_type'] . '_count'] += -$tempMoney;
                            }
                        }
                        if (check_date_valid($list['invoice_time'])) {
                            $openTotalSpending += $tempMoney;
                        }
                    } else {
                        if (check_date_valid($list['pay_time'])) {
                            $totalRevenue += $tempMoney;
                            $dataDetail[$tempKey] += $tempMoney;
                            if (isset($payWaysCount[$list['pay_type'] . '_count'])) {
                                $payWaysCount[$list['pay_type'] . '_count'] += $tempMoney;
                            }
                        }
                        if (check_date_valid($list['invoice_time'])) {
                            $openTotaRevenue += $tempMoney;
                        }
                    }
                }
                $totalDetail = [
                    "title"               => $contractTitleId[$contractId] ?? '',
                    "net_profit"          => $totalRevenue - $totalSpending,
                    "total_revenue"       => $totalRevenue,
                    "open_total_revenue"  => $openTotaRevenue,
                    "total_spending"      => $totalSpending,
                    "open_total_spending" => $openTotalSpending,
                ];
                if (!empty($payWaysCount)) {
                    $totalDetail = array_merge($totalDetail, $payWaysCount);
                }
                $dataDetail = array_merge($totalDetail, $dataDetail);
                $data[]     = $dataDetail;
            }
        }
        return compact('header', 'data');
    }

    public function flowSendStoreProject(array $params)
    {
        $data = isset($params['data']) ? $params['data'] : [];
        if(empty($data)){
            return ['code' => ['0x000001', 'common']];
        }
        if (!isset($data['contract_t_id']) || !$data['contract_t_id']) {
            return ['code' => ['0x066009', 'contract']];
        }

        $contractId = $data['contract_t_id'];
        $validate = ContractProjectRepository::validateInput($data);
        if (isset($validate['code'])) {
            return $validate;
        }

        // 款项性质与金额不填写，默认为0
        if(empty($data['type'])){
            $data['type'] = 0;
        }
        if(empty($data['money'])){
            $data['money'] = 0;
        }
        if(empty($data['pay_way'])){
            $data['pay_way'] = 0;
        }

        // 验证是否有查看权限
        if (!$contractObj = app($this->contractRepository)->getDetail($contractId)) {
            return ['code' => ['already_delete', 'contract']];
        }
        $own = own() ? own() : [];

        $user_id = (isset($params['current_user_id']) && $params['current_user_id']) ? $params['current_user_id'] : $own['user_id'];
        $info = app($this->userRepository)->getUserAllData($user_id)->toArray();
        if($info){
            $role_ids = [];
            foreach ($info['user_has_many_role'] as $key => $vo) {
                $role_ids[] = $vo['role_id'];
            }
            $own = [
                'user_id' => $user_id,
                'dept_id' => $info['user_has_one_system_info']['dept_id'],
                'role_id' => $role_ids,
            ];
        }
        // 如果没有结算计划标签查看权限，则肯定没有新建权限
        $lists = $this->menus(0,$own);
        // 菜单被隐藏，直接返回无权限
        foreach ($lists as $key => $value){
            if($value['key'] == 'contract_statistics' && !$value['isShow']){
                return ['code' => ['not_project_permission', 'contract']];
            }
        }

        // 检测是否有编辑权限
        // 非草稿合同走权限
        if(!(!$contractObj->status && $contractObj->creator && $contractObj->creator == own()['user_id']))
        {
            // 验证是否有查看权限
            list($ids,$user_arr,$monitor) = $this->getHasPermission($own,['type_id'=>[$contractObj->type_id]]);
            if(!$ids || !in_array($contractId,$ids)){
                return ['code' => ['0x000017', 'common']];

            }
            // 标签编辑权限
            list(
                $basiEdit,
                $textEdit,
                $childEdit,
                $projectEdit,
                $orderEdit,
                $runEdit,
                $remindEdit,
                $attachmentEdit,
                $attachmentDownload,
                ) = $this->groupPermission($contractObj,'edit',$user_arr,$own);
            if(!$projectEdit){
                return ['code' => ['0x000017', 'common']];
            }
        }

        $result = app($this->formModelingService)->addCustomData($data, 'contract_t_project');
        // 先检测是否原先就有数据，如果有数据，则是编辑，暂时不支持
        if(isset($result['code'])){
            return $result;
        }
        if($result){
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'contract_t_project',
                        'field_to' => 'project_id',
                        'id_to' => $result
                    ]
                ]
            ];
        }
    }

    public function flowSendStoreOrder(array $params)
    {
        $data = isset($params['data']) ? $params['data'] : [];
        if(empty($data)){
            return ['code' => ['0x000001', 'common']];
        }
        if (!isset($data['contract_t_id']) || !$data['contract_t_id']) {
            return ['code' => ['0x066009', 'contract']];
        }
        if(isset($data['order']) && !$data['order']){
            return ['code' => ['not_empty_order_data', 'contract']];
        }
        $contractId = $data['contract_t_id'];
        $validate = ContractOrderRepository::validateInput($data);
        if (isset($validate['code'])) {
            return $validate;
        }

        // 验证是否有查看权限
        if (!$contractObj = app($this->contractRepository)->getDetail($contractId)) {
            return ['code' => ['already_delete', 'contract']];
        }

        $own = own() ? own() : [];

        $user_id = (isset($params['current_user_id']) && $params['current_user_id']) ? $params['current_user_id'] : $own['user_id'];

        $info = app($this->userRepository)->getUserAllData($user_id)->toArray();
        if($info){
            $role_ids = [];
            foreach ($info['user_has_many_role'] as $key => $vo) {
                $role_ids[] = $vo['role_id'];
            }
            $own = [
                'user_id' => $user_id,
                'dept_id' => $info['user_has_one_system_info']['dept_id'],
                'role_id' => $role_ids,
            ];
        }

        // 如果没有订单标签查看权限，则肯定没有新建权限
        $lists = $this->menus(0,$own);
        // 菜单被隐藏，直接返回无权限
        foreach ($lists as $key => $value){
            if($value['key'] == 'contract_order' && !$value['isShow']){
                return ['code' => ['not_order_permission', 'contract']];
            }
        }
        // 检测是否有编辑权限
        // 非草稿合同走权限
        if(!(!$contractObj->status && $contractObj->creator && $contractObj->creator == $own['user_id']))
        {
            // 验证是否有查看权限
            list($ids,$user_arr,$monitor) = $this->getHasPermission($own,['type_id'=>[$contractObj->type_id]]);
            if(!$ids || !in_array($contractId,$ids)){
                return ['code' => ['0x000017', 'common']];

            }
            // 标签编辑权限
            list(
                $basiEdit,
                $textEdit,
                $childEdit,
                $projectEdit,
                $orderEdit,
                $runEdit,
                $remindEdit,
                $attachmentEdit,
                $attachmentDownload
                ) = $this->groupPermission($contractObj,'edit',$user_arr,$own);
            if(!$orderEdit){
                return ['code' => ['0x000017', 'common']];
            }
        }

        // 先检测是否原先就有数据，如果有数据，则是编辑，暂时不支持
        $result = app($this->formModelingService)->addCustomData($data, 'contract_t_order');

        if(isset($result['code'])){
            return $result;
        }
        if($result){
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'contract_t_order',
                        'field_to' => 'order_id',
                        'id_to' => $result
                    ]
                ]
            ];
        }
    }

    public function flowSendStoreRemind(array $params)
    {
        $data = isset($params['data']) ? $params['data'] : [];
        if(empty($data)){
            return ['code' => ['0x000001', 'common']];
        }

        if (!isset($data['contract_t_id']) || !$data['contract_t_id']) {
            return ['code' => ['0x066009', 'contract']];
        }
        if(isset($data['remind']) && !$data['remind']){
            return ['code' => ['not_empty_remind_data', 'contract']];
        }

        $contractId = $data['contract_t_id'];
        $validate = ContractRemindRepository::validateInput($data);
        if (isset($validate['code'])) {
            return $validate;
        }

        // 如果没有提醒标签查看权限，则肯定没有新建权限
        $own = own() ? own() : [];

        $user_id = (isset($params['current_user_id']) && $params['current_user_id']) ? $params['current_user_id'] : $own['user_id'];

        $info = app($this->userRepository)->getUserAllData($user_id)->toArray();
        if($info){
            $role_ids = [];
            foreach ($info['user_has_many_role'] as $key => $vo) {
                $role_ids[] = $vo['role_id'];
            }
            $own = [
                'user_id' => $user_id,
                'dept_id' => $info['user_has_one_system_info']['dept_id'],
                'role_id' => $role_ids,
            ];
        }
        $lists = $this->menus(0,$own);
        // 菜单被隐藏，直接返回无权限
        foreach ($lists as $key => $value){
            if($value['key'] == 'contract_remind' && !$value['isShow']){
                return ['code' => ['not_remind_permission', 'contract']];
            }
        }

        // 验证是否有查看权限
        if (!$contractObj = app($this->contractRepository)->getDetail($contractId)) {
            return ['code' => ['already_delete', 'contract']];
        }
        // 检测是否有编辑权限
        // 非草稿合同走权限
        if(!(!$contractObj->status && $contractObj->creator && $contractObj->creator == $own['user_id']))
        {
            // 验证是否有查看权限
            list($ids,$user_arr,$monitor) = $this->getHasPermission($own,['type_id'=>[$contractObj->type_id]]);
            if(!$ids || !in_array($contractId,$ids)){
                return ['code' => ['0x000017', 'common']];

            }
            // 标签编辑权限
            list(
                $basiEdit,
                $textEdit,
                $childEdit,
                $projectEdit,
                $orderEdit,
                $runEdit,
                $remindEdit,
                $attachmentEdit,
                $attachmentDownload
                ) = $this->groupPermission($contractObj,'edit',$user_arr,$own);
            if(!$remindEdit){
                return ['code' => ['0x000017', 'common']];
            }
        }

        // 获取表对应的remind_id
        $result = app($this->formModelingService)->addCustomData($data, 'contract_t_remind');
        if(isset($result['code'])){
            return $result;
        }
        if($result){
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'contract_t_order',
                        'field_to' => 'order_id',
                        'id_to' => $result
                    ]
                ]
            ];
        }
    }

    public function childProjects(int $id)
    {
        // 判断是否有查看权限
//        $type_id = app($this->contractTypeRepository)->getHasPermissionIdLists(own());

        // 判断是否有查看权限
//        $type_id = app($this->contractTypeRepository)->getHasPermissionIdLists(own());
        $lists = app($this->contractRepository)->childLists($id);
        $returnData = [];
        if (!$lists->isEmpty()) {
            foreach ($lists as $key => $item) {
                $item['projects'] = $this->parseProjects($item['projects']);
                $returnData[] = $item;
            }
        }
        return $returnData;

        return $lists;
    }

    private function parseProjects($lists)
    {
        if (empty($lists)) {
            return $lists;
        }
        $runIds = [];
        foreach ($lists as $key => $value) {
            $lists[$key]->pay_way_name  = app($this->systemComboboxService)->parseCombobox(self::PAY_WAY_SELECT_FIELD, $value->pay_way);
            $lists[$key]->pay_type_name = app($this->systemComboboxService)->parseCombobox(self::PAY_TYPE_SELECT_FIELD, $value->pay_type);
        }
//        foreach ($lists as $key => $value) {
//            $tempRunIds = explode(',', $value['run_id']);
//            if (!empty($tempRunIds)) {
//                $lists[$key]['run_id'] = $tempRunIds;
//            }
//            $runIds = array_merge($runIds, $tempRunIds);
//        }
//        if ($runLists = $this->getSimpleFlowRun($runIds)) {
//            foreach ($lists as $key => $value) {
//                if (empty($value['run_id'])) {
//                    continue;
//                }
//                $temp_lists = [];
//                foreach ($value['run_id'] as $temp_run_id) {
//                    if (isset($runLists[$temp_run_id])) {
//                        $temp_lists[] = $runLists[$temp_run_id];
//                    }
//                }
//                $lists[$key]['run_lists'] = $temp_lists;
//            }
//        }
        return $lists;
    }

    public function exportManager(array $params)
    {
        $own         = $params['user_info'];
        $flagEmpty = false;
        list($ids,$user_arr,$monitor) = $this->getHasPermission($own);
        $params['search']['id'] = [$ids,'in'];
        $exportDatas = app($this->formModelingService)->exportFields('contract_t', $params, $own);
        if (empty($exportDatas) || !isset($exportDatas['header']) || !isset($exportDatas['data'])) {
            return $exportDatas;
        }
        $header = [
            "net_profit"          => ['data' => trans("contract.net_profit")],
            "total_revenue"       => ['data' => trans("contract.total_revenue")],
            "open_total_revenue"  => ['data' => trans("contract.open_total_revenue")],
            "total_spending"      => ['data' => trans("contract.total_spending")],
            "open_total_spending" => ['data' => trans("contract.open_total_spending")],
        ];
        $header      = array_merge($exportDatas['header'], $header);
        $data        = $exportDatas['data'];
        // 没有权限的合同列表直接返回空数据
        if($flagEmpty){
            $data = [];
            return compact('header', 'data');
        }
        $payWayLists = app($this->systemComboboxService)->getAllFields(self::PAY_TYPE_SELECT_FIELD);
        foreach ($data as $index => $itemContract) {
            $number = isset($itemContract['number']) ? $itemContract['number'] : '';
            if (!$number) {
                continue;
            }
            $contractId  = app($this->contractRepository)->getContractIdByNumber($number);
            $contractIds = [$contractId];
            // 导出子合同
            $childsEx = isset($params['showChilds']) ? (bool) $params['showChilds'] : false;
            if ($childsEx) {
                $childLists = app($this->contractRepository)->getChildLists($contractId);

                if (!$childLists->isEmpty()) {
                    foreach ($childLists as $key => $item) {
                        if(in_array($item->id,$ids)){
                            array_push($contractIds, $item->id);
                        }
                    }
                }
            }
            $totalRevenue = $openTotaRevenue = $totalSpending = $openTotalSpending = 0;
            $projectLists = app($this->contractProjectRepository)->lists($contractIds);
//            $projectIdLists = DB::table('contract_t_project')->select('project_id')->whereIn('contract_t_id',$contractIds)->get()->toArray();
//            $projectLists = [];
//            if($projectIdLists){
//                $project_id = [];
//                foreach ($projectIdLists as $vo){
//                    $project_id[] = $vo->project_id;
//                }
//                $search = [
//                    'search' => [
//                        'project_id' => [$project_id,'in']
//                    ]
//                ];
//                $projectLists = app($this->contractProjectProjectRepository)->getProjectLists($search);
//            }

//            $projectLists = app($this->contractProjectRepository)->lists($contractIds);
            if (isset($payWayLists['list']) && !empty($payWayLists['list'])) {
                foreach ($payWayLists['list'] as $iWayIndex => $iItemIndex) {
                    $header[$iItemIndex['field_value'] . '_count']       = ['data' => $iItemIndex['field_name']];
                    $payWaysCount[$iItemIndex['field_value'] . '_count'] = 0;
                }
            }

            if ($projectLists) {
                foreach ($projectLists as $key => $list) {
                    $tempMoney = floatval($list['money']);
                    if (intval($list['type']) === 1) {
                        if (check_date_valid($list['pay_time'])) {
                            $totalSpending += $tempMoney;
                            if (isset($payWaysCount[$list['pay_type'] . '_count'])) {
                                $payWaysCount[$list['pay_type'] . '_count'] += -$tempMoney;
                            }
                        }
                        if (check_date_valid($list['invoice_time'])) {
                            $openTotalSpending += $tempMoney;
                        }

                    } else {
                        if (check_date_valid($list['pay_time'])) {
                            $totalRevenue += $tempMoney;
                            if (isset($payWaysCount[$list['pay_type'] . '_count'])) {
                                $payWaysCount[$list['pay_type'] . '_count'] += $tempMoney;
                            }
                        }
                        if (check_date_valid($list['invoice_time'])) {
                            $openTotaRevenue += $tempMoney;
                        }

                    }
                }
            }
            $totalDetail = [
                "net_profit"          => $totalRevenue - $totalSpending,
                "total_revenue"       => $totalRevenue,
                "open_total_revenue"  => $openTotaRevenue,
                "total_spending"      => $totalSpending,
                "open_total_spending" => $openTotalSpending,
            ];
            if (!empty($payWaysCount)) {
                $totalDetail = array_merge($totalDetail, $payWaysCount);
            }
            $data[$index] = array_merge($itemContract, $totalDetail);
        }
        return compact('header', 'data');
    }

    public function getImportContractFields($param)
    {
        return app($this->formModelingService)->getImportFields(self::CUSTOM_TABLE_KEY, $param, trans("contract.import_template"));
    }

    public function getImportOrderFields($param){
        return app($this->formModelingService)->getImportFields('contract_t_order', $param, trans("contract.import_order_template"));
    }

    public function getImportRemindFields($param){
        return app($this->formModelingService)->getImportFields('contract_t_remind', $param, trans("contract.import_remind_template"));
    }

    public function importRemindFilter($data, $param = []){
        $own  = $param['user_info'] ?? [];
        $contract_id = $param['params']['contract_t_id'];
        $model = app($this->formModelingService);
        foreach ($data as $key => $vo){
            $validate = ContractRemindRepository::validateImportInput($vo);
            if($validate){
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail($validate);
                continue;
            }

            // 自定义字段验证
            $result = $model->importDataFilter('contract_t_remind', $vo, $param);
            if (!empty($result)) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail($result);
                continue;
            }
            $data[$key]['contract_t_id'] = $contract_id;
            $data[$key]['importResult'] = importDataSuccess();
        }

        return $data;
    }

    public function importRemind($data, $param){
        app($this->formModelingService)->importCustomData('contract_t_remind', $data, $param);
        return ['data' => $data];
    }

    public function importOrderFilter($data, $param = []){
        $own  = $param['user_info'] ?? [];
        $contract_id = $param['params']['contract_t_id'];

        $model = app($this->formModelingService);
        foreach ($data as $key => $vo){
            $validate = ContractOrderRepository::validateImportInput($vo);
            if($validate){
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail($validate);
                continue;
            }
            // 自定义字段验证
            $result = $model->importDataFilter('contract_t_order', $vo, $param);
            if (!empty($result)) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail($result);
                continue;
            }
            $data[$key]['contract_t_id'] = $contract_id;
            $data[$key]['importResult'] = importDataSuccess();
        }
        return $data;
    }

    public function importOrder($data, $param){

        app($this->formModelingService)->importCustomData('contract_t_order', $data, $param);
        return ['data' => $data];

    }

    public function getImportProjectFields($param){
        return app($this->formModelingService)->getImportFields('contract_t_project', $param, trans("contract.import_project_template"));
    }

    public function importProjectFilter($data, $param = []){
        $own  = $param['user_info'] ?? [];
        $contract_id = $param['params']['contract_t_id'];

        $model = app($this->formModelingService);
        foreach ($data as $key => $vo){
            // 款项性质与金额不填写，默认为0
            if(empty($vo['type'])){
                $data[$key]['type'] = 0;
            }
            if(empty($vo['money'])){
                $data[$key]['money'] = 0;
            }
            if(empty($vo['pay_way'])){
                $data[$key]['pay_way'] = 0;
            }
            // 自定义字段验证
            $result = $model->importDataFilter('contract_t_project', $vo, $param);
            if (!empty($result)) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail($result);
                continue;
            }
            $data[$key]['contract_t_id'] = $contract_id;
            $data[$key]['importResult'] = importDataSuccess();
        }
        return $data;
    }
    public function importProject($data, $param){
        app($this->formModelingService)->importCustomData('contract_t_project', $data, $param);
        return ['data' => $data];
    }

    public function importContract($data, $param)
    {
        app($this->formModelingService)->importCustomData(self::CUSTOM_TABLE_KEY, $data, $param);
        return ['data' => $data];
    }
    public function importContractFilter($data, $param = [])
    {
        $nowTime = time();
        $userId  = $param['user_info']['user_id'] ?? '';
        $tempTitle = [];
        list($ids,$user_arr,$monitor) = $this->getHasPermission($param['user_info']);
        $model = app($this->formModelingService);
        foreach ($data as $k => $v) {
            if($v['main_id']){
                $main_id = explode(',',$v['main_id']);
                if(count($main_id) >1){
                    $data[$k]['importResult'] = importDataFail();
                    $data[$k]['importReason'] = importDataFail(trans('contract.error_number_main'));
                    continue;
                }
                if (!$contractObj = app($this->contractRepository)->getDetail($v['main_id'])) {
                    $data[$k]['importResult'] = importDataFail();
                    $data[$k]['importReason'] = importDataFail(trans('contract.deleted_main_contract'));
                    continue;
                }
                if($contractObj['status'] == 0){
                    $data[$k]['importResult'] = importDataFail();
                    $data[$k]['importReason'] = importDataFail(trans('contract.err_contract_status'));
                    continue;
                }
                if($contractObj['recycle_status'] == 1){
                    $data[$k]['importResult'] = importDataFail();
                    $data[$k]['importReason'] = importDataFail(trans('contract.err_contract_recycle_status'));
                    continue;
                }
                if(!in_array($v['main_id'],$ids)){
                    $data[$k]['importResult'] = importDataFail();
                    $data[$k]['importReason'] = importDataFail(trans('contract.no_private_contract'));
                    continue;
                }
            }

            $number               = $v['number'] ?? '';

            $v['money'] = ltrim($v['money'],"'");
            if($v['money']){
                if($nums = explode('.',$v['money'])){
                    if(strlen($nums[0]) > 18){
                        $data[$k]['importResult'] = importDataFail();
                        $data[$k]['importReason'] = importDataFail(trans('contract.error_money_length'));
                        continue;
                    };
                };
            }
//            $data[$k]['money']    = round($v['money'], 2);
            $data[$k]['money_cp'] = $data[$k]['money'] ?? 0;
            if (!$data[$k]['number'] = $this->checkNumber($number)) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail(trans('contract.0x066003'));
                continue;
            }
            if (key_exists('user_id', $v) && !$v['user_id']) {
                $data[$k]['user_id'] = $userId;
            }

            $data[$k]['status'] = 1;
            $data[$k]['creator'] = (isset($v['creator']) && $v['creator']) ? $v['creator'] : $userId;
            $result = $model->importDataFilter(self::CUSTOM_TABLE_KEY, $data[$k], $param);
            if (!empty($result)) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail($result);
                continue;
            }
            $data[$k]['importResult'] = importDataSuccess();
        }
        return $data;
    }

    public function afterImportContract($data)
    {
        $contractId = $data['id'] ?? 0;
        if (!$contractId) {
            return true;
        }
        $updateData = [
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        app($this->contractRepository)->updateData($updateData, ['id' => $contractId]);
        return true;
    }

    // 获取合同详情菜单
    public function menus(int $id, array $own)
    {
        $menuLists = FormModelingRepository::getCustomTabMenus('contract_t', function (){
            return self::getCustomerTabMenus();
        }, $id);

        if (empty($menuLists)) {
            return $menuLists;
        }
        if($id){
            $share_ids = self::getShareListId(own());
            $data = app($this->contractRepository)->getDetail($id);
            if(!$data){
                return [];
            }
            list($ids,$user_arr,$monitor) = $this->getHasPermission($own,['type_id'=>[$data->type_id]]);
            // 草稿合同或者分享合同直接返回
            $label_custom =[];
            if($data && $data->status && !in_array($id,$share_ids)){

                // 详情页权限
                list($basicFlag,$textFlag,$childFlag,$projectFlag,$orderFlag,$runFlag,$remindFlag,$attachmentFlag,$attachmentDownloadFlag,$label_custom) = $this->groupPermission($data,'view',$user_arr,$own);
            }

            $label = ['contract_info','contract_text','contract_child','contract_statistics','contract_order','contract_process','contract_remind','contract_attachment'];
            foreach ($menuLists as $key => $item) {
                if (!isset($item['key'])) {
                    continue;
                }
                if (isset($item['count'])) {
                    $item['count'] = $this->getCustomerMenuCount($item['key'], $id, $own);
                }
                if($data && $data->status && !in_array($id,$share_ids)){
                    if($item['key'] == 'contract_info'){
                        $item['isShow'] = $item['isShow'] ? $basicFlag : false;
                    }
                    if($item['key'] == 'contract_text'){
                        $item['isShow'] = $item['isShow'] ? $textFlag : false;
                    }
                    if($item['key'] == 'contract_child'){
                        $item['isShow'] = $item['isShow'] ? $childFlag : false;
                    }
                    if($item['key'] == 'contract_statistics'){
                        $item['isShow'] = $item['isShow'] ? $projectFlag : false;
                    }
                    if($item['key'] == 'contract_order'){
                        $item['isShow'] = $item['isShow'] ? $orderFlag : false;
                    }
                    if($item['key'] == 'contract_process'){
                        $item['isShow'] = $item['isShow'] ? $runFlag : false;
                    }
                    if($item['key'] == 'contract_remind'){
                        $item['isShow'] = $item['isShow'] ? $remindFlag : false;
                    }
                    if($item['key'] == 'contract_attachment'){
                        $item['isShow'] = $item['isShow'] ? $attachmentFlag : false;
                    }
                    if(!in_array($item['key'],$label)){
                        if($label_custom && !is_array($label_custom) && $label_custom == 'all'){
                            $item['isShow'] = $item['isShow'];
                        }else{
                            $item['unique'] = $item['key'] .'_'. $item['menu_code'];
                            if($item['isShow']){
                                if($label_custom && in_array($item['unique'],$label_custom)){
                                    $item['isShow'] = true;
                                }else{
                                    $item['isShow'] = false;
                                }
                            }else{
                                $item['isShow'] = false;
                            }
                        }

                    }
                }
                $menuLists[$key] = $item;
            }
        }
        return $menuLists;
    }

    public function allMenus($params){
        $menuLists = FormModelingRepository::getCustomTabMenus('contract_t', function (){
            return self::getCustomerTabMenus();
        }, 0);
        if (empty($menuLists)) {
            return $menuLists;
        }
        $data = array_chunk($menuLists,$params['limit']);
        return [
            'total' => count($menuLists),
            'list'  => $data[$params['page']-1]
        ];
    }

    public static function getCustomerTabMenus()
    {
        $result[] = [
            'key' => 'contract_info',
            'isShow' => true,
            'view' => 'detail',
            'fixed' => true,
        ];
        $result[] = [
            'key' => 'contract_text',
            'isShow' => true,
            'view' => 'text',
            'fixed' => true
        ];
        $result[] = [
            'key' => 'contract_child',
            'isShow' => true,
            'view' => 'child',
            'count' => '',
            'fixed' => true
        ];
        $result[] = [
            'key' => 'contract_statistics',
            'isShow' => true,
            'view' => 'statistics',
            'fixed' => true
        ];
        $result[] = [
            'key' => 'contract_order',
            'isShow' => true,
            'view' => 'order',
            'count' => '',
            'fixed' => true
        ];
        $result[] = [
            "key" => "contract_process",
            "isShow" => true,
            "view" => "related_process",
            'fixed' => true
        ];
        $result[] = [
            "key" => "contract_remind",
            "isShow" => true,
            "view" => "remind",
            'fixed' => true
        ];
        $result[] = [
            "key" => "contract_attachment",
            "isShow" => true,
            "view" => "attachment",
            'fixed' => true
        ];
        return $result;
    }

    public function toggleCustomerMenus(string $menuKey, $own, array $params = [])
    {
        if($menuKey && $menuKey == 'customer_share'){
            if($own['user_id'] != 'admin'){
                return ['code' => '0x024003', 'customer'];
            }
        }
        $fieldTableKey = Arr::get($params, 'field_table_key', 'contract_t');

        FormModelingRepository::toggleCustomTabMenus($fieldTableKey, $menuKey);
        return $this->menus(0,$own);
    }

    // 获取合同详情页面菜单的数据总数
    private function getCustomerMenuCount(string $key, int $id, array $own = [])
    {
        $result = 0;
        switch (strtolower($key)) {
            default:
                $menuParams = [
                    'foreign_key' => $id,
                    'response' => 'count',
                ];

                $list   = app('App\EofficeApp\FormModeling\Services\FormModelingService')->getCustomDataLists($menuParams, $key, $own);
                $result = $list;
        }
        return $result;
    }

    public function shareContract($id, $data, $own){
        $contract_ids = explode(',',trim($id,','));
        // 验证是否有查看权限
        // 获取修改合同的数据
        $params = [
            'search' => ['id' => [$contract_ids,'in']]
        ];
        $result = app($this->formModelingService)->getCustomDataLists($params, self::CUSTOM_TABLE_KEY, $own);
        if(empty($result['list'])){
            return ['code' => ['0x000017', 'common']];
        }
        $type_ids = array_column($result['list'],'raw_type_id');
        list($ids,$user_arr,$monitor) = $this->getHasPermission($own,['type_id'=>$type_ids]);
        // 获取删除合同所属的权限组
        $param = ['type_id' => $type_ids];
        $fieldsGroupLists = app($this->contractTypeService)->getPermissionLists($param,'fields');
        $monitorGroupLists = app($this->contractTypeService)->getPermissionLists($param,'monitor');
        foreach ($result['list'] as $vo){
            if(!in_array($vo->id,$ids)){
                return ['code' => ['0x000017', 'common']];
            }
            if ($vo->recycle_status) {
                return ['code' => ['0x000006', 'common']];
            }
            // 修改跟进人权限
            $flag = $this->getButtonPermission($vo,$user_arr,$own,$fieldsGroupLists,$monitorGroupLists);
            if(!$flag || ($flag && !$flag['data_share'])){
                return ['code' => ['0x000017', 'common']];
            }
        }

        $dept_id = $data['dept_ids'] ? array_unique($data['dept_ids']): [];
        $role_id = $data['role_ids'] ? array_unique($data['role_ids']): [];
        $user_id = $data['user_ids'] ? array_unique($data['user_ids']): [];
        sort($dept_id);sort($role_id);sort($user_id);
        try{
            // 编辑分享，先将原先的记录删除
            ContractShareRepository::deleteShareIds($contract_ids,ContractShareRepository::TABLE_SHARE_DEPT);
            ContractShareRepository::deleteShareIds($contract_ids,ContractShareRepository::TABLE_SHARE_ROLE);
            ContractShareRepository::deleteShareIds($contract_ids,ContractShareRepository::TABLE_SHARE_USER);
            // 再插入数据
            ContractShareRepository::insertShareIds(self::parseShareParams($contract_ids,$dept_id,'dept_id',$own),ContractShareRepository::TABLE_SHARE_DEPT);
            ContractShareRepository::insertShareIds(self::parseShareParams($contract_ids,$role_id,'role_id',$own),ContractShareRepository::TABLE_SHARE_ROLE);
            ContractShareRepository::insertShareIds(self::parseShareParams($contract_ids,$user_id,'user_id',$own),ContractShareRepository::TABLE_SHARE_USER);

        }catch(\Exception $e){
            return ['code'=>['aaa','customer']];
        }
    }

    private static function parseShareParams($ids,$data,$prim_key,$own){
        $returnData = [];
        if($ids && is_array($ids)){
            foreach ($ids as $key => $item){
                if($data && is_array($data)){
                    foreach ($data as $vo){
                        $returnData[] = [
                            'contract_t_id' => $item,
                            $prim_key => $vo,
                            'share_creator' => $own['user_id'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                    }
                }
            }
        }
        return $returnData;
    }

    public function getShare($id){
        // 获取部门分享id
        $dept_ids = ContractShareRepository::getShareDeptIds($id);
        // 获取角色分享id
        $role_ids = ContractShareRepository::getShareRoleIds($id);
        // 获取用户分享id
        $user_ids = ContractShareRepository::getShareUserIds($id);

        return [
            'dept_ids' => $dept_ids ? array_column(((array) $dept_ids),'dept_id') : [],
            'role_ids' => $role_ids ? array_column(((array) $role_ids),'role_id') : [],
            'user_ids' => $user_ids ? array_column(((array) $user_ids),'user_id') : [],
        ];
    }

    public function flowOutUpdate($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $updateData = $data['data'] ?? [];
        $user_id = (isset($data['current_user_id']) && $data['current_user_id']) ? $data['current_user_id'] : $own['user_id'];;
        $updateData['id'] = $data['unique_id'];
        if($list = app($this->contractRepository)->getDetail($data['unique_id'])){
            if(isset($updateData['main_id']) && $updateData['main_id'] && $updateData['main_id'] == $data['unique_id']){
                return ['code' => ['relation_error_contract','contract']];
            }
           
            if(!isset($updateData['user_id'])){
                $updateData['user_id'] = explode(',',$list['user_id']);
            }
            if(!isset($updateData['content'])){
                $updateData['content'] = $list['content'];
            }

            if($list['status'] == 1){
                if(isset($updateData['status']) && $updateData['status'] === 0){
                    return ['code' => ['edit_status_error','contract']];
                }
                $updateData['status'] = 1;
            }
            if(isset($updateData['money'])){
                $updateData['money_cp'] = $updateData['money'];
            }

            $input = ['contract' => $updateData];
            if(isset($updateData['main_id']) && $updateData['main_id']){
                if($childContracts = app($this->contractRepository)->getChildLists($data['unique_id'])){
                    $input['childContracts'] = array_column($childContracts->toArray(),'id');
                };
            }
            $result = app($this->userRepository)->getUserAllData($user_id)->toArray();
            if($result){
                $role_ids = [];
                foreach ($result['user_has_many_role'] as $key => $vo) {
                    $role_ids[] = $vo['role_id'];
                }
                $own = [
                    'user_id' => $user_id,
                    'dept_id' => $result['user_has_one_system_info']['dept_id'],
                    'role_id' => $role_ids,
                ];
            }
            $result = $this->updateContract($data['unique_id'],$input,$own);
            if(isset($result['code'])){
                return $result;
            }
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'contract_t',
                        'field_to' => 'id',
                        'id_to'    => $data['unique_id']
                    ]
                ]
            ];
        }
        return ['code' => ['0x024011','customer']];

    }

    public function flowOutDelete($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $user_id = (isset($data['current_user_id']) && $data['current_user_id']) ? $data['current_user_id'] : $own['user_id'];
        if (!$contractObj = app($this->contractRepository)->getDetail($data['unique_id'])) {
            return ['code' => ['already_delete', 'contract']];
        }
        if($contractObj->recycle_status){
            return ['code' => ['already_delete', 'contract']];
        }
        $info = app($this->userRepository)->getUserAllData($user_id)->toArray();
        if($info){
            $role_ids = [];
            foreach ($info['user_has_many_role'] as $key => $vo) {
                $role_ids[] = $vo['role_id'];
            }
            $own = [
                'user_id' => $user_id,
                'dept_id' => $info['user_has_one_system_info']['dept_id'],
                'role_id' => $role_ids,
            ];
        }
        $result = $this->recycleContract($data['unique_id'],$own);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'contract_t',
                    'field_to' => 'id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }

    public function contractAfterAdd($contractData){
        // 新建成功后添加日志
        $user_id = (isset($contractData['creator']) && $contractData['creator']) ? $contractData['creator'] : own()['user_id'];
        $logContent = trans('contract.created_contract').'：'.$contractData['title'];
        $identify = 'contract.contract_info.add';
        self::saveLogs($contractData['id'], $logContent, $user_id,$identify,$contractData['title']);
    }

    public function typeList(){
        $param['page'] = 0;
        $lists = app($this->contractTypeRepository)->getLists($param);
        return $lists;
    }

    public static function saveLogs($id, $content, $userId,$identify,$title){
        logCenter::info($identify,[
            'creator' => $userId,
            'content' => $content,
            'relation_id' => $id,
            'relation_table' => 'contract_t',
            'relation_title' => $title,
        ]);
//        $data = [
//            'log_creator' => $userId ? $userId : 'admin',
//            'log_type' => 'contract_t',
//            'log_relation_table' => 'contract_t',
//            'log_relation_id' => $id,
//            'log_content' => $content,
//        ];
//        add_system_log($data);
    }

    public function saveUpdateLogs(array $originData, array $inputData, $userId){
        $contract_id = $originData['id'] ?? '';
        if (!$contract_id) {
            return true;
        }
        $words = trans('customer.changed') . "： ".$originData['title']. " ";
        if(isset($originData['a_sign_time']) && isset($originData['a_sign_time']) && $originData['a_sign_time'] == '0000-00-00'){
            $originData['a_sign_time'] = '';
        }
        if(isset($originData['b_sign_time']) && isset($originData['b_sign_time']) && $originData['b_sign_time'] == '0000-00-00'){
            $originData['b_sign_time'] = '';
        }
        // 字段修改
        foreach (self::$focusFields as $field) {
            if(isset($inputData[$field])){
                if ($originData[$field] != $inputData[$field]) {
                    $words .= $this->getCustomerChangedLog($field, $originData[$field], $inputData[$field]) . ', ';
                }
            }
        }

        // 主合同修改(选择器)
        if(isset($inputData['main_id']) && $inputData['main_id'] != $originData['main_id']){
            $oName = $nName = '';
            if($originData['main_id']){
                $ContractData = ContractRepository::getContractById($originData['main_id']);
                $oName = $ContractData->title;
            }
            if($inputData['main_id']){
                $ContractData = ContractRepository::getContractById($inputData['main_id']);
                $nName = $ContractData->title;
            }
            $words .= $this->getCustomerChangedLog('main_id', $oName, $nName) . ', ';
        }

        // 跟进人修改(选择器)
        if(isset($inputData['user_id'])){
            $originData['user_id'] = is_array($originData['user_id']) ? $originData['user_id'] : explode(',',$originData['user_id']);
            $inputData['user_id'] = is_array($inputData['user_id']) ? $inputData['user_id'] : explode(',',$inputData['user_id']);

            if(array_diff($inputData['user_id'],$originData['user_id']) || array_diff($originData['user_id'],$inputData['user_id'])){
                $oName = $nName = '';
                if($originData['user_id']){
                    foreach ($originData['user_id'] as $user_id){
                        $oName .= app($this->userRepository)->getUserName($user_id) .', ';
                    }
                    $oName = trim($oName,', ');
                }
                if($inputData['user_id']){
                    $user_ids = (!is_array($inputData['user_id'])) ? explode(',',$inputData['user_id']) : $inputData['user_id'];
                    foreach ($user_ids as $id){
                        $nName .= app($this->userRepository)->getUserName($id). ', ';
                    }
                    $nName = trim($nName,', ');
                }
                $words .= $this->getCustomerChangedLog('user_id', $oName, $nName) . ', ';
            }
        }
        // 合同状态变更记录
        if(isset($inputData['status'])){
            if($inputData['status'] != $originData['status']){
                $params['search'] = ['field_code' => [['status'], 'in']];
                $fieldLists = app($this->formModelingService)->listCustomFields($params,'contract_t');
                if ($fieldLists) {
                    $field_options = array_column($fieldLists, 'field_options');
                    $field_options = json_decode($field_options[0],1);
                    $statusArray = [];
                    foreach ($field_options['datasource'] as $vv){
                        $statusArray[$vv['id']] = $vv['title'];
                    }

                    $oName = isset($statusArray[$originData['status']]) ?  $statusArray[$originData['status']] : trans('customer.empty');
                    $nName = isset($statusArray[$inputData['status']]) ? $statusArray[$inputData['status']] : trans('customer.empty');
                    $fields = array_column($fieldLists, 'field_name');
                    $langName = $fields[0] ?? '';
                    $words .= $langName . "：" . $oName . "-><span style='color:#E46D0A;'>" . $nName . "</span>";
                }
            }
        }

        // 合同分类下拉框字段修改
        foreach (self::$customerSelectFieldValue as $kys => $field) {
            if (!isset($inputData[$field])) {
                continue;
            }
            if (isset($inputData[$field]) && ($originData[$field] != $inputData[$field])) {
                $oName = app($this->contractTypeRepository)->getDetail($originData[$field])['name'];
                $nName = app($this->contractTypeRepository)->getDetail($inputData[$field])['name'];
                $words .= $this->getCustomerChangedLog($field, $oName, $nName) . ', ';
            }
        }
        $words = rtrim($words, ', ');
        $identify = 'contract.contract_info.edit';
        return self::saveLogs($contract_id, $words, $userId, $identify, $originData['title']);
    }

    public function getCustomerChangedLog($field, $from, $changedTo,$langName = null){
        $from = $from ?: trans('customer.empty');
        $changedTo = $changedTo ?: trans('customer.empty');
        if(!$langName){
            $params['search'] = ['field_code' => [[$field], 'in']];
            $fieldLists = app($this->formModelingService)->listCustomFields($params,'contract_t');
            $langName = "";
            if ($fieldLists) {
                $fields = array_column($fieldLists, 'field_name');
                $langName = $fields[0] ?? '';
            }
        }
        return $langName . "：" . $from . "->" . $changedTo;
    }

    // 获取日志列表
    public function logLists($id,$input,$own)
    {
        $params                                 = $this->parseParams($input);
        $params['search']['log_relation_table'] = [isset($params['relation_table']) ? $params['relation_table'] : self::CUSTOM_TABLE_KEY];
        $params['search']['log_relation_id']    = [$id];
        return app($this->logService)->getSystemLogList($params);
    }

    // 合同分类报表分析
    public function typeReport($type,$data,$own){
        $param = $this->parseParams($data);
        $param['fields'] = ['id','name'];
        $result = [];
        $lists = app($this->contractTypeRepository)->getTypeLists($param);
        if(!empty($lists)){
            if($type == 'number'){
                foreach ($lists as $key => $vo){
                    $result[] = [
                        'id'    => $vo->id,
                        'name'  => $vo->name,
                        'value' => app($this->contractRepository)->getContractCount($vo->id,$param),
                    ];
                }
            }else{
                foreach ($lists as $key => $vo){
                    $result[] = [
                        'id'    => $vo->id,
                        'name'  => $vo->name,
                        'value' => app($this->contractRepository)->getContractMoney($vo->id,$param),
                    ];
                }
            }

        };
        return $result;
    }

    // 合同结算报表分析
    public function projectReport($type,$input,$own){
        $input = $this->parseParams($input);
        $this->parseReportParams($input);
        if(!$input){
            return [];
        }
        switch ($type){
            case 'income-spending':
                // 合同总收入/总支出款
                $data = app($this->contractProjectRepository)->getProjectData($input);

//                $data = app($this->contractProjectProjectRepository)->getProjectData($input);
                return $data;
                break;
            case 'income':
                // 款项收入
                $tempWay = [];
                if($projectLists = app($this->systemComboboxService)->getAllFields(self::CONTRACT_PROJECT_TYPE)){
                    if($projectLists['list']){
                        foreach ($projectLists['list'] as $key => $vt){
                            $tempWay[$key]['name'] = $vt['field_name'];
                            $tempWay[$key]['money'] = app($this->contractProjectRepository)->getProjectPayLists($input,['type'=> 0,'pay_type'=>$vt['field_value']]);
                        }
                    }
                }
                $other[] = [
                    'name' => trans('contract.other'),
                    'money'=> app($this->contractProjectRepository)->getProjectPayLists($input,['type'=> 0,'pay_type'=>0])
                ];
                $tempWay = array_merge($tempWay,$other);
                return $tempWay;
                break;
            case 'statistics':
                // 获取 总收入，净利等
                $data = app($this->contractProjectRepository)->getProjectLists($input);
                return $data;
                break;
            case 'spending':
                // 款项支出
                $tempWay = [];
                if($projectLists = app($this->systemComboboxService)->getAllFields(self::CONTRACT_PROJECT_TYPE)){
                    if($projectLists['list']){
                        foreach ($projectLists['list'] as $key => $vt){
                            $tempWay[$key]['name'] = $vt['field_name'];
                            $tempWay[$key]['money'] = app($this->contractProjectRepository)->getProjectPayLists($input,['type'=> 1,'pay_type'=>$vt['field_value']]);
                        }
                    }
                }
                $other[] = [
                    'name' => trans('contract.other'),
                    'money'=> app($this->contractProjectRepository)->getProjectPayLists($input,['type'=> 1,'pay_type'=>0])
                ];
                $tempWay = array_merge($tempWay,$other);
                return $tempWay;
                break;
            default:
                break;
        }
    }

    // 合同金额统计
    public function contractMoney($data,$own){
        $param = $this->parseParams($data);

        $default = [
            'search' => ['recycle_status'=>[0],'status'=>[1]],
            'page' => 0,
            'fields' => ['id','title','money']
        ];
        if(isset($param['search'])){
            $search = array_merge($default['search'],$param['search']);
            $default['search'] = $search;
        }
        if($contractData = app($this->contractRepository)->getContractLists($default)){
            return $contractData;
        };
        return [];
    }

    private function parseReportParams(&$input) : void {
        // 获取有权限的合同id
        $default = [
            'search' => ['recycle_status'=>[0]],
            'page' => 0,
            'fields' => ['id']
        ];
        if(isset($input['search'])){
            $aa = array_merge($default['search'],$input['search']);
            $default['search'] = $aa;
        }
        $contractData = app($this->contractRepository)->getContractLists($default);

        if(!$contractData){
            $input = [];
        }
        $ids = array_column($contractData->toArray(),'id');
        $search = [
            'search' => ['contract_t_id' => [$ids,'in']]
        ];

        $input['search'] = $search['search'];
//        $project_data = app($this->contractProjectRepository)->getIds($ids);
//        if(!$project_data){
//            $input = [];
//        }
//        $project_ids = array_column($project_data,'project_id');
//        $search = [
//            'search' => ['project_id' => [$project_ids,'in']]
//        ];
//        $input['search'] = $search['search'];

    }

    public function userSelector($input,$own){
        // 获取自定义字段设置添加的人员选择器
        $tempArray = [];
        $result = ContractRepository::getUserSelector(['field_table_key'=>'contract_t','field_directive'=>'selector']);
        if(empty($result)){
            return [];
        }
        foreach ($result as $key => $vo){
            $field_options = json_decode($vo->field_options,1);
            if($field_options['selectorConfig'] && $field_options['selectorConfig']['type'] == 'user' && !isset($field_options['parentField'])){
                $params['search'] = ['field_code' => [$vo->field_code]];
                $fieldLists = app($this->formModelingService)->listCustomFields($params,'contract_t');
                $name = '';
                $multiple = 0;
                if($fieldLists){
                    $fields = array_column($fieldLists, 'field_name');
                    $field_options = array_column($fieldLists, 'field_options');
                    $field_options = json_decode($field_options[0],1);
                    $multiple = (isset($field_options['selectorConfig']['multiple']) && $field_options['selectorConfig']['multiple'] == 1) ? 1 : 0;
                    $name = $fields[0];
                }
                $tempArray[] = [
                    'field' => $vo->field_code,
                    'name' => $name,
                    'multiple' => $multiple,
                ];
            }
        }
        return $tempArray;
    }

    public function getTypePermissionDetail($type_id){
        $permission = app($this->contractTypePermissionRepository)->getTypeDetail($type_id);
        if(!empty($permission)){
            foreach (self::$labelName as $vo){
                if(isset($permission->{$vo})){
                    $permission->{$vo} = json_decode($permission->{$vo},1);
                }
            }
        }
        return $permission ? $permission->toArray() : [];
    }

    public function getHasPermission($own,$params = [],$recycle = false){
        if(empty($own)){
            return [];
        }
        $fieldsDataIds = $monitorDataIds = [];
        $user_arr = $permission = [];
        // 数据权限(根据关联字段上下级来获取id集合)
        if($dataGroupLists = app($this->contractTypePermissionRepository)->getLists($params)){
            list($fieldsDataIds,$user_arr) =  $this->paresTypeList($dataGroupLists,$own,'fields',$recycle);
        }
        // 监控权限(根据选择部门,角色,人员来获取id集合)
        if($monitorGroupLists = app($this->contractTypeMonitorPermissionRepository)->groupList($params)){
            list($monitorDataIds,$permission) =  $this->paresTypeList($monitorGroupLists,$own,'monitor',$recycle);
        }
        $ids =  array_merge($fieldsDataIds,$monitorDataIds);
        return [$ids,$user_arr,$permission];
    }

    /**
     * 根据数据权限组/监控权限组，获取该分类下可查看的合同id集合
     */
    public function paresTypeList($lists,$own,$sign = 'fields',$recycle){
        $data = app($this->contractTypeRepository)->getLists(['fields'=>['id']]);
        $type_ids = array_column($data->toArray(),'id');
        $ids  = $type_id = $result = [];
        $user_arr = [];
        $monitor_arr = [];
        foreach ($lists as $key => $vo){
            $type_id = ($vo['type_id'] == 'all') ? $type_ids : explode(',',$vo['type_id']);
            if($sign == 'fields'){
                list($result,$users) = $this->fieldsGroupIds($vo,$own,$type_id,$recycle);
                if($users){
                    $user_arr[$vo['id']] = $users;
                }
            }else{
                list($result,$monitor_arr) = $this->monitorGroupIds($vo,$own,$type_id,$recycle);
            }
            if($result){
                $ids = array_merge($ids,$result);
            }

        }
        $ids = array_unique($ids);
        sort($ids);
        return ($sign == 'fields') ? [$ids,$user_arr] : [$ids,$monitor_arr];

    }

    // 根据数据权限组获取有权限的id集合
    public function fieldsGroupIds($vo,$own,$type_id,$recycle = false){
        $user_array = [];
        $ids = [];
        // 如果没有查看权限，则直接返回空集合
        if(!$vo['data_view'] && !$vo['all_privileges']){
            return [[],[]];
        }
        if($vo['relation_fields']){
            // 关联字段存在
            // 本人
            if($vo['own']){
                array_push($user_array,$own['user_id']);
            }
            // 上级可看,获取当前操作人员的下级
            if($vo['superior']){
                $result = app($this->userService)->getSubordinateArrayByUserId($own['user_id'],['include_leave' => true]);
                if($result['id']){
                    $user_array = array_merge($user_array,$result['id']);
                }
            }
            // 所有上级(获取当前人员的所有下级)
            if($vo['all_superior']){
                $ancestor = app($this->userService)->getSubordinateArrayByUserId($own['user_id'], ['all_subordinate' => 1, 'include_leave' => true]);
                if($ancestor['id']){
                    $user_array = array_merge($user_array,$ancestor['id']);
                }
            }
            // 部门负责人
            if($vo['department_header']){
                $director = app($this->departmentDirectorRepository)->getManageDeptByUser($own['user_id'])->pluck('dept_id')->toArray();
                if (!empty($director)) {
                    // 如果是部门负责人，则获取当前部门下的人员id
                    $belongsDept = app($this->userRepository)->getUserByAllDepartment($director)->pluck('user_id')->toArray();
                    $user_array = array_merge($user_array,$belongsDept);
                }
            }

            // 同部门人员
            if($vo['department_person']){
                if($result = UserService::getUserIdsByDeptIds([$own['dept_id']])){
                    $user_array = array_merge($user_array,$result);
                };
            }

            // 同角色人员
            if($vo['role_person']){
                if($result = UserService::getUserIdsByRoleIds($own['role_id'])){
                    $user_array = array_merge($user_array,$result);
                };
            }
            // 去重
            $user_array = array_unique($user_array);
            if($user_array){
                sort($user_array);
                $searchFields = [
                    $vo['relation_fields'] => $user_array,
                    'type_id' => [$type_id,'in'],
                    'status' => [1],
                    'recycle_status' => [0]
                ];
                if($recycle){
                    $searchFields['status'] = [[0,1],'in'];
                    $searchFields['recycle_status'] = [1];
                }
                $ids =  app($this->contractRepository)->getContractIdsByFields($searchFields,$vo['relation_fields'],$this->checkMultiple($vo));
            }
            return [$ids,$user_array];
        }
    }

    // 根据监控权限组获取有权限的id集合
    public function monitorGroupIds($vo,$own,$type_id,$recycle){
        // 如果没有查看权限，则直接返回空集合
        $user = [];
        $params = [
            'type_id' => [$type_id,'in'],
            'status' => [1],
            'recycle_status' => [0]
        ];
        if($recycle){
            $params['status'] = [[0,1],'in'];
            $params['recycle_status'] = [1];
        }
        if(!$vo['data_view'] && !$vo['all_privileges']){
            return [[],[]];
        }
        if($vo['all_user']){
            $ids = app($this->contractRepository)->getContractIds($params);
            return [$ids,$user];
        }
        // 根据人员，角色，部门判断权限
        $user_ids = $vo['user_ids'] ? explode(',',$vo['user_ids']) : [];
        $dept_ids = $vo['dept_ids'] ? explode(',',$vo['dept_ids']) : [];
        $role_ids = $vo['role_ids'] ? explode(',',$vo['role_ids']) : [];
        $userFlag = ($user_ids && in_array($own['user_id'],$user_ids)) ? 1 : 0;
        $deptFlag = ($dept_ids && in_array($own['dept_id'],$dept_ids)) ? 1 : 0;
        $roleFlag = ($role_ids && array_intersect($role_ids,$own['role_id'])) ? 1 : 0;
        if($userFlag || $deptFlag || $roleFlag){
            $ids = app($this->contractRepository)->getContractIds($params);
            return [$ids,$user];
        }
    }



    public function checkMultiple($item){
        $multiple = 0;
        if($result = DB::table('custom_fields_table')->where(['field_table_key' => 'contract_t','field_code' =>$item['relation_fields']])->first()){
            $field_options = json_decode($result->field_options,1);
            $multiple = (isset($field_options['selectorConfig']['multiple']) && $field_options['selectorConfig']['multiple'] == 1) ? 1 : 0;
        };
        return $multiple;
    }

    public function BeforeOrderDetail($id){
        $hasMenuArr = app($this->userMenuService)->getUserMenus(own()['user_id']);
        if(!array_intersect([151,152],$hasMenuArr['menu'])){
            return ['code' => ['error_view', 'contract']];
        }
        if(!$orderObj = DB::table('contract_t_order')->where('id',$id)->first()){
            return ['code' => ['already_delete_order', 'contract']];
        }
        // 对应合同是否存在
        if (!$contractObj = app($this->contractRepository)->getDetail($orderObj->contract_t_id)) {
            return ['code' => ['already_delete', 'contract']];
        }

        $menuLists = FormModelingRepository::getCustomTabMenus('contract_t', function (){
            return self::getCustomerTabMenus();
        }, 0);
        if (empty($menuLists)) {
            return ['code' => ['error_view', 'contract']];
        }
        foreach ($menuLists as $menu){
            if($menu['key'] == 'contract_order' && $menu['isShow'] != 1){
                return ['code' => ['hidden_label', 'contract']];
            }
        }
        // 草稿合同
        if(!$contractObj->status && $contractObj->creator && $contractObj->creator == own()['user_id']){
            return true;
        }

        $recycle = false;
        if($contractObj->recycle_status){
            $recycle = true;
        }
        // 验证是否有查看权限
        list($ids,$user_arr,$monitor) = $this->getHasPermission(own(),['type_id'=>[$contractObj->type_id]],$recycle);
        // 他人分享给你的合同自己没有权限可以查看
        $share_ids = self::getShareListId(own());

        // 分享合同
        if(in_array($orderObj->contract_t_id,$share_ids)){
            return true;
        }

        if(!$ids || !in_array($orderObj->contract_t_id,$ids)){
            return ['code' => ['0x000017', 'common']];
        }
        // 标签查看权限
        list(
            $basicView,
            $textView,
            $childView,
            $projectView,
            $orderView,
            $runView,
            $remindView,
            $attachmentView,
            $attachmentDownload
            ) = $this->groupPermission($contractObj,'view',$user_arr,own());

//        $share_ids = $this->getShareIds(own());
        $exists_share = in_array($contractObj->id,$share_ids) ? true : false;
        if(!$orderView && !$exists_share){
            return ['code' => ['0x000017', 'common']];
        }
        return true;
    }

    public function BeforeProjectDetail($id){
        $hasMenuArr = app($this->userMenuService)->getUserMenus(own()['user_id']);
        if($hasMenuArr && !array_intersect([151,152],$hasMenuArr['menu'])){
            return ['code' => ['error_view', 'contract']];
        }
        if(!$projectObj = DB::table('contract_t_project')->where('id',$id)->first()){
            return ['code' => ['already_delete_project', 'contract']];
        }
        // 对应合同是否存在
        if (!$contractObj = app($this->contractRepository)->getDetail($projectObj->contract_t_id)) {
            return ['code' => ['already_delete', 'contract']];
        }
        $menuLists = FormModelingRepository::getCustomTabMenus('contract_t', function (){
            return self::getCustomerTabMenus();
        }, 0);
        if (empty($menuLists)) {
            return ['code' => ['error_view', 'contract']];
        }
        foreach ($menuLists as $menu){
            if($menu['key'] == 'contract_statistics' && $menu['isShow'] != 1){
                return ['code' => ['hidden_label', 'contract']];
            }
        }

        // 草稿合同查看
        if(!$contractObj->status && $contractObj->creator && $contractObj->creator == own()['user_id']){
            return true;
        }
        $recycle = false;
        if($contractObj->recycle_status){
            $recycle = true;
        }
        // 验证是否有查看权限
        list($ids,$user_arr,$monitor) = $this->getHasPermission(own(),['type_id'=>[$contractObj->type_id]],$recycle);
        $share_ids = self::getShareListId(own());
        // 分享合同
        if(in_array($projectObj->contract_t_id,$share_ids)){
            return true;
        }
        if(!$ids || !in_array($projectObj->contract_t_id,$ids)){
            return ['code' => ['0x000017', 'common']];
        }
        // 标签查看权限
        list(
            $basicView,
            $textView,
            $childView,
            $projectView,
            $orderView,
            $runView,
            $remindView,
            $attachmentView,
            $attachmentDownload
            ) = $this->groupPermission($contractObj,'view',$user_arr,own());
//        $share_ids = $this->getShareIds(own());
        $exists_share = in_array($contractObj->id,$share_ids) ? true : false;
        if(!$projectView && !$exists_share){
            return ['code' => ['0x000017', 'common']];
        }
        return true;
    }
    public function BeforeRemindDetail($id){
        $hasMenuArr = app($this->userMenuService)->getUserMenus(own()['user_id']);
        if(!array_intersect([151,152],$hasMenuArr['menu'])){
            return ['code' => ['error_view', 'contract']];
        }
        if(!$remindObj = DB::table('contract_t_remind')->where('id',$id)->first()){
            return ['code' => ['already_delete_remind', 'contract']];
        }
        // 对应合同是否存在
        if (!$contractObj = app($this->contractRepository)->getDetail($remindObj->contract_t_id)) {
            return ['code' => ['already_delete', 'contract']];
        }

        $menuLists = FormModelingRepository::getCustomTabMenus('contract_t', function (){
            return self::getCustomerTabMenus();
        }, 0);
        if (empty($menuLists)) {
            return ['code' => ['error_view', 'contract']];
        }
        foreach ($menuLists as $menu){
            if($menu['key'] == 'contract_remind' && $menu['isShow'] != 1){
                return ['code' => ['hidden_label', 'contract']];
            }
        }
        // 草稿合同
        if(!$contractObj->status && $contractObj->creator && $contractObj->creator == own()['user_id']){
            return true;
        }
        $recycle = false;
        if($contractObj->recycle_status){
            $recycle = true;
        }
        // 验证是否有查看权限
        list($ids,$user_arr,$monitor) = $this->getHasPermission(own(),['type_id'=>[$contractObj->type_id]],$recycle);
        // 他人分享给你的合同自己没有权限可以查看
        $share_ids = self::getShareListId(own());
        // 分享合同
        if(in_array($remindObj->contract_t_id,$share_ids)){
            return true;
        }

        if(!$ids || !in_array($remindObj->contract_t_id,$ids)){
            return ['code' => ['0x000017', 'common']];
        }
        // 标签查看权限
        list(
            $basicView,
            $textView,
            $childView,
            $projectView,
            $orderView,
            $runView,
            $remindView,
            $attachmentView,
            $attachmentDownload
            ) = $this->groupPermission($contractObj,'view',$user_arr,own());

        $exists_share = in_array($contractObj->id,$share_ids) ? true : false;
        if(!$remindView && !$exists_share){
            return ['code' => ['0x000017', 'common']];
        }
        return true;
    }

    public function singlePermission($contractObj,$type = 'edit',$label){
        $param = [
            'type_id' => [$contractObj->type_id],
        ];
        $fieldsGroupLists = app($this->contractTypeService)->getPermissionLists($param,'fields');
        $monitorGroupLists = app($this->contractTypeService)->getPermissionLists($param,'monitor');
        $result = array_merge_recursive($fieldsGroupLists,$monitorGroupLists);
        $lists = $this->menus(0,own());
        $menuList = [];
        // 菜单被隐藏，直接返回无权限
        foreach ($lists as $key => $value){
            $menuList[$value['key']] = $value['isShow'];
        }
        if($label && isset($menuList[$label]) && !$menuList[$label]){
            return [false];
        }
        $flag = false;
        foreach ($result as $item){
            if($item['all_privileges']){
                $flag = true;
                return [$flag];
            }
            if($item['label_project'][$type] == 1 && $label == 'contract_statistics'){
                $flag = true;
            }
            if($item['label_order'][$type] == 1 && $label == 'contract_order'){
                $flag = true;
            }
            if($item['label_remind'][$type] == 1 && $label == 'contract_remind'){
                $flag = true;
            }
        }
        return [$flag];
    }

    // 外联提醒计划标签列表权限过滤
    public function filterRemindList($params){
        $own = $params['user_info'] ?? [];
        if(own()){
            $user_id = own()['user_id'];
        }else if($own){
            $user_id = $own['user_id'];
        }
        $hasMenuArr = app($this->userMenuService)->getUserMenus($user_id);
        if($hasMenuArr && !array_intersect([151,152],$hasMenuArr['menu'])){
            return ['code' => ['error_view', 'contract']];
        }
        return [];
        if(isset($params['foreign_key']) && $params['foreign_key']){
            $contractObj = app($this->contractRepository)->getDetail($params['foreign_key']);
            if(!$contractObj->status){
                return [];
            }
            $menuLists = FormModelingRepository::getCustomTabMenus('contract_t_remind', function (){
                return self::getCustomerTabMenus();
            }, 0);
            if (empty($menuLists)) {
                return $menuLists;
            }
            foreach ($menuLists as $menu){
                if($menu['key'] == 'contract_remind' && $menu['isShow'] != 1){
                    // 如果没权限，则直接返回一个查询不到的id
                    return ['remind_id' => [[0], 'in']];
                }
            }
            $foreign_field = array_column($menuLists,'foreign_key');
            if(isset($params['foreign_key']) && $foreign_field){
                // 根据外键获取对应的合同id
                $contractIds = DB::table('contract_t_remind')->where($foreign_field[0],$params['foreign_key'])->pluck('contract_t_id')->toArray();
                list($ids,$user_arr,$monitor) = $this->getHasPermission(own());
                $contract_ids = array_intersect($contractIds,$ids);
                if(!$ids || !$contract_ids){
                    return ['remind_id' => [[0], 'in']];
                }
                return ['contract_t_id' => [$contract_ids, 'in']];
            }
        }
    }

    // 外联订单标签列表权限权限过滤
    public function filterOrderList($params){
        $own = $params['user_info'] ?? [];
        if(own()){
            $user_id = own()['user_id'];
        }else if($own){
            $user_id = $own['user_id'];
        }
        $hasMenuArr = app($this->userMenuService)->getUserMenus($user_id);
        if($hasMenuArr && !array_intersect([151,152],$hasMenuArr['menu'])){
            return ['code' => ['error_view', 'contract']];
        }
        return [];
        if(isset($params['foreign_key']) && $params['foreign_key']){
            // 根据外键获取当前订单的合同主键
            $contractObj = app($this->contractRepository)->getDetail($params['foreign_key']);

            if(!$contractObj->status){
                return [];
            }
            $menuLists = FormModelingRepository::getCustomTabMenus('contract_t_order', function (){
                return self::getCustomerTabMenus();
            }, 0);
            if (empty($menuLists)) {
                return $menuLists;
            }
            $foreignMenu = [];
            foreach ($menuLists as $menu){
                if($menu['key'] == 'contract_order' && $menu['isShow'] != 1){
                    // 如果没权限，则直接返回一个查询不到的id
                    return ['order_id' => [[0], 'in']];
                }
            }
            $foreign_field = array_column($menuLists,'foreign_key');
            if(isset($params['foreign_key']) && $foreign_field){
                // 根据外键获取对应的合同id
                $contractIds = DB::table('contract_t_order')->where($foreign_field[0],$params['foreign_key'])->pluck('contract_t_id')->toArray();
                if($contractIds){
                    list($ids,$user_arr,$monitor) = $this->getHasPermission(own());
                    $contract_ids = array_intersect($contractIds,$ids);
                    if(!$ids || !$contract_ids){
                        return ['order_id' => [[0], 'in']];
                    }
                    return ['contract_t_id' => [$contract_ids, 'in']];
                }
            }
        }
    }

    // 外联标签列表权限权限过滤
    public function filterProjectList($params){
        $own = $params['user_info'] ?? [];
        if(own()){
            $user_id = own()['user_id'];
        }else if($own){
            $user_id = $own['user_id'];
        }
        $hasMenuArr = app($this->userMenuService)->getUserMenus($user_id);
        if($hasMenuArr && !array_intersect([151,152],$hasMenuArr['menu'])){
            return ['code' => ['error_view', 'contract']];
        }
        return [];
        if(isset($params['foreign_key']) && $params['foreign_key']){
            $contractObj = app($this->contractRepository)->getDetail($params['foreign_key']);
            if(!$contractObj->status){
                return [];
            }
            $menuLists = FormModelingRepository::getCustomTabMenus('contract_t_project', function (){
                return self::getCustomerTabMenus();
            }, 0);
            if (empty($menuLists)) {
                return $menuLists;
            }
            foreach ($menuLists as $menu){
                if($menu['key'] == 'contract_statistics' && $menu['isShow'] != 1){
                    // 如果没权限，则直接返回一个查询不到的id
                    return ['project_id' => [[0], 'in']];
                }
            }
            $foreign_field = array_column($menuLists,'foreign_key');
            if(isset($params['foreign_key']) && $foreign_field){
                // 根据外键获取对应的合同id
                $contractIds = DB::table('contract_t_project')->where($foreign_field[0],$params['foreign_key'])->pluck('contract_t_id')->toArray();
                if($contractIds){
                    list($ids,$user_arr,$monitor) = $this->getHasPermission(own());

                    $contract_ids = array_intersect($contractIds,$ids);
                    if(!$ids || !array_intersect($contractIds,$ids)){
                        return ['project_id' => [[0], 'in']];
                    }
                    return ['contract_t_id' => [$contract_ids, 'in']];
                }
            }
        }
    }

    public function BeforeContractDetail($params){
        $hasMenuArr = app($this->userMenuService)->getUserMenus(own()['user_id']);
        if(!array_intersect([151,152],$hasMenuArr['menu'])){
            return ['code' => ['error_view', 'contract']];
        }
        if (!$contractObj = app($this->contractRepository)->getDetail($params)) {
            return ['code' => ['already_delete', 'contract']];
        }
        // 草稿合同不走权限
        if(!(!$contractObj->status && $contractObj->creator && $contractObj->creator == own()['user_id'])){
            // 验证是否有查看权限
            $recycle = false;
            if($contractObj->recycle_status){
                $recycle = true;
            }
            list($ids,$user_arr,$monitor) = $this->getHasPermission(own(),['type_id'=>[$contractObj->type_id]],$recycle);
            $share_ids = self::getShareListId(own());
            if(!in_array($params,$share_ids)){
                if(!$ids || !in_array($params,$ids)){
                    return ['code' => ['error_view', 'contract']];
                }
            }
        }
    }

    public function contractExportOrder($params){
        $own = $params['user_info'];
        return app($this->formModelingService)->exportFields('contract_t_order', $params, $own, trans('contract.contract_export_order_template'));
    }

    public function contractExportRemind($params){
        $own = $params['user_info'];
        return app($this->formModelingService)->exportFields('contract_t_remind', $params, $own, trans('contract.contract_export_remind_template'));
    }

    public function projectList($input,$own){
        $param = $this->parseParams($input);
        if(isset($param['include']) && $param['include'] == 1){
            $contractId = $param['contract_t_id'];
            $childLists = app($this->contractRepository)->getChildLists($contractId);
            if(!empty($childLists)){
                $id = array_column($childLists->toarray(),'id');
                list($ids,$user_arr,$monitor) = $this->getHasPermission($own);
                if($id = array_intersect($id,$ids)){
                    $param['search']['contract_t_id'][0] = array_merge($param['search']['contract_t_id'][0],$id);
                }

            }
        }
        $result = app($this->formModelingService)->getCustomDataLists($param, 'contract_t_project', $own);
        return $result;
    }

}
