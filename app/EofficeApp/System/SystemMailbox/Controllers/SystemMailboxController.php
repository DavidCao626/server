<?php
namespace App\EofficeApp\System\SystemMailbox\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\SystemMailbox\Services\WebmailEmailboxService;
use App\EofficeApp\System\SystemMailbox\Requests\SystemMailboxRequest;
/**
 * 系统邮箱设置 controller
 * 这个类，用来：1、验证request；2、组织数据；3、调用service实现功能；[4、组织返回值]
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class SystemMailboxController  extends Controller
{
    protected $service;
    protected $request;

    public function __construct(
        Request $request,
        WebmailEmailboxService $webmailEmailboxService,
        SystemMailboxRequest $systemMailboxRequest
    ) {
        parent::__construct();
        $this->webmailEmailboxService = $webmailEmailboxService;
        $this->formFilter($request, $systemMailboxRequest);
        $this->request = $request;
    }

    /**
     * 系统邮箱设置--获取系统邮箱列表，不带查询
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return json
     */
    public function getSystemMailboxList() {
        $result = $this->webmailEmailboxService->getSystemMailboxList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 系统邮箱设置--新建系统邮箱
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return status_id
     */
    public function createSystemMailbox() {
        $result = $this->webmailEmailboxService->createSystemMailbox($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 系统邮箱设置--编辑系统邮箱
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return [type] [description]
     */
    public function modifySystemMailbox($emailboxId) {
        $result = $this->webmailEmailboxService->modifySystemMailbox($this->request->all(), $emailboxId);
        return $this->returnResult($result);
    }

    /**
     * 系统邮箱设置--删除系统邮箱
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return [type] [description]
     */
    public function deleteSystemMailbox($emailboxId) {
        $result = $this->webmailEmailboxService->deleteSystemMailbox($emailboxId);
        return $this->returnResult($result);
    }

    /**
     * 系统邮箱设置--获取系统邮箱详情
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return [type] [description]
     */
    public function getSystemMailboxDetail($emailboxId) {
        $result = $this->webmailEmailboxService->getSystemMailboxDetail(["emailbox_id" => $emailboxId]);
        return $this->returnResult($result);
    }
    /**
     * 系统邮箱设置--获取默认系统邮箱详情
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return [type] [description]
     */
    public function getDefaultSystemMailboxDetail() {
        $result = $this->webmailEmailboxService->getDefaultSystemMailboxDetail();
        return $this->returnResult($result);
    }

    /**
     * 系统邮箱设置--设置为系统默认邮箱
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return [type] [description]
     */
    public function setAsDefaultMailbox($emailboxId) {
        $result = $this->webmailEmailboxService->setAsDefaultMailbox($this->request->all(), $emailboxId);
        return $this->returnResult($result);
    }
    public function cancelAsDefaultMailbox($emailboxId) {
        $result = $this->webmailEmailboxService->cancelAsDefaultMailbox($this->request->all(), $emailboxId);
        return $this->returnResult($result);
    }
}
