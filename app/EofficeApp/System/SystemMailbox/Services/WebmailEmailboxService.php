<?php
namespace App\EofficeApp\System\SystemMailbox\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\System\SystemMailbox\Repositories\WebmailEmailboxRepository;
use App\Utils\Utils;
/**
 * 系统邮箱service类，用来调用所需资源，提供和用户有关的服务。
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class WebmailEmailboxService extends BaseService
{

    public function __construct(
        WebmailEmailboxRepository $webmailEmailboxRepository
    ) {
        parent::__construct();
        $this->webmailEmailboxRepository     = $webmailEmailboxRepository;
    }

    /**
     * 系统邮箱设置--获取系统邮箱列表
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return array      返回结果
     */
    public function getSystemMailboxList($param) {
        $param = $this->parseParams($param);
        // 只获取系统邮箱
        $param["search"]["mail_type"] = ["sysmail"];
        return $this->response($this->webmailEmailboxRepository, 'webmailEmailboxListRepositoryGetTotal', 'webmailEmailboxListRepository', $param);
    }

    /**
     * 系统邮箱设置--新建系统邮箱
     *
     * @author 丁鹏
     *
     * @param  array            $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array     返回结果
     */
    public function createSystemMailbox($param) {
        // // 邮箱地址
        // $insertData["email_address"] = $param["email_address"];
        // // 发件人昵称
        // $insertData["nickname"]      = $param["nickname"];
        // // 用户ID
        // $insertData["user_id"]       = $param["user_id"];
        // // POP3服务器
        // $insertData["pop_server"]    = $param["pop_server"];
        // // SMTP服务器
        // $insertData["smtp_server"]   = $param["smtp_server"];
        // // SMTP服务器是否需要身份验证0-不需要 1-需要
        // $insertData["is_smtp_auth"]  = $param["is_smtp_auth"];
        // // POP3端口--可以为空
        // $insertData["pop_port"]      = $param["pop_port"];
        // // SMTP端口--可以为空
        // $insertData["smtp_port"]     = $param["smtp_port"];
        // // 是否使用SSL连接服务器0-否 1-是--可以为空
        // $insertData["is_ssl_auth"]   = $param["is_ssl_auth"];
        // // 是否是默认邮箱0-否 1-是--可以为空
        // $insertData["is_default"]    = $param["is_default"];
        // 邮箱密码
        $param["password"] = Utils::encrypt($param["password"]);
        // // 登录用户名
        // $insertData["user_name"]     = $param["user_name"];
        $insertData              = array_intersect_key($param,array_flip($this->webmailEmailboxRepository->getTableColumns()));
        $insertData["mail_type"] = "sysmail";
        return $this->webmailEmailboxRepository->insertData($insertData);
    }

    /**
     * 系统邮箱设置--编辑系统邮箱
     *
     * @author 丁鹏
     *
     * @param  array          $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array   返回结果
     */
    public function modifySystemMailbox($param, $emailboxId) {
        $updateData = array_intersect_key($param,array_flip($this->webmailEmailboxRepository->getTableColumns()));
        $updateData["password"] = Utils::encrypt($param["password"]);
        return $this->webmailEmailboxRepository->updateData($updateData, ["emailbox_id" => $emailboxId]);
    }

    /**
     * 系统邮箱设置--删除系统邮箱
     *
     * @author 丁鹏
     *
     * @param  array            $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array     返回结果
     */
    public function deleteSystemMailbox($emailboxId) {
        return $this->webmailEmailboxRepository->deleteById($emailboxId);
    }

    /**
     * 系统邮箱设置--获取系统邮箱详情
     *
     * @author 丁鹏
     *
     * @param  array            $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array     返回结果
     */
    public function getSystemMailboxDetail($param) {
        $info = $this->webmailEmailboxRepository->getDetail($param["emailbox_id"]);
        $info["password"] = Utils::decrypt($info->password);
        return $info;
    }
    /**
     * 系统邮箱设置--获取系统邮箱详情
     *
     * @author 丁鹏
     *
     * @param  array            $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array     返回结果
     */
    public function getDefaultSystemMailboxDetail() {
        $info = $this->webmailEmailboxRepository->getWebmailEmailboxByWhere(["is_default" => ["1"]]);
        if($info) {
            $info->password = Utils::decrypt($info->password);
            return $info;
        }else{
            return '';
        }
    }

    /**
     * 系统邮箱设置--设置为系统默认邮箱
     *
     * @author 丁鹏
     *
     * @param  array          $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array   返回结果
     */
    public function setAsDefaultMailbox($param, $emailboxId) {
        $this->webmailEmailboxRepository->updateData(["is_default" => "0"],["mail_type" => "sysmail"]);
        return $this->webmailEmailboxRepository->updateData(["is_default" => "1"], ["emailbox_id" => $emailboxId,"mail_type" => "sysmail"]);
    }

    public function checkMailPermission() {
        $data = $this->webmailEmailboxRepository->getSetMail();
        if (!empty($data)) {
            return '1';
        } else {
            return '0';
        }
    }
    public function cancelAsDefaultMailbox($param, $emailboxId) {
        $this->webmailEmailboxRepository->updateData(["is_default" => "0"],["mail_type" => "sysmail"]);
        return $this->webmailEmailboxRepository->updateData(["is_default" => "0"], ["emailbox_id" => $emailboxId,"mail_type" => "sysmail"]);
    }
}
