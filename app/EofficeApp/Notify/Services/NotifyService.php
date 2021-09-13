<?php
namespace App\EofficeApp\Notify\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use App\EofficeApp\Notify\Permissions\NotifyPermission;
use App\EofficeApp\Notify\Repositories\NotifyRepository;
use Carbon\Carbon;
use Eoffice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Arr;
use App\EofficeApp\LogCenter\Facades\LogCenter;
/**
 * 公告模块服务类。
 *
 * @author 李志军
 *
 * @since 2015-10-21
 */
class NotifyService extends BaseService
{
    /** @var object 公告阅读人资源库对象 */
    private $notifyReadersRepository;

    /** @var object 公告类型资源库对象 */
    private $notifyTypeRepository;

    private $notifyCommentRepository;
    private $notifySettingsRepository;

    /** @var object 公告资源库对象 */
    private $notifyRepository;

    private $systemComboboxService;
    private $departmentRepository;

    private $attachmentService;
    private $userRepository;
    private $userMenuService;
    private $companyRepository;
    const allowed_setting_fields = [
        'expired_visible_scope', 'expired_visible_dept', 'expired_visible_role', 'expired_visible_user'
    ];

    /**
     * 公告模块服务类。
     *
     * @param \App\EofficeApp\Notify\Repositories\NotifyReadersRepository $notifyReadersRepository
     * @param \App\EofficeApp\Notify\Repositories\NotifyRepository $notifyRepository
     * @param \App\EofficeApp\Notify\Repositories\NotifyTypeRepository $notifyTypeRepository
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function __construct()
    {
        parent::__construct();

        $this->notifyReadersRepository = 'App\EofficeApp\Notify\Repositories\NotifyReadersRepository';
        $this->notifyRepository = 'App\EofficeApp\Notify\Repositories\NotifyRepository';
        $this->notifyTypeRepository = 'App\EofficeApp\Notify\Repositories\NotifyTypeRepository';
        $this->notifyCommentRepository = 'App\EofficeApp\Notify\Repositories\NotifyCommentRepository';
        $this->notifySettingsRepository = 'App\EofficeApp\Notify\Repositories\NotifySettingsRepository';
        $this->departmentRepository = 'App\EofficeApp\System\Department\Repositories\DepartmentRepository';
        $this->systemComboboxService = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->companyRepository ='App\EofficeApp\System\Company\Repositories\CompanyRepository';
    }
    /**
     * 获取公告类别列表
     *
     * @param array $param
     *
     * @return array 公告类别列表
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function listNotifyType($param)
    {
        return $this->response(app($this->notifyTypeRepository), 'getNotifyTypeCount', 'listNotifyType', $this->parseParams($param));
    }

    /**
     * 获取公告类别列表
     *
     * @param array $param
     * @param $own
     * @return array 公告类别列表
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function listNotifyTypeForSelect($param, $own)
    {
        $param = $this->parseParams($param);
        $type_list = app($this->notifyTypeRepository)->listNotifyType($param)->toArray();
        $total = app($this->notifyTypeRepository)->getNotifyTypeCount($param);
        foreach($type_list as $key => $value) {
            if(!empty($value['type_has_many_notify'])) {
                $type_list[$key]['has_children'] = 1;
            }else{
                $type_list[$key]['has_children'] = 0;
            }
        }

        if ($this->needAddUnclassifiedType($param, $total, $own)){
            array_push($type_list, ["notify_type_id" => 0, "notify_type_name" => trans('notify.unclassified_announcement')]);
            $total++;
        }

        $list = $type_list;
        return compact('total', 'list');
    }

    /**
     * 是否需要加入未分类
     * @param $param
     * @param $total
     * @return bool
     */
    public function needAddUnclassifiedType($param, $total, $own)
    {
        if (!$this->canSeeUnclassifiedNotify($own)) {
            return false;
        }
        if (isset($param['search']) && isset($param['search']['notify_type_name'])) {
            // 如果搜索名称中含有未分类，则返回未分类类别
            if (is_array($param['search']['notify_type_name'])
                && stripos(trans('notify.unclassified_announcement'), $param['search']['notify_type_name'][0]) !== false
            ) {
                return true;
            }
        } elseif (isset($param['search']) && isset($param['search']['notify_type_id'])){
            // 搜索id 0的
            $searchId = $param['search']['notify_type_id'];
            if (is_array($searchId)){
                if(is_array($searchId[0])){
                    if(in_array(0, $searchId[0])){
                        return true;
                    }
                } else {
                    if($searchId[0] == 0){
                        return true;
                    }
                }
            }
        } else {
            // 如果该接口用来返回种类列表，则需要插入未分类的类别
            //page=0或者不存在page或者最后一页加入未分类
            $param['limit'] = $param['limit']??config('eoffice.pagesize');
            if(!isset($param['page']) || $param['page'] == 0 || $param['page'] == ceil(++$total/$param['limit'])){
                return true;
            }
        }
        return false;
    }

    /**
     * 是否存在未分类公告
     * @return bool
     */
    public function existUnclassifiedNotify()
    {
        /** @var NotifyRepository $notifyRepository */
        $notifyRepository = app($this->notifyRepository);

        return (bool) $notifyRepository->entity->select(\DB::raw(1))->where('notify_type_id', 0)->first();
    }

    /**
     * 当前用户是否存在未分类公告
     * @param $own
     * @return bool
     */
    public function canSeeUnclassifiedNotify($own)
    {
        /** @var NotifyRepository $notifyRepository */
        $notifyRepository = app($this->notifyRepository);
        $param['search']['notify_type_id'] = [0];
        $param['own'] = $own;

        if(!$this->checkCanReadExpiredNotify($own)){
            $param['expired_not_visible'] = 1;
        }

        return (bool) $notifyRepository->getNotifyCount($param);
    }

    /**
     * 新建公告类别
     *
     * @param array $data
     *
     * @return array 公告类别id
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function addNotifyType($data)
    {
        if ($this->notifyTypeNameExists($data['notify_type_name'])) {
            return ['code' => ['0x025003', 'notify']];
        }
        if (isset($data['sort']) && !intval($data['sort']) == $data['sort']) {
            return ['code' => ['0x025025', 'notify']];
        }
        if(isset($data['notify_type_name']) && strlen($data['notify_type_name']) > 100) {
            return ['code' => ['0x025035', 'notify']];
        }
        $notifyTypeData = [
            'notify_type_name' => $this->defaultValue('notify_type_name', $data, ''),
            'sort'             => $this->defaultValue('sort', $data, 0),
        ];

        if ($result = app($this->notifyTypeRepository)->insertData($notifyTypeData)) {
            return ['notify_type_id' => $result->notify_type_id];
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 编辑公告类别
     *
     * @param array $data
     * @param int $notifyTypeId
     *
     * @return int | array 编辑结果
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function editNotifyType($data, $notifyTypeId)
    {
        if ($notifyTypeId == 0) {
            return ['code' => ['0x025002', 'notify']];
        }

        if ($this->notifyTypeNameExists($data['notify_type_name'], $notifyTypeId)) {
            return ['code' => ['0x025003', 'notify']];
        }
        if (!(intval($data['sort']) == $data['sort'])) {
            return ['code' => ['0x025025', 'notify']];
        }
        if(isset($data['notify_type_name']) && strlen($data['notify_type_name']) > 100) {
            return ['code' => ['0x025035', 'notify']];
        }

        $notifyTypeData = [
            'notify_type_name' => $this->defaultValue('notify_type_name', $data, ''),
            'sort'             => $this->defaultValue('sort', $data, 0),
        ];

        if (app($this->notifyTypeRepository)->updateData($notifyTypeData, ["notify_type_id" => $notifyTypeId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 获取公告类别详情
     *
     * @param int $notifyTypeId
     *
     * @return object 公告类别详情
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function showNotifyType($notifyTypeId)
    {
        if ($notifyTypeId == 0) {
            return ['code' => ['0x025002', 'notify']];
        }

        $notifyInfo          = app($this->notifyTypeRepository)->getDetail($notifyTypeId);
        $notifyInfo->user_id = explode(',', $notifyInfo->user_id);
        $notifyInfo->role_id = explode(',', $notifyInfo->role_id);
        $notifyInfo->dept_id = explode(',', $notifyInfo->dept_id);
        return $notifyInfo;
    }
    /**
     * 删除公告类别
     *
     * @param int $notifyTypeId
     *
     * @return int | array 删除结果
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function deleteNotifyType($notifyTypeId)
    {
        if ($notifyTypeId == 0) {
            return ['code' => ['0x025002', 'notify']];
        }

        if (app($this->notifyTypeRepository)->deleteById($notifyTypeId)) {
            app($this->notifyRepository)->updateData(['notify_type_id' => 0], ['notify_type_id' => $notifyTypeId]);
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 获取公告列表
     *
     * @param array $param
     *
     * @return array 公告列表
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function listNotify($param, $own)
    {
        $param = $this->parseParams($param);

        if (isset($param['fields'])) {
            $param['fields'] = $this->handleNotifyFields($param['fields']);
        }

        if (isset($param['search'])) {
            $param['search'] = $this->handleNotifySearch($param['search']);
        }

        if (!$own) {
            $param['own'] = ['user_id' => ''];
        } else {
            $param['own'] = $own;
        }

        // 公告设置过期可见范围
        if(!$this->checkCanReadExpiredNotify($own)){
            $param['expired_not_visible'] = 1;
        }

        $list       = $this->response(app($this->notifyRepository), 'getNotifyCount', 'listNotify', $param);
        $handleList = [];
        if ($list['list']) {
            foreach ($list['list'] as $notify) {
                if (($own['user_id'] == (isset($notify->from_id) ? $notify->from_id : '')) || $own['user_id'] == 'admin') {
                    $notify->has_purview = true;
                }
                $notify->creator = $this->parseCreatorName($notify->creator_type, $notify->creator,$notify->from_id );
                $handleList[]           = $notify;
                $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
                $notify->attachments = $attachmentService->getAttachmentIdsByEntityId(['entity_table' => 'notify', 'entity_id' => $notify->notify_id]);
            }
        }
        $list['list'] = $handleList;

        return $list;
    }
    /**
     * 新建公告
     *
     * @param array $data
     * @param array $own
     * @param string|null $createdAtDate 流程外发公告数据时，设置发布时间，用于有效期过滤
     *
     * @return array 公告id
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function addNotify($data, $own, $createdAtDate = null)
    {
        $createdAtDate = $createdAtDate ?? date('Y-m-d');
        if (isset($data['end_date']) && $data['end_date'] != '' && $data['end_date'] != '0000-00-00' && ($data['end_date'] < $data['begin_date'])) {
            return ['code' => ['0x025004', 'notify']];
        }

        // 终止日期不能设置为当前时间之前
        if ((isset($data['end_date']) && $data['end_date'] < $createdAtDate && $data['end_date'] != '0000-00-00' && $data['end_date'] != '')) {
            return ['code' => ['0x025021', 'notify']];
        }

        $notifyData = [
            'subject'        => $this->defaultValue('subject', $data, ''),
            'notify_type_id' => $this->defaultValue('notify_type_id', $data, 0),
            'publish'        => $this->defaultValue('publish', $data, 0),
            'allow_reply'    => $this->defaultValue('allow_reply', $data, 0) ? 1 : 0,
            'begin_date'     => $this->defaultValue('begin_date', $data, ''),
            'end_date'       => $this->defaultValue('end_date', $data, ''),
            'priv_scope'     => $this->defaultValue('priv_scope', $data, 1),
            'dept_id'        => $this->defaultValue('dept_id', $data, ''),
            'role_id'        => $this->defaultValue('role_id', $data, ''),
            'user_id'        => $this->defaultValue('user_id', $data, ''),
            'from_id'        => $own['user_id'],
            'content'        => $this->defaultValue('content', $data, ''),
            'top'            => $this->defaultValue('top', $data, 0) ? 1 : 0,
            'top_end_time'   => $this->defaultValue('top_end_time', $data, '0000-00-00 00:00:00'),
            'top_create_time' => $this->defaultValue('top', $data, 0) ? date('Y-m-d H:i:s') : "",
            'open_unread_after_login' => $this->defaultValue('open_unread_after_login', $data, 0) ? 1 : 0,
        ];
        $notifyData = $this->handleNotifyInfo($notifyData,$own);
        //处理发布单位
        $creatorData = $this->handleCreator($data , $own['user_id']);
        if(isset($creatorData['code'])){
            return $creatorData;
        }
        $notifyData['creator_type'] = $creatorData['creator_type'];
        $notifyData['creator'] = $creatorData['creator'];
        if ($notifyData['priv_scope'] == 1) {
            $notifyData['dept_id'] = '';
            $notifyData['role_id'] = '';
            $notifyData['user_id'] = '';
        } else {
            if (isset($data['dept_id']) && is_array($data['dept_id'])) {
                $notifyData['dept_id'] = implode(',', $data['dept_id']);
            }
            if (isset($data['role_id']) && is_array($data['role_id'])) {
                $notifyData['role_id'] = implode(',', $data['role_id']);
            }
            if (isset($data['user_id']) && is_array($data['user_id'])) {
                $notifyData['user_id'] = implode(',', $data['user_id']);
            }
        }

        if ($result = app($this->notifyRepository)->insertData($notifyData)) {
            if (isset($data['attachment_id']) && $data['attachment_id'] != "") {
                app($this->attachmentService)->attachmentRelation("notify", $result->notify_id, $data['attachment_id']);
            }
            if ($result->publish == 1) {
                if ($result->begin_date <= date('Y-m-d')) {
                    $sendData['remindMark'] = 'notice-publish';
                    $userId                 = app($this->notifyReadersRepository)->getReadersUserid($result->notify_id);

                    $sendData['toUser']       = implode(',', $userId);
                    $sendData['contentParam'] = ['noticeTitle' => $notifyData['subject']];
                    $sendData['stateParams']  = ['notify_id' => $result->notify_id];
                    \Log::info('消息类型：notice-publish-441' .'发送人：'.$own['user_name'].'接收人：'.implode(',', $userId));
                    Eoffice::sendMessage($sendData);

                    // 全站搜索消息队列更新数据
                    $this->updateGlobalSearchDataByQueue($result->notify_id);
                }
            }
            if ($result->publish == 2) {
                if (!isset($own['user_name'])) {
                    $userRepository = app($this->userRepository);
                    $createUser = $userRepository->getUserName($own['user_id']);
                } else {
                    $createUser = $own['user_name'];
                }
                $sendData['remindMark'] = 'notice-submit';
                $userId                 = app($this->userMenuService)->getMenuRoleUserbyMenuId(234);

                $sendData['toUser']       = implode(',', $userId);
                $sendData['contentParam'] = ['noticeTitle' => $notifyData['subject'], 'userName' => $createUser];
                $sendData['stateParams']  = ['notify_id' => $result->notify_id];
                \Log::info('消息类型：notice-submit-461' .'发送人：'.$own['user_name'].'接收人：'.implode(',', $userId));
                Eoffice::sendMessage($sendData);
            }
            return ['notify_id' => $result->notify_id];
        }

        return ['code' => ['0x000003', 'common']];
    }

    public function modifyType($notifyId,$data){
        if (empty($notifyId)) {
            return false;
        }
        $notify_id = explode(",", $notifyId);
        $notifyData = [
            'notify_type_id' => $this->defaultValue('notify_type_id', $data, 0),
        ];
       return  app($this->notifyRepository)->updateData($notifyData, ['notify_id' => [$notify_id, 'in']]);
    }

    /**
     * 处理开关权限
     * @param $notifyData
     * @param $own
     * @return mixed
     */
    public function handleNotifyInfo($notifyData , $own ){
        $settings = $this->getExpiredVisibleSettings();
        //不在权限里面
        if($settings['comment_display_scope'] == 1){
            $notifyData['allow_reply'] = $settings['comment'];

        }else if($settings['comment_display_scope'] == 2){
            if(array_intersect(explode(",",$settings['comment_display_role']),$own['role_id']) || in_array($own['dept_id'], explode(',', $settings['comment_display_dept']))
                || in_array($own['user_id'], explode(',', $settings['comment_display_user']))){
                //在权限里面
            }else{
                $notifyData['allow_reply'] = $settings['comment'];
            }
        }

        if($settings['login_open_unread_display_scope'] == 1){
            $notifyData['open_unread_after_login'] = $settings['login_open_unread'];
        }else if($settings['login_open_unread_display_scope'] == 2){
            if(array_intersect(explode(",",$settings['login_open_unread_display_role']),$own['role_id']) || in_array($own['dept_id'], explode(',', $settings['login_open_unread_display_dept']))
                || in_array($own['user_id'], explode(',', $settings['login_open_unread_display_user']))){
                //在权限里面
            }else{
                $notifyData['open_unread_after_login'] = $settings['login_open_unread'];

            }
        }

        if($settings['top_display_scope'] == 1){
            $notifyData['top'] = $settings['top'];
        }else if($settings['top_display_scope'] == 2){
            if(array_intersect(explode(",",$settings['top_display_role']),$own['role_id']) || in_array($own['dept_id'], explode(',', $settings['top_display_dept']))
                || in_array($own['user_id'], explode(',', $settings['top_display_user']))){
                //在权限里面
            }else{
                $notifyData['top'] = $settings['top'];
            }
        }
        return $notifyData;

    }
    /**
     * 编辑公告
     *
     * @param array $data
     * @param int $notifyId
     *
     * @param $own
     * @param bool $fromFlow
     * @return array 编辑结果
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function editNotify($data, $notifyId, $own, $fromFlow = false)
    {

        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }

        if (isset($data['end_date']) && $data['end_date'] != '' && $data['end_date'] != '0000-00-00' && ($data['end_date'] < $data['begin_date'])) {
            return ['code' => ['0x025004', 'notify']];
        }

        // 终止日期不能设置为当前时间之前
        if ((isset($data['end_date']) && $data['end_date'] < date('Y-m-d') && $data['end_date'] != '0000-00-00' && $data['end_date'] != '')) {
            return ['code' => ['0x025021', 'notify']];
        }

        $notify = app($this->notifyRepository)->showNotify($notifyId);
        if (!$notify) {
            return ['code' => ['0x025020', 'notify']];
        }
        if ($notify->publish == 1) {
            return ['code' => ['0x025014', 'notify']];
        }
        if (!$fromFlow && $notify->from_id != $own['user_id']) {
            return ['code' => ['0x025015', 'notify']];
        }

        $notifyData = [];
        if(array_key_exists('subject',$data) && empty($data['subject'])){
            return ['code' => ['0x025006', 'notify']];
        }
        if(array_key_exists('content',$data) && empty($data['content'])){
            return ['code' => ['0x025007', 'notify']];
        }
        if(array_key_exists('begin_date',$data) && empty($data['begin_date'])){
            return ['code' => ['0x025008', 'notify']];
        }
        if(array_key_exists('priv_scope',$data) && !in_array($data['priv_scope'],[0,1])){
            return ['code' => ['0x025027', 'notify']];
        }
        if(array_key_exists('priv_scope',$data) && $data['priv_scope'] == 0){
            $dept = isset($data['dept_id'])?$data['dept_id']:'';
            $role = isset($data['role_id'])?$data['role_id']:'';
            $user = isset($data['user_id'])?$data['user_id']:'';
            if(empty($dept) && empty($role) && empty($user)){
                return ['code' => ['0x025042', 'notify']];
            }
        }

        if(!empty($data['subject'])){
            $notifyData['subject'] = $data['subject'];
        }
        if(isset($data['notify_type_id'])){
            $notifyData['notify_type_id'] = $this->defaultValue('notify_type_id', $data, 0);
        }
        if(isset($data['publish'])){
            $notifyData['publish'] = $this->defaultValue('publish', $data, 0);
        }
        if(isset($data['allow_reply'])){
            $notifyData['allow_reply'] = $this->defaultValue('allow_reply', $data, 0);
        }
        if(isset($data['begin_date'])){
            $notifyData['begin_date'] = $this->defaultValue('begin_date', $data, '');
        }
        if(isset($data['end_date'])){
            $notifyData['end_date'] = $this->defaultValue('end_date', $data, '');
        }
        if(isset($data['priv_scope'])){
            $notifyData['priv_scope'] = $this->defaultValue('priv_scope', $data, 1);
            $privScope = $notifyData['priv_scope'];
        }

        if(isset($data['dept_id'])){
            if($data['dept_id'] == 'all'){
                $privScope = 1;
                $notifyData['priv_scope'] = 1;
            }else{
                $notifyData['dept_id'] = is_array($data['dept_id'])?implode(',', $this->defaultValue('dept_id', $data, [])):$data['dept_id'];
            }
        }
        if(isset($data['role_id'])){
            if($data['role_id'] == 'all'){
                $privScope = 1;
                $notifyData['priv_scope'] = 1;
            }else{
                $notifyData['role_id'] = is_array($data['role_id'])?implode(',', $this->defaultValue('role_id', $data, [])):$data['role_id'];
            }
        }
        if(isset($data['user_id'])){
            if($data['user_id'] == 'all'){
                $privScope = 1;
                $notifyData['priv_scope'] = 1;
            }else{
                $notifyData['user_id'] = is_array($data['user_id'])?implode(',', $this->defaultValue('user_id', $data, [])):$data['user_id'];
            }
        }
        if(!empty($data['content'])){
            $notifyData['content'] = $this->defaultValue('content', $data, '');
        }
        if(isset($data['top'])){
            $notifyData['top'] = $this->defaultValue('top', $data, 0);
            $notifyData['top_create_time'] = $this->defaultValue('top', $data, 0) ? date('Y-m-d H:i:s') : "";
        }
        if(isset($data['top_end_time'])){
            $notifyData['top_end_time'] = $this->defaultValue('top_end_time', $data, '0000-00-00 00:00:00');
        }
        if(isset($data['open_unread_after_login'])){
            $notifyData['open_unread_after_login'] = $this->defaultValue('open_unread_after_login', $data, 0);
        }

        $creatorId = $data['from_id'] ?? $notify->from_id;
        $creatorData = $this->handleCreator($data , $creatorId);
        if(isset($creatorData['code'])){
            return $creatorData;
        }
        $notifyData['creator_type'] = $creatorData['creator_type'];
        $notifyData['creator'] = $creatorData['creator'];
        $privScope = $privScope ?? $notify->priv_scope;
        if ($privScope == 1) {
            $notifyData['dept_id'] = '';
            $notifyData['role_id'] = '';
            $notifyData['user_id'] = '';
        }
        $notifyData = $this->handleNotifyInfo($notifyData,$own);
        if (app($this->notifyRepository)->updateData($notifyData, ['notify_id' => $notifyId])) {
            if (isset($data['attachment_id'])) {
                app($this->attachmentService)->attachmentRelation("notify", $notifyId, $data['attachment_id']);
            }

            $publish = $notifyData['publish'] ?? $notify->publish;
            $beginDate = $notifyData['begin_date'] ?? $notify->begin_date;
            $subject = $notifyData['subject'] ?? $notify->subject;

            if ($publish == 1) {
                if ($beginDate <= date('Y-m-d')) {
                    $sendData['remindMark'] = 'notice-publish';
                    $userId                 = app($this->notifyReadersRepository)->getReadersUserid($notifyId);

                    $sendData['toUser']       = implode(',', $userId);
                    $sendData['contentParam'] = ['noticeTitle' => $subject];
                    $sendData['stateParams']  = ['notify_id' => $notifyId];
                    \Log::info('消息类型：notice-publish-620' .'发送人：'.$own['user_name'].'接收人：'.implode(',', $userId));
                    Eoffice::sendMessage($sendData);
                }
            }
            if ($publish == 2) {
                $sendData['remindMark'] = 'notice-submit';
                $userId                 = app($this->userMenuService)->getMenuRoleUserbyMenuId(234);

                $sendData['toUser']       = implode(',', $userId);
                $sendData['contentParam'] = ['noticeTitle' => $notifyData['subject'], 'userName' => $own['user_name']];
                $sendData['stateParams']  = ['notify_id' => $notifyId];
                \Log::info('消息类型：notice-submit-631' .'发送人：'.$own['user_name'].'接收人：'.implode(',', $userId));
                Eoffice::sendMessage($sendData);
            }

            // 全站搜索消息队列更新数据
            $this->updateGlobalSearchDataByQueue($notifyId);

            return ['notify_id' => $notifyId];
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 获取公告详情
     *
     * @param int $notifyId
     *
     * @return object 公告详情
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function showNotify($notifyId, $own, $params = [])
    {
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }
        $param['own']  = $own;
        $currentUserId = $own['user_id'];
        $check         = 0;
        $currentDate   = date("Y-m-d", time());
        $notifypublish = app($this->notifyRepository)->showNotify($notifyId);
        if (!$notifypublish) {
            return ['code' => ['0x025020', 'notify']];
        }
        if (($notifypublish['publish'] != 1 || ($notifypublish['publish'] == 1 && $notifypublish['begin_date'] > $currentDate)) && ($notifypublish['from_id'] != $own['user_id'])) {
            return ['code' => ['0x000006', 'common']];
        }
        // 过期可见判断
        if ($notifypublish['status'] == 1 || ($notifypublish['end_date'] != '0000-00-00' && $notifypublish['end_date'] < date('Y-m-d'))) {
            if($notifypublish->publish == 1 && !$this->checkCanReadExpiredNotify($own)){
                return ['code' => ['0x000006', 'common']];
            }
        }
        if (in_array(234, $own['menus']['menu']) && $notifypublish['publish'] == 2) {
            $check = 1;
        }
        if ((($notifyInfo = app($this->notifyRepository)->showNotifyAccess($notifyId, $param)) && $notifypublish['publish'] == 1 && $notifypublish['begin_date'] <= $currentDate && in_array(131, $own['menus']['menu'])) || $check == 1 || ($notifypublish['publish'] == 1 && $own['user_id'] == 'admin') || $notifypublish['from_id'] == $own['user_id']) {
            // 已读未读
            $hasRead = app($this->notifyReadersRepository)->readerExists($notifyId, $currentUserId);
            // 判断是否是登录时的弹出
            $isLogin = $params['is_login'] ?? 0;
            if (!$isLogin && !$hasRead) {
                $data = [
                    'notify_id' => $notifyId,
                    'user_id'   => $currentUserId,
                ];
                app($this->notifyReadersRepository)->insertData($data);
            }
            $notifyInfo = app($this->notifyRepository)->showNotify($notifyId);
            $notifyInfo->has_read = $hasRead;
        } else {
            return ['code' => ['0x000006', 'common']];
        }
        if($notifyInfo->user_id){
            $user_ids = explode(',', $notifyInfo->user_id);
        }else{
            $user_ids = [];
        }
        if($notifyInfo->role_id){
            $role_ids = explode(',', $notifyInfo->role_id);
            foreach ($role_ids as $key => $value) {
                $role_ids[$key] = (int) $value;
            }
        }else{
            $role_ids = [];
        }
        if($notifyInfo->dept_id){
            $dept_ids = explode(',', $notifyInfo->dept_id);
            foreach ($dept_ids as $key => $value) {
                $dept_ids[$key] = (int) $value;
            }
        }else{
            $dept_ids = [];
        }
        $userAllData = app($this->userRepository)->getUserAllData($notifyInfo->from_id)->toArray();
        $notifyInfo->dept_name     = isset($userAllData['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name']) ? $userAllData['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'] : '';
        $notifyInfo->user_id       = $user_ids;
        $notifyInfo->role_id       = $role_ids;
        $notifyInfo->dept_id       = $dept_ids;
        $notifyInfo->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'notify', 'entity_id' => $notifyId]);
        $notifyInfo->creator = $this->parseCreatorId($notifyInfo->creator_type, $notifyInfo->creator,$notifyInfo->from_id );
        $notifyInfo->creator_name = $this->parseCreatorName($notifyInfo->creator_type, $notifyInfo->creator,$notifyInfo->from_id );

        return $notifyInfo;
    }
    private function parseCreatorId($creatorType, $creator , $from_id)
    {
        if($creatorType == 1){
            if(empty($creator)){
                $creator = $from_id;
            }
        }else if($creatorType == 2){
            if(empty($creator)){
                $detail = app($this->userRepository)->getUserDeptIdAndRoleIdByUserId($from_id);
                $creator = $detail['dept_id'] ?? '';
            }

        }
        return $creator;
    }

    private function parseCreatorName($creatorType, $creator , $from_id)
    {
        if($creatorType == 1){
            if(empty($creator)){
                $creator =  get_user_simple_attr($from_id);
            }else{
                $creator =  get_user_simple_attr($creator);
            }
        }else if($creatorType == 2){
            $detail = app($this->departmentRepository)->getDetail($creator);
            $creator = $detail->dept_name ?? '';
        }else if($creatorType == 3){
            $companyDetail = app($this->companyRepository)->getCompanyDetail();
            $creator = $companyDetail->company_name ?? '';
        }

        return $creator;
    }

    /**
     * 获取个人可访问的全部公告
     *
     * @return array 可访问的ids
     */
    public function getAllAccessibleNotifyIdsToPerson($own)
    {
        $expiredSettings = $this->getExpiredVisibleSettings();

        /** @var NotifyRepository $notifyRepository */
        $notifyRepository = app('App\EofficeApp\Notify\Repositories\NotifyRepository');
        $notifyIds = $notifyRepository->getAllAccessibleNotifyIdsToPerson($own, $expiredSettings);

        return $notifyIds;
    }

    /**
     * 删除公告
     *
     * @param int $notifyId
     *
     * @param $own
     * @return array 删除结果
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function deleteNotify($notifyId, $own)
    {
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }

        // 添加公告删除日志
        $userId = isset($own['user_id']) ? $own['user_id'] : '';
        $notifyIds = explode(',', $notifyId);
        foreach ($notifyIds as $k => $v) {
            $log = app($this->notifyRepository)->showNotify($v);

            if(!$log){
                return ['code' => ['0x025020', 'notify']];
            }
            if($log->publish !==1 && $userId !== $log->from_id){
                return ['code' => ['0x000006', 'common']];
            }
            if($userId !== 'admin' && $log->from_id !== $own['user_id']){
                return ['code' => ['0x000006', 'common']];
            }
            $data = [];
            $subject = isset($log->subject) ? $log->subject : '';
            // $content = isset($log->content) ? $log->content : '';
            // $data['log_content']          = '公告标题：' . $subject . '公告内容：' . $content;
            $data['log_content']          = trans('systemlog.notify_subject') . $subject;
            $data['log_type']             = 'notify';
            $data['log_creator']          = $userId;
            $data['log_ip']               = getClientIp();
            $data['log_time']             = date('Y-m-d H:i:s');
            $data['log_relation_table']   = 'notify';
            $data['log_relation_id']      = isset($log->notify_id) ? $log->notify_id : '';
//            add_system_log($data);
            if($log->publish == 1){
                $identifier  = "notify.notify.delete";
                $content = $own['user_name'] . trans('notify.delete') . $log->subject;
                $logParams = $this->handleLogParams($own['user_id'], $content, $log->notify_id, $log->subject);
                logCenter::info($identifier , $logParams);
            }


        }

        $notifyIdsArray = explode(',', $notifyId);
        if (app($this->notifyRepository)->deleteById($notifyIdsArray)) {
            app($this->notifyCommentRepository)->deleteByWhere(['notify_id' => [$notifyIdsArray, 'in']]);

            // 全站搜索消息队列更新数据
            $this->updateGlobalSearchDataByQueue($notifyId);

            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 公告立即生效
     *
     * @param int $notifyId
     *
     * @return int 公告立即生效结果
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function imediateNotify($notifyId, $currentUserId)
    {
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }

        $notify = app($this->notifyRepository)->showNotify($notifyId);
        if (!$notify) {
            return ['code' => ['0x025020', 'notify']];
        }
        if ($notify->publish != 1 || ($notify->from_id != $currentUserId && $currentUserId != "admin")) {
            return ['code' => ['0x025009', 'notify']];
        }
        if ($notify->status === 0){
            return ['code' => ['0x025009', 'notify']];
        }
        if (($notify->end_date != '0000-00-00') && $notify->end_date <= date('Y-m-d')) {
            $updateData = [
                'begin_date' => date('Y-m-d'),
                'publish'    => 1,
                'status'     => 0,
                'end_date'   => '0000-00-00',
            ];
        } else {
            $updateData = [
                'begin_date' => date('Y-m-d'),
                'publish'    => 1,
                'status'     => 0,
            ];
        }

        if (app($this->notifyRepository)->updateData($updateData, ['notify_id' => $notifyId])) {

            $sendData['remindMark'] = 'notice-publish';
            $userId                 = app($this->notifyReadersRepository)->getReadersUserid($notify->notify_id);

            $sendData['toUser']       = implode(',', $userId);
            $sendData['contentParam'] = ['noticeTitle' => $notify->subject];
            $sendData['stateParams']  = ['notify_id' => $notify->notify_id];
            \Log::info('消息类型：notice-publish-887' .'发送人：'.$own['user_name'].'接收人：'.implode(',', $userId));
            Eoffice::sendMessage($sendData);

            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 公告立即结束
     *
     * @param int $notifyId
     *
     * @return int 立即结束结果
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function endNotify($notifyId, $currentUserId)
    {
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }

        $notify = app($this->notifyRepository)->showNotify($notifyId);
        if (!$notify) {
            return ['code' => ['0x025020', 'notify']];
        }
        if ($notify->publish != 1 || ($notify->from_id != $currentUserId && $currentUserId != "admin")) {
            return ['code' => ['0x025016', 'notify']];
        }

        $updateData = [
            'status'   => 1,
            'end_date' => date('Y-m-d'),
            'top'      => 0,
        ];

        if (app($this->notifyRepository)->updateData($updateData, ['notify_id' => $notifyId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 获取审核公告列表
     *
     * @param array $param
     *
     * @return array 审核公告列表
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function listVerifyNotify($param, $own)
    {
        $param = $this->parseParams($param);

        if (isset($param['fields'])) {
            $param['fields'] = $this->handleNotifyFields($param['fields']);
        }

        if (isset($param['search'])) {
            $param['search'] = $this->handleNotifySearch($param['search']);
        }
        $param['own'] = $own;
        $list         = $this->response(app($this->notifyRepository), 'getVerifyNotifyCount', 'listVerifyNotify', $param);
        $handleList   = [];
        if ($list['list']) {
            foreach ($list['list'] as $notify) {
                $notify->notify_type_name = app($this->notifyTypeRepository)->getNotifyTypeName($notify->notify_type_id);
                $notify->creator = $this->parseCreatorName($notify->creator_type, $notify->creator,$notify->from_id );
                $handleList[] = $notify;
            }
        }
        $list['list'] = $handleList;

        return $list;

    }
    /**
     * 拒绝公告
     *
     * @param int $notifyId
     *
     * @return int 拒绝结果
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function refuseNotify($notifyId, $own)
    {
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }

        $updateData = [
            'publish' => 0,
        ];
        $notifyId = explode(',', $notifyId);
        foreach ($notifyId as $notify) {
            $notifyInfo = app($this->notifyRepository)->showNotify($notify);
            if (!$notifyInfo) {
                return ['code' => ['0x025020', 'notify']];
            }
            if ($notifyInfo->publish == 1){
                return ['code' => ['0x025018', 'notify']];
            }
            if ($notifyInfo->publish != 2) {
                return ['code' => ['0x025012', 'notify']];
            }

            if (app($this->notifyRepository)->editNotify($updateData, ['notify_id' => [$notify]])) {

                $notifyInfo             = app($this->notifyRepository)->showNotify($notify);
                $sendData['remindMark'] = 'notice-refuse';

                $sendData['toUser']       = $notifyInfo->from_id;
                $sendData['contentParam'] = ['noticeTitle' => $notifyInfo->subject, 'userName' => $own['user_name']];
                $sendData['stateParams']  = ['notify_id' => $notifyInfo->notify_id];
                \Log::info('消息类型：notice-refuse-1010' .'发送人：'.$own['user_name'].'接收人：'.$notifyInfo->from_id);
                Eoffice::sendMessage($sendData);

            }
        }
        return true;
        // return ['code' => ['0x000003', 'common']];
    }
    /**
     * 批准公告
     *
     * @param int $notifyId
     *
     * @return int 批准结果
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function approveNotify($notifyId, $own)
    {
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }

        $updateData = [
            'publish' => 1,
        ];
        $notifyId = explode(',', $notifyId);
        foreach ($notifyId as $notify) {
            $notifyInfo = app($this->notifyRepository)->showNotify($notify);
            if (!$notifyInfo) {
                return ['code' => ['0x025020', 'notify']];
            }
            if ($notifyInfo->publish == 1){
                return ['code' => ['0x025018', 'notify']];
            }
            if ($notifyInfo->publish != 2) {
                return ['code' => ['0x025012', 'notify']];
            }
            if (app($this->notifyRepository)->editNotify($updateData, ['notify_id' => [$notify]])) {
                $sendData['remindMark']   = 'notice-pass';
                $sendData['toUser']       = $notifyInfo->from_id;
                $sendData['contentParam'] = ['noticeTitle' => $notifyInfo->subject, 'userName' => $own['user_name']];
                $sendData['stateParams']  = ['notify_id' => $notifyInfo->notify_id];
                \Log::info('消息类型：notice-pass-1055' .'发送人：'.$own['user_name'].'接收人：'.$notifyInfo->from_id);
                Eoffice::sendMessage($sendData);
                if ($notifyInfo->begin_date <= date("Y-m-d")) {
                    $sendData1['remindMark']   = 'notice-publish';
                    $userId                    = app($this->notifyReadersRepository)->getReadersUserid($notify);
                    $sendData1['toUser']       = implode(',', $userId);
                    $sendData1['contentParam'] = ['noticeTitle' => $notifyInfo->subject];
                    $sendData1['stateParams']  = ['notify_id' => $notify];
                    \Log::info('消息类型：notice-publish-1063' .'发送人：'.$own['user_name'].'接收人：'.implode(',', $userId));
                    Eoffice::sendMessage($sendData1);
                }
            }
        }

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($notifyId);

        return true;
        // return ['code' => ['0x000003', 'common']];
    }
    /**
     * 获取审核公告详情
     *
     * @param int $notifyId
     *
     * @return object 审核公告详情
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function showVerifyNotify($notifyId, $currentUserId, $own)
    {
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }

        $notify = app($this->notifyRepository)->showNotify($notifyId);
        if (!$notify) {
            return ['code' => ['0x025020', 'notify']];
        }
        if ($notify->publish == 1){
            return ['code' => ['0x025018', 'notify']];
        }
        if ($notify->publish != 2) {
            return ['code' => ['0x025012', 'notify']];
        }

        app($this->notifyRepository)->updateData(['last_check_time' => date('Y-m-d H:i:s')], ['notify_id' => $notifyId]);
        $notifyInfo                = app($this->notifyRepository)->showNotify($notifyId);
        $notifyInfo->creator_name = $this->parseCreatorName($notifyInfo->creator_type, $notifyInfo->creator,$notifyInfo->from_id );
        $notifyInfo->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'notify', 'entity_id' => $notifyId]);
        return $notifyInfo;
    }
    /**
     * 获取公告阅读情况
     *
     * @param int $notifyId
     *
     * @return array 公告阅读情况
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function showReaders($notifyId, $currentUserId, $own)
    {
        $param['own']  = $own;
        $check         = 0;
        $currentDate   = date("Y-m-d", time());
        $notifypublish = app($this->notifyRepository)->showNotify($notifyId);
        if (!$notifypublish) {
            return ['code' => ['0x025020', 'notify']];
        }
        if($notifypublish['publish'] != 1){
            return ['code' => ['0x000006', 'common']];
        }
        if (($notifypublish['publish'] == 1 && $notifypublish['begin_date'] > $currentDate) && ($notifypublish['from_id'] != $own['user_id'])) {
            return ['code' => ['0x000006', 'common']];
        }
        if (in_array(234, $own['menus']['menu']) && $notifypublish['publish'] == 2) {
            $check = 1;
        }
        if ((($notifyInfo = app($this->notifyRepository)->showNotifyAccess($notifyId, $param)) && $notifypublish['publish'] == 1 && $notifypublish['begin_date'] <= $currentDate) || $check == 1 || ($notifypublish['publish'] == 1 && $own['user_id'] == 'admin') || $notifypublish['from_id'] == $own['user_id']) {
            return app($this->notifyReadersRepository)->getReaders($notifyId);
        } else {
            return ['code' => ['0x000006', 'common']];
        }

    }

    public function showReadersBySign($notifyId, $currentUserId, $own ,$inputParams)
    {
        $param['own']  = $own;
        $check         = 0;
        $currentDate   = date("Y-m-d", time());
        $notifypublish = app($this->notifyRepository)->showNotify($notifyId);
        if (!$notifypublish) {
            return ['code' => ['0x025020', 'notify']];
        }
        if($notifypublish['publish'] != 1){
            return ['code' => ['0x000006', 'common']];
        }
        if (($notifypublish['publish'] == 1 && $notifypublish['begin_date'] > $currentDate) && ($notifypublish['from_id'] != $own['user_id'])) {
            return ['code' => ['0x000006', 'common']];
        }
        if (in_array(234, $own['menus']['menu']) && $notifypublish['publish'] == 2) {
            $check = 1;
        }
        if ((($notifyInfo = app($this->notifyRepository)->showNotifyAccess($notifyId, $param)) && $notifypublish['publish'] == 1 && $notifypublish['begin_date'] <= $currentDate) || $check == 1 || ($notifypublish['publish'] == 1 && $own['user_id'] == 'admin') || $notifypublish['from_id'] == $own['user_id']) {
            return app($this->notifyReadersRepository)->searchReaders($notifyId, $inputParams);
        } else {
            return ['code' => ['0x000006', 'common']];
        }

    }

    public function getReadersCount($notifyId){
        return app($this->notifyReadersRepository)->searchReaderCount($notifyId);
    }
    /**
     * 为参数赋予默认值
     *
     * @param string $key 键值
     * @param array $data 原来的数据
     * @param mixed $default 默认值
     *
     * @return string 处理后的值
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function defaultValue($key, $data, $default)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
    /**
     * 判断类别名称是否重复
     *
     * @param type $notifyTypeName
     * @param type $notifyTypeId
     *
     * @return boolean
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    private function notifyTypeNameExists($notifyTypeName, $notifyTypeId = 0)
    {
        if (app($this->notifyTypeRepository)->notifyTypeNameExists($notifyTypeName, $notifyTypeId) > 0) {
            return true;
        }

        return false;
    }
    /**
     * 处理公告字段
     *
     * @param array $fields
     *
     * @return array 处理后公告字段
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    private function handleNotifyFields($fields)
    {
        $_fields = [];

        foreach ($fields as $field) {
            $preFix = 'notify.';

            if ($field == 'notify_type_name') {
                $preFix = 'notify_type.';
            } else if ($field == 'user_name') {
                $preFix = 'user.';
            }

            $_fields[] = $preFix . $field;
        }

        return $_fields;
    }
    /**
     * 处理公告查询数组
     *
     * @param array $search
     *
     * @return array 处理后的查询数组
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    private function handleNotifySearch($search)
    {
        $_search = [];

        foreach ($search as $k => $v) {
            if ($k == 'user_name') {
                $_search['user.user_name'] = $v;
            } else if ($k == 'read' || $k == 'unread') {
                $_search[$k] = $v;
            } else {
                $_search['notify.' . $k] = $v;
            }
        }
        return $_search;
    }
    /**
     * 当天有效期开始的公告
     *
     * @param
     *
     * @return array 处理后的消息数组
     *
     * @author 李志军
     *
     * @since 2015-10-21
     */
    public function notifyBeginRemind()
    {
        $list     = app($this->notifyRepository)->listBeginNotify();
        $messages = [];
        foreach ($list as $value) {
            $userId     = app($this->notifyReadersRepository)->getReadersUserid($value['notify_id']);
            $messages[] = [
                'remindMark'   => 'notice-publish',
                'toUser'       => implode(',', $userId),
                'contentParam' => ['noticeTitle' => $value['subject']],
                'stateParams'  => ['notify_id' => $value['notify_id']],
            ];
        }
        return $messages;
    }

    // 公告外发
    public function flowOutSendToNotify($data)
    {
        if (!isset($data['from_id']) || empty($data['from_id'])) {
            return ['code' => ['0x025019', 'notify']];
        }
        if(count(explode(',',$data['from_id'])) > 1){
            return ['code' => ['0x025046', 'notify']];
        }
        if (!isset($data['subject']) || empty($data['subject'])) {
            return ['code' => ['0x025006', 'notify']];
        }
        if (!isset($data['content']) || empty($data['content'])) {
            return ['code' => ['0x025007', 'notify']];
        }
        if (empty($data['begin_date'])){
            return ['code' => ['0x025008', 'notify']];
        }
        if (!isset($data['top']) || (isset($data['top']) && $data['top'] == 0)){
            $data['top_end_time'] = '0000-00-00 00:00:00';
        }
        if (isset($data['top']) && $data['top'] && isset($data['top_end_time']) && $data['top_end_time'] && strtotime($data['top_end_time']) <= time()){
            return ['code' => ['0x025041', 'notify']];
        }
        if (isset($data['priv_scope']) && $data['priv_scope']!=='' && $data['priv_scope'] !== 0 && $data['priv_scope']!== 1 && $data['priv_scope'] !== '0' && $data['priv_scope'] !== '1'){
            return ['code' => ['0x025027', 'notify']];
        }
        if ((!isset($data['priv_scope']) || empty($data['priv_scope'])) && (!isset($data['dept_id']) || empty($data['dept_id']))
            && (!isset($data['role_id']) || empty($data['role_id'])) && (!isset($data['user_id']) || empty($data['user_id']))){
            return ['code' => ['0x025026', 'notify']];
        }
        if(((isset($data['dept_id']) && !empty($data['dept_id'])) || (isset($data['role_id']) && !empty($data['role_id']))
            || (isset($data['user_id']) && !empty($data['user_id']))) && (!isset($data['priv_scope']) || $data['priv_scope'] ==='')){
            $data['priv_scope'] = 0;
        }

        if((isset($data['dept_id']) && $data['dept_id'] == 'all' ) || (isset($data['role_id']) && $data['role_id'] == 'all' ) || (isset($data['user_id']) && $data['user_id'] == 'all' )){
            $data['priv_scope'] = 1;
            $data['dept_id'] = '';
            $data['role_id'] = '';
            $data['user_id'] = '';
        }
        //起始时间与当前时间更小的时间
        $createdAt = Arr::get($data, 'begin_date');
        $now = date('Y-m-d');
        $createdAt = $createdAt <= $now ? $createdAt : $now;
        $userDetail = own();
        if(isset($data['from_id'])){
            $userDetail['user_id'] = $data['from_id'];
        }
        $result = $this->addNotify($data, $userDetail, $createdAt);

        if(isset($result['code'])){
            return $result;
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'notify',
                    'field_to' => 'notify_id',
                    'id_to' => $result['notify_id']
                ]
            ]
        ];
    }

    /**
     * 流程外发更新
     * @param $data
     * @return array
     */
    public function flowOutSendToUpdateNotify($data)
    {
        if (empty($data['unique_id'])) {
            return ['code' => ['0x025002', 'notify']];
        }
        if(empty($data['data'])) {
            return ['code' => ['0x000003', 'common']];
        }
        $result = $this->editNotify($data['data'], $data['unique_id'], own(), true);
        if(isset($result['code'])){
            return $result;
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'notify',
                    'field_to' => 'notify_id',
                    'id_to' => $data['unique_id']
                ]
            ]
        ];
    }

    /**
     * 流程外发删除
     * @param $data
     * @return array
     */
    public function flowOutSendToDeleteNotify($data)
    {
        if (empty($data['unique_id'])) {
            return ['code' => ['0x025002', 'notify']];
        }

        $result = $this->deleteNotify($data['unique_id'], own());

        if(isset($result['code'])){
            return $result;
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'notify',
                    'field_to' => 'notify_id',
                    'id_to' => $data['unique_id']
                ]
            ]
        ];
    }

    /**
     * 定时任务清空置顶时间
     */
    public function cancelOutTimeTop()
    {
        app($this->notifyRepository)->cancelOutTimeTop();
    }


    /**
     * 报表数据
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function getNotifyReportData($datasourceGroupBy = 'dept_id', $datasourceDataAnalysis='', $chartSearch)
    {
        /*$datasourceGroupBy = 'dept_id';
        $datasourceDataAnalysis = 'count';
        $depts = [];
        $deptUnreaderCountData = [];
        $deptReaderCountData = [];
        if(isset($chartSearch['notifyId']) && !empty($chartSearch['notifyId'])) {
            $deptArray = [];
            if(isset($chartSearch['dept_id']) && !empty($chartSearch['dept_id'])) {
                $deptArray = explode(',' , $chartSearch['dept_id']);
            }
            $deptByRole = [];
            if(isset($chartSearch['role_id']) && !empty($chartSearch['role_id'])) {
                $role = app($this->notifyReadersRepository)->getDeptByRole($chartSearch['role_id']);
                foreach ($role as $key => $value) {
                    $deptByRole[] = $value->dept_id;
                }
            }
            $depts = array_unique(array_merge($deptByRole,$deptArray));
            $notifyId = $chartSearch['notifyId'];
            $notifyList = app($this->notifyReadersRepository)->getReaders($notifyId);
            if($notifyList) {
                foreach ($notifyList as $k => $v) {
                    if(!empty($depts)) {
                        if(!in_array($v['dept_id'],$depts)) {
                            continue;
                        }
                    }
                    $deptUnreaderCountData[] = ['name' => $v['dept_name'], 'y' => count($v['users']['unreader'])];
                    $deptReaderCountData[] = ['name' => $v['dept_name'], 'y' => count($v['users']['reader'])];
                }
                return [
                    [
                        'name' => '未读数量',
                        'group_by' => '部门',
                        'data' => $deptUnreaderCountData
                    ],
                    [
                        'name' => '已读数量',
                        'group_by' => '部门',
                        'data' => $deptReaderCountData
                    ]
                ];
            }
        }
        return [];*/
        // $datasourceGroupBy = 'dept_id';
        // $datasourceDataAnalysis = 'count';
        $deptReaderCountData = $deptUnreaderCountData = $depts = $users = [];
        if(isset($chartSearch['notifyId']) && !empty($chartSearch['notifyId'])) {
            if(isset($chartSearch['dept_id']) && !empty($chartSearch['dept_id'])) {
                $depts = explode(',' , $chartSearch['dept_id']);
            }
            if (isset($chartSearch['role_id']) && !empty($chartSearch['role_id'])) {
                $users = app($this->notifyReadersRepository)->getUserIdsByRoleId($chartSearch['role_id']);
            }
            $notifyId = $chartSearch['notifyId'];
            $notifyList = app($this->notifyReadersRepository)->getReaders($notifyId);
            $notifyInfo          = app($this->notifyRepository)->showNotify($notifyId);
            if(isset($notifyList['list']) && $notifyList['list']) {
                foreach ($notifyList['list'] as $k => $v) {
                    $unreader = $reader = 0;
                    if(!empty($depts)) {
                        if(!in_array($v['dept_id'],$depts)) {
                            continue;
                        }
                    }
                    if (!empty($users)) {
                        if (!empty($v['users']['unreader'])) {
                            foreach ($v['users']['unreader'] as $j => $unreaderList) {
                                if (in_array($unreaderList['user_id'], $users)) {
                                    ++$unreader;
                                }
                            }
                        }
                        if (!empty($v['users']['reader'])) {
                            foreach ($v['users']['reader'] as $j => $readerList) {
                                if (in_array($readerList['user_id'], $users)) {
                                    ++$reader;
                                }
                            }
                        }
                    } else {
                        $unreader = count($v['users']['unreader']);
                        $reader = count($v['users']['reader']);
                    }
                    $deptUnreaderCountData[] = ['name' => $v['dept_name'], 'y' => $unreader];
                    $deptReaderCountData[] = ['name' => $v['dept_name'], 'y' => $reader];
                }
                return [
                    [
                        // 'name' => '未读数量',
                        // 'group_by' => '部门',
                        'name' => $notifyInfo['subject'].trans('notify.not_read'),
                        'group_by' => trans('notify.dept'),
                        'data' => $deptUnreaderCountData
                    ],
                    [
                        // 'name' => '已读数量',
                        // 'group_by' => '部门',
                        'name' => $notifyInfo['subject'].trans('notify.has_read'),
                        'group_by' => trans('notify.dept'),
                        'data' => $deptReaderCountData
                    ]
                ];
            }
        }
        return [];
    }
    /**
     * 报表数据分类依据
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function getGroupAnalyze($notify)
    {
        $notifyId = isset($notify['notify_id']) ? $notify['notify_id'] : '';
        $data = [];
        if($notifyId) {
            $notifyId = explode(',', trim($notifyId, ','));
            foreach ($notifyId as $key => $value) {
                 $notifyInfo          = app($this->notifyRepository)->showNotify($value);
                 if (empty($notifyInfo)) {
                    continue;
                 }
                $data[$value] = [
                //数据源分组依据
                'datasource_group_by' => [
                    // 'dept_id' => '部门'
                    'dept_id' => trans('notify.dept')
                ],
                //数据源分析字段
                'datasource_data_analysis' => [
                    // 'count' => '阅读数量'
                    'count' => trans('notify.read_num')
                ],
                'flow_name' => $notifyInfo['subject']
            ];
            }
            return $data;
        }
        return [];
    }

    /**
     * 置顶
     *
     */
    public function top($notifyId, $params)
    {
        if (!empty($params)) {
            $topEndTime = $params['top_end_time'] ? $params['top_end_time'] : "";
        } else {
            $topEndTime = "";
        }

        $TopCreateTime = date('Y-m-d H:i:s');
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }

        if (app($this->notifyRepository)->editNotify(['top' => 1, 'top_end_time' => $topEndTime, 'top_create_time' => $TopCreateTime], ['notify_id' => $notifyId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 取消置顶
     *
     */
    public function cancelTop($notifyId)
    {
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }

        if (app($this->notifyRepository)->editNotify(['top' => 0, 'top_end_time' => "", 'top_create_time' => ""], ['notify_id' => $notifyId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    public function withdraw($notifyId)
    {
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }
        return app($this->notifyRepository)->editNotify(['publish' => 0, 'status' => 0], ['notify_id' => $notifyId]);
    }


     //获取评论列表
    public function getCommentList($notifyId, $param)
    {
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }

        $_comments = [];

        if ($comments = app($this->notifyCommentRepository)->getCommentList($notifyId, $this->parseParams($param))) {
            foreach ($comments as $comment) {
                ///$comment->user_name = get_user_simple_attr($comment->user_id);
                $comment->attachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'notify_comments', 'entity_id' => $comment->comment_id]);
                $reply                = $this->getChildrenComments($comment->comment_id);
                foreach ($reply as &$v) {
                    $v['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'notify_comments', 'entity_id' => $v['comment_id']]);
                }
                $comment->reply       = $reply;
                $_comments['list'][]  = $comment;
                $_comments['total']   = app($this->notifyCommentRepository)->getCommentsCount($notifyId);
            }
        }
        return $_comments;
    }

    //获取子评论
    public function getChildrenComments($commentId)
    {
        return app($this->notifyCommentRepository)->getChildrenComments($commentId);
    }


     //新建评论
    public function addComment($data, $notifyId, $currentUserId)
    {
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }

        $notify = app($this->notifyRepository)->getDetail($notifyId);

        if ($notify->allow_reply == 0) {
            return ['code' => ['0x025017', 'notify']];
        }

        // 判断评论内容或者附件两者不能同时为空
        if ((isset($data['content']) && empty($data['content'])) && (!isset($data['attachments']))) {
            return ['code' => ['0x025022', 'notify']];
        }
        $commonData = [
            'parent_id'   => $this->defaultValue('parent_id', $data, 0),
            'notify_id'     => $notifyId,
            'user_id'     => $currentUserId,
            'content'     => $data['content'],
            'blockquote'  => isset($data['blockquote']) ? $data['blockquote'] : 0,
//            'created_at' => date('Y-m-d H:i:s'),
        ];
        if ($commonData['parent_id'] !== 0){
            $parentId = $commonData['parent_id'];
            $parentInfo = app($this->notifyCommentRepository)->getDetail($parentId);
            // 父评论不存在
            if(!$parentInfo){
                return ['code' => ['0x025030', 'notify']];
            }
            // 父评论有父评论，目前评论只能两级
            if($parentInfo->parent_id !== 0){
                return ['code' => ['0x025031', 'notify']];
            }
        }
        if ($result = app($this->notifyCommentRepository)->insertData($commonData)->toArray()) {
            // 附件处理
            if (isset($data['attachments']) && $data['attachments']) {
                app($this->attachmentService)->attachmentRelation("notify_comments", $result["comment_id"], $data['attachments']);
            }
            app($this->notifyRepository)->editNotify(['comments' => $this->getCommentsCount($notifyId)], ['notify_id' => $notifyId]);

            return ['comment_id' => $result['comment_id'], 'content' => $result['content'], 'created_at' => $result['created_at'], 'parent_id' => $result['parent_id'], 'blockquote' => $result['blockquote'], 'user_id' => $result['user_id']];
        }

        return ['code' => ['0x000003', 'common']];
    }

    //获取评论数量
    public function getCommentsCount($notifyId)
    {
        return app($this->notifyCommentRepository)->getCommentsCount($notifyId);
    }

    //删除评论
    public function deleteComment($commentId, $currentUserId)
    {
        if ($commentId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }
        //自己发布的评论可以删
        if (!$this->checkDeleteCommentAuth($commentId, $currentUserId)) {
            return ['code' => ['0x025017', 'notify']];
        }
        $commentInfo = app($this->notifyCommentRepository)->getDetail($commentId);
        if(!$commentInfo){
            return ['code' => ['0x025023', 'notify']];
        }
        //过期不能删
        $expireTime = $commentInfo->created_at->addMinutes(10);
        if(Carbon::now()->greaterThan($expireTime)){
            return ['code' => ['0x025032', 'notify']];
        }
        //有子评论不能删
        $children = app($this->notifyCommentRepository)->getChildrenComments($commentId);
        if($children){
            return ['code' => ['0x025033', 'notify']];
        }
        if (app($this->notifyCommentRepository)->deleteComment($commentId)) {

            app($this->notifyCommentRepository)->deleteByWhere(['parent_id' => [$commentId]]);

            app($this->notifyRepository)->editNotify(['comments' => $this->getCommentsCount($commentInfo->notify_id)], ['notify_id' => $commentInfo->notify_id]);
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    //编辑评论
    public function editComment($data, $commentId, $userId)
    {
        $oldData = $this->getCommentDetail($data['notify_id'], $commentId);
        if (!$oldData) {
            return ['code' => ['0x025023', 'notify']];
        }

        if ($oldData->user_id != $userId) {
            return ['code' => ['0x000006', 'common']];
        }
        //过期不能修改
        $expireTime = $oldData->created_at->addMinutes(10);
        if(Carbon::now()->greaterThan($expireTime)){
            return ['code' => ['0x025032', 'notify']];
        }
        //有子评论不能修改
        $children = app($this->notifyCommentRepository)->getChildrenComments($commentId);
        if($children){
            return ['code' => ['0x025033', 'notify']];
        }
        // if ($oldData->content == $data['content']) {
        //     return true;
        // }
        $updateData = [
            'content' => $data['content'],
        ];

        $updateWhere = [
            'comment_id' => [$commentId],
        ];
        // 编辑附件
        if (isset($data['attachments']) && $data['attachments'] != "") {
            app($this->attachmentService)->attachmentRelation("notify_comments", $commentId, $data['attachments']);
        }
        return app($this->notifyCommentRepository)->updateData($updateData, $updateWhere);
    }

    public function getCommentDetail($notifyId, $commentId)
    {
        if ($commentId == 0) {
            return ['code' => ['0x025023', 'notify']];
        }

        $comment = app($this->notifyCommentRepository)->getDetail($commentId);

        $comment->user_name = get_user_simple_attr($comment->user_id);

        return $comment;
    }

    //是否有删除评论权限
    public function checkDeleteCommentAuth($commentId, $userId)
    {
        return app($this->notifyCommentRepository)->checkDeleteCommentAuth($commentId, $userId);
    }

    //手动提醒未读人员
    public function remindUnreaders($notifyId, $own)
    {
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }
        $notify = app($this->notifyRepository)->showNotify($notifyId);
        if(!$notify){
            return ['code' => ['0x025020', 'notify']];
        }
        $currentDate   = date("Y-m-d", time());
        if($notify->publish != 1 || $notify->begin_date > $currentDate){
            return ['code' => ['0x000006', 'common']];
        }
        if($notify['status'] == 1 || ($notify['end_date'] != '0000-00-00' && $notify['end_date'] < $currentDate)){
            return ['code' => ['0x025040', 'notify']];
        }
        if(!($notify['from_id'] == $own['user_id'] || $own['user_id']=='admin')){
            return ['code' => ['0x025017', 'notify']];
        }
        $unreaders = app($this->notifyReadersRepository)->getUnreadersIds($notifyId);
        if(empty($unreaders)){
            return ['code' => ['0x025024', 'notify']];
        }
        $noticeName = $notify['subject'];

        $messages = [
            'remindMark' => 'notice-unread',
            'toUser' => $unreaders,
            'contentParam' => ['noticeName'=>$noticeName],
            // 路由参数
            'stateParams' => ['notify_id' => $notifyId]
        ];
        return Eoffice::sendMessage($messages);
    }

    //手动提醒一个未读人员
    public function remindOneUnreader($params, $own)
    {
        $notifyId=$params['notifyId'];
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }
        $notify = app($this->notifyRepository)->showNotify($notifyId);
        if(!$notify){
            return ['code' => ['0x025020', 'notify']];
        }
        $currentDate   = date("Y-m-d", time());
        if($notify->publish != 1 || $notify->begin_date > $currentDate){
            return ['code' => ['0x000006', 'common']];
        }
        if($notify['status'] == 1 || ($notify['end_date'] != '0000-00-00' && $notify['end_date'] < $currentDate)){
            return ['code' => ['0x025040', 'notify']];
        }
        if(!($notify['from_id'] == $own['user_id'] || $own['user_id']=='admin')){
            return ['code' => ['0x025017', 'notify']];
        }
        $unreaders[] = $params['userId'];
        if(empty($unreaders)){
            return ['code' => ['0x025024', 'notify']];
        }
        $noticeName = $notify['subject'];

        $messages = [
            'remindMark' => 'notice-unread',
            'toUser' => $unreaders,
            'contentParam' => ['noticeName'=>$noticeName],
            // 路由参数
            'stateParams' => ['notify_id' => $notifyId]
        ];
        return Eoffice::sendMessage($messages);
    }
    //是否为发布人
    public function isPublisher($notify, $own)
    {
        $from_id = $notify['from_id'];
        $result = $from_id == $own['user_id'] ? 1 : 0;
        return $result;
    }

    //是否有审核权限
    public function canCheck($own)
    {
        $menu = $own['menus']['menu'];
        return in_array('234', $menu) ? 1 : 0;
    }

    public function canRemind($notifyId, $own)
    {
        if ($notifyId == 0) {
            return ['code' => ['0x025005', 'notify']];
        }
        $notify = app($this->notifyRepository)->showNotify($notifyId);
        if(!$notify){
            return ['code' => ['0x025020', 'notify']];
        }
        $currentDate = date("Y-m-d", time());
        if($notify->publish != 1 || $notify->begin_date > $currentDate) {
            return 0;
        }
        if($notify['status'] == 1 || ($notify['end_date'] != '0000-00-00' && $notify['end_date'] < $currentDate)){
            return 0;
        }
        if($own['user_id'] == 'admin'){
            return 1;
        }
        $from_id = $notify['from_id'];
        $result = $from_id == $own['user_id'] ? 1 : 0;
        return $result;
    }

    public function getAfterLoginOpenList($own)
    {
        $expiredVisible = $this->checkCanReadExpiredNotify($own);
        $params['expired_visible'] = $expiredVisible;
        return app($this->notifyRepository)->getAfterLoginOpenList($params);
    }

    // 确认已阅
    public function commitRead($notifyId, $userId)
    {
        if (app($this->notifyReadersRepository)->readerExists($notifyId, $userId) == 0) {
            $data = [
                'notify_id' => $notifyId,
                'user_id'   => $userId,
            ];
            app($this->notifyReadersRepository)->insertData($data);
        };
        return 'success';
    }

    public function countMyNotify($own)
    {
        $param['own'] = $own;
        // 公告设置过期可见范围
        if(!$this->checkCanReadExpiredNotify($own)){
            $param['expired_not_visible'] = 1;
        }
        $total = app($this->notifyRepository)->getNotifyCount($param);
        $param['search']['unread'] = 1;
	    $unread = app($this->notifyRepository)->getNotifyCount($param);
        return compact('total', 'unread');
    }

    // 过期查看设置
    public function getExpiredVisibleSettings()
    {
        $settings = Redis::hGetAll('notify_settings:expired');
        if($settings !== []){
            return $settings;
        }
        $settings = app($this->notifySettingsRepository)->getExpiredVisibleSettings();
        $res = [];
        foreach ($settings as $value){
            $res[$value->setting_key] = $value->setting_value;
            Redis::hSet('notify_settings:expired', $value->setting_key, $value->setting_value);
        }
        return $res;
    }

    public function setExpiredVisibleSettings($data)
    {

        foreach($data as $key => $value) {
//            if (!in_array($key, self::allowed_setting_fields)){
//                unset($data[$key]);
//                continue;
//            }
            $setting = app($this->notifySettingsRepository)->entity->find($key);
            $setting->setting_value = $value;
            if($setting->isDirty()){
                $setting->save();
                Redis::hset('notify_settings:expired', $key, $value);
            }
        }
        return true;
    }

    public function checkCanReadExpiredNotify($own)
    {
        $expiredSettings = $this->getExpiredVisibleSettings();
        if (isset($expiredSettings['expired_visible_scope']) && $expiredSettings['expired_visible_scope'] == 1){
            return false;
        }
        if ($expiredSettings['expired_visible_scope'] == 2) {
            if (in_array($own['dept_id'], explode(',', $expiredSettings['expired_visible_dept']))
                || in_array($own['user_id'], explode(',', $expiredSettings['expired_visible_user']))
            ){
                return true;
            }
            foreach($own['role_id'] as $value){
                if (in_array($value, explode(',', $expiredSettings['expired_visible_role']))){
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    /***
     * 催促公告审核
     *
     * @param $notifyId
     * @param $own
     * @return mixed
     */
    public function urgeReview($notifyId, $own)
    {
        $notify = app($this->notifyRepository)->getDetail($notifyId);
        if(!$notify){
            return NotifyPermission::NOTIFY_NOT_EXIST;
        }
        if ($notify->from_id != $own['user_id']){
            return NotifyPermission::NO_PERMISSION;
        }
        if($notify->publish != 2){
            return NotifyPermission::NOT_AUDIT_NOTIFY;
        }
        if (!isset($own['user_name'])) {
            $userRepository = app($this->userRepository);
            $createUser = $userRepository->getUserName($own['user_id']);
        } else {
            $createUser = $own['user_name'];
        }
        $sendData['remindMark'] = 'notice-urge';
        $userId                 = app($this->userMenuService)->getMenuRoleUserbyMenuId(234);

        $sendData['toUser']       = implode(',', $userId);
        $sendData['contentParam'] = ['noticeTitle' => $notify->subject, 'userName' => $createUser];
        $sendData['stateParams']  = ['notify_id' => $notify->notify_id];
        \Log::info('消息类型：notice-urge-2031' .'发送人：'.$own['user_name'].'接收人：'.implode(',', $userId));
        Eoffice::sendMessage($sendData);

        return 'success';
    }

    /**
     * 使用消息队列更新全站搜索数据
     *
     * @param   string|int    $id
     */
    public function updateGlobalSearchDataByQueue($ids)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchNotifyMessage($ids);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    /**
     * 表单建模模块列表展示分类用，处理未分类
     * @param $notifyTypeId
     * @return array
     */
    public function getNotifyTypeForFormModelingList($notifyTypeId)
    {
        $idArray = explode(',', $notifyTypeId);

        $types = app($this->notifyTypeRepository)->entity->find($idArray)->toArray();

        if(in_array(0, $idArray)){
            array_push($types, ["notify_type_id" => 0, "notify_type_name" => trans('notify.unclassified_announcement')]);
        }

        return $types;
    }

    public function getNotifyReadRange($notifyId){
        $range = app($this->notifyReadersRepository)->getNotifyReadRange($notifyId);
        return $range;
    }
    public function handleLogParams($user , $content , $relation_id ='' , $relation_title='')
    {
        $data = [
            'creator' => $user,
            'content' => $content,
            'relation_table' => 'notify_content',
            'relation_id' => $relation_id,
            'relation_title' => $relation_title
        ];
        return $data;
    }

    private function handleCreator($data , $creatorId){
        $notifyData = [];
        if(isset($data['creator_type']) && !empty($data['creator_type'])){
            //外发判断
            if(count(explode(',',$data['creator_type'])) > 1){
                return ['code' => ['0x025045', 'notify']];
            }
            if($data['creator_type'] == 1){
                if(count(explode(',',$data['creator_id'])) > 1){
                    return ['code' => ['0x025043', 'notify']];
                }
                $notifyData['creator_type'] = 1;
                if(isset($data['creator_id']) && !empty($data['creator_id'])){
                    $notifyData['creator'] = $data['creator_id'];
                }else{
                    $notifyData['creator'] = $creatorId;
                }
            }else if($data['creator_type'] == 2){
                if(count(explode(',',$data['department_id'])) > 1){
                    return ['code' => ['0x025044', 'notify']];
                }
                $notifyData['creator_type'] = 2;
                if(isset($data['department_id']) && !empty($data['department_id'])){
                    $notifyData['creator'] = $data['department_id'];
                }else{
                    $detail = app($this->userRepository)->getUserDeptIdAndRoleIdByUserId($creatorId);
                    $notifyData['creator'] = $detail['dept_id'] ?? '';
                }
            }else if($data['creator_type'] == 3){
                $notifyData['creator_type'] = 3;
                $companyDetail = app($this->companyRepository)->getCompanyDetail();
                $notifyData['creator'] = $companyDetail->company_id;

            }
        }else{
            $notifyData['creator_type'] = 1;
            $notifyData['creator'] = $data['from_id'] ?? $creatorId;
        }

        return $notifyData;
    }

}
