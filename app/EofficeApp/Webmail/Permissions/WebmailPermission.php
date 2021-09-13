<?php
namespace App\EofficeApp\Webmail\Permissions;
use DB;
use App\EofficeApp\Webmail\Repositories\WebmailOutboxRepository;
class WebmailPermission
{
    // 验证引擎会优先调用类里拥有的方法，如果没有则从该数组匹配找到对应的方法调用。
    public $rules = [
        'getAccountInfo' => 'commonValidate',
        'getOutbox'      => 'commonValidate',           //验证获取发件箱详情权限
        'deleteMail'     => 'commonDeleteMailValidate',
    ];
    public function __construct() 
    {
        $this->webmailOutboxRepository = 'App\EofficeApp\Webmail\Repositories\WebmailOutboxRepository';
        $this->webmailMailRepository   = 'App\EofficeApp\Webmail\Repositories\WebmailMailRepository';
        $this->webmailFolderRepository   = 'App\EofficeApp\Webmail\Repositories\WebmailFolderRepository';
        $this->webmailShareConfigRepository   = 'App\EofficeApp\Webmail\Repositories\WebmailShareConfigRepository';
    }


    /**
     * 验证邮箱账号删除是否属于自己邮件权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function deleteOutbox($own, $data, $urlData){
        $outboxId = array_filter(explode(',', $urlData['outboxId']));
        $result = DB::table('webmail_outbox')->select('outbox_creator')->whereIn('outbox_id',$outboxId)->get()->toArray();
        if($result && is_array($result)){
            foreach ($result as $value){
                if($own['user_id'] != $value->outbox_creator){
                    return ['code' => ['no_privileges', 'email']];
                }
            }

        }
        return true;
    }


    /**
     * 验证邮件删除权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function commonDeleteMailValidate($own, $data, $urlData){
        if(isset($data['search'])){
            $data['search'] = json_decode($data['search'],1);
            $folderData = app($this->webmailFolderRepository)->getDetail($data['search']['folder'][0])->toArray();
            if(!$folderData) return false;
            if($folderData['folder_creator'] == $own['user_id']){
                return true;
            }
        }
        $mailIds = array_filter(explode(',', $urlData['mailId']));
        $result = app($this->webmailMailRepository)->getDetail($mailIds)->toArray();
        if(!$result) return false;
        $outbox_ids = [];
        foreach ($result as $key => $vo){
            $outbox_ids[] = $vo['outbox_id'];
        }
        $webmailOutboxObj = app($this->webmailOutboxRepository)->getDetail($outbox_ids)->toArray();
        if(!$webmailOutboxObj) return false;
        foreach ($webmailOutboxObj as $ky => $value){
            if(($value['outbox_creator'] != $own['user_id']) && $value['is_public'] != 1){
                return false;
            }
        }
        return true;
    }

    /**
     * 验证删除文件夹权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function deleteFolder($own, $data, $urlData){
        $result = app($this->webmailFolderRepository)->getDetail($urlData['folderId']);
        if($result && ($result->folder_creator != $own['user_id'])){
            return ['code' => ['no_privileges', 'email']];
        }
        return true;
    }


    /**
     * 验证编辑我的邮箱权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function updateOutbox($own, $data, $urlData){
        $outbox = app($this->webmailOutboxRepository)->getDetail($urlData['outboxId'])->toArray();
        if(isset($data['is_default']) && isset($data['outbox_creator'])){
            if($outbox && ($outbox['outbox_creator'] == $data['outbox_creator'])){
                return true;
            }
        }
        if($outbox['outbox_creator'] == $own['user_id']){
            return true;
        }
        return false;
    }

    /**
     * 验证编辑我的文件夹权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function updateFolder($own, $data, $urlData){
        $result = app($this->webmailFolderRepository)->getDetail($urlData['folderId']);
        if($result && ($result['folder_creator'] == $own['user_id'])){
            return true;
        }
        return false;
    }

    /**
     * 验证新建邮件权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function createMail($own, $data, $urlData){
        $outbox = app($this->webmailOutboxRepository)->getDetail($data['outbox_id'])->toArray();
        if($outbox && $outbox['outbox_creator'] == $own['user_id'] || $outbox['is_public'] == 1){
            return true;
        }
        return false;
    }

    /**
     * 验证查看邮件详情权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function getMail($own, $data, $urlData){
        $result = app($this->webmailMailRepository)->getDetail($urlData['mailId']);
        if(!$result) return false;
        $webmailOutboxObj = app($this->webmailOutboxRepository)->getDetail($result['outbox_id']);
        if(!$webmailOutboxObj) return false;
        if(($result['mail_creator'] == $own['user_id']) || ($webmailOutboxObj['is_public'] == 1) || $webmailOutboxObj['outbox_creator'] == $own['user_id']){
            return true;
        }
        // 共享权限验证
        if (app($this->webmailShareConfigRepository)->getShareconfigPremission($webmailOutboxObj, $result, $own)) {
            return true;
        }
        return false;
    }

    public function updateMail($own, $data, $urlData){
        $mailIds = array_filter(explode(',', $urlData['mailId']));
        if(isset($data['folder'])){
            // 文件夹内邮件删除处理(转移)
            if(is_numeric($data['folder'])){
                $result = app($this->webmailFolderRepository)->getDetail($data['folder']);
                if($result['is_email'] == 1 || ($result && $result['folder_creator'] == $own['user_id'])){
                    return true;
                }
                return false;
            }
            if(is_string($data['folder'])){
                $result = app($this->webmailMailRepository)->getDetail($mailIds)->toArray();
                if(!$result) return false;
                $outbox_ids = [];
                foreach ($result as $key => $vo){
                    $outbox_ids[] = $vo['outbox_id'];
                }
                $webmailOutboxObj = app($this->webmailOutboxRepository)->getDetail($outbox_ids)->toArray();
                if(!$webmailOutboxObj) return false;
                foreach ($webmailOutboxObj as $ky => $value){
                    if(($value['outbox_creator'] != $own['user_id']) && $value['is_public'] != 1){
                        return false;
                    }
                }
            }
            return true;
        }
        if(isset($data['is_read'])){
            $result = app($this->webmailMailRepository)->getDetail($mailIds)->toArray();
            if(!$result) return false;
            $outbox_ids = [];
            foreach ($result as $key => $vo){
                $outbox_ids[] = $vo['outbox_id'];
            }
            $webmailOutboxObj = app($this->webmailOutboxRepository)->getDetail($outbox_ids)->toArray();
            if(!$webmailOutboxObj) return false;
            foreach ($webmailOutboxObj as $ky => $value){
                if(($value['outbox_creator'] != $own['user_id']) && $value['is_public'] != 1){
                    return false;
                }
            }
        }
        if(isset($data['is_star'])){
            $result = app($this->webmailMailRepository)->getDetail($mailIds)->toArray();
            if(!$result) return false;
            $outbox_ids = [];
            foreach ($result as $key => $vo){
                $outbox_ids[] = $vo['outbox_id'];
            }
            $webmailOutboxObj = app($this->webmailOutboxRepository)->getDetail($outbox_ids)->toArray();
            if(!$webmailOutboxObj) return false;
            foreach ($webmailOutboxObj as $ky => $value){
                if(($value['outbox_creator'] != $own['user_id']) && $value['is_public'] != 1){
                    return false;
                }
            }
        }
        return true;
    }


    public function commonValidate($own, $data, $urlData)
    {
        $outbox = app($this->webmailOutboxRepository)->getDetail($urlData['outboxId']);
        if(!$outbox) return false;
        $outbox = $outbox->toArray();
        if(($outbox['outbox_creator'] == $own['user_id']) || $outbox['is_public'] == 1){
            return true;
        }
        return false;
    }

    public function setTag($own, $data, $urlData)
    {
        $mailIds = array_filter(explode(',', $data['mail_id']));
        if(isset($data['tag_id'])){
            $result = app($this->webmailMailRepository)->getDetail($mailIds)->toArray();
            if(!$result) return false;
            $outbox_ids = [];
            foreach ($result as $key => $vo){
                $outbox_ids[] = $vo['outbox_id'];
            }
            $webmailOutboxObj = app($this->webmailOutboxRepository)->getDetail($outbox_ids)->toArray();
            if(!$webmailOutboxObj) return false;
            foreach ($webmailOutboxObj as $ky => $value){
                if(($value['outbox_creator'] != $own['user_id']) && $value['is_public'] != 1){
                    return false;
                }
            }
        }
        return true;
    }

    public function cancelTag($own, $data, $urlData)
    {
        $mailIds = isset($data['mail_id']) ? array_filter(explode(',', $data['mail_id'])) : [];
        if($mailIds){
            $result = app($this->webmailMailRepository)->getDetail($mailIds)->toArray();
            if(!$result) return false;
            $outbox_ids = [];
            foreach ($result as $key => $vo){
                $outbox_ids[] = $vo['outbox_id'];
            }
            $webmailOutboxObj = app($this->webmailOutboxRepository)->getDetail($outbox_ids)->toArray();
            if(!$webmailOutboxObj) return false;
            foreach ($webmailOutboxObj as $ky => $value){
                if(($value['outbox_creator'] != $own['user_id']) && $value['is_public'] != 1){
                    return false;
                }
            }
        }
        return true;
    }
}
