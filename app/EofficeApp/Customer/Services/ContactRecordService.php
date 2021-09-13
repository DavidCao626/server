<?php

namespace App\EofficeApp\Customer\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Customer\Repositories\ContactRecordRepository;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use App\EofficeApp\Customer\Repositories\LinkmanRepository;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use Illuminate\Support\Facades\Redis;
use DB;


class ContactRecordService extends BaseService
{
    // 联系类型下拉框value
    const RECORD_TYPE_VALUE = 11;
    // 关联的附件表标识
    const ATTACHMENT_MARK = 'customer_contact_record';
    // 关联联系记录评论附件表标识
    const ATTACHMENT_COMMENT_MARK = 'customer_contact_record_comment';
    // 联系记录获取logo缓存
    const CUSTOMER_LOGO = 'customer:logo';

    public function __construct()
    {
        $this->repository                    = 'App\EofficeApp\Customer\Repositories\ContactRecordRepository';
        $this->commentRepository             = 'App\EofficeApp\Customer\Repositories\ContactRecordCommentRepository';
        $this->attachmentService             = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->systemComboboxService         = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->systemComboboxFieldRepository = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxFieldRepository';
        $this->userRepository                = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->calendarService               = 'App\EofficeApp\Calendar\Services\CalendarService';
    }

    public function store(array $data, $own)
    {
        $validate = ContactRecordRepository::validateInput($data, $own);
        if (isset($validate['code'])) {
            return $validate;
        }
        // 附件
        $attachments = [];
        if (isset($data['attachments'])) {
            $attachments = $data['attachments'];
            unset($data['attachments']);
        }
        if(is_array($data['customer_id']) && $data['customer_id']){
            $saveData = [];
            foreach ($data['customer_id'] as $key => $vo) {
                $saveData = [
                    'record_content' => $data['record_content'],
                    'customer_id' => $vo,
                    'linkman_id' => '',
                    'record_type' => isset($data['record_type']) ? $data['record_type'] : 0,
                    'record_start' => isset($data['record_start']) ? $data['record_start'] : '',
                    'record_end' => isset($data['record_end']) ? $data['record_end'] : '',
                    'record_creator' =>$data['record_creator'],
                ];
                if (!$list = app($this->repository)->insertData($saveData)) {
                    return ['code' => ['0x024004', 'customer']];
                }
                // 关联日程
                $this->calendarEmit($saveData, $own, $list, $attachments);
                // 更改客户最后联系记录
                $customerId = $vo ?? 0;
                $lastTime   = $saveData['record_start'] ?? date("Y-m-d H:i:s");
                if ($customerId) {
                    CustomerRepository::refreshLastContactTime($customerId, $lastTime);
                }
                if (!empty($attachments)) {
                    app($this->attachmentService)->attachmentRelation(self::ATTACHMENT_MARK, $list->record_id, $attachments);
                }
                $this->show($list->record_id, $own);
                // 全站搜索消息队列更新数据
                $this->updateGlobalSearchDataByQueue($list->record_id);
            }
            return $data;
        }else{
            if (!$list = app($this->repository)->insertData($data)) {
                return ['code' => ['0x024004', 'customer']];
            }
            // 关联日程
            $this->calendarEmit($data, $own, $list, $attachments);

            // 更改客户最后联系记录
            $customerId = $data['customer_id'] ?? 0;
            $lastTime   = $input['record_start'] ?? date("Y-m-d H:i:s");
            if ($customerId) {
                CustomerRepository::refreshLastContactTime($customerId, $lastTime);
            }
            if (!empty($attachments)) {
                app($this->attachmentService)->attachmentRelation(self::ATTACHMENT_MARK, $list->record_id, $attachments);
            }
            $result = $this->show($list->record_id, $own);
            $this->updateGlobalSearchDataByQueue($list->record_id);
            return $result;
        }

    }

    /**
     * 使用消息队列更新全站搜索数据
     *
     * @param   string|int  $id
     */
    public function updateGlobalSearchDataByQueue($ids)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchContactRecordMessage($ids);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    private function calendarEmit($data, $own, $list, $attachments) {

        $title = trans('customer.contract_record').':'.(CustomerRepository::getCustomerName($data['customer_id']));
        if (empty($data['record_start']) && !empty($data['record_end'])) {
            $data['record_start'] = date('Y-m-d H:i:s',strtotime('-1 hour ',strtotime($data['record_end'])));
        } else if (!empty($data['record_start']) && empty($data['record_end'])) {
            $data['record_end'] = date('Y-m-d H:i:s',strtotime('+1 hour ',strtotime($data['record_start'])));
        } else if (empty($data['record_start']) && empty($data['record_end'])) {
            $data['record_start'] = date('Y-m-d H:i:s');
            $data['record_end'] = date('Y-m-d') . '23:59:59';
        }
        // 关联日程
        $calendarData = [
            'calendar_content' => $title,
            'handle_user'      => [$own['user_id']],
            'calendar_begin'   => date('Y-m-d H:i:s',strtotime($data['record_start'])),
            'calendar_end'     => date('Y-m-d H:i:s',strtotime($data['record_end'])),
            'calendar_remark'  => preg_replace('/\[emoji.*?\]/', '', str_replace('&nbsp;','',strip_tags($data['record_content']))),
            'attachment_id'    => $attachments,
            'calendar_address' => $data['address'] ?? ''
        ];
        $relationData = [
            'source_id'        => $list->record_id,
            'source_from'      => 'customer-visit',
            'source_title'     => $title,
            'source_params'    => ['customer_id' => $data['customer_id']]
        ];
        app($this->calendarService)->emit($calendarData, $relationData, $own['user_id']);
    }

    // 外发创建联系记录
    public function flowStore(array $params)
    {
        $userId = (isset($params['record_creator']) && $params['record_creator']) ? $params['record_creator'] : $params['current_user_id'];
        unset($params['current_user_id']);
        $own = own();
        $timeArr = [];
//        if (isset($params['record_start'])) {
//            $timeArr['record_start'] = $params['record_start'];
//            unset($params['record_start']);
//        }
//        if (isset($params['record_end'])) {
//            $timeArr['record_end'] = $params['record_end'];
//            unset($params['record_end']);
//        }
        if(isset($params['record_end']) && $params['record_end']){
            if(isset($params['record_start']) && ($params['record_start'] > $params['record_end'])){
                return ['code' => ['correct_record_end', 'customer']];
            }
        }
        $params['attachments'] = [];
        $params['record_creator'] = $userId;

        if(isset($params['attachment_id'])){
            $params['attachments'] = $params['attachment_id'];
            unset($params['attachment_id']);
        }
        // 处理附件，或者内容必填
        if(!$params['attachments'] && !$params['record_content']){
            return ['code' => ['contract_record_not_empty', 'customer']];
        }
        if(empty($params['customer_id'])){
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
                    'user_id' => $userId,
                    'dept_id' => $result['user_has_one_system_info']['dept_id'],
                    'role_id' => $role_ids,
                ];
            }
        }

        if (!$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$params['customer_id']], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }else{
            $result = $this->store($params, $own);
            if(isset($result['code'])){
                return $result;
            }
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'customer_contact_record',
                        'field_to' => 'record_id',
                        'id_to' => $result['record_id']
                    ]
                ]
            ];
        }
    }

    public function customerContactRecords(int $customerId, $currentPage,$own)
    {
        if (!$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$customerId], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $result['list']  = app($this->repository)->customerContactRecords($customerId, $currentPage);
        $result['total'] = app($this->repository)->customerContactRecordTotals($customerId, $currentPage);
        // 类型
        $result['typeLists'] = app($this->systemComboboxFieldRepository)->getComboboxFieldsNameByComboboxId(self::RECORD_TYPE_VALUE);
        if (empty($result['list'])) {
            return $result;
        }
        
        $recordIds           = $commentIds           = [];
        foreach ($result['list'] as $index => $item) {
            $recordIds[] = $item['record_id'];
            if (isset($item['has_many_comment']) && !empty($item['has_many_comment'])) {
                $tCommentIds = array_column($item['has_many_comment'], 'comment_id');
                $commentIds  = array_merge($commentIds, $tCommentIds);
            }
        }
        $result['attachmentLists']        = app($this->attachmentService)->getAttachmentsByEntityIds(self::ATTACHMENT_MARK, $recordIds);
        $result['commentAttachmentLists'] = app($this->attachmentService)->getAttachmentsByEntityIds(self::ATTACHMENT_COMMENT_MARK, $commentIds);
        return $result;
    }

    public function deleteContactRecordComment($commentId)
    {
        return (bool) app($this->commentRepository)->deleteById($commentId);
    }

    /**
     * 获取联系记录列表
     * gridFlag标识grid列表
     */
    public function lists(array $input, array $own)
    {
        $params        = $this->parseParams($input);
        $customerIds   = CustomerRepository::getViewIds($own);
        $originSearchs = $params['search'] ?? [];
        if ($customerIds !== CustomerRepository::ALL_CUSTOMER && !isset($originSearchs['customer_id'])) {
            $originSearchs['customer_id'] = [$customerIds, 'in'];
        }
        $params['search'] = $originSearchs;
        $result['total'] = app($this->repository)->total($params);
        //获取列表数据
        //处理修改每页条数后重新获取数据的问题
        if ($result['total'] == 0) {
            $param['page'] = 1;
        }
        $limit = $params['limit'] ?? config('eoffice.pagesize');
        $params['page'] = $params['page'] ?? 0;
        $totalPage = ceil($result['total'] / $limit);
        if ($totalPage < $params['page']) {
            $params['page'] = $totalPage;
        }
        $result['list']   = app($this->repository)->lists($params);
//        if (!isset($input['page']) || $input['page'] <= 1 || isset($params['gridFlag']) || isset($params['isMobile'])) {
//            if (!$result['list']->isEmpty()) {
//                $result['total'] = app($this->repository)->total($params);
//            }
//        }
        // 类型
        $result['typeLists'] = app($this->systemComboboxFieldRepository)->getComboboxFieldsNameByComboboxId(self::RECORD_TYPE_VALUE);
        $recordIds = $commentIds = [];
        $result['list'] = $result['list']->toArray();
        foreach ($result['list'] as $index => $item) {
            $recordIds[] = $item['record_id'];
            if (isset($item['has_many_comment']) && !empty($item['has_many_comment'])) {
                $tCommentIds = array_column($item['has_many_comment'], 'comment_id');
                $commentIds  = array_merge($commentIds, $tCommentIds);
            }
            $result['list'][$index]['record_type_name'] = ($item['record_type'] && $result['typeLists']) ? $result['typeLists'][$item['record_type']] ?? '' : '';
            if(isset($item['contact_record_customer']) && $item['contact_record_customer'] && $item['contact_record_customer']['customer_logo']){
                if(!$logo_name_base = Redis::hget(self::CUSTOMER_LOGO,$item['contact_record_customer']['customer_logo'])){
                    $logo_name_base = app($this->attachmentService)->getCustomerFace($item['contact_record_customer']['customer_logo']);
                    Redis::hset(self::CUSTOMER_LOGO,$item['contact_record_customer']['customer_logo'],$logo_name_base);
                }
                $result['list'][$index]['contact_record_customer']['customer_logo_path']= $logo_name_base;
            }
        }
        Redis::expire(self::CUSTOMER_LOGO,60);
        if (!isset($params['gridFlag'])) {
            // 附件列表
            $result['attachmentLists'] = app($this->attachmentService)->getAttachmentsByEntityIds(self::ATTACHMENT_MARK, $recordIds);
        }
        if($result['commentAttachmentLists'] = app($this->attachmentService)->getAttachmentsByEntityIds(self::ATTACHMENT_COMMENT_MARK, $commentIds)){
            foreach ($commentIds as $id){
                if(!isset($result['commentAttachmentLists'][$id])){
                    $result['commentAttachmentLists'][$id] = [];
                }
            }
        };
        return $result;
    }

    public function delete(array $idArr, array $own)
    {
        if (!$validate = CustomerRepository::validatePermission([CustomerRepository::DELETE_MARK], [], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        foreach ($idArr as $key => $value) {
            $this->emitCalendarDelete($value, $own['user_id']);
        }
        return app($this->repository)->deleteById($idArr);
    }
    private function emitCalendarDelete($sourceId, $userId)
    {
        $relationData = [
            'source_id' =>$sourceId,
            'source_from' => 'customer-visit'
        ];
        return app($this->calendarService)->emitDelete($relationData, $userId);
    }

    public function storeComment(array $data)
    {
        $attachments = [];
        if (isset($data['attachments'])) {
            $attachments = $data['attachments'];
            unset($data['attachments']);
        }
        if (!$validate = $this->validateCommentInput($data)) {
            return ['code' => ['0x024002', 'customer']];
        }
        $record_id = array_unique(explode(',',trim($data['record_id'],',')));
        if(count($record_id) > 1){
            // 批量添加联系记录回复
            unset($data['record_id']);
            try {
                array_map(function ($id) use($data,$attachments){
                    $data['record_id'] = $id;
                    $list = app($this->commentRepository)->insertData($data);
                    if (!empty($attachments)) {
                        app($this->attachmentService)->attachmentRelation(self::ATTACHMENT_COMMENT_MARK, $list->comment_id, array_filter($attachments));
                    }
                },$record_id);
            } catch (\Exception $e) {
                return ['code' => ['0x024004', 'customer']];
            }

        }else{
            if (!$list = app($this->commentRepository)->insertData($data)) {
                return ['code' => ['0x024004', 'customer']];
            }
            if (!empty($attachments)) {
                app($this->attachmentService)->attachmentRelation(self::ATTACHMENT_COMMENT_MARK, $list->comment_id, array_filter($attachments));
            }
            return $list;
        }

    }

    public function show(int $id, $own)
    {
        if (!$validate = ContactRecordRepository::validatePermission([CustomerRepository::VIEW_MARK], $id, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $result = app($this->repository)->show($id);
        if (empty($result)) {
            return $result;
        }
        if (isset($result['record_type']) && $result['record_type']) {
            $result['record_type_name'] = app($this->systemComboboxService)->getComboboxFieldsNameById(self::RECORD_TYPE_VALUE, $result['record_type']);
        }
        $result->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => self::ATTACHMENT_MARK, 'entity_id' => $id]);
        $result['canUpdate'] = false;
        if(DB::table('customer')->where('customer_id',$result['customer_id'])->whereNull('deleted_at')->first()){
            $result['canUpdate'] = true;
        }
        return $result;
    }

    public function commentLists(int $id, array $params)
    {
        $params = $this->parseParams($params);
        if (!isset($params['search'])) {
            $params['search'] = [];
        }
        $params['search']['record_id'] = [$id];
        $result                        = $this->response(app($this->commentRepository), 'total', 'lists', $params);
        if (!isset($result['list']) || empty($result['list'])) {
            return $result;
        }
        $commentIds      = array_column($result['list'], 'comment_id');
        $attachmentLists = app($this->attachmentService)->getAttachmentsByEntityIds(self::ATTACHMENT_COMMENT_MARK, $commentIds);
//        if (empty($attachmentLists)) {
//            return $result;
//        }
        foreach ($result['list'] as $index => $item) {
            if($attachmentLists){
                $result['list'][$index]['attachments'] = $attachmentLists[$item['comment_id']] ?? [];
            }
            $result['list'][$index]['user_id'] = $item['comment_has_one_user'] ? $item['comment_has_one_user']['user_id'] : '';
            $result['list'][$index]['user_name'] = $item['comment_has_one_user'] ? $item['comment_has_one_user']['user_name'] : '';
        }
        return $result;
    }

    private function validateCommentInput(array &$data)
    {
        return true;
    }

    public function exportContactRecords($param){
        list($own,$params) = [$param['user_info'],$param];
        unset($params['user_info']);
        $header = [
            'customer_name' => ['data' => trans("customer.customer_name"), 'style' => ['width' => '30']],
            'record_type' => ['data' => trans("customer.contact_type"), 'style' => ['width' => '30']],
            'record_start' => ['data' => trans("customer.record_start"), 'style' => ['width' => '20']],
            'record_end' => ['data' => trans("customer.record_end"), 'style' => ['width' => '20']],
            'record_content' => ['data' => trans("customer.record_content"), 'style' => ['width' => '50']],
            'contact_record_creator' => ['data' => trans("customer.contact_record_creator"), 'style' => ['width' => '15']],
            'contact_record_linkman' => ['data' => trans("customer.linkmans"), 'style' => ['width' => '15']],
            'created_at' => ['data' => trans("customer.creation_time"), 'style' => ['width' => '20']],
            'address' => ['data' => trans("customer.address"), 'style' => ['width' => '30']],
        ];
        $params        = $this->parseParams($params);
        $customerIds   = CustomerRepository::getViewIds($own);
        $originSearchs = $params['search'] ?? [];
        if ($customerIds !== CustomerRepository::ALL_CUSTOMER && !isset($originSearchs['customer_id'])) {
            $originSearchs['customer_id'] = [$customerIds, 'in'];
        }
        $params['search'] = $originSearchs;
        $params['limit'] = 9999;
        $result['list']   = app($this->repository)->lists($params)->toArray();
        $data = [];
        if($result['list'] && is_array($result['list'])){
            $typeLists = app($this->systemComboboxFieldRepository)->getComboboxFieldsNameByComboboxId(11);
            foreach ($result['list'] as $key => $vo){
                $data[$key]['customer_name'] = $vo['contact_record_customer'] ? $vo['contact_record_customer']['customer_name'] : '';
                $data[$key]['contact_record_creator']  = $vo['contact_record_creator'] ? $vo['contact_record_creator']['user_name'] : '';
                $data[$key]['contact_record_linkman']  = $vo['contact_record_linkman'] ? $vo['contact_record_linkman']['linkman_name'] : '';
                $data[$key]['created_at']    = $vo['created_at'];
                $data[$key]['record_content']= str_replace('&nbsp;','',strip_tags($vo['record_content']));
                $data[$key]['record_start']  = ($vo['record_start'] == '0000-00-00 00:00:00') ? '' : $vo['record_start'];
                $data[$key]['record_end']    = ($vo['record_end'] == '0000-00-00 00:00:00') ? '' : $vo['record_end'];
                $data[$key]['address']       = $vo['address'];
                $data[$key]['record_type']   = isset($typeLists[$vo['record_type']]) ? $typeLists[$vo['record_type']] : '';
            }
        }
        return compact('header', 'data');
    }


    // 联系记录外发删除
    public function flowOutDelete($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $recordId = explode(',',$data['unique_id']);
        $result = $this->delete($recordId,$own);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => self::ATTACHMENT_MARK,
                    'field_to' => 'record_id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }

}
