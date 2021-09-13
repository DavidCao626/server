<?php

namespace App\EofficeApp\Sms\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Sms\Repositories\SmsReceiveRepository;
use App\EofficeApp\Sms\Repositories\SmsRepository;
use App\EofficeApp\User\Repositories\UserRepository;
use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\PublicGroup\Services\PublicGroupService;
use App\EofficeApp\PersonalSet\Repositories\UserGroupRepository;

/**
 * 内部消息服务
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 */
class SmsService extends BaseService {

    /** @var object 内部消息资源库变量 */
    private $smsRepository;
    private $smsReceiveRepository;
    private $userRepository;
    private $publicGroupService;
    private $userGroupRepository;

    public function __construct(
    SmsRepository $smsRepository, SmsReceiveRepository $smsReceiveRepository, UserRepository $userRepository, PublicGroupService $publicGroupService, UserGroupRepository $userGroupRepository
    ) {
        $this->smsRepository = $smsRepository;
        $this->smsReceiveRepository = $smsReceiveRepository;
        $this->userRepository = $userRepository;
        $this->publicGroupService = $publicGroupService;
        $this->userGroupRepository = $userGroupRepository;
    }

    /**
     * 增加消息 根据sms_type进行区分
     */
    public function addSms($data) {
        $smsData = array_intersect_key($data, array_flip($this->smsRepository->getTableColumns()));
        $result = $this->smsRepository->insertData($smsData);
        $sms_id = $result->sms_id;

        $toSms = [
            'sms_id' => $sms_id,
            'recipients' => $data['recipients'],
        ];

        $this->smsReceiveRepository->insertData($toSms);

        return $result;
    }

    /**
     * 获取消息 [发送者是当前登录的yongh]
     *
     * 个人对个人的
     * 个人对组的：个人组 用户组（讨论组） 公共组
     *
     */
    public function getTalkList($user1, $user2, $type, $data) {

        if (!($user1 && $user2 && $type)) {
            return [];
        }

        $data["user1"] = $user1;
        $data["user2"] = $user2;
        $data["type"] = $type;



        return $this->response($this->smsRepository, "getTalkListTotal", "getTalkList", $this->parseParams($data));
    }

    /**
     * 消息未读数量
     */
    public function getUnreadCountByReceive($user_id) {
        $userInfo = $this->userRepository->getUserAllData($user_id);



        $own["dept_id"] = $userInfo->userHasOneSystemInfo->dept_id;
        $own["role_id"] = $userInfo->userHasManyRole[0]->role_id;
        $own["user_id"] = $user_id;

        $groupsData = $this->publicGroupService->getGroups($own);

        $temp = [];
        foreach ($groupsData as $group) {
            $temp[] = $group["group_id"];
        }


        $userGroup = $this->userGroupRepository->getUserGrop($own);

        $userGroupTemp = [];
        foreach ($userGroup as $ugroup) {
            $userGroupTemp[] = $ugroup["group_id"];
        }

        $data["recipients"] = $own["user_id"];
        //获取包含我的公共组
        $data["group"] = $temp;
        $data["individuals"] = $userGroupTemp;



        $arr1 = $this->smsRepository->getUnreadCountPerson($data);

        $arr2 = $this->smsRepository->getUnreadCountGroup($data);

        return array_merge($arr1, $arr2);
    }

    /**
     * 返回用户的消息组
     */
    public function getSmsGroup($user_id){
         $userInfo = $this->userRepository->getUserAllData($user_id);

        $own["dept_id"] = $userInfo->userHasOneSystemInfo->dept_id;
        $own["role_id"] = $userInfo->userHasManyRole[0]->role_id;
        $own["user_id"] = $user_id;

        $groupsData = $this->publicGroupService->getGroups($own);
        $userGroup = $this->userGroupRepository->getUserGrop($own);

        return [
            "user_group" => $userGroup,
            "public_group"=>$groupsData
        ];
    }

    /**
     * 删除消息
     */
    public function deleteSms($id) {

        //通过ID 加上匹配用户
        //发件人 删除sms
        //收件人 删除组

        $data['id'] = $id;
        $smsIds = explode(",", $data['id']);
        if ($data['type'] == 'send') { //发消息用户删除
            $where = [
                'sms_id' => [$smsIds, 'in'],
                'from_id' => [[$data['user_id']], "="]
            ];
            $result = $this->smsRepository->deleteSms($where);
        } else { //收信息人
            $where = [
                'id' => [$smsIds, 'in'],
                'recipients' => [[$data['user_id']], "="]
            ];
            $result = $this->smsReceiveRepository->deleteSms($where);
        }
        return $result;
    }

    /**
     * 查看消息
     */
    public function readSms($id) {
        return $this->smsReceiveRepository->readSms($id);
    }

}
