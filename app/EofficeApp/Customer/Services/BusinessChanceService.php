<?php

namespace App\EofficeApp\Customer\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Customer\Repositories\BusinessChanceLogRepository;
use App\EofficeApp\Customer\Repositories\BusinessChanceRepository;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use App\EofficeApp\Customer\Services\CustomerService;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use Illuminate\Support\Facades\Log;

class BusinessChanceService extends BaseService
{
    const CUSTOM_TABLE_KEY = 'customer_business_chance';
    // 商机阶段下拉框标识
    const CHANCE_STEP_VALUE = 15;

    public function __construct()
    {
        $this->repository                    = 'App\EofficeApp\Customer\Repositories\BusinessChanceRepository';
        $this->businessChanceLogRepository   = 'App\EofficeApp\Customer\Repositories\BusinessChanceLogRepository';
        $this->systemComboboxService         = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->systemComboboxFieldRepository = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxFieldRepository';
        $this->customerService               = 'App\EofficeApp\Customer\Services\CustomerService';
        $this->userRepository                = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->formModelingService           = 'App\EofficeApp\FormModeling\Services\FormModelingService';
        $this->attachmentService             = 'App\EofficeApp\Attachment\Services\AttachmentService';
    }

    public function lists($input, $own)
    {
        $result = app($this->formModelingService)->getCustomDataLists($input, self::CUSTOM_TABLE_KEY, $own);
        if (!isset($result['list']) || empty($result['list'])) {
            return $result;
        }
        $ids                       = array_column((array) $result['list'], 'chance_id');
        list($result['updateIds']) = BusinessChanceRepository::getPermissionIds([CustomerRepository::UPDATE_MARK], $own, $ids);
        return $result;
    }

    public function filterBusinessChanceLists(&$params,$own)
    {
        $result = $multiSearchs = $chanceIds = [];
        if (isset($params['search']['multiSearch'])) {
            $multiSearchs = $params['search']['multiSearch'];
            unset($params['search']['multiSearch']);
        }
        $ids = CustomerRepository::getViewIds($own);
        if (!empty($multiSearchs)) {
            $chanceIds = app($this->repository)->multiSearchIds($multiSearchs, $ids);
            $result = ['customer_business_chance.chance_id' => [$chanceIds, 'in']];
        }
        if ($ids === CustomerRepository::ALL_CUSTOMER) {
            return $result;
        }
        $result = array_merge($result, ['customer_business_chance.customer_id' => [$ids, 'in']]);
        return $result;
    }

    public function show(int $id, array $own)
    {
        if (!$validate = BusinessChanceRepository::validatePermission([CustomerRepository::VIEW_MARK], $id, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $result = app($this->formModelingService)->getCustomDataDetail(self::CUSTOM_TABLE_KEY, $id);
        $result = (array) $result;
        $result['chance_step_name'] =  app($this->systemComboboxService)->getComboboxFieldsNameByIdentify('BUSINESS_STAGE', $result['chance_step']);
        return $result;
    }

    public function store($data, $own)
    {
        $userId = $own['user_id'] ?? '';
        $validate = BusinessChanceRepository::validateInput($data, 0, $own);
        if (isset($validate['code'])) {
            return $validate;
        }
        if (!$result = app($this->formModelingService)->addCustomData($data, self::CUSTOM_TABLE_KEY)) {
            return ['code' => ['0x024004', 'customer']];
        }
        if (isset($result['code'])) {
            return $result;
        }
        app($this->repository)->updateData($data, ['chance_id' => $result]);
        $logData = [
            'chance_id'          => $result,
            'log_content'        => $own['user_name'] . trans('customer.edit_business_chance'),
            'chance_log_creator' => $own['user_id'],
            'chance_step'        => $data['chance_step'] ?? '',
            'first_possibility'  => $data['chance_possibility'] ?? '',
            'this_possibility'   => $data['chance_possibility'] ?? '',
        ];
       $chance = app($this->businessChanceLogRepository)->insertData($logData);

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($chance->chance_id);

        return $chance;
    }

    // 外发创建商机
    public function flowStore(array $params)
    {
        $data   = $params['data'] ?? [];
        $userId = (isset($data['chance_creator']) && $data['chance_creator']) ? $data['chance_creator'] : $params['current_user_id'];
        $created_at = (isset($data['created_at']) && $data['created_at']) ? $data['created_at'] : date('Y-m-d H:i:s',time());
        // $own    = app($this->userRepository)->getUserAllData($userId)->toArray();
        $own    = own();
        $data['chance_creator'] = $userId;
        $data['created_at'] = $created_at;
        if(empty($data['customer_id'])){
            return ['code' => ['0x024028','customer']];
        }
        if($userId != $own['user_id']){
            $result = app($this->userRepository)->getUserAllData($userId)->toArray();
            if($result){
                $role_ids = [];
                foreach ($result['user_has_many_role'] as $key => $vo) {
                    $role_ids[] = $vo['role_id'];
                }
                $own = [
                    'user_id'  => $userId,
                    'dept_id'  => $result['user_has_one_system_info']['dept_id'],
                    'role_id'  => $role_ids,
                    'user_name'=> $result['user_name']
                ];
            }
        }
        if (!$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$data['customer_id']], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }else{
            $result = $this->store($data, $own);
            if (isset($result['code'])) {
                return $result;
            }
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'customer_business_chance',
                        'field_to' => 'chance_id',
                        'id_to' => $result['chance_id']
                    ]
                ]
            ];
        }
    }

    public function delete(array $ids, array $own)
    {
        $userId = $own['user_id'] ?? '';
        $customerIds = BusinessChanceRepository::getCustomerIds($ids);
        if ($userId !== 'admin' || empty($customerIds) || !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], $customerIds, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $result = app($this->repository)->deleteById($ids);
        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($ids);

        return $result;
    }

    public function customerLinkmans(int $customerId)
    {
        return app($this->repository)->customerLinkmans($customerId);
    }

    public function update(int $id, array $data, array $own)
    {
        $validate = BusinessChanceRepository::validateInput($data, $id, $own);
        if (isset($validate['code'])) {
            return $validate;
        }
        list($originStar, $originPossibility, $originChanceStep) = app($this->repository)->getColumns(['business_star', 'chance_possibility', 'chance_step'], ['chance_id' => $id]);
        if (!$result = app($this->formModelingService)->editCustomData($data, self::CUSTOM_TABLE_KEY, $id)) {
            return ['code' => ['0x024004', 'customer']];
        }
        app($this->repository)->updateData(['chance_name_py'=> $data['chance_name_py'],'chance_name_zm'=>$data['chance_name_zm']], ['chance_id' => $id]);
        // 修改了商机等级，写入日志
        if ($originPossibility != $data['chance_possibility'] || $originChanceStep != $data['chance_step']) {
            $logData = [
                'chance_id'          => $id,
                'log_content'        => $own['user_name'] . trans('customer.edit_business_chance'),
                'chance_log_creator' => $own['user_id'],
                'chance_step'        => $data['chance_step'],
                'last_possibility'   => $originPossibility,
                'this_possibility'   => $data['chance_possibility'],
            ];
            app($this->businessChanceLogRepository)->insertData($logData);
        }
        $result = app($this->formModelingService)->editCustomData($data, self::CUSTOM_TABLE_KEY, $id);

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($id);

        return $result;
//        return app($this->repository)->updateData($data, ['chance_id' => $id]);
    }

    public function logLists($params)
    {
        $params                  = $this->parseParams($params);
        $data                    = $this->response(app($this->businessChanceLogRepository), 'total', 'lists', $params);
        $data['chanceStepLists'] = app($this->systemComboboxFieldRepository)->getComboboxFieldsNameByComboboxId(self::CHANCE_STEP_VALUE);
        if($data['list']){
            foreach ($data['list'] as $key => $vo){
                $data['list'][$key]['attachment'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => self::CUSTOM_TABLE_KEY, 'entity_id' => $vo->chance_log_id]);
            }
        }
        return $data;
    }

    public function storeLog($chanceId, $data, $own)
    {
        $userId = $own['user_id'] ?? '';
        $validate = BusinessChanceLogRepository::validateInput($data, $chanceId, $own);
        if (isset($validate['code'])) {
            return $validate;
        }
        // 附件
        $attachments = [];
        if (isset($data['attachments'])) {
            $attachments = $data['attachments'];
            unset($data['attachments']);
        }
        list($originPossibility, $originChanceStep) = app($this->repository)->getColumns(['chance_possibility', 'chance_step'], ['chance_id' => $chanceId]);
        $newPossibility = $data['chance_possibility'] + $originPossibility;
        if ($newPossibility > 100) {
            return ['code' => ['0x024031', 'customer']];
        }
        if ($newPossibility < 0) {
            return ['code' => ['error_possibility', 'customer']];
        }
        $logData                                    = [
            'chance_id'          => $chanceId,
            'log_content'        => $data['log_content'],
            'chance_log_creator' => $userId,
            'chance_step'        => $data['chance_step'],
            'last_possibility'   => $originPossibility,
            'this_possibility'   => $newPossibility,
        ];

        // 判断注释，看后面是否是需求导致加的判断
//        if ($originPossibility != $data['chance_possibility'] || $originChanceStep != $data['chance_step']) {
            $chanceData = [
                'chance_step'        => $data['chance_step'],
                'chance_possibility' => $newPossibility,
                'business_star'      => BusinessChanceRepository::getBusinessChancesStar($newPossibility, $data['chance_step']),
            ];
            app($this->repository)->updateData($chanceData, ['chance_id' => $chanceId]);
//        }
        if(!$list = app($this->businessChanceLogRepository)->insertData($logData)){
            return ['code' => ['0x024004', 'customer']];
        };
        if (!empty($attachments)) {
            app($this->attachmentService)->attachmentRelation(self::CUSTOM_TABLE_KEY, $list->chance_log_id, $attachments);
        }
        return $list;
    }

    public function exportBusinessChance($params)
    {
        $own = $params['user_info'];
        return app($this->formModelingService)->exportFields(self::CUSTOM_TABLE_KEY, $params, $own, trans('customer.business_opportunity_export'));
    }


    /**
     * 使用消息队列更新全站搜索数据
     *
     * @param   string|int  $id
     */
    public function updateGlobalSearchDataByQueue($ids)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchCustomerBusinessChanceMessage($ids);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    // 业务机会外发更新
    public function flowOutUpdate($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }

        $own = own() ? own() : [];
        $user_id = $own ? $own['user_id'] : $data['current_user_id'];
        $updateData = $data['data'] ?? [];

        if(isset($updateData['customer_id']) && $updateData['customer_id'] == ''){
            return ['code' => ['0x024028','customer']];
        }
        if(isset($updateData['chance_possibility']) && $updateData['chance_possibility'] == ''){
            return ['code' => ['empty_chance_possibility','customer']];
        }
        unset($updateData['current_user_id']);
        $dataDetail = app($this->formModelingService)->getCustomDataDetail(self::CUSTOM_TABLE_KEY, $data['unique_id']);

        if(!$dataDetail){
            return ['code' => ['0x024011','customer']];
        }
        // 权限处理
        list($ids) = BusinessChanceRepository::getPermissionIds([CustomerRepository::UPDATE_MARK], ['user_id' => $user_id], [$data['unique_id']]);
        if(!$ids || in_array(!$data['unique_id'],$ids)){
            return ['code' => ['0x000006','common']];
        }
        $fields = ['chance_name','customer_id','chance_possibility','chance_step'];

        self::parseFlowUpdate($fields,$updateData,$dataDetail);
        $info = app($this->userRepository)->getUserAllData($user_id)->toArray();
        if($info){
            $role_ids = [];
            foreach ($info['user_has_many_role'] as $key => $vo) {
                $role_ids[] = $vo['role_id'];
            }
            $own = [
                'user_id' => $user_id,
                'user_name' => $info['user_name'],
                'dept_id' => $info['user_has_one_system_info']['dept_id'],
                'role_id' => $role_ids,
            ];
        }
        $result = $this->update($data['unique_id'], $updateData, $own);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => self::CUSTOM_TABLE_KEY,
                    'field_to' => 'chance_id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }

    // 业务机会外发删除
    public function flowOutDelete($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $chanceId = explode(',',$data['unique_id']);
        $result = $this->delete($chanceId,$own);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => self::CUSTOM_TABLE_KEY,
                    'field_to' => 'chance_id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }

    // 外发更新数据单独处理
    public static function parseFlowUpdate($fields,&$data,$searchData) : void {
        // $fields 需要处理的字段
        array_map(function ($vo) use(&$data,$searchData){
            if(!isset($data[$vo])){
                $data[$vo] = $searchData[$vo] ?? '';
            }
        },$fields);
    }
}
