<?php
namespace App\EofficeApp\PublicGroup\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\PublicGroup\Requests\PublicGroupRequest;
use App\EofficeApp\PublicGroup\Services\PublicGroupService;

/**
 * 公共用户组控制
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class PublicGroupController extends Controller {

    public function __construct(
       Request $request,
       PublicGroupService $publicGroupService,
       PublicGroupRequest $publicGroupRequest
    ) {
        parent::__construct();
        $this->publicGroupService = $publicGroupService;
        $this->publicGroupRequest = $request;
        $this->formFilter($request, $publicGroupRequest);
    }


    /**
     * 获取公共用户组的列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getPublicGroupList() {
        $result = $this->publicGroupService->getPublicGroupList($this->own['user_id'], $this->own['dept_id'], $this->own['role_id'],$this->publicGroupRequest->all());
       return $this->returnResult($result);

    }
    /**
     * 获取公共用户组的列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getPublicGroupManageList() {
        $result = $this->publicGroupService->getPublicGroupManageList($this->publicGroupRequest->all());
        return $this->returnResult($result);

    }

    /**
     * 增加公共用户组
     *
     * @return int 自增ID
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addPublicGroup() {
        $result = $this->publicGroupService->addPublicGroup($this->publicGroupRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 编辑公共用户组
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editPublicGroup() {
        $result = $this->publicGroupService->editPublicGroup($this->publicGroupRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 删除公共用户组
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deletePublicGroup() {
        $result = $this->publicGroupService->deletePublicGroup($this->publicGroupRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 获取公共用户组的详细
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function getOnepublicGroup($groupId){
        $result = $this->publicGroupService->getOnepublicGroup($groupId);
        return $this->returnResult($result);
    }

    /**
     * 获取公共用户组的成员列表
     *
     * @return array
     *
     * @author 缪晨晨
     *
     * @since 2017-10-24
     */
    public function getOnePublicGroupUserList($groupId){
        $result = $this->publicGroupService->getOnePublicGroupUserList($groupId, $this->publicGroupRequest->all());
        return $this->returnResult($result);
    }

}
