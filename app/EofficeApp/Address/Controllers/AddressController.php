<?php
namespace App\EofficeApp\Address\Controllers;

use \Illuminate\Http\Request,
	\App\EofficeApp\Address\Requests\AddressGroupRequest,
	\App\EofficeApp\Address\Requests\AddressRequest,
	\App\EofficeApp\Address\Services\AddressService,
	\App\EofficeApp\Base\Controller;
/**
 * @通讯录控制器
 *
 * @author 李志军
 */
class AddressController extends Controller
{
	private $addressService;

	/**
	 * @注册通讯录服务
	 * @param \App\EofficeApp\Services\AddressService $addressService
	 */
	public function __construct(
            AddressService $addressService,
            Request $request,
            AddressGroupRequest $addressGroupRequest,
            AddressRequest $addressRequest
            )
	{
		parent::__construct();

		$this->addressService = $addressService;
        $this->request = $request;
        $this->formFilter($request, $addressGroupRequest);
        $this->formFilter($request, $addressRequest);
	}
	/**
	 * @获取子通讯录组
	 * @param \Illuminate\Http\Request $request
	 * @param type $groupType
	 * @param type $parentId
	 * @return json 子通讯录组
	 */
	public function getChildren($groupType, $parentId)
	{
		return $this->returnResult($this->addressService->getChildren(intval($groupType), $parentId, $this->request->input('fields', []),$this->own));
	}
	public function getViewChildrenBySearch($groupType)
	{
		return $this->returnResult($this->addressService->getViewChildrenBySearch(intval($groupType), $this->request->all(),$this->own));
	}
	public function getViewChildren($groupType,$parentId)
	{
		return $this->returnResult($this->addressService->getViewChildren(intval($groupType), $parentId, $this->request->input('fields', []),$this->own));
	}
    public function getAddressPublicFamily($parentId)
	{
		return $this->returnResult($this->addressService->getAddressFamily(1, $parentId, $this->request->input('fields', []),$this->request->input('filter',''),$this->own));
	}
	public function getAddressPrivateFamily($parentId)
	{
		return $this->returnResult($this->addressService->getAddressFamily(2, $parentId, $this->request->input('fields', []),$this->request->input('filter',''),$this->own));
	}
	public function listGroup($groupType)
	{
		return $this->returnResult($this->addressService->listGroup(intval($groupType), $this->request->all(),$this->own));
	}
	/**
	 * @新建通讯录组
	 * @param \App\Http\Requests\StoreAddressGroupRequest $request
	 * @return json group_id
	 */
	public function addGroup()
	{
		return $this->returnResult($this->addressService->addGroup($this->request->all(), intval($this->request->input('group_type')),$this->own['user_id']));
	}
	/**
	 * @编辑通讯录组
	 * @param \App\Http\Requests\StoreAddressGroupRequest $request
	 * @param type $groupId
	 * @return 成功与否
	 */
	public function editGroup($groupId)
	{
		return $this->returnResult($this->addressService->editGroup($this->request->all(), $groupId, intval($this->request->input('group_type')),$this->own['user_id']));
	}
	/**
	 * @删除通讯录
	 * @param type $groupType
	 * @param type $groupId
	 * @return 成功与否
	 */
	public function deleteGroup($groupType, $groupId)
	{
		return $this->returnResult($this->addressService->deleteGroup(intval($groupId), intval($groupType)));
	}
	/**
	 * @通讯录组排序
	 * @param \Illuminate\Http\Request $request
	 * @param type $groupType
	 * @return 成功与否
	 */
	public function sortGroup($groupType)
	{
		return $this->returnResult($this->addressService->sortGroup($this->request->input('sort_data', []), intval($groupType)));
	}
	/**
	 * @获取通讯录组详情
	 * @param type $groupType
	 * @param type $groupId
	 * @return 通讯录组详情
	 */
	public function showGroup($groupType, $groupId)
	{
		return $this->returnResult($this->addressService->showGroup(intval($groupId), intval($groupType),$this->own));
	}
	public function showManageGroup($groupType, $groupId)
	{
		return $this->returnResult($this->addressService->showManageGroup(intval($groupId), intval($groupType),$this->own));
	}
	/**
	 * @通讯录组迁移
	 * @param type $groupType
	 * @param type $fromId
	 * @param type $toId
	 * @return 成功与否
	 */
	public function migrateGroup($groupType, $fromId, $toId)
	{
		return $this->returnResult($this->addressService->migrateGroup(intval($groupType), $fromId, $toId));
	}
	/**
	 * @获取通讯录列表
	 * @param \Illuminate\Http\Request $request
	 * @param type $groupType
	 * @return 通讯录列表
	 */
	public function listAddressPublic()
	{
		return $this->returnResult($this->addressService->listAddress($this->request->all(), 1, $this->own));
	}
	public function listAddressPrivate()
	{
		return $this->returnResult($this->addressService->listAddress($this->request->all(), 2, $this->own));
	}

    public function listAddressPublicForSelector($groupType)
    {
        $params = $this->request->all();
        return $this->returnResult($this->addressService->listAddress($params, $groupType, $this->own));
    }
	/**
	 * @新建通讯录
	 * @param \App\Http\Requests\StoreAddressRequest $request
	 * @return 通讯录id
	 */
	public function addAddress()
	{
		return $this->returnResult($this->addressService->addAddress($this->request->all(),$this->own['user_id']));
	}
	/**
	 * @编辑通讯录
	 * @param \App\Http\Requests\StoreAddressRequest $request
	 * @param type $addressId
	 * @return 成功与否
	 */
	public function editAddress($addressId)
	{
		return $this->returnResult($this->addressService->editAddress($this->request->all(), $addressId));
	}
	/**
	 * @获取通讯录详情
	 * @param type $groupType
	 * @param type $addressId
	 * @return 通讯录信息
	 */
	public function showAddress($groupType, $addressId)
	{
		return $this->returnResult($this->addressService->showAddress(intval($groupType), intval($addressId),$this->own));
	}
	/**
	 * @删除通讯录
	 * @param type $addressId
	 * @return 成功与否
	 */
	public function deleteAddress($addressId)
	{
		return $this->returnResult($this->addressService->deleteAddress($addressId));
	}
	/**
	 * @通讯录迁移
	 * @param type $groupId
	 * @param type $addressId
	 * @return 成功与否
	 */
	public function migrateAddress($groupId,$addressId,$tableKey)
	{
		return $this->returnResult($this->addressService->migrateAddress($groupId, $addressId, $tableKey, $this->own));
	}
	/**
	 * @通讯录复制
	 * @param type $groupId
	 * @param type $addressId
	 * @return 成功与否
	 */
	public function copyAddress($groupId,$addressId)
	{
		return $this->returnResult($this->addressService->copyAddress($groupId, $addressId,$this->own));
	}
	/**
	 * @导出通讯录
	 */
	public function exportAddress()
	{
		return $this->returnResult($this->addressService->exportAddress($this->request->all(),$this->own));
	}
	/**
	 * @导入通讯录
	 */
	public function importAddress()
	{
		return $this->returnResult($this->addressService->importAddress());
	}

	public function getAllViewGroupsByRecursion() {
		return $this->returnResult($this->addressService->getAllViewGroupsByRecursion($this->own, $this->request->all()));
	}

    public function getPrivateGroupsOrderByTree() {
        return $this->returnResult($this->addressService->getPrivateGroupsOrderByTree($this->own, $this->request->all()));
    }

	public function getAddressPublicList($groupId) {
		return $this->returnResult($this->addressService->getAddressList(1, $groupId, $this->own, $this->request->all()));
	}

	public function getAddressPrivateList($groupId) {
		return $this->returnResult($this->addressService->getAddressList(2, $groupId, $this->own, $this->request->all()));
	}

	public function getAddressPublicTree($groupId) {
		return $this->returnResult($this->addressService->getAddressTree(1, $groupId, $this->own));
	}
	public function getAddressPrivateTree($groupId) {
		return $this->returnResult($this->addressService->getAddressTree(2, $groupId, $this->own));
	}

	public function directPublicAddress() {
		return $this->returnResult($this->addressService->listAddressByGroupId($this->request->all(), 1, $this->own));
	}

	public function directPrivateAddress() {
		return $this->returnResult($this->addressService->listAddressByGroupId($this->request->all(), 2, $this->own));
	}
}
