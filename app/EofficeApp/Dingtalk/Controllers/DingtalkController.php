<?php

namespace App\EofficeApp\Dingtalk\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\Dingtalk\Requests\DingtalkRequest;
use App\EofficeApp\Dingtalk\Services\DingtalkService;
use Illuminate\Http\Request;

class DingtalkController extends Controller
{

    private $request;
    private $dingtalkService;
    private $dingtalkRequest;

    public function __construct(
        Request $request, DingtalkService $dingtalkService, DingtalkRequest $dingtalkRequest
    ) {
        parent::__construct();

        $this->dingtalkService = $dingtalkService;
        $this->request         = $request;
        $this->dingtalkRequest = $dingtalkRequest;
        $this->formFilter($request, $dingtalkRequest);
    }

    public function checkDingtalk()
    {
        $result = $this->dingtalkService->checkDingtalk($this->request->all());
        return $this->returnResult($result);
    }

    public function saveDingtalk()
    {
        $result = $this->dingtalkService->saveDingtalk($this->request->all());
        return $this->returnResult($result);
    }

    public function truncateDingtalk()
    {
        $result = $this->dingtalkService->truncateDingtalk();
        return $this->returnResult($result);
    }

    public function getDingtalk()
    {
        $result = $this->dingtalkService->getDingtalk();
        return $this->returnResult($result);
    }

    public function dingtalkSignPackage()
    {
        $result = $this->dingtalkService->dingtalkSignPackage();
        return $this->returnResult($result); //直接
    }

    public function dingtalkClientpackage()
    {
        $result = $this->dingtalkService->dingtalkClientpackage($this->request->all());
        return $this->returnResult($result); //直接
    }

    public function dingtalkAccess()
    {
        $result = $this->dingtalkService->dingtalkAccess($this->request->all());
        return $this->returnResult($result);
    }

    public function pcDingtalkAccess()
    {
        $result = $this->dingtalkService->pcDingtalkAccess($this->request->all());
        return $this->returnResult($result);
    }

    public function dingtalkAuth()
    {
        $result = $this->dingtalkService->dingtalkAuth($this->request->all());
        return $this->returnResult($result);
    }

    public function dingtalkExport()
    {
        $result = $this->dingtalkService->dingtalkExport();
        return $this->returnResult($result);
    }

    public function dingtalkUserList()
    {
        $result = $this->dingtalkService->dingtalkUserList();
        return $this->returnResult($result);
    }

    public function dingtalkAttendance()
    {
        $result = $this->dingtalkService->dingtalkAttendance($this->request->all());
        return $this->returnResult($result);
    }

    public function dingtalkMove()
    {
        $result = $this->dingtalkService->dingtalkMove($this->request->all());
        return $this->returnResult($result);
    }

    public function dingtalkIndex()
    {
        $result = $this->dingtalkService->dingtalkIndex($this->request->all());
        return $this->returnResult($result);
    }

    public function dingtalkAuthWork()
    {
        $result = $this->dingtalkService->dingtalkAuthWork($this->request->all());
        return $this->returnResult($result);
    }

    public function dingtalkSync()
    {
        $result = $this->dingtalkService->dingtalkAttendanceSync($this->request->all(),$this->own['user_id']);
        // $result = $this->dingtalkService->dingtalkSync($this->request->all());
        return $this->returnResult($result);
    }
    public function dingtalkLogs()
    {
        $result = $this->dingtalkService->dingtalkLogs($this->request->all());
        return $this->returnResult($result);
    }
    public function saveSyncTime()
    {
        $result = $this->dingtalkService->saveSyncTime($this->request->all());
        return $this->returnResult($result);
    }
    public function deleteDingtalkLog($id)
    {
        $result = $this->dingtalkService->deleteDingtalkLog($id);
        return $this->returnResult($result);
    }
    public function getDingtalkUserList(Request $request)
    {
        $result = $this->dingtalkService->getDingtalkUserList($request->all());
        return $this->returnResult($result);
    }
    public function deleteBindByOaId($userId)
    {
        $result = $this->dingtalkService->deleteBindByOaId($userId);
    }
    public function addException()
    {
        $result = $this->dingtalkService->addException($this->request->all());
        return $this->returnResult($result);
    }
    public function getDingtalkDepartmentList($parentId)
    {
        $result = $this->dingtalkService->getDingtalkDepartmentList($this->request->all(), $parentId);
        return $this->returnResult($result);
    }
    // 增加钉钉部门与OA关联
    public function addDingtalkDepartmentRelation()
    {
        $result = $this->dingtalkService->addDingtalkDepartmentRelation($this->request->all());
        return $this->returnResult($result);
    }
    public function getRoleList()
    {
        $result = $this->dingtalkService->getRoleList($this->request->all());
        return $this->returnResult($result);
    }
    // 增加钉钉角色与OA关联
    public function addDingtalkRoleRelation()
    {
        $result = $this->dingtalkService->addDingtalkRoleRelation($this->request->all());
        return $this->returnResult($result);
    }
    // 组织架构同步
    public function dingtalkOASync()
    {
        $result = $this->dingtalkService->dingtalkOASync();
        return $this->returnResult($result);
    }
    // 组织架构同步
    public function organizationSync()
    {
        $result = $this->dingtalkService->organizationSync();
        return $this->returnResult($result);
    }
    // 钉钉事件注册函数
    public function registerCallback()
    {
        $result = $this->dingtalkService->registerCallback();
        return $this->returnResult($result);
    }
    // 钉钉事件回调加解密接收函数
    public function dingtalkCallbackReceive()
    {
        $result = $this->dingtalkService->dingtalkCallbackReceive($this->request->all());
        return $this->returnResult($result);
        // return $this->returnResult($result);
        // return $result;
    }

    // 钉钉组织架构同步日志列表
    public function getDingtalkSyncLogList()
    {
        $result = $this->dingtalkService->getDingtalkSyncLogList($this->request->all());
        return $this->returnResult($result);
    }

    // 钉钉组织架构同步日志详情
    public function getDingtalkSyncLogdetail($id)
    {
        $result = $this->dingtalkService->getDingtalkSyncLogdetail($id);
        return $this->returnResult($result);
    }

    // 保存钉钉组织架构同步
    public function saveDingtalkOASyncConfig()
    {
        $result = $this->dingtalkService->saveDingtalkOASyncConfig($this->request->all());
        return $this->returnResult($result);
    }

    // 获取钉钉组织架构同步
    public function getDingtalkOASyncConfig()
    {
        $result = $this->dingtalkService->getDingtalkOASyncConfig();
        return $this->returnResult($result);
    }

    // 测试接口
    public function test()
    {
        $result = $this->dingtalkService->testss();
        return $this->returnResult($result);
    }

}
