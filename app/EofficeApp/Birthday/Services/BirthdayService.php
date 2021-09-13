<?php

namespace App\EofficeApp\Birthday\Services;

use App\EofficeApp\Base\BaseService;
use Eoffice;
use Cache;

/**
 * 生日贺卡服务
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 */
class BirthdayService extends BaseService {

    /** @var object 生日贺卡资源库变量 */
    private $birthdayRepository;
    private $userInfoRepository;
    private $emailService;

    public function __construct() {
        $this->birthdayRepository   = 'App\EofficeApp\Birthday\Repositories\BirthdayRepository';
        $this->userInfoRepository   = 'App\EofficeApp\User\Repositories\UserInfoRepository';
        $this->emailService         = 'App\EofficeApp\Email\Services\EmailService';
        $this->systemRemindService  = 'App\EofficeApp\System\Remind\Services\SystemRemindService';
        $this->userService          = 'App\EofficeApp\User\Services\UserService';
        $this->userRepository       = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->birthdaySetRepository       = 'App\EofficeApp\Birthday\Repositories\BirthdaySetRepository';
         $this->remindService       = 'App\EofficeApp\System\Remind\Services\SystemRemindService';
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
    public function getBirthdayList($data) {
        return $this->response(app($this->birthdayRepository), 'getTotal', 'getBirthdayList', $this->parseParams($data));
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
    public function addBirthday($data) {
        $data['birthday_creator'] = $data['user_id'];
        $data['birthday_create_date'] = date("Y-m-d H:i:s",time());
        $data['selected'] = 0 ;
        $birthdayData = array_intersect_key($data, array_flip(app($this->birthdayRepository)->getTableColumns()));
        $result = app($this->birthdayRepository)->insertData($birthdayData);
        return $result->birthday_id;
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
    public function editBirthday($data) {

        $birthdayInfo = app($this->birthdayRepository)->infoBirthday($data['birthday_id']);
        if (count($birthdayInfo) == 0) {
            return ['code' => ['0x029002', 'birthday']];
        }
        $data['selected'] = $birthdayInfo[0]['selected'];
        if ($data['selected'] == 1) {
            $remindType = isset($data['sms_select']) ? $data['sms_select'] : '';
            $menu = "birthday";
            $type = 'start';
            app($this->systemRemindService)->modifyBirthdayReminds($menu, $type, $remindType);
        }
        $birthdayData = array_intersect_key($data, array_flip(app($this->birthdayRepository)->getTableColumns()));
        return app($this->birthdayRepository)->updateData($birthdayData, ['birthday_id' => $data['birthday_id']]);
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
    public function deleteBirthday($data) {
        $destroyIds = explode(",", $data['birthday_id']);
        $where = [
            'birthday_id' => [$destroyIds, 'in']
        ];
        $menu = "birthday";
        $type = 'start';
        foreach($destroyIds as $key => $value) {
            $list = app($this->birthdayRepository)->infoBirthday($value);
            $birthdayInfo = isset($list[0]) ? $list[0] : '';
            if (isset($birthdayInfo['sms_select']) && $birthdayInfo['sms_select'] == 1) {
                $smsSelect = '';
                app($this->systemRemindService)->modifyBirthdayReminds($menu, $type, $smsSelect);
            }
        }
        $result = app($this->birthdayRepository)->deleteByWhere($where);

        return $result;
    }

    /**
     * 获取当前生日贺卡的明细
     *
     * @param array $data
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function getOneBrithday($data) {
        $return = app($this->birthdayRepository)->infoBirthday($data['birthday_id']);
        $result = $return[0] ?? '';
        $result['sms_select'] = explode(',', $result['sms_select']);
        $result['sms_selected'] = [];
        foreach ($result['sms_select'] as $key => $value) {
            $result['sms_selected'][] = (int)$value;
        }
        unset($result['sms_select']);
        return $result;
    }

    /**
     * 应用生日贺卡
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function selectBrithday($data) {

        $result = app($this->birthdayRepository)->selectBrithday($data['birthday_id']);
        $menu = "birthday";
        $type = 'start';
        if ($result) {
            $info = $this->getOneBrithday($data);
            $smsSelect = isset($info['sms_selected']) && is_array($info['sms_selected']) ? implode(',', $info['sms_selected']) : '';
            app($this->systemRemindService)->modifyBirthdayReminds($menu, $type, $smsSelect);
        }
        return $result;
    }
     /**
     * 取消应用生日贺卡
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function cancelSelectBrithday($data) {

        $result = app($this->birthdayRepository)->cancelSelectBrithday($data['birthday_id']);
        $menu = "birthday";
        $type = 'start';
        if ($result) {
            $info = $this->getOneBrithday($data);
            $smsSelect = '';
            app($this->systemRemindService)->modifyBirthdayReminds($menu, $type, $smsSelect);
        }
        return $result;
    }
     /**
     * 获取当天生日用户发送贺卡
     *
     */
    public function sendBrithday() {
        $param = [];
        $result = app($this->birthdaySetRepository)->getBirthdaySetting();
        if ($result && isset($result->paramValue) && ($result->paramValue == 0 || $result->paramValue == 2)) {

            $param ['search'] = ['selected'=>[1],'sms_remind'=>[1]];
            $param ['fields'] = ['birthday_content','birthday_underwrite'];
            $brithday = app($this->birthdayRepository)->getBirthdayList($param);
            $sms_title = trans('birthday.happy_birthday');
            $sms_content = trans('birthday.happy_birthday');
            if($brithday && isset($brithday[0])) {
                $sms_content = $brithday[0]['birthday_content'];
                $sendUser      =    $brithday[0]['birthday_underwrite'];
                $sms_title = $brithday[0]['birthday_title'];
                $sms_content = $brithday[0]['birthday_content'];
            }else{
                return '';
            }
            $currentDate = date('m-d');
            $userId = app($this->userInfoRepository)->getThisDateBrithday($currentDate);
            $user_id = [];
            if(!empty($userId)) {
                foreach ($userId as $key => $value) {
                    $user_id[$key] = $value['user_id'];
                    $userName = app($this->userService)->getUserName($value['user_id']);
                    $sendData['toUser']       = $value['user_id'];
                    $sendData['contentParam'] = ['userName' => $userName];
                    $sendData['remindMark']   = 'birthday-start';
                    Eoffice::sendMessage($sendData);
                }
                $remindsInfo = app($this->remindService)->getRemindByMark('birthday-start');

                if (isset($remindsInfo['email']) && isset($remindsInfo['email']['selected']) && $remindsInfo['email']['selected']) {
                    if($user_id) {
                        $sendData = [
                            'user_id'   => $sendUser,
                            'to_id'     => $user_id,
                            'subject'   => $sms_title,
                            'content'   => $sms_content,
                            'send_flag'     => 1
                        ];
                        app($this->emailService)->systemSendEmail($sendData);
                    }
                }
                
            }
        }
        return true;
    }

    public function getBirthdayUser() {
        $currentDate = date('m-d');
        $userId = app($this->userRepository)->getThisDateBrithday($currentDate);

        $returnUser = [];
        $date = date('Y-m-d');
        if (!empty($userId)) {
            foreach ($userId as $key => $value) {
                $returnUser[$key]['user_id']   = $value['user_id'];
                $returnUser[$key]['user_name'] = $value['user_name'];
                $returnUser[$key]['dept_name']   = isset($value['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name']) ? $value['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'] : '';
            }
        }
        return $returnUser;
    }

    public function birthdaySet($data) {
        $result = app($this->birthdaySetRepository)->getBirthdaySetting();
        if ($result) {
            if(!app($this->birthdaySetRepository)->updateData(['paramValue' => $data['remind_type']], ['paramKey' => 'remind_type'])) {
                return false;
            }
        } else {
            $return = app($this->birthdaySetRepository)->insertData(['paramKey' => 'remind_type', 'paramValue' => $data['remind_type']]);
            if (!$return) {
                return false;
            }
        }
        return true;
    }

    public function birthdaySetGet() {
        $result = app($this->birthdaySetRepository)->getBirthdaySetting();
        return $result;
    }
}
