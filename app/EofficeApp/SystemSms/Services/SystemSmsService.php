<?php

namespace App\EofficeApp\SystemSms\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\SystemSms\Repositories\SystemSmsReceiveRepository;
use App\EofficeApp\SystemSms\Repositories\SystemSmsRepository;
use App\EofficeApp\User\Repositories\UserRepository;
use App\EofficeApp\Lang\Services\LangService;
use App\EofficeApp\System\Remind\Services\SystemRemindService;
use App\EofficeApp\FormModeling\Services\FormModelingService;
use Lang;
use DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Arr;
/**
 * 内部消息服务
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 */
class SystemSmsService extends BaseService {

    /** @var object 内部消息资源库变量 */
    private $systemSmsRepository;
    private $systemSmsReceiveRepository;
    private $userRepository;
    private $langService;
    private $remindService;
    private $systemLangs;
    public function __construct(
    SystemSmsRepository $systemSmsRepository, SystemSmsReceiveRepository $systemSmsReceiveRepository, UserRepository $userRepository, LangService $langService, SystemRemindService $remindService
    ) {
        $this->systemSmsRepository = $systemSmsRepository;
        $this->systemSmsReceiveRepository = $systemSmsReceiveRepository;
        $this->userRepository = $userRepository;
        $this->langService = $langService;
        $this->remindService = $remindService;
        $this->messageDataService     = 'App\EofficeApp\UnifiedMessage\Services\MessageDataService';

    }
    public function getSystemLangs($key = '', $locale = 'zh-CN')
    {
        $systemLangs = trans('system', [], $locale);
        $thirdSystemLangs = trans('third_system', [], $locale);
        $messageData = app($this->messageDataService)->getUndefinedMessageData();
        $systemLangs = array_merge($systemLangs, $messageData);
        if(!$this->systemLangs) {
            $this->systemLangs = is_array($thirdSystemLangs) ? array_merge($systemLangs, $thirdSystemLangs) : $systemLangs;
        }

        if(!$key) {
            return $this->systemLangs;
        }
        return $this->systemLangs[$key] ?? '';
    }
    /**
     * 发送系统消息
     */
    public function addSystemSms($data) {
        //必须要有接收人
        if (!isset($data['recipients'])) {
            return false;
        }
        $smsData = array_intersect_key($data, array_flip($this->systemSmsRepository->getTableColumns()));
        $smsData['contentParam'] = isset($smsData['contentParam']) ? $smsData['contentParam'] : '';
        $smsData['contentParam'] = json_encode($smsData['contentParam'], true);
        if (($data['sms_menu'].'-'.$data['sms_type']) == 'flow-press') {
            $smsData['content'] = $data['content'];
            $smsData['contentParam'] = null;
        }
        $smsData['module_type'] = isset($smsData['module_type']) ? $smsData['module_type'] : '';
        $result = $this->systemSmsRepository->insertData($smsData);
        $sms_id = $result->sms_id ?? '';
        $toSms = [
            'sms_id' => $sms_id,
            'remind_flag' => 0, //强制归0
            'deleted' => 0 //强制归0
        ];
        if (is_array($data['recipients'])) {
            $recipientsIds = $data['recipients'];
        } else {
            $recipientsIds = explode(",", $data['recipients']);
        }


        foreach ($recipientsIds as $recipientsId) {
            if ($recipientsId) {
                $toSms['recipients'] = $recipientsId;
                $this->systemSmsReceiveRepository->insertData($toSms);
            }
        }
        return $result;
    }

    /**
     * 查看我的系统消息（列表|高级查询|筛选）
     */
    public function mySystemSms($user_id, $data) {
        $locale = Lang::getLocale();

        if (!$user_id) {
            return false;
        } else {
            $data["user_id"] = $user_id;
        }


        $res = [];
        if (isset($data["search"])) {
            if (!is_array($data["search"])) {
                $temp = json_decode($data["search"], true);
            } else {
                $temp = $data["search"];
            }
            foreach ($temp as $k => $v) {
                if ($k == "sms_menu") {
                    if ((isset($v[0]) && !$v[0])) {
                        continue;
                    }
                }
                $res[$k] = $v;
            }
            $data["search"] = json_encode($res);
        }
        $receivedSms = $this->response($this->systemSmsReceiveRepository, 'mySystemSmsTotal', 'mySystemSms', $this->parseParams($data));
        $result = [];
        $allLangData = $this->getSystemLangs('', [], $locale);
        foreach ($receivedSms['list'] as $receive) {
            //news_submit_src
            $linkname = $allLangData[$receive["sms_menu"] . "_" . $receive["sms_type"] . "_src" ] ?? '';
            $temp = [];
            $temp["link_name"] = trans($linkname);
            $temp["link_url"] = "";
            if ($receive['sms_type'] == 'custom' || substr($receive['sms_menu'], 0, 13) == 'heterogeneous') {
                $temp["link_name"] = trans('system.see_detail');
            }
            $url = $receive["remind_state"];

            if ($receive["params"] && $receive['sms_menu'] != 'remind') {
                $parameterArray = json_decode($receive["params"], true);
                $parameterString = "{";
                foreach ($parameterArray as $k => $v) {
                    if (is_numeric($v)) {
                        $parameterString.= $k . ":" . $v . ",";
                    } else {
                        $parameterString.= $k . ":'" . $v . "',";
                    }
                }
                $parameterString = trim($parameterString, ",");
                $parameterString .= "}";

                $temp["link_url"] = $url . "(" . $parameterString . ")";
            } else {
                $temp["link_url"] = $url;
            }

            //判断是否有要替换的发送内容参数
            foreach ($receive as $key => $value) {
                $temp[$key] = $value;
            }
            array_push($result, $temp);
        }
        $finalData = [
            "total" => $receivedSms['total'],
            "list" => $result,
        ];
        foreach($finalData['list'] as $key => &$value) {
            $value['remind_name'] = mulit_trans_dynamic('system_reminds.remind_name.'.$value['sms_menu']. '_' . $value['sms_type'] . '_'. $value['remind_id'], [], $locale);
            if (isset($value['contentParam']) && !empty($value['contentParam'])) {
                    // 对任务进行特殊处理
                    if (($value['sms_menu']. '-' . $value['sms_type']) == 'task-press') {
                        $content = json_decode($value['contentParam'], true);
                        $value['content'] = $this->getTaskContent($value['content'], $content);
                    }
                    // 对导出进行处理
                    if (($value['sms_menu']. '-' . $value['sms_type']) == 'export-download') {
                        $contents = json_decode($value['contentParam'], true);
                        $value['content'] =  trans('export.your', [], $locale) . '〈' . $contents .'〉'. trans('export.down_success', [], $locale);
                    }
                }else{
                    $value['content'] = $value['content'];
                }
                // 对自定义消息进行处理
                $fieldName = json_decode($value['params'], true);

                if (isset($fieldName['field_code']) && isset($fieldName['field_table_key'])) {

                     $value['remind_name'] = mulit_trans_dynamic("custom_fields_table.field_name." . $fieldName['field_table_key'] . '_' . $fieldName['field_code'], [], $locale) . trans('system.reminds', [], $locale);
                     if (($value['sms_type'] == 'custom') && ($value['sms_menu'] == 'office_supplies')) {
                        $value['remind_name'] = mulit_trans_dynamic("custom_fields_table.field_name." . $fieldName['field_table_key'] . '_' . $fieldName['field_code'], [], $locale) . trans('system.reminds', [], $locale);
                    }
                    if (($value['sms_type'] == 'custom') && ($value['sms_menu'] == 'customer')) {
                        $value['remind_name'] = mulit_trans_dynamic("custom_fields_table.field_name." . $fieldName['field_table_key'] . '_' . $fieldName['field_code'], [], $locale) . trans('system.reminds', [], $locale);
                    }
                    if (($value['sms_type'] == 'custom') && ($value['sms_menu'] == 'personnel_files')) {
                        $value['remind_name'] = mulit_trans_dynamic("custom_fields_table.field_name." . $fieldName['field_table_key'] . '_' . $fieldName['field_code'], [], $locale) . trans('system.reminds', [], $locale);
                    }
                    if (($value['sms_type'] == 'custom') && ($value['sms_menu'] == 'project')) {
                        $comboboxValue = substr($fieldName['field_table_key'],strripos($fieldName['field_table_key'],"_")+1);
                        $combobox = DB::table('system_combobox')->where('combobox_identify', '=', 'PROJECT_TYPE')->get();
                        $comboboxId = '';
                        if ($combobox) {
                            $comboboxId = $combobox[0]->combobox_id;
                        }
                        $projectType = DB::table('system_combobox_field')->where('combobox_id', $comboboxId)->where('field_value', $comboboxValue)->get();
                        $fieldNames = '';
                        $identify = '';
                        if ($projectType) {
                            $fieldNames = isset($projectType[0]->field_name) ? $projectType[0]->field_name : '';
                        }
                        $value['remind_name'] = mulit_trans_dynamic("custom_fields_table.field_name." . $fieldName['field_table_key'] . '_' . $fieldName['field_code'], [], $locale) . trans('system.reminds', [], $locale);
                        if (mulit_trans_dynamic('system_combobox_field_project_type.field_name.'.$fieldNames, [], $locale)) {
                            $value['remind_name'] = '['.mulit_trans_dynamic('system_combobox_field_project_type.field_name.'.$fieldNames, [], $locale).']'.$value['remind_name'];
                        } else {
                            $value['remind_name'] = $value['remind_name'];
                        }

                    }
                }

                if (($value['sms_type'] == 'custom') && ($value['sms_menu'] != 'customer') && ($value['sms_menu'] != 'office_supplies') && ($value['sms_menu'] != 'personnel_files') && ($value['sms_menu'] != 'project')) {
                    $fieldName = json_decode($value['params'], true);
                    if (isset($fieldName['field_code'])) {
                         $value['remind_name'] = mulit_trans_dynamic("custom_fields_table.field_name." . $fieldName['field_table_key'] . '_' . $fieldName['field_code'], [], $locale) . trans('system.reminds', [], $locale);
                    }

                }
                // 对流程催办进行处理
                if (($value['sms_menu']. '-' . $value['sms_type']) == 'flow-press') {
                    $value['content'] = $value['content'];
                }
                if (($value['sms_menu']. '-' . $value['sms_type']) == 'export-download') {
                    $value['remind_name'] = trans('system.export_dpwnload');
                }
                if (($value['sms_menu']. '-' . $value['sms_type']) == 'task-press') {
                    $finalData['list'][$key]['remind_name'] = trans('system.task_press_remind', [], $locale);
                }
                if (($value['sms_menu']. '-' . $value['sms_type']) == 'flow-press') {
                    $finalData['list'][$key]['remind_name'] = trans('system.flow_press_remind', [], $locale);
                }

        }
        return $finalData;
    }


    public function signSystemSmsRead($user_id, $data){
        if (!$user_id) {
            return false;
        } else {
            $data["user_id"] = $user_id;
        }
        $temp = $this->systemSmsReceiveRepository->signSystemSmsRead($this->parseParams($data));
        return $temp;
    }

    // 模块联动消息已读
    public function moduleToReadSms($param, $userId) {
        $param = $this->parseParams($param);
        $info = $this->systemSmsReceiveRepository->moduleToReadSms($param);
        if ($info) {
            try {
                if (!empty($userId)) {
                    if (is_array($userId)) {
                        $userId = implode(',', $userId);
                    }
                    $systemMessageRefreshParams = [
                        'user_id' => $userId
                    ];
                    // OA实例模式下，发送REDIS_DATABASE参数
                    if (envOverload('CASE_PLATFORM', false)) {
                        $systemMessageRefreshParams['REDIS_DATABASE'] = envOverload('REDIS_DATABASE', 0);
                    }
                    Redis::publish('eoffice.system-message-refresh', json_encode($systemMessageRefreshParams));
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }
        return $info;
    }
    /**
     * 查看具体某一条消息（sms_id）
     * 消息标示 置1
     */
    public function viewSystemSms($id) { //不是消息sms_id
        $this->systemSmsReceiveRepository->updateData(["remind_flag" => 1], ["id" => [$id]]);
        return $this->systemSmsReceiveRepository->viewSystemSms($id);
    }

    public function setSmsRead($smsId, $userId) {
        $return = $this->systemSmsReceiveRepository->getSmsRead($userId, $smsId);
        $id = $return->id ?? '';
        return $this->viewSystemSms($id);
    }

    /**
     * 获取分组类别中最新的一条
     */
    public function getNewDetailByGroupBySmsType($user_id)
    {
        // $smsTypes = $this->systemSmsRepository->getSmsType();
        $maxSmsIdMap = $this->systemSmsReceiveRepository->getMaxSmsIdGroupByModule($user_id);
        $result = [];
        if (!empty($maxSmsIdMap)) {
            $fileds = ['content', 'sms_id','sms_menu', 'sms_type', 'contentParam', 'send_time'];
            $maxSmsIds = array_values($maxSmsIdMap->toArray());
            $smsMap = $this->systemSmsRepository->getSmsDetailByIds($maxSmsIds, $fileds)->mapWithKeys(function($item) {
                return [$item->sms_id => $item];
            });

            foreach ($maxSmsIdMap as $module => $smsId) {
                $result[$module] = $smsMap[$smsId] ?? null;
            }
        }
        if (empty($result)) {
            return $result;
        }
        foreach($result as $key => $value) {
            if (isset($value) && !empty($value)) {
                if (isset($value->contentParam) && !empty($value->contentParam)) {
                     // 对任务进行特殊处理
                    if (($value->sms_menu. '-' . $value->sms_type) == 'task-press') {
                        $content = json_decode($value->contentParam, true);

                        $value->content = $this->getTaskContent($value->content, $content);
                    }
                    // 对导出进行处理
                    if (($value->sms_menu. '-' . $value->sms_type) == 'export-download') {
                        $contents = json_decode($value->contentParam, true);
                        $value->content =  trans('export.your') . '<' . $contents .'>'. trans('export.down_success');
                    }
                }else{
                    $value->content = $value->content;
                }
            }

        }
        return $result;
    }

    public function getSystemSmsUnread($user_id, $data) {
        if (!$user_id) {
            return false;
        } else {
            $data["user_id"] = $user_id;
        }

        $temp = $this->systemSmsReceiveRepository->getSystemSmsUnread($this->parseParams($data));
        $module = [];
        $other = 0;

        foreach ($temp as $key => $val) {
            $keyName = $val["sms_menu"] ? $val["sms_menu"] : "other";
            if ($keyName == "other") {
                $other+=$val["count"];
            } else {
                $module["module"][$keyName] = $val["count"];
            }
        }
        $module["module"]["other"] = $other;
        $module["total"] = $this->systemSmsReceiveRepository->getUnreadTotal($user_id);
        return $module;
    }
   public function getLastTotal($user_id) {
        if (!$user_id) {
            return false;
        }
        $module = [];
        $module["total"] = $this->systemSmsReceiveRepository->getUnreadTotal($user_id);
        $lastMessage = $this->systemSmsReceiveRepository->getLastMessage($user_id);
        if ($lastMessage) {
            // 对任务进行特殊处理
            if (($lastMessage['sms_menu']. '-' . $lastMessage['sms_type']) == 'task-press') {
                $content = json_decode($lastMessage['contentParam'], true);

               $lastMessage['content'] =  $this->getTaskContent($lastMessage['content'], $content);
            }
            // 对导出进行处理
            if (($lastMessage['sms_menu']. '-' . $lastMessage['sms_type']) == 'export-download') {
                $contents = json_decode($lastMessage['contentParam'], true);
                $lastMessage['content'] =  trans('export.your') . '<' . $contents .'>'. trans('export.down_success');
            }
            $module["lastMessage"] = $lastMessage;
        }else{
            $module["lastMessage"] = [];
        }
        return $module;
   }

    public function getTaskContent($content, $contentParam)
    {
        if (!is_array($contentParam)) {
            return $content;
        }
        return $content . ', ' .
            trans('task.0x046024') . ':' . Arr::get($contentParam, 'task_name', '') . ' ' .
            trans('task.0x046026') . ':' . Arr::get($contentParam, 'userName', '');
    }
}
