<?php
namespace App\EofficeApp\PersonalSet\Permissions;
use Illuminate\Support\Arr;
class PersonalSetPermission
{
    private $userGroupRepository;
    private $toDoListRepository;
    private $userInfoRepository;
    public $rules = [
        'resetUserGroupName' => 'userGroupModify',
        'deleteUserGroup' => 'userGroupModify',
        'selectUsersForUserGroup' => 'userGroupModify',
        'showUserGroup' => 'userGroupModify',
        'dragToDoItem' => 'toDoListModify',
        'toDoItemSort' => 'toDoListModify',
        'changeInstancyType' => 'toDoListModify',
        'editUserInfo' => 'editUserInfoPermission',
        'editCommonPhrase' => 'commonPhrasePermission',
        'deleteCommonPhrase' => 'commonPhrasePermission',
        'setSignaturePicture' => 'setSignaturePicturePermisson'
    ];

    public function __construct()
    {
        $this->userGroupRepository = 'App\EofficeApp\PersonalSet\Repositories\UserGroupRepository';
        $this->toDoListRepository = 'App\EofficeApp\PersonalSet\Repositories\ToDoListRepository';
        $this->userInfoRepository = 'App\EofficeApp\User\Repositories\UserInfoRepository';
        $this->commonPhraseRepository = 'App\EofficeApp\PersonalSet\Repositories\CommonPhraseRepository';
    }

    //个人用户组修改
    public function userGroupModify($own, $data, $urlData)
    {
        $groupId = Arr::get($urlData, 'groupId');
        return $this->userGroupPermission($groupId, $own['user_id']);
    }

    //toDoList修改
    public function toDoListModify($own, $data, $urlData)
    {
        $itemId = Arr::get($urlData, 'itemId');
        return $this->toDoListPermission($itemId, $own['user_id']);
    }

    //验证是否是用户自身的用户组
    private function userGroupPermission($groupId, $userId)
    {
        return app($this->userGroupRepository)->entity
            ->where('creator', $userId)
            ->where('group_id', $groupId)
            ->exists();
    }

    //验证是否是用户自身的todolist数据
    private function toDoListPermission($itemId, $userId)
    {
        return app($this->toDoListRepository)->entity
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->exists();
    }
    // 修改个人信息
    public function editUserInfoPermission($own, $data, $urlData) {
        return $this->userInfoPermission($own['user_id']);
    }

    private function  userInfoPermission($userId) {
        return app($this->userInfoRepository)->entity
            ->where('user_id', $userId)
            ->exists();
    }
    // 常用短语删除, 编辑权限
    public function commonPhrasePermission($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($urlData['phraseId']) && empty($urlData['phraseId'])) {
            return ['code' => ['0x039015', 'personalset']];
        }
        $detail = app($this->commonPhraseRepository)->getDetail($urlData['phraseId']);
        $userId = $detail->user_id;
        if ($currentUserId != $userId) {
            return false;
        }
        return true;
    }

    public function setSignaturePicturePermisson($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (isset($data['signature_picture']) && !empty($data['signature_picture'])) {
            $userSignaturePicture = app($this->userInfoRepository)->getUserSignaturPicture($currentUserId);
            $userSignaturePictureId = $userSignaturePicture[0] ?? '';
            // if (!isset($userSignaturePictureId['signature_picture']) && empty($userSignaturePictureId['signature_picture'])) {
            //     // if ($data['signature_picture'] != $userSignaturePictureId) {
            //         return false;
            //     // }
            // }
            return true;
        }else{
            return app($this->userInfoRepository)->entity
            ->where('user_id', $currentUserId)
            ->exists();
        }
    }

}
