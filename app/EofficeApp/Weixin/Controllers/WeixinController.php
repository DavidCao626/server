<?php

namespace App\EofficeApp\Weixin\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Weixin\Requests\WeixinRequest;
use App\EofficeApp\Weixin\Services\WeixinService;

/**
 * 生日贺卡控制
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class WeixinController extends Controller {

    public function __construct(
    Request $request, WeixinService $weixinService, WeixinRequest $weixinRequest
    ) {
        parent::__construct();
        $this->weixinService = $weixinService;
        $this->weixinRequest = $request;
        $this->formFilter($request, $weixinRequest);
    }

    // 公众号设置

    public function setWeixinToken() {
        $result = $this->weixinService->setWeixinToken($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    //清空
    public function clearWeixinToken() {
        $result = $this->weixinService->clearWeixinToken();
        return $this->returnResult($result);
    }

    //连接测试
    public function connectWeixinToken() {
        $result = $this->weixinService->connectWeixinToken($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    public function getWeixinToken() {
        $result = $this->weixinService->getWeixinToken();
        return $this->returnResult($result);
    }

    //增加菜单
    public function addMenu() {
        $result = $this->weixinService->addMenu($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    //删除菜单
    public function deleteMenu($id) {
        $result = $this->weixinService->deleteMenu($id);
        return $this->returnResult($result);
    }

    //编辑菜单
    public function editMenu($id) {
        $result = $this->weixinService->editMenu($id, $this->weixinRequest->all());
        return $this->returnResult($result);
    }

    //微信菜单更新
    public function updateMenu() {
        $result = $this->weixinService->updateMenu();
        return $this->returnResult($result);
    }

    //关注用户
    public function getWeixinUserFollowList() {
        $result = $this->weixinService->getWeixinUserFollowList($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    //同步用户
    public function synchronizeUser($next_openid = null) {
        $result = $this->weixinService->synchronizeUser($next_openid = null);
        return $this->returnResult($result);
    }

    //绑定
    public function getWeixinUserBindList() {
        $result = $this->weixinService->getWeixinUserBindList($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    //微信菜单树
    public function weixinMenuTree($menuParent) {
        $result = $this->weixinService->weixinMenuTree($menuParent, $this->weixinRequest->all());
        return $this->returnResult($result);
    }

    //获取微信菜单信息（菜单ID）

    public function getMenuByMenuId($id) {
        $result = $this->weixinService->getMenuByMenuId($id);
        return $this->returnResult($result);
    }

    //检查菜单（3 主菜单 5 子菜单）
    public function checkMenu($node) {
        $result = $this->weixinService->checkMenu($node);
        return $this->returnResult($result);
    }

    //新建下级菜单
    public function addJuniorMenu() {
        $result = $this->weixinService->addJuniorMenu($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    //微信验证
    public function weixinAuth() {
        $result = $this->weixinService->weixinAuth($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    //微信用户获取
    public function weixinCode($code) {

        $result = $this->weixinService->weixinCode($code, $this->weixinRequest->all());
        return $this->returnResult($result);
    }

    //生成二维码
    public function getBindingQRcode() {
        $user_id=$this->own['user_id'];
        $result = $this->weixinService->getBindingQRcode($user_id, $this->weixinRequest->all());
        return $this->returnResult($result);
    }

    //解绑用户
    public function unwrapWeixin($userId) {
        $result = $this->weixinService->unwrapWeixin($userId);
        return $this->returnResult($result);
    }

    //获取微信signPackage
    public function weixinSignPackage() {
        $result = $this->weixinService->weixinSignPackage($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    //文件上传
    public function weixinMove() {

        $result = $this->weixinService->weixinMove($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    //微信检查
    public function weixinCheck() {
        $result = $this->weixinService->weixinCheck($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    public function weixinAccess() {
        $result = $this->weixinService->weixinAccess($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    public function weixinMenuList() {
        $result = $this->weixinService->weixinMenuList();
        return $this->returnResult($result);
    }
    /**
     * 微信公众号用户主动绑定oa账号（未绑定oa用户首次从微信公众号登陆，需要先登录绑定）
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function weixinLogin()
    {
        $result = $this->weixinService->weixinLogin($this->weixinRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 微信自动回复************************start***************************
     */
    /**
     * 设置微信自动回复
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function setWeixinReply()
    {
        $result = $this->weixinService->setWeixinReply($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 获取微信自动回复设置
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function getWeixinReply()
    {
        $result = $this->weixinService->getWeixinReply();
        return $this->returnResult($result);
    }

    /**
     * 获取微信自动回复模板列表
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function getReplyTemplateList()
    {
        $result = $this->weixinService->getReplyTemplateList($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 凭借模板id获取回复模板
     * @param $id
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function getReplyTemplate($id)
    {
        $result = $this->weixinService->getReplyTemplate($id);
        return $this->returnResult($result);
    }

    /**
     * 设置微信回复模板内容
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function setReplyTemplate()
    {
        $result = $this->weixinService->setReplyTemplate($this->weixinRequest->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 凭借模板id删除回复模板
     * @param $id
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function deleteReplyTemplate($id)
    {
        $result = $this->weixinService->deleteReplyTemplate($id);
        return $this->returnResult($result);
    }

    public function getInvoiceParam()
    {
        $result = $this->weixinService->getInvoiceParam($this->weixinRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 下载微信服务器IP地址
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @creatTime 2020/11/25 17:27
     * @author [dosy]
     */
    public function downWeiXinIp(){
        $result = $this->weixinService->downWeiXinIp();
        return $result;
    }

}
