<?php
namespace App\EofficeApp\Sso\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Sso\Requests\SsoRequest;
use App\EofficeApp\Sso\Services\SsoService;

/**
 * 单点登录控制
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class SsoController extends Controller {

    public function __construct(
       Request $request,
       SsoService $ssoService,
       SsoRequest $ssoRequest
    ) {
        parent::__construct();
        $this->ssoService = $ssoService;
        $this->ssoRequest = $request;
        $this->formFilter($request, $ssoRequest);
    }


    /**
     * 获取单点登录的列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getSsoList() {
        $result = $this->ssoService->getSsoList($this->ssoRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 增加单点登录：登录设置系统
     *
     * @return int 自增ID
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addSso() {
        $result = $this->ssoService->addSso($this->ssoRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 编辑单点登录
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editSso() {
        $result = $this->ssoService->editSso($this->ssoRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 删除单点登录
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteSso() {
        $result = $this->ssoService->deleteSso($this->ssoRequest->all());
        return $this->returnResult($result);
    }

    public function getOneSso(){
        $result = $this->ssoService->getOneSso($this->ssoRequest->all());
        return $this->returnResult($result);
    }
    // public function getSsoLogin(){
    //     $result = $this->ssoService->getSsoLogin($this->ssoRequest->all());
    //     return $this->returnResult($result);
    // }




    /**
     * 获取外部系统账号列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
     // public function getSsoLoginList($user_id){
     //     $result = $this->ssoService->getSsoLoginList($this->ssoRequest->all(),$user_id);
     //     return $this->returnResult($result);
     // }

    public function getMySsoLoginList(){
         $user_id = $this->own['user_id'];
         $result = $this->ssoService->getSsoLoginList($this->ssoRequest->all(),$user_id);
         return $this->returnResult($result);
    }

     /**
     * 编辑外部系统账户
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editSsoLogin($ssoLoginId) {
        $result = $this->ssoService->editSsoLogin($this->ssoRequest->all(),$ssoLoginId);
        return $this->returnResult($result);
    }

    /**
     * sso 二级菜单
     */
    public function getSsoTree(){
        $result = $this->ssoService->getSsoTree($this->ssoRequest->all());
        return $this->returnResult($result);
    }

    /***
     * 获取当前用户某个单点登录详情
     */
    public function getMySsoLoginDetail($ssoId)
    {
        $userId = $this->own['user_id'];
        $result = $this->ssoService->getMySsoLoginDetail($ssoId,$userId);
        return $this->returnResult($result);
    }


}
