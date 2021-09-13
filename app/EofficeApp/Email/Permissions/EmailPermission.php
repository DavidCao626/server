<?php
namespace App\EofficeApp\Email\Permissions;
use DB;
use Illuminate\Support\Arr;
class EmailPermission
{
    private $emailRepository;
    // 验证引擎会优先调用类里拥有的方法，如果没有则从该数组匹配找到对应的方法调用。
    public $rules = [
        'downloadEml' => 'commonValidate',
    ];
    public function __construct() 
    {
        $this->emailReceiveRepository = 'App\EofficeApp\Email\Repositories\EmailReceiveRepository';
        $this->emailReceiveEntity = 'App\EofficeApp\Email\Entities\EmailReceiveEntity';
    }
    /**
     * 验证导出内部邮件权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function downloadEml($own, $data, $urlData)
    {
        $emailIds  = explode(',', $urlData['emailId']);
        $count = count($emailIds);
        if(!$count){
            return ['code' => ['no_privileges', 'email']];
        }

        foreach ($emailIds as $vo){
            $result = DB::table('email')->select('from_id')->where('email_id',$vo)->first();
            $check = app($this->emailReceiveEntity)->select('recipients')->where(['email_id'=>$vo,'recipients'=>$own['user_id']])->get()->toArray();
            if(!$check && $result->from_id != $own['user_id'] ){
                return false;
            }
        }
        return true;
    }


    // 草稿箱 已发送   api/email/from/2129? (转发邮件也用)
    public function getEmailData($own, $data, $urlData){
        // 当是已发送详情页转发邮件时
        $where = ['email_id' => $urlData['emailId'],'recipients'=>$own['user_id']];
        // 当是收件箱，我的 文件夹 ,已删除文件夹下 详情页转发邮件时
        if($data['email_box']){
            if ($data['email_box'] > 0) {
                return app($this->emailReceiveEntity)->select('recipients')->where($where)->exists();
            }
             switch ($data['email_box']) {
                 case -1:
                     //查看是否是收件箱删除的邮件
                     $deleteData = app($this->emailReceiveEntity)->select('recipients')->where($where)->get()->toArray();
                     // 检测是否是草稿箱删除的邮件
                     $draftData = DB::table('email')->select('*')->where('email_id',$urlData['emailId'])->first();
                     if($draftData->from_id == $own['user_id'] || $deleteData)
                     {
                         return true;
                     }
                     return false;
                 break;
                 case -3:
                     //草稿箱
                     if(DB::table('email')->select('email_id')->where(['email_id'=>$urlData['emailId'],'from_id'=>$own['user_id']])->first())
                     {
                         return true;
                     }
                     return false;
                 break;
                 default:
                     $draftData = DB::table('email')->select('*')->where('email_id',$urlData['emailId'])->first();
                     if($draftData->from_id == $own['user_id'])
                     {
                         return true;
                     }
                     return false;
                 break;

             }
        }
    }


    // 收件箱 已删除 我的文件夹  api/email/my/2149?id=16383
    public function getEmailInfo($own, $data, $urlData){
        // 自己的邮件均能查看
        $email = DB::table('email')->select('*')->where('email_id',$urlData['emailId'])->first();
        if (!$email) return false;
        // 已发送的邮件，相关人员可以查看
        //即时消息下查看详情，发件人不给数据权限
        $isRemind = Arr::get($data, 'is_remind');
        if (!$isRemind && $email->from_id == $own['user_id']) {
            return true;
        }
        if ($email->send_flag == 1) {

            $where = ['email_id' => $urlData['emailId'],'recipients'=>$own['user_id']];
            $exists = app($this->emailReceiveEntity)->select('recipients')->where($where)->exists();
            return $exists;
        }

        return false;
    }
    

    /**
     * 标记为已读(只能标记自己邮箱列表下的邮件)
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function readEmail($own, $data, $urlData){
        $emailIds  = explode(',', $urlData['emailId']);
        $count = count($emailIds);
        if(!$count){
            return ['code' => ['no_privileges', 'email']];
        }
        $result = app($this->emailReceiveEntity)->select('recipients')->whereIn('id',$emailIds)->get();
        if($result){
            foreach ($result as $vo){
                if($vo->recipients != $own['user_id']){
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 编辑草稿箱权限验证
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function editEmail($own, $data, $urlData){
        $result = DB::table('email')->select('*')->where('email_id',$urlData['emailId'])->first();
        if($result && $result->from_id == $own['user_id']){
            return true;
        }
        return false;
    }
}
