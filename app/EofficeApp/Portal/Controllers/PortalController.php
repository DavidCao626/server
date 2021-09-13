<?php
namespace App\EofficeApp\Portal\Controllers;

use App\EofficeApp\Portal\Services\PortalService;
use App\EofficeApp\Portal\Requests\PortalRequest;
use \Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
/**
 * 门户控制器类
 * 
 * @author 李志军
 * 
 * @since 2015-10-27
 */
class PortalController extends Controller
{
    /** @var object 门户服务类对象  */
    private $portalService;

    /**
     * 注册门户服务类对象
     * 
     * @param \App\EofficeApp\Portal\Services\PortalService $portalService
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function __construct(PortalService $portalService,Request $request,PortalRequest $portalRequest) 
    {
        parent::__construct();

        $this->portalService = $portalService;
        $this->request = $request;
        $this->formFilter($request, $portalRequest);
    }
    /**
     * 获取门户列表
     * 
     * @param \Illuminate\Http\Request $request
     * 
     * @return json 门户列表
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function listPortal()
    {
        return $this->returnResult($this->portalService->listPortal($this->request->input('fields',''), $this->own['user_id'], $this->own['dept_id'],$this->own['role_id']));
    }
    public function listMenuPortal()
    {
        return $this->returnResult($this->portalService->listMenuPortal($this->own['user_id'], $this->own['dept_id'],$this->own['role_id']));
    }
    public function listMangePortal()
    {
        return $this->returnResult($this->portalService->listManagePortal($this->request->input('fields',''), $this->own));
    }
    /**
     * 新建门户
     * 
     * @param \App\Http\Requests\PortalRequest $request
     * 
     * @return json 门户id
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function addPortal()
    {
        return $this->returnResult($this->portalService->addPortal($this->request->all(), $this->own['user_id']));
    }
    public function editPortal($portalId)
    {
        return $this->returnResult($this->portalService->editPortal($this->request->all(), $portalId));
    }
    /**
     * 编辑门户
     * 
     * @param \App\Http\Requests\PortalRequest $request
     * @param int $portalId
     * 
     * @return json 编辑结果
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function editPortalPriv($portalId)
    {
        return $this->returnResult($this->portalService->editPortalPriv($this->request->all(), $portalId, $this->own['user_id'], $this->own['dept_id'],$this->own['role_id']));
    }
    public function editPortalName($portalId)
    {
        return $this->returnResult($this->portalService->editPortalName($this->request->all(), $portalId));
    }
    public function editPortalElementMargin($portalId) 
    {
        return $this->returnResult($this->portalService->editPortalElementMargin($this->request->all(), $portalId, $this->own['user_id']));
    }
    public function getPortalElementMargin($portalId) 
    {
        return $this->returnResult($this->portalService->getPortalElementMargin( $portalId, $this->own['user_id']));
    }
    public function editPortalIcon($portalId)
    {
        return $this->returnResult($this->portalService->editPortalIcon($this->request->all(), $portalId));
    }
    public function getPortalInfo($portalId)
    {
        return $this->returnResult($this->portalService->getPortalInfo($portalId, $this->own, $this->request->input('type', 'all')));
    }
    /**
     * 删除门户
     * 
     * @param int $portalId
     * 
     * @return json 删除结果
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function deletePortal($portalId)
    {
        return $this->returnResult($this->portalService->deletePortal($portalId, $this->own['user_id'], $this->own['dept_id'],$this->own['role_id']));
    }
    /**
     * 设置门户权限
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $portalId
     * 
     * @return json 设置结果
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function setPortalPriv( $portalId)
    {
        return $this->returnResult($this->portalService->setPortalPriv($this->request->all(), $portalId));
    }
    /**
     * 门户排序
     * 
     * @param \App\Http\Requests\PortalRequest $request
     * 
     * @return json 排序结果
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function sortPortal()
    {
        return $this->returnResult($this->portalService->sortPortal($this->request->input('sort_data')));
    }
    /**
     * 恢复默认门户
     * 
     * @param int $portalId
     * 
     * @return json 恢复结果
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function recoverDefaultPortal($portalId)
    {
        return $this->returnResult($this->portalService->recoverDefaultPortal($portalId, $this->own['user_id'], $this->own['dept_id'],$this->own['role_id']));
    }
    /**
     * 统一门户
     * 
     * @param int $portalId
     * 
     * @return json 同意结果
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function unifyPortal($portalId)
    {
        return $this->returnResult($this->portalService->unifyPortal($portalId, $this->own['user_id'], $this->own['dept_id'],$this->own['role_id']));
    }
    /**
     * 设置为默认显示门户
     * 
     * @param int $portalId
     * 
     * @return json 操作结果
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function setDefaultPortal($portalId)
    {
        return $this->returnResult($this->portalService->setDefaultPortal($portalId, $this->own['user_id'], $this->own['dept_id'],$this->own['role_id']));
    }
    /**
     * 设置门户布局
     * 
     * @param \App\Http\Requests\PortalRequest $request
     * 
     * @return json 设置结果
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function setPortalLayout() 
    {
        return $this->returnResult($this->portalService->setPortalLayout($this->request->all(), $this->own['user_id']));
    }
    /**
     * 获取门户布局
     * 
     * @param int $portalId
     * 
     * @return json 门户布局
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function getPortalLayout($portalId)
    {
        return $this->returnResult($this->portalService->getLayoutContent($portalId, $this->own['user_id']));
    }

    public function getRssContent() 
    {
        return $this->returnResult($this->portalService->getRssContent($this->request->all()));
    }
    
    public function setUserAvatar() {
       return $this->returnResult($this->portalService->setUserAvatar($this->request->all(), $this->own['user_id'])); 
    }
    
    public function setSystemLogo() {
        return $this->returnResult($this->portalService->setSystemLogo($this->request->all(), $this->own['user_id'])); 
    }
    public function getEofficeAvatar($userId)
    {
        return $this->returnResult($this->portalService->getEofficeAvatar($userId));
    }
    public function getUserAvatar($userId)
    {
    	return $this->returnResult($this->portalService->getUserAvatar($userId)); 
    }

    public function getUserQrCode($userId)
    {
    	return $this->returnResult($this->portalService->getUserQrCode($userId));
    }
    public function getReportType()
    {
    	return $this->returnResult($this->portalService->getReportType());
    }
    public function getReportsByTypeId()
    {
    	return $this->returnResult($this->portalService->getReportsByTypeId($this->request->all()));
    }
    public function setMenuPortal()
    {
    	return $this->returnResult($this->portalService->setMenuPortal($this->request->all()));
    }
    public function getMenuPortal(){
    	return $this->returnResult($this->portalService->getMenuPortal($this->request->all()));
    }
    public function setFavorite(){
    	return $this->returnResult($this->portalService->setFavorite($this->request->all()));
    }
    public function setUserCommonMenu() 
    {
        return $this->returnResult($this->portalService->setUserCommonMenu($this->request->all(), $this->own['user_id']));
    }
    public function cancelFavorite(){
    	return $this->returnResult($this->portalService->cancelFavorite($this->request->all()));
    }
    public function setNavbar(){
    	return $this->returnResult($this->portalService->setNavbar($this->request->all()));
    }
    public function getNavbar(){
    	return $this->returnResult($this->portalService->getNavbar($this->request->all()));
    }
    
    public function getHomeInitData()
    {
        return $this->returnResult($this->portalService->getHomeInitData($this->own));
    }
    public function checkWeChat()
    {
        return $this->returnResult($this->portalService->checkWeChat());
    }
    
    public function setSearchItem()
    {
        return $this->returnResult($this->portalService->setSearchItem($this->request->input('items', []), $this->own));
    }
    public function getSearchItem()
    {
        return $this->returnResult($this->portalService->getSearchItem($this->own));
    }
    public function getImportLayout() {
        return $this->returnResult($this->portalService->getImportLayout($this->request->all(), $this->own['user_id']));
    }
    public function getExportLayout($portalId) {
        return $this->returnResult($this->portalService->getExportLayout($portalId, $this->own['user_id']));
    }
}
