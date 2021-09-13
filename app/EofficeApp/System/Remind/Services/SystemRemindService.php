<?php

namespace App\EofficeApp\System\Remind\Services;

use App\EofficeApp\Base\BaseService;
use DB;
use Illuminate\Support\Facades\Redis;
use Cache;
use Lang;
use Eoffice;
/**
 * 系统提醒设置service
 *
 * @author  朱从玺
 *
 * @since  2015-10-28 创建
 */
class SystemRemindService extends BaseService
{

    const REMIND_SWITCH_MARK = 'system:remind_switch';

    /**
     * [$systemRemindsRepository system_reminds表资源库]
     *
     * @var [object]
     */
    protected $systemRemindsRepository;

    /**
     * [$remindsRepository reminds表资源库]
     *
     * @var [object]
     */
    protected $remindsRepository;
    private $systemLangs;
    private $locale;
    private $custom = [
        44 => 'customer',
        264 => 'office_supplies',
        160 => 'project',
        920 => 'assets',
        415 => 'personnel_files',
        600 => 'car',
        150 => 'contract',
        233 => 'book'

    ];

    public function __construct() {
        parent::__construct();
        $this->systemRemindsRepository = 'App\EofficeApp\System\Remind\Repositories\SystemRemindsRepository';
        $this->remindsRepository       = 'App\EofficeApp\System\Remind\Repositories\RemindsRepository';
        $this->birthdayRepository      = 'App\EofficeApp\Birthday\Repositories\BirthdayRepository';
        $this->systemSmsService        = 'App\EofficeApp\SystemSms\Services\SystemSmsService';
        $this->empowerService          = 'App\EofficeApp\Empower\Services\EmpowerService';
        $this->meetingService          = 'App\EofficeApp\Meeting\Services\MeetingService';
        $this->webmailEmailboxService  = 'App\EofficeApp\System\SystemMailbox\Services\WebmailEmailboxService';
        $this->userService             = 'App\EofficeApp\User\Services\UserService';
        $this->formModelingService     = 'App\EofficeApp\FormModeling\Services\FormModelingService';
        $this->messageDataService     = 'App\EofficeApp\UnifiedMessage\Services\MessageDataService';
        $locale = Lang::getLocale();
    }
    public function getSystemLangs($key = '', $local = 'zh-CN')
    {
        $systemLangs = trans('system', [], $local);
        $thirdSystemLangs = trans('third_system', [], $local);

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
     * [getReminds 获取所有提醒方式]
     *
     * @author 朱从玺
     *
     * @since  2015-10-28 创建
     *
     * @return [array]     [查询结果]
     */
    public function getReminds($userId = '')
    {
        $locale = Lang::getLocale();
        $config = config('eoffice.reminds');
        $power = app($this->meetingService)->checkPermission();
        $mailPower = app($this->webmailEmailboxService)->checkMailPermission();
        if (!empty($config)) {
            foreach ($config as $k => $v) {
                $data["$k"] = app($v['0'])->{$v['1']}($userId);
            }
        }
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $remid = app($this->remindsRepository)->checkReminds("$k");
                if (!empty($remid)) {
                    if ($v == "false") {
                        $where = [
                            "reminds" => ["$k"],
                        ];
                        app($this->remindsRepository)->deleteByWhere($where);
                    }
                } else {
                    if ($v == "true") {
                        $id = $this->getRemindId($k);
                        if (!empty($id)) {
                            app($this->remindsRepository)->insertData(["id" => $id, "reminds" => "$k"]);
                        }
                    }
                }
            }
        }
        $reminds = app($this->remindsRepository)->getAllReminds();

        if (!$reminds) {
            return '';
        }
        $temp = 'shortMessage';
        $temps = 'webMail';
        foreach ($reminds as $key => $value) {
            if ($power == '0') {
                if ($reminds[$key]['reminds'] == $temp) {
                    unset($reminds[$key]);
                    continue;
                }
            }
            if ($mailPower == '0') {
                if ($reminds[$key]['reminds'] == $temps) {
                    unset($reminds[$key]);
                    continue;
                }
            }
            $reminds[$key]['reminds_name'] = $this->getSystemLangs($value['reminds'], [], $locale);
        }
        $reminds = $reminds->values();
        return $reminds;
    }

    public function getRemindId($reminds)
    {
        $id = array(
            'sms'          => 1,
            'wechat'       => 2,
            'email'        => 3,
            'qyweixin'     => 4,
            'shortMessage' => 5,
            'dingtalk'     => 6,
            'appPush'      => 7,
            'workwechat'   => 8,
            'webMail'     => 9,
            'dgwork'       => 10,
        );
        if (!empty($id[$reminds])) {
            return $id[$reminds];
        }

    }

    /**
     * [arrangeReminds 整理菜单提醒设置]
     *
     * @author 朱从玺
     *
     * @param  [array]         $reminds      [提醒方式]
     * @param  [array]         $functionData [菜单数据]
     *
     * @since  2015-10-28 创建
     *
     * @return [array]                       [整理结果]
     */
    public function arrangeReminds($reminds, $functionData)
    {
        $locale = Lang::getLocale();
        $selectArray = array_filter(explode(',', $functionData['reminds_select']));
        $select = [];
        foreach ($reminds as $key => $value) {
            if (in_array($value['id'], $selectArray)) {
                $functionData[$value['reminds']]['selected'] = 1;
                $select[] = $value['id'];
            } else {
                $functionData[$value['reminds']]['selected'] = 0;
            }

            $functionData[$value['reminds']]['id']           = $value['id'];
            $functionData[$value['reminds']]['reminds_name'] = $this->getSystemLangs($value['reminds'], [], $locale);
        }
        // 需要处理字段
        // unset($functionData['reminds_select']);
        $functionData['reminds_select'] = $select;
        return $functionData;
    }

    /**
     * [getRemindsMiddleList 获取提醒设置中间列]
     *
     * @method 朱从玺
     *
     * @return [object]               [查询结果]
     */
    public function getRemindsMiddleList($userId, $param)
    {
        // $locale = Lang::getLocale();

        $param = $this->parseParams($param);
        $param = array_merge($param, ['fields' => ['id', 'remind_name', 'remind_menu']]);
    
        $this->checkReminds();

        //下载文件提醒 id = -1
        $result[] = [
            "id"             => -1,
            "remind_name"    => trans("system.download_reminds"),
            "remind_menu"    => "export",
            "menu_name"      => trans("system.download_center"),
            "system_reminds" => [],
        ];

        $reminds = app($this->systemRemindsRepository)->getRemindsList($param);
	    $list = app($this->formModelingService)->getCustomRemindModules();
        $permissionModule = app($this->empowerService)->getPermissionModules();
        $maxId = app($this->systemRemindsRepository)->getMaxId();
        Cache::forever('system_reminds', $reminds);
        $permissionIds = [98, 242];
        foreach ($reminds as $key => $value) {
            if (isset($value->menu_id) && !in_array($value->menu_id, $permissionModule) && !in_array($value->menu_id, $permissionIds)) {
                unset($reminds->$key);
                continue;
            }
            $menuName = mulit_trans_dynamic("menu.menu_name.menu_" . $value['menu_id']);
            $systemName = $this->getSystemLangs($value['remind_menu'], [], $this->locale);
            $value['menu_name'] = $menuName ? $menuName : $systemName;

            $value['remind_name'] = mulit_trans_dynamic("system_reminds.remind_name.".$value['remind_menu'] .'_'. $value['remind_type'] .'_'.$value['id'], [], $this->locale);
            if (isset($value->systemReminds) && !empty($value->systemReminds)) {
                foreach ($value->systemReminds as $k => $v) {
                    $v['remind_name'] = mulit_trans_dynamic("system_reminds.remind_name." . $v['remind_menu'] . '_' . $v['remind_type'] . '_' . $v['id'], [], $this->locale);
                }
            $value['remind_name'] = $v['remind_name'];
                $value['remind_time'] = $v['remind_time'];
            }
            $result[] = $value;
        }
        $customRemind = [];
        $systemRemind = [];

        if ($list) {
            foreach ($list as $k => $v) {
                $tempRemind       = [];
                $tempRemind['id'] = ++$maxId;
                if (isset($v['menu_parent']) && ($v['menu_parent'] == 44 || $v['menu_parent'] == 264 || $v['menu_parent'] == 415 || $v['menu_parent'] == 160)) {
                    if (!in_array($v['menu_parent'], $permissionModule)) {
                        unset($list[$k]);
                        continue;
                    }
                }
                $tempRemind['menu_name'] = isset($this->custom[$v['menu_parent']]) ? $this->custom[$v['menu_parent']] : $v['title'];
                
                $tempRemind['remind_menu']    = $v['menu_parent'];
                $tempRemind['system_reminds'] = [];

                if (isset($v['category_sub']) && !empty($v['category_sub'])) {
                    foreach ($v['category_sub'] as $key => $value) {
                        if (isset($value->remind_set) && !empty($value->remind_set)) {

                            foreach ($value->remind_set as $keys => $val) {
                                $temp                = [];
                                $temp['menu_name']   = $value->menu_name;
                                $temp['remind_time'] = trans('system.system_set_reminds');
                                $temp['id']          = $maxId++;
                                if (isset($val->reminds_select)) {
                                    $temp['reminds_select'] = $val->reminds_select;
                                } else {
                                    $temp['reminds_select'] = '';
                                }
                                if (isset($val->field_name)) {
                                    $temp['remind_name'] = $val->field_name . trans('system.reminds');
                                    if ($v['menu_type'] == 'project') {
                                        $temp['remind_name'] = '[' . $value->menu_name . ']' . $val->field_name . trans('system.reminds');
                                    }
                                }
                                if (isset($val->field_id)) {
                                    // 更新数据表
                                    DB::table('custom_reminds')->where('id', $val->id)->where('field_code', $val->field_code)->update(['remind_id' => $temp['id']]);
                                }
                                $tempRemind['system_reminds'][] = $temp;
                            }
                        }

                    }
                    if (isset($temp['remind_name'])) {
                        $customRemind[] = $tempRemind;
                    }
                } else {
                    $tempRemind['remind_name']    = $v['title'] . trans('system.reminds');
                    $tempRemind['system_reminds'] = [];
                    $customRemind[]               = $tempRemind;
                }

            }
        }
        foreach($result as $key => $value) {
            foreach($customRemind as $k => $v) {
                if (isset($v['system_reminds']) && !empty($v['system_reminds'])) {
                    foreach($v['system_reminds'] as $keys => $val) {
                        $v['remind_name'] = isset($val['remind_name']) ? $val['remind_name'] : '';
                        if ($value['remind_menu'] == $v['menu_name']) {
                            $val['menu_name'] = trans('system.' . $v['menu_name']);
                            $result[$key] = is_object($result[$key]) ? $result[$key]->toArray() : $result[$key];
                            $result[$key]['system_reminds'][] = $val;
                            unset($customRemind[$k]);
                        }
                    }
                }else{
                    if ($value['remind_menu'] == $v['menu_name']) {
                        unset($customRemind[$k]);
                    }
                }
            }

        }
        if ($customRemind && isset($param['search']) && isset($param['search']['remind_name'])) {
            // 数组模糊匹配数据
            $list = array();        // 匹配后的结果
            
            $searchStr = $param['search']['remind_name'][0];        // 搜索的字符串
            foreach($customRemind as $key => $val ){
                if (strstr($val['menu_name'], $searchStr ) !== false ){
                  array_push($list, $val);
                }
            }
            $customRemind = $list;
        }
        
        $data = array_merge($result, $customRemind);
        return $data;
    }

    /**
     * 手机版获取模块消息提醒
     */
    public function getRemindsTypeMobile($userId)
    {
        $locale = Lang::getLocale();
        $param = [
            'fields' => ['id', 'remind_name', 'remind_menu'],
        ];
        // $this->checkReminds();

        //下载文件提醒 id = -1
        $result[] = [
            "id"             => -1,
            "remind_name"    => trans("system.download_reminds"),
            "remind_menu"    => "export",
            "menu_name"      => trans("system.download_center"),
            "system_reminds" => [],
        ];
        // if(Cache::has('system_reminds')) {
        //     $reminds = Cache::get('system_reminds');
        // } else {
        //     $reminds = app($this->systemRemindsRepository)->getRemindsList($param);
        // }
        $reminds = app($this->systemRemindsRepository)->getRemindsList($param);
        $list = app($this->formModelingService)->getCustomRemindModules();
        $permissionModule = app($this->empowerService)->getPermissionModules();
        $maxId = app($this->systemRemindsRepository)->getMaxId();
        $unread = app($this->systemSmsService)->getSystemSmsUnread($userId, $param=[]);
        $lastSms = app($this->systemSmsService)->getNewDetailByGroupBySmsType($userId);
        Cache::forever('system_reminds', $reminds);
        if ($lastSms) {
            foreach($lastSms as $k => $v) {
                if (isset($unread['module']) && !empty($unread['module'])) {
                    foreach($unread['module'] as $key => $value) {
                        if ($k == $key) {
                            $v->unread = $value;
                        }
                    }
                }

            }
        }
        $permissionIds = [98, 242];
        foreach ($reminds as $key => $value) {
            if (isset($value->menu_id) && !in_array($value->menu_id, $permissionModule) && !in_array($value->menu_id, $permissionIds)) {
                unset($reminds->$key);
                continue;
            }
            $menuName = mulit_trans_dynamic("menu.menu_name.menu_" . $value['menu_id']);
            $systemName = $this->getSystemLangs($value['remind_menu'], [], $this->locale);
            $value['menu_name'] = $menuName ? $menuName : $systemName;
            $value['remind_name'] = mulit_trans_dynamic("system_reminds.remind_name.".$value['remind_menu'] .'_'. $value['remind_type'] .'_'.$value['id']);
            if (isset($value->systemReminds) && !empty($value->systemReminds)) {
                foreach ($value->systemReminds as $k => $v) {
                    $v['remind_name'] = mulit_trans_dynamic("system_reminds.remind_name.".$v['remind_menu'] .'_'. $v['remind_type'] .'_'.$v['id']);
                }
                $value['remind_name'] = $v['remind_name'];
                $value['remind_time'] = $v['remind_time'];
            }
            $result[]           = $value;
        }
        $customRemind = [];
        $systemRemind = [];
        if ($list) {
            foreach($list as $k => $v) {
                $tempRemind = [];
                $tempRemind['id'] = ++$maxId;
                if (isset($v['menu_parent']) && ($v['menu_parent'] == 44 || $v['menu_parent'] == 264 || $v['menu_parent'] == 415 || $v['menu_parent'] == 160)) {
                    if (!in_array($v['menu_parent'], $permissionModule)) {
                        unset($list[$k]);
                        continue;
                    }
                }
                $tempRemind['menu_name'] = isset($this->custom[$v['menu_parent']]) ? $this->custom[$v['menu_parent']] : $v['title'];
                $tempRemind['remind_menu'] = $v['menu_parent'];
                $tempRemind['system_reminds'] = [];

                if (isset($v['category_sub']) && !empty($v['category_sub'])) {
                    foreach($v['category_sub'] as $key => $value) {
                        if (isset($value->remind_set) && !empty($value->remind_set)) {

                            foreach($value->remind_set as $keys => $val) {
                                $temp = [];
                                $temp['menu_name'] = $value->menu_name;
                                $temp['remind_time'] = trans('system.system_set_reminds');
                                $temp['id'] = $maxId ++;
                                if (isset($val->reminds_select)) {
                                    $temp['reminds_select'] = $val->reminds_select;
                                }else{
                                    $temp['reminds_select'] = '';
                                }
                                if (isset($val->field_name)) {
                                    $temp['remind_name'] = $val->field_name . trans('system.reminds');
                                    if ($v['menu_type'] == 'project') {
                                        $temp['remind_name'] = '[' . $value->menu_name.']'.$val->field_name . trans('system.reminds');
                                    }
                                }
                                if (isset($val->field_id)) {
                                     // 更新数据表
                                    DB::table('custom_reminds')->where('id', $val->id)->where('field_code', $val->field_code)->update(['remind_id' => $temp['id']]);
                                }
                                $tempRemind['system_reminds'][] = $temp;
                            }
                        }

                    }
                    if (isset($temp['remind_name'])) {
                        $customRemind[] = $tempRemind;
                    }
                }else{
                    $tempRemind['remind_name'] = $v['title'] . trans('system.reminds');
                    $tempRemind['system_reminds'] = [];
                    $customRemind[] = $tempRemind;
                }

            }
        }
        foreach($result as $key => $value) {
            foreach($lastSms as $keys => $val) {

                if ($keys == $value['remind_menu']) {
                    if ($value['remind_menu'] == 'export') {
                        $result[$key]['last_unread'] = $val;
                    } else {
                        $value['last_unread'] = $val;
                    }
                }
            }
        }
        foreach ($customRemind as $key => $value) {
            foreach ($lastSms as $keys => $val) {
                if ($keys == $value['remind_menu']) {
                    $customRemind[$key]['last_unread'] = $val;
                }
            }
        }

        foreach ($result as $key => $value) {
            foreach ($customRemind as $k => $v) {
                if (isset($v['system_reminds']) && !empty($v['system_reminds'])) {
                    foreach ($v['system_reminds'] as $keys => $val) {
                        $v['remind_name'] = isset($val['remind_name']) ? $val['remind_name'] : '';
                        if ($value['remind_menu'] == $v['menu_name']) {
                            $val['menu_name'] = trans('system.' . $v['menu_name']);
                            $result[$key] = is_object($result[$key]) ? $result[$key]->toArray() : $result[$key];
                            $result[$key]['system_reminds'][] = $val;
                            unset($customRemind[$k]);
                        }
                    }
                } else {
                    if ($value['remind_menu'] == $v['menu_name']) {
                        unset($customRemind[$k]);
                    }
                }
            }

        }

        $data = array_merge($result, $customRemind);
        return $data;
    }

    public function getSystemRemindsList($param)
    {
        $locale = Lang::getLocale();
        $this->checkReminds();
        if (isset($param['type']) && $param['type'] == "parent") {
            $reminds = app($this->systemRemindsRepository)->getRemindsParent($param);
            foreach ($reminds as $key => $value) {
                $value['menu_name']   = $this->getSystemLangs($value['remind_menu'], [], $locale);
                $value['remind_name'] = mulit_trans_dynamic("system_reminds.remind_name." . $value['remind_menu'] . '_' . $value['remind_type'] . '_' . $value['id']);
                $result[]             = $value;
            }
            return $result;
        } else {

            $reminds = app($this->systemRemindsRepository)->getRemindsChild($param);
            foreach ($reminds as $key => $value) {
                $value['remind_name'] = mulit_trans_dynamic("system_reminds.remind_name." . $value['remind_menu'] . '_' . $value['remind_type'] . '_' . $value['id']);
            }
            return $reminds;

        }
    }

    /**
     * [getRemindsInfo 获取提醒设置数据]
     *
     * @method 朱从玺
     *
     * @param  [int]          $remindsId [提醒设置ID]
     *
     * @return [object]                    [查询结果]
     */
    public function getRemindsInfo($remindsId, $locale = 'zh-CN')
    {
        //获取所有的提醒方式
        $reminds = app($this->remindsRepository)->getAllReminds();

        $remindsInfo = app($this->systemRemindsRepository)->getDetail($remindsId);
        if (!empty($remindsInfo)) {
            $remindsInfo                   = $remindsInfo->toArray();
            $remindsInfo['remind_time']    = mulit_trans_dynamic("system_reminds.remind_time." . $remindsInfo['remind_menu'] . '_' . 'time' . '_' . $remindsInfo['id'], [], $locale);
            $remindsInfo['remind_name']    = mulit_trans_dynamic("system_reminds.remind_name." . $remindsInfo['remind_menu'] . '_' . $remindsInfo['remind_type'] . '_' . $remindsInfo['id'], [], $locale);
            $remindsInfo['receive_range']  = mulit_trans_dynamic("system_reminds.receive_range." . $remindsInfo['remind_menu'] . '_' . 'range' . '_' . $remindsInfo['id'], [], $locale);
            $remindsInfo['remind_content'] = mulit_trans_dynamic("system_reminds.remind_content." . $remindsInfo['remind_menu'] . '_' . 'content' . '_' . $remindsInfo['id'], [], $locale);
            $patten                        = "/\{(\S+?)\}/";

            preg_match_all($patten, $remindsInfo['remind_content'], $variableName);

            foreach ($variableName[1] as $key => $value) {

                $remindsInfo['remind_content'] = str_replace($value, $this->getSystemLangs($value, [], $locale), $remindsInfo['remind_content']);
            }
        } else {
            $data = DB::table('custom_reminds')
                ->where('custom_reminds.remind_id', $remindsId)->first();
            if ($data) {
                $fieldValue    = isset($data->field_table_key) ? $data->field_table_key : '';
                $comboboxValue = substr($fieldValue, strripos($fieldValue, "_") + 1);
                $field         = substr($fieldValue, 0, 7);

                $remindsInfo['remind_content'] = rtrim(app($this->formModelingService)->restoreContent($data->field_table_key, $data->content), '#');
                $remindsInfo['remind_name']    = mulit_trans_dynamic("custom_fields_table.field_name." . $data->field_table_key . "_" . $data->field_code, [], $locale);
                $remindsInfo['remind_name']    = $remindsInfo['remind_name'] . trans('system.reminds');
                if ($field == 'project') {
                    $combobox   = DB::table('system_combobox')->where('combobox_identify', '=', 'PROJECT_TYPE')->get();
                    $comboboxId = '';
                    if ($combobox) {
                        $comboboxId = $combobox[0]->combobox_id;
                    }
                    $projectType = DB::table('system_combobox_field')->where('combobox_id', $comboboxId)->where('field_value', $comboboxValue)->get();
                    $fieldName   = '';
                    $identify    = '';
                    if ($projectType) {
                        $fieldName = $projectType[0]->field_name;
                    }
                    $remindsInfo['remind_name'] = '[' . mulit_trans_dynamic('system_combobox_field_project_type.field_name.' . $fieldName, [], $locale) . ']' . $remindsInfo['remind_name'];
                }
                $remindsInfo['reminds_select'] = $data->reminds_select ?? '';
                $remindsInfo['remind_time']    = trans('system.system_set_reminds', [], $locale);
                $remindsInfo['receive_range']  = $this->parseCustomRemindRange($data->target);
            }
        }
        return $this->arrangeReminds($reminds, $remindsInfo);
    }

    /**
     * [getRemindByMark 根据提醒标记查询消息提醒数据]
     *
     * @method 朱从玺
     *
     * @param  [string]          $remindMark [提醒标记,system_reminds表remind_menu和remind_type字段以-相连]
     *
     * @return [array]                       [查询结果]
     */
    public function getRemindByMark($remindMark)
    {
        //获取所有的提醒方式
        $reminds = app($this->remindsRepository)->getAllReminds();

        //分解remindMark,拼装查询条件
        $remindMark = explode('-', $remindMark);

        if (count($remindMark) < 2) {
            return array('code' => array('0x015004', 'system'));
        }

        $where = [
            'remind_menu' => [$remindMark[0]],
            'remind_type' => [$remindMark[1]],
        ];

        //获取消息提醒数据
        $remindsInfo = app($this->systemRemindsRepository)->getRemindDetail($where);

        if (!$remindsInfo) {
            return '';
        }
        $remindsInfo = $remindsInfo->toArray();

        return $this->arrangeReminds($reminds, $remindsInfo);
    }

    /**
     * [modifyReminds 编辑提醒设置]
     *
     * @method 朱从玺
     *
     * @param  [int]          $remindsId  [设置ID]
     * @param  [array]        $modifyData [编辑数据]
     *
     * @return [bool]                     [编辑结果]
     */
    public function modifyReminds($remindsId, $modifyData)
    {
        $where = ['id' => $remindsId];

        // 查询当前传入的ID是否在系统提醒表里面
        $remindArr    = app($this->systemRemindsRepository)->getRemindId();
        if (isset($modifyData['reminds_select'])) {
            $modifyData['reminds_select'] = rtrim(implode(',', $modifyData['reminds_select']), ',');
        }
        $remindSelect = isset($modifyData['reminds_select']) ? $modifyData['reminds_select'] : [];
        if (in_array($remindsId, $remindArr)) {
            $result = app($this->systemRemindsRepository)->updateDataBatch($modifyData, $where);
            $remindInfo = app($this->systemRemindsRepository)->getDetail($remindsId)->toArray();
            if ($remindInfo && ($remindInfo['remind_menu'] == 'birthday' && $remindInfo['remind_type'] == 'start')) {
                app($this->birthdayRepository)->updateSelectBrithdayRemind($remindInfo['reminds_select']);
            }
            return $result;
        } else {
            // 查询改自定义字段的field_option, 更新method
            $data = DB::table('custom_reminds')
                ->leftJoin('custom_fields_table', 'custom_fields_table.field_table_key', '=', 'custom_reminds.field_table_key')
                ->where('custom_reminds.remind_id', $remindsId)->first();
            if ($data) {
                $fieldId                  = $data->field_id;
                $extra                    = json_decode($data->field_options, true);
                $extra['timer']['method'] = explode(',', $remindSelect);
                $extraData                = json_encode($extra);
                DB::table('custom_fields_table')->where('field_id', $fieldId)->update(['field_options' => $extraData]);
            }
            return DB::table('custom_reminds')->where('remind_id', $remindsId)->update(['reminds_select' => $remindSelect]);
        }

    }
    /**
     * [modifyReminds 编辑生日提醒设置]
     *
     * @method 朱从玺
     *
     * @param  [int]          $remindsId  [设置ID]
     * @param  [array]        $modifyData [编辑数据]
     *
     * @return [bool]                     [编辑结果]
     */
    public function modifyBirthdayReminds($menu, $type, $modifyData)
    {
        if (!$menu || !$type) {
            return array('code' => array('0x000003', 'system'));
        }
        return app($this->systemRemindsRepository)->updateByMenuType($menu, $type, $modifyData);

    }

    public function checkReminds()
    {
        $remind_name = trans("system.visit_reminds");
        $db_res      = app($this->systemRemindsRepository)->entity->where('remind_name', $remind_name)->get()->toArray();
        if (!empty($db_res)) {
            $id   = $db_res[0]['id'];
            $save = ['remind_time' => trans("system.reminds_time"), 'receive_range' => trans("system.reminds_user")];
            app($this->systemRemindsRepository)->entity->where('id', $id)->update($save);
        }
    }

    public function postSystemReminds($saveData)
    {
        if (isset($saveData['reminds']) && $saveData['reminds'] == "all") {
            $saveData['reminds'] = app($this->systemRemindsRepository)->getAllReminds();
            $saveData['reminds'] = array_column($saveData['reminds'], "id");
        }
        if (isset($saveData['reminds']) && !is_array($saveData['reminds'])) {
            $saveData['reminds'] = json_decode($saveData['reminds']);
        }
        if (isset($saveData['reminds']) && !empty($saveData['reminds'])) {
            foreach ($saveData['reminds'] as $k => $v) {
                $where  = ['id' => $v];
                $data   = ['reminds_select' => isset($saveData['reminds_select']) ? $saveData['reminds_select'] : ''];
                $update = app($this->systemRemindsRepository)->updateData($data, $where);

            }
        }
        return "true";

    }

    // 解析自定义提醒接受人的范围
    public function parseCustomRemindRange($receiveRange)
    {
        if (!$receiveRange) {
            return '';
        }
        switch ($receiveRange) {
            case 'all':
                return '全体人员';
                break;
            case 'currentMenu':
                return '拥有此菜单权限的人';
                break;
            case 'definite':
                return '指定对象';
                break;
            case 'relation':
                return '来源字段值';
                break;
        }
    }

    public function checkRemindIsOpen(string $type)
    {
        if (($result = Redis::hget(self::REMIND_SWITCH_MARK, $type)) === null) {
            $result = false;
            $lists = $this->getRemindByMark($type);
            if (!empty($lists)) {
                foreach ($lists as $item) {
                    if (is_array($item) && isset($item['selected']) && $item['selected'] == 1) {
                        $result = true;
                        break;
                    }
                }
            }
            Redis::hset(self::REMIND_SWITCH_MARK, $type, $result);
            return $this->checkRemindIsOpen($type);
        }
        return $result;
    }
    // 搜索用户和群组列表返回
    public function getUserChatGroup($param, $userId) {
        if (!$param || !isset($param['communicate_type'])) {
            return [];
        }
        $username = [];
        if (isset($param['communicate_type']) && $param['communicate_type'] == 'sms') {
            if (!empty($param['user_group_name'])) {
                $username =app($this->userService)->userSystemList($param, $userId);
            }else{
                $username = [];
            }
        }
        $data = [];
        $group = [];
        if (isset($param['user_group_name']) && !empty($param['user_group_name'])) {
            $data = DB::table('chat_group')->where(function($query) use($param, $userId){
                $query->whereRaw('find_in_set(?, members)', [$userId])->where('name', 'like', "%" . $param['user_group_name'] . "%");
            })
            ->get()->toArray();
            if ($data) {
                foreach($data as $key => $value) {
                    $group[$key]['name'] = $value->name;
                    $group[$key]['creator'] = $value->creator;
                    $group[$key]['members'] = $value->members;
                    $group[$key]['id'] = $value->id;
                }
            }
        }
        $dataList = [
            'user'  => isset($username['list']) ? ($username['list']) : [],
            'group' => $group
        ];

        return $dataList;
    }
    // 公共发送消息
    public function sendNotifyMessage($param)
    {
        $param = $this->parseParams($param);
        if (!$param) {
            return true;
        }
        $sendData = [
            'remindMark'   => $param['remindMark'] ?? '',
            'toUser'       => $param['toUser'] ?? '',
            'contentParam' => $param['contentParam'] ?? '',
            'stateParams'  => $param['stateParams'] ?? '',
            'content'      => $param['content'] ?? ''
        ];
        Eoffice::sendMessage($sendData);
    }
}
