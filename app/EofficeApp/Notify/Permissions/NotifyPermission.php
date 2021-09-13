<?php

namespace App\EofficeApp\Notify\Permissions;

use Illuminate\Support\Collection;

class NotifyPermission
{
    const NO_PERMISSION = ['code' => ['0x025017', 'notify']];
    const NOTIFY_NOT_EXIST = ['code' => ['0x025020', 'notify']];
    const COMMENT_NOT_EXIST = ['code' => ['0x025023', 'notify']];
    const EMPTY_NOTIFY_ID = ['code' => ['0x025005', 'notify']];
    const EMPTY_COMMENT_ID = ['code' => ['0x025029', 'notify']];
    const ALREADY_PUBLISH_CANNOT_EDIT = ['code' => ['0x025014', 'notify']];
    const NOT_AUDIT_NOTIFY = ['code' => ['0x025012', 'notify']];

    private $notifyRepository;
    private $notifyCommentRepository;
    public $rules = [
        'modifyType' => 'modify',
        'top'        => 'modify',
        'cancelTop'  => 'modify',
        'commentList'  => 'comment',
        'addComment'  => 'comment',
        'getCommentDetail'  => 'comment',
        'getChildrenComments'  => 'comment',
        'deleteComment'  => 'comment',
        'editComment'  => 'comment',
    ];

    public function __construct()
    {
        $this->notifyRepository = 'App\EofficeApp\Notify\Repositories\NotifyRepository';
        $this->notifyCommentRepository = 'App\EofficeApp\Notify\Repositories\NotifyCommentRepository';
    }

    private function isAdmin($own)
    {
        return $own['user_id'] === 'admin';
    }

    // 是否有审核菜单权限
    private function hasCheckPermission($own)
    {
        return in_array(234, $own['menus']['menu']);
//        return app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(321) === 'true';
    }

    private function isPublisher($own, $notify)
    {
        return $own['user_id'] == $notify->from_id;
    }

    // 新建公告，直接发布需要审核权限
    public function addNotify($own, $data)
    {
        if (!$this->hasCheckPermission($own) && $data['publish'] == 1) {
            return self::NOTIFY_NOT_EXIST;
        }
        return true;
    }

    // 编辑公告：已发布不能编辑，操作者为管理员，或者发布者直接发布需要审核权限
    public function editNotify($own, $data, $urlData)
    {
        if ($data['publish'] == 1 && !$this->hasCheckPermission($own)) {
            return self::NO_PERMISSION;
        }
        return true;
    }

    //变更类别、置顶、取消置顶：发布人或者发布状态的管理员
    public function modify($own, $data, $urlData)
    {
        $notifyId = $urlData['notifyId'];
        if (!$notifyId) {
            return self::EMPTY_NOTIFY_ID;
        }
        $notifyId = explode(',', $notifyId);

        /** @var Collection $notifyList */
        $notifyList = app($this->notifyRepository)->entity->select('notify_id', 'from_id', 'publish')->find($notifyId);
        if ($notifyList->isEmpty()) {
            return self::NOTIFY_NOT_EXIST;
        }
        //判断是否都是发布状态且是管理员
        $switch = true;
        foreach($notifyList as $notify){
            if ($notify->publish == 1) {
                if(!$this->isPublisher($own, $notify) && !$this->isAdmin($own)){
                    $switch = false;
                    break;
                }
            } else {
                if(!$this->isPublisher($own, $notify)){
                    $switch = false;
                    break;
                }
            }
        }

        return $switch;
    }

    // 评论相关，发布状态且可评论，用户管理员或者必须要有阅读公告权限
    public function comment($own, $data, $urlData)
    {
        if(isset($urlData['notifyId'])){
            $notifyId = $urlData['notifyId'];
            if (!$notifyId) {
                return self::EMPTY_NOTIFY_ID;
            }
        }else{
            $comment_id = $urlData['commentId'];
            if(!$comment_id){
                return self::EMPTY_COMMENT_ID;
            }
            $comment = app($this->notifyCommentRepository)->entity->find($comment_id);
            if(!$comment){
                return self::COMMENT_NOT_EXIST;
            }
            $notifyId = $comment->notify_id;
        }
        $notify = app($this->notifyRepository)->entity
            ->select('notify_id', 'from_id', 'priv_scope', 'dept_id', 'role_id', 'user_id', 'publish', 'allow_reply')
            ->find($notifyId);
        if(!$notify){
            return self::NOTIFY_NOT_EXIST;
        }
        $permission = true;
        if($notify->publish !== 1 || !$notify->allow_reply){
            $permission = false;
        }
        if(!$notify->priv_scope && !$this->isAdmin($own) && $notify->from_id != $own['user_id']){
            $userId = $own['user_id'] ?? '';
            $deptId = $own['dept_id'] ?? '';
            $roleId = $own['role_id'] ?? [];
            if(!in_array($userId, explode(',', $notify->user_id))&&
                !in_array($deptId, explode(',', $notify->dept_id))&&
                !array_intersect($roleId, explode(',', $notify->role_id))
            ){
                $permission = false;
            }
        }
        if(!$permission){
            return self::NO_PERMISSION;
        }
        return true;
    }


}
