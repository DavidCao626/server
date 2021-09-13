<?php
namespace App\EofficeApp\Cooperation\Services;
use Eoffice;
use App\EofficeApp\Base\BaseService;

/**
 * 协作service类，用来调用所需资源，提供和协作有关的服务。
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationService extends BaseService
{

    /** @var object 协作区权限表 */
    private $cooperationPurviewRepository;

    /** @var object 协作区回复表 */
    private $cooperationRevertRepository;

    /** @var object 协作区分类表 */
    private $cooperationSortRepository;

    /** @var object 协作区主题 */
    private $cooperationSubjectRepository;

    /** @var object 协作类别角色成员 */
    private $cooperationSortMemberUserRepository;

    /** @var object 协作类别部门成员 */
    private $cooperationSortMemberRoleRepository;

    /** @var object 协作类别用户成员 */
    private $cooperationSortMemberDepartmentRepository;

    /** @var object 协作主题管理人员 */
    private $cooperationSubjectUserRepository;

    /** @var object 协作主题角色权限 */
    private $cooperationSubjectRoleRepository;

    /** @var object 协作主题部门权限 */
    private $cooperationSubjectDepartmentRepository;

    /** @var object 协作主题用户权限 */
    private $cooperationSubjectManageRepository;

    public function __construct() {
        parent::__construct();
        $this->cooperationPurviewRepository              = 'App\EofficeApp\Cooperation\Repositories\CooperationPurviewRepository';
        $this->cooperationRevertRepository               = 'App\EofficeApp\Cooperation\Repositories\CooperationRevertRepository';
        $this->cooperationSortRepository                 = 'App\EofficeApp\Cooperation\Repositories\CooperationSortRepository';
        $this->cooperationSubjectRepository              = 'App\EofficeApp\Cooperation\Repositories\CooperationSubjectRepository';
        $this->cooperationSortMemberUserRepository       = 'App\EofficeApp\Cooperation\Repositories\CooperationSortMemberUserRepository';
        $this->cooperationSortMemberRoleRepository       = 'App\EofficeApp\Cooperation\Repositories\CooperationSortMemberRoleRepository';
        $this->cooperationSortMemberDepartmentRepository = 'App\EofficeApp\Cooperation\Repositories\CooperationSortMemberDepartmentRepository';
        $this->cooperationSubjectUserRepository          = 'App\EofficeApp\Cooperation\Repositories\CooperationSubjectUserRepository';
        $this->cooperationSubjectRoleRepository          = 'App\EofficeApp\Cooperation\Repositories\CooperationSubjectRoleRepository';
        $this->cooperationSubjectDepartmentRepository    = 'App\EofficeApp\Cooperation\Repositories\CooperationSubjectDepartmentRepository';
        $this->cooperationSubjectManageRepository        = 'App\EofficeApp\Cooperation\Repositories\CooperationSubjectManageRepository';
        $this->userRepository                            = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->attachmentService                         = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->userService                               = 'App\EofficeApp\User\Services\UserService';
    }

    /**
     * 获取协作分类的列表，没有查询
     *
     * @author 丁鹏
     *
     * @param  array            $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作分类数据
     */
    function getCooperationSortList($param) {
        $param      = $this->parseParams($param);
        $returnData = $this->response(app($this->cooperationSortRepository), 'getTotal', 'getCooperationSortListRepository', $param);
        $list       = $returnData["list"];
        if($returnData["total"]) {
            foreach ($list as $key => $value) {
                if($value->memberDept != "all" && $value->memberUser != "all" && $value->memberRole != "all") {
                    if(count($value->sortHasManyUser)) {
                        $hasUserName = "";
                        $userCollect = $value->sortHasManyUser->toArray();
                        foreach ($userCollect as $userKey => $userItem) {
                            // 如果 has_one_user 为 null 则跳过
                            if (!$userItem["has_one_user"]) {
                                continue;
                            }
                            if(count($userItem["has_one_user"]) && $userItem["has_one_user"]["user_name"]) {
                                $hasUserName .= $userItem["has_one_user"]["user_name"].",";
                            }
                        }
                        $hasUserName = trim($hasUserName,",");
                        if($hasUserName) {
                            $list[$key]["sortHasManyUserName"] = $hasUserName;
                        }
                    }
                    if(count($value->sortHasManyRole)) {
                        $hasRoleName = "";
                        $roleCollect = $value->sortHasManyRole->toArray();
                        foreach ($roleCollect as $roleKey => $roleItem) {
                            // 如果 has_one_role 为 null 则跳过
                            if (!$roleItem["has_one_role"]) {
                                continue;
                            }
                            if(count($roleItem["has_one_role"]) && $roleItem["has_one_role"]["role_name"]) {
                                $hasRoleName .= $roleItem["has_one_role"]["role_name"].",";
                            }
                        }
                        $hasRoleName = trim($hasRoleName,",");
                        if($hasRoleName) {
                            $list[$key]["sortHasManyRoleName"] = $hasRoleName;
                        }
                    }
                    if(count($value->sortHasManyDept)) {
                        $hasDeptName = "";
                        $deptCollect = $value->sortHasManyDept->toArray();
                        foreach ($deptCollect as $deptKey => $deptItem) {
                            // 如果 has_one_dept 为 null 则跳过
                            if (!$deptItem["has_one_dept"]) {
                                continue;
                            }
                            if(count($deptItem["has_one_dept"]) && $deptItem["has_one_dept"]["dept_name"]) {
                                $hasDeptName .= $deptItem["has_one_dept"]["dept_name"].",";
                            }
                        }
                        $hasDeptName = trim($hasDeptName,",");
                        if($hasDeptName) {
                            $list[$key]["sortHasManyDeptName"] = $hasDeptName;
                        }
                    }
                }
            }
        }
        $returnData["list"] = $list;
        return $returnData;
    }

    /**
     * 新建协作分类
     *
     * @author 丁鹏
     *
     * @param  array            $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作分类数据
     */
    function addCooperationSort($data) {
        $updateData                           = $data;
        $updateData["cooperation_sort_order"] = isset($data["cooperation_sort_order"]) ? $data["cooperation_sort_order"]:0;
        $updateData["cooperation_sort_time"]  = date('Y-m-d H:i:s');
        // 协作分类的权限范围为全体的处理
        if(isset($data["member_user"]) && $data["member_user"] == 'all') {
            $updateData['member_user'] = "all";
            unset($data['member_user']);
        } else {
            $updateData['member_user'] = "";
        }
        if(isset($data["member_dept"]) && $data["member_dept"] == 'all') {
            $updateData['member_dept'] = "all";
            unset($data['member_dept']);
        } else {
            $updateData['member_dept'] = "";
        }
        if(isset($data["member_role"]) && $data["member_role"] == 'all') {
            $updateData['member_role'] = "all";
            unset($data['member_role']);
        } else {
            $updateData['member_role'] = "";
        }
        $sortData              = array_intersect_key($updateData,array_flip(app($this->cooperationSortRepository)->getTableColumns()));
        $cooperationSortObject = app($this->cooperationSortRepository)->insertData($sortData);
        $sortId                = $cooperationSortObject->cooperation_sort_id;
        $member_user           = isset($data["member_user"]) ? $data["member_user"]:"";
        $member_role           = isset($data["member_role"]) ? $data["member_role"]:"";
        $member_dept           = isset($data["member_dept"]) ? $data["member_dept"]:"";
        // 插入协作分类权限数据
        if (!empty($member_user)) {
            $userData = [];
            foreach (array_filter(explode(',', trim($member_user,","))) as $v) {
                $userData[] = ['cooperation_sort_id' => $sortId, 'user_id' => $v];
            }
            app($this->cooperationSortMemberUserRepository)->insertMultipleData($userData);
        }
        if (!empty($member_role)) {
            $roleData = [];
            foreach (array_filter(explode(',', trim($member_role,","))) as $v) {
                $roleData[] = ['cooperation_sort_id' => $sortId, 'role_id' => $v];
            }
            app($this->cooperationSortMemberRoleRepository)->insertMultipleData($roleData);
        }
        if (!empty($member_dept)) {
            $deptData = [];
            foreach (array_filter(explode(',', trim($member_dept,","))) as $v) {
                $deptData[] = ['cooperation_sort_id' => $sortId, 'dept_id' => $v];
            }
            app($this->cooperationSortMemberDepartmentRepository)->insertMultipleData($deptData);
        }
        return $sortId;
    }

    /**
     * 编辑协作分类
     *
     * @author 丁鹏
     *
     * @param  array            $data [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作分类数据
     */
    function modifyCooperationSort($data,$sortId) {
        $updateData = $data;
        // 协作分类的权限范围为全体的处理
        if(isset($data["member_user"]) && $data["member_user"] == 'all') {
            $updateData['member_user'] = "all";
            unset($data['member_user']);
        } else {
            $updateData['member_user'] = "";
        }
        if(isset($data["member_dept"]) && $data["member_dept"] == 'all') {
            $updateData['member_dept'] = "all";
            unset($data['member_dept']);
        } else {
            $updateData['member_dept'] = "";
        }
        if(isset($data["member_role"]) && $data["member_role"] == 'all') {
            $updateData['member_role'] = "all";
            unset($data['member_role']);
        } else {
            $updateData['member_role'] = "";
        }
        $sortData = array_intersect_key($updateData,array_flip(app($this->cooperationSortRepository)->getTableColumns()));
        app($this->cooperationSortRepository)->updateData($sortData, ['cooperation_sort_id' => $sortId]);
        // 先删除已有协作分类权限数据
        $where = ['cooperation_sort_id' => [$sortId]];
        app($this->cooperationSortMemberUserRepository)->deleteByWhere($where);
        app($this->cooperationSortMemberRoleRepository)->deleteByWhere($where);
        app($this->cooperationSortMemberDepartmentRepository)->deleteByWhere($where);
        $member_user = isset($data["member_user"]) ? $data["member_user"]:"";
        $member_role = isset($data["member_role"]) ? $data["member_role"]:"";
        $member_dept = isset($data["member_dept"]) ? $data["member_dept"]:"";
        // 插入协作分类权限数据
        if (!empty($member_user)) {
            $userData = [];
            foreach (array_filter(explode(',', trim($member_user,","))) as $v) {
                $userData[] = ['cooperation_sort_id' => $sortId, 'user_id' => $v];
            }
            app($this->cooperationSortMemberUserRepository)->insertMultipleData($userData);
        }
        if (!empty($member_role)) {
            $roleData = [];
            foreach (array_filter(explode(',', trim($member_role,","))) as $v) {
                $roleData[] = ['cooperation_sort_id' => $sortId, 'role_id' => $v];
            }
            app($this->cooperationSortMemberRoleRepository)->insertMultipleData($roleData);
        }
        if (!empty($member_dept)) {
            $deptData = [];
            foreach (array_filter(explode(',', trim($member_dept,","))) as $v) {
                $deptData[] = ['cooperation_sort_id' => $sortId, 'dept_id' => $v];
            }
            app($this->cooperationSortMemberDepartmentRepository)->insertMultipleData($deptData);
        }
        return "1";
    }

    /**
     * 删除协作分类
     *
     * @author 丁鹏
     *
     * @param  string            $sortId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 删除结果
     */
    function destroyCooperationSort($sortIdString) {
        foreach (explode(',', trim($sortIdString,",")) as $key=>$sortId) {
            if($sortDataObject = app($this->cooperationSortRepository)->cooperationSortData($sortId)) {
                // 删除协作分类权限
                $where = ['cooperation_sort_id' => [$sortId]];
                app($this->cooperationSortMemberUserRepository)->deleteByWhere($where);
                app($this->cooperationSortMemberRoleRepository)->deleteByWhere($where);
                app($this->cooperationSortMemberDepartmentRepository)->deleteByWhere($where);
                app($this->cooperationSortRepository)->deleteById($sortId);
                // 删除关联的协作主题
                if(count($sortDataObject->sortHasManySubjectList)) {
                    $subjectList = $sortDataObject->sortHasManySubjectList;
                    foreach ($subjectList as $key => $value) {
                        $this->deleteCooperationSubjectRealize($value->subject_id);
                    }
                }
            }
        }
        return "1";
    }

    /**
     * 获取某条协作分类详情
     *
     * @author 丁鹏
     *
     * @param  string            $sortId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作分类详情数据
     */
    function cooperationSortData($sortId) {
        if($result = app($this->cooperationSortRepository)->cooperationSortData($sortId)) {
            if(count($result->sortHasManyUser)){
                $sort_user = $result->sortHasManyUser->pluck("user_id");
            }
            if(count($result->sortHasManyRole)){
                $sort_role = $result->sortHasManyRole->pluck("role_id");
            }
            if(count($result->sortHasManyDept)){
                $sort_dept = $result->sortHasManyDept->pluck("dept_id");
            }
            $result = $result->toArray();
            if(isset($sort_user))
                $result["user_id"] = $sort_user;
            if(isset($sort_role))
                $result["role_id"] = $sort_role;
            if(isset($sort_dept))
                $result["dept_id"] = $sort_dept;
            return $result;
        }
        return ['code' => ['0x007003','cooperation']];
    }

    /**
     * 获取某条协作主题的有权限人员。走的是用户模块的那个查询的函数 getConformScopeUserList
     * 这里默认将【管理人员】【创建人员】也加入权限计算结果中，通过参数 relate_manage_user 控制
     * 这个函数是 service 内部调用的函数，没有路由可以访问
     *
     * @author 丁鹏
     *
     * @param  array            $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作主题数据
     */
    function getCooperationPermissionScopeUser($param) {
        $relateManageUser = "relate";
        if(isset($param["relate_manage_user"])) {
            $relateManageUser = $param["relate_manage_user"];
        }
        if($detailResult = app($this->cooperationSubjectRepository)->getCooperationSubjectDetail($param["subject_id"])) {
            $subjectCreater = $detailResult->subject_creater;
            if(count($detailResult->subjectHasManyUser)){
                $cooperation_user = $detailResult->subjectHasManyUser->pluck("user_id");
            }
            if(count($detailResult->subjectHasManyRole)){
                $cooperation_role = $detailResult->subjectHasManyRole->pluck("role_id");
            }
            if(count($detailResult->subjectHasManyDept)){
                $cooperation_dept = $detailResult->subjectHasManyDept->pluck("dept_id");
            }
            if(count($detailResult->subjectHasManyManage)){
                $cooperation_manage_user = $detailResult->subjectHasManyManage->pluck("user_id");
            }
            // 如果没有参与人
            if(!isset($cooperation_user) && !isset($cooperation_role) && !isset($cooperation_dept)) {
                if($relateManageUser == "relate") {
                    if(!isset($cooperation_manage_user) && !isset($subjectCreater)) {
                        return [];
                    }
                } else{
                    return [];
                }
            }
            // 查所有范围内的人员
            $getUserParam = [
                "fields"     => ["user_id","user_name"],
                "page"       => "0",
                "returntype" => "object",
            ];
            // 全部人员
            if($detailResult->subject_user == "all" || $detailResult->subject_dept == "all" || $detailResult->subject_role == "all") {
            } else {
                if($relateManageUser == "relate") {
                    $userPermissionArray = [];
                    if(isset($cooperation_user)) {
                        foreach ($cooperation_user->toArray() as $keyUser => $valueUser) {
                            array_push($userPermissionArray, $valueUser);
                        }
                    }
                    if(isset($cooperation_manage_user)) {
                        foreach ($cooperation_manage_user->toArray() as $keyManager => $valueManager) {
                            array_push($userPermissionArray, $valueManager);
                        }
                    }
                    if(isset($subjectCreater)) {
                        array_push($userPermissionArray, $subjectCreater);
                    }
                    $getUserParam["search"]["user_id"] = $userPermissionArray;
                } else {
                    if(isset($cooperation_user)) {
                        $getUserParam["search"]["user_id"] = $cooperation_user;
                    }
                }
                if(isset($cooperation_role)) {
                    $getUserParam["search"]["role_id"] = $cooperation_role;
                }
                if(isset($cooperation_dept)) {
                    $getUserParam["search"]["dept_id"] = $cooperation_dept;
                }
            }
            $permissionUserInfo = app($this->userRepository)->getConformScopeUserList($getUserParam);
            return $permissionUserInfo;
        } else {
            return "";
        }
    }

     /**
     * 根据权限范围内的人员，更新某条协作的 cooperation_purview 表
     *
     * @author 丁鹏
     *
     * @param  array            $param : ["subject_id"=>$subjectId]
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作主题数据
     */
    function updateCooperationPurviewData($param) {
        // 新权限范围下的人员
        if($newScopeUser = $this->getCooperationPermissionScopeUser($param)) {
            $newScopeUserArray = $newScopeUser->pluck("user_id")->toArray();
            // 已有的权限人员表数据
            $oldPermissionUser = app($this->cooperationPurviewRepository)->getPermissionUserBySubjectId($param["subject_id"]);
            $oldPermissionUserArray = $oldPermissionUser->pluck("user_id")->toArray();
            // 这个是旧的里面没有，要插入的
            $insertUserArray = array_diff($newScopeUserArray,$oldPermissionUserArray);
            // 这个是新的里面没有的，要删掉
            $deleteUserArray = array_diff($oldPermissionUserArray,$newScopeUserArray);
            // 插入
            $insertUserData = [];
            foreach (array_filter($insertUserArray) as $v) {
                $insertUserData[] = ['subject_id' => $param["subject_id"], 'user_id' => $v, 'purview_order' => '1'];
            }
            app($this->cooperationPurviewRepository)->insertMultipleData($insertUserData);
            // 删除
            foreach ($deleteUserArray as $key => $value) {
                app($this->cooperationPurviewRepository)->deleteByWhere(["subject_id" => [$param["subject_id"]],"user_id" => [$value]]);
            }
            return $newScopeUserArray;
        } else {
            return "";
        }
    }

    /**
     * userservice 调用协作的函数，为新建的用户，添加 cooperation_purview 表信息
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function newUserAddCooperationPurview($param) {
        if(!isset($param["user_id"]) || !$param["user_id"]) {
            return "";
        }
        $listParam = [
            "dept_id" => isset($param["dept_id"]) ? $param["dept_id"] : "",
            "role_id" => isset($param["role_id"]) ? $param["role_id"] : "",
            "user_id" => isset($param["user_id"]) ? $param["user_id"] : "",
        ];
        $subjectList = app($this->cooperationSubjectRepository)->getHasPurviewSubjectList($listParam);
        if(count($subjectList)) {
            // 删除这个用户的所有数据，防止出现重复
            app($this->cooperationPurviewRepository)->deleteByWhere(["user_id" => [$param["user_id"]]]);
            // 插入新数据
            $insertUserData = [];
            foreach ($subjectList as $key => $subjectInfo) {
                $insertUserData[] = ['subject_id' => $subjectInfo["subject_id"], 'user_id' => $param["user_id"], 'purview_order' => '1'];
            }
            app($this->cooperationPurviewRepository)->insertMultipleData($insertUserData);
        }
        return "";
    }

    /**
     * 获取协作主题的列表
     *
     * @author 丁鹏
     *
     * @param  array            $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作主题数据
     */
    function getCooperationSubjectList($param) {
        $param = $this->parseParams($param);
        $data = $this->response(app($this->cooperationSubjectRepository), 'getCooperationSubjectListTotal', 'getCooperationSubjectList', $param);
        if (isset($data['list']) && !empty($data['list'])) {
            foreach($data['list'] as $key => $value) {
                if (isset($value['subject_has_many_purview'][0])) {
                    if (!$value['subject_has_many_purview'][0]['purview_time'] || $value['subject_has_many_purview'][0]['purview_time'] == "0000-00-00 00:00:00") {
                        $value['subject_has_many_purview'][0]['unread'] = 0;
                    }else{
                        $value['subject_has_many_purview'][0]['unread'] = 1;
                    }
                }
                if (isset($param['revert']) && isset($value['subject_has_many_revert']) && empty($value['subject_has_many_revert'])) {
                    unset($data['list'][$key]);
                }
            }
        }
        if (isset($param['revert'])) {
            $data['list'] = array_values($data['list']);
            $data['total'] = count($data['list']);
        }
        return $data;
    }

    /**
     * 获取有权限的【协作主题列表】所属的【协作类别列表】【这个路由，用在新建协作页面，在有权限的类别下新建协作】
     *
     * @author 丁鹏
     *
     * @param  array            $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作主题数据
     */
    function getPermissionSubjectRelationSortList($data) {
        $data = $this->parseParams($data);
        return app($this->cooperationSortRepository)->getPermissionSubjectRelationSortList($data);
    }

    /**
     * 新建协作主题
     *
     * @author 丁鹏
     *
     * @param  array            $data [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作主题数据
     */
    function addCooperationSubject($data) {
        // 调用 addAndModifyOperationCooperationData ： 新建/编辑协作主题的数据操作，代码段形式封装
        return $this->addAndModifyOperationCooperationData($data,"");
    }

    /**
     * 编辑协作主题
     *
     * @author 丁鹏
     *
     * @param  array            $data [description]
     * @param  string           $subjectId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作主题数据
     */
    function modifyCooperationSubject($data,$subjectId) {
        // 调用 addAndModifyOperationCooperationData ： 新建/编辑协作主题的数据操作，代码段形式封装
        return $this->addAndModifyOperationCooperationData($data,$subjectId);
    }

    /**
     * 新建/编辑协作主题的数据操作，代码段形式封装
     *
     * @author 丁鹏
     *
     * @param  array            $data [description]
     * @param  string           $subjectId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作主题数据
     */
    function addAndModifyOperationCooperationData($data,$subjectId) {

        $updateData = $data;
        // 协作主题的权限范围为全体的处理
        if(isset($data["subject_user"]) && $data["subject_user"] == 'all') {
            $updateData['subject_user'] = "all";
            unset($data['subject_user']);
        } else {
            $updateData['subject_user'] = "";
        }
        if(isset($data["subject_dept"]) && $data["subject_dept"] == 'all') {
            $updateData['subject_dept'] = "all";
            unset($data['subject_dept']);
        } else {
            $updateData['subject_dept'] = "";
        }
        if(isset($data["subject_role"]) && $data["subject_role"] == 'all') {
            $updateData['subject_role'] = "all";
            unset($data['subject_role']);
        } else {
            $updateData['subject_role'] = "";
        }
        if(isset($data['subject_start'])) {
            $updateData['subject_start'] = $data['subject_start'];
        }
        if(isset($data['subject_end'])) {
            $updateData['subject_end'] = $data['subject_end'];
        }


        // subject_start 处理
        $subject_start = isset($updateData["subject_start"]) ? $updateData["subject_start"] : "";
        // subject_end 处理
        if(isset($updateData["subject_end"])) {
            if($updateData["subject_end"] == "") {
                $updateData["subject_end"] = NULL;
            }
        }
        if($subject_start) {
            if(isset($updateData["subject_end"]) && $updateData["subject_end"] !='') {
                if($updateData["subject_end"] < $updateData['subject_start']) {
                    return ['code' => ['0x007009','cooperation']];
                }
            }
        }else{
            return ['code' => ['0x007011','cooperation']];
        }

        $updateData["subject_start"] = $subject_start;
        if($subjectId) {
            $subjectData = array_intersect_key($updateData,array_flip(app($this->cooperationSubjectRepository)->getTableColumns()));
            app($this->cooperationSubjectRepository)->updateData($subjectData, ['subject_id' => $subjectId]);
            $remindMark = 'cooperation-modify';
        } else {
            $updateData["subject_time"] = date('Y-m-d H:i:s');
            $subjectData                = array_intersect_key($updateData,array_flip(app($this->cooperationSubjectRepository)->getTableColumns()));
            $cooperationSubjectObject   = app($this->cooperationSubjectRepository)->insertData($subjectData);
            $subjectId                  = $cooperationSubjectObject->subject_id;
            $remindMark                 = 'cooperation-start';
        }
        if(!$subjectId) {
            return ['code' => ['0x007001','cooperation']];
        }
        if(isset($data['attachments'])) {
            app($this->attachmentService)->attachmentRelation("cooperation_subject", $subjectId,$data['attachments']);
        }
        // 先删除已有协作主题权限数据
        $where = ['subject_id' => [$subjectId]];
        app($this->cooperationSubjectUserRepository)->deleteByWhere($where);
        app($this->cooperationSubjectRoleRepository)->deleteByWhere($where);
        app($this->cooperationSubjectDepartmentRepository)->deleteByWhere($where);
        app($this->cooperationSubjectManageRepository)->deleteByWhere($where);
        $subject_user   = isset($data["subject_user"]) ? $data["subject_user"]:"";
        $subject_role   = isset($data["subject_role"]) ? $data["subject_role"]:"";
        $subject_dept   = isset($data["subject_dept"]) ? $data["subject_dept"]:"";
        $subject_manage = isset($data["subject_manage"]) ? $data["subject_manage"]:"";
        // 插入协作主题权限数据
        if (!empty($subject_user)) {
            $userData = [];
            foreach (array_filter(explode(',', trim($subject_user,","))) as $v) {
                $userData[] = ['subject_id' => $subjectId, 'user_id' => $v];
            }
            app($this->cooperationSubjectUserRepository)->insertMultipleData($userData);
        }
        if (!empty($subject_role)) {
            $roleData = [];
            foreach (array_filter(explode(',', trim($subject_role,","))) as $v) {
                $roleData[] = ['subject_id' => $subjectId, 'role_id' => $v];
            }
            app($this->cooperationSubjectRoleRepository)->insertMultipleData($roleData);
        }
        if (!empty($subject_dept)) {
            $deptData = [];
            foreach (array_filter(explode(',', trim($subject_dept,","))) as $v) {
                $deptData[] = ['subject_id' => $subjectId, 'dept_id' => $v];
            }
            app($this->cooperationSubjectDepartmentRepository)->insertMultipleData($deptData);
        }
        if (!empty($subject_manage)) {
            $userData = [];
            foreach (array_filter(explode(',', trim($subject_manage,","))) as $v) {
                $userData[] = ['subject_id' => $subjectId, 'user_id' => $v];
            }
            app($this->cooperationSubjectManageRepository)->insertMultipleData($userData);
        }
        // 更新purview表的权限数据， $permissionUserArray 就是"权限范围内的人员"
        if($permissionUserArray = $this->updateCooperationPurviewData(["subject_id"=>$subjectId])) {
            if(isset($updateData['subject_start']) && strtotime($updateData['subject_start']) <= strtotime(date('Y-m-d H:i:s',time()))){
                // 发送消息提醒
                $subjectTitle = isset($data['subject_title']) ? $data['subject_title'] : '';
                $subjectCreater = isset($data['subject_creater']) ? $data['subject_creater'] : '';
                if($subjectCreater) {
                    // 取用户 name
                    $userName = app($this->userRepository)->getUserName($subjectCreater);
                }
                $sendData['remindMark']     = $remindMark;
                $sendData['toUser']         = $permissionUserArray;
                $sendData['contentParam']   = ['cooperationName'=>$subjectTitle, 'userName'=>$userName];
                $sendData['stateParams']    = ["subject_id" => $subjectId];
                Eoffice::sendMessage($sendData);
            }
        }
        return $subjectId;
    }

    /**
     * 删除协作主题
     *
     * @author 丁鹏
     *
     * @param  string            $subjectId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 删除结果
     */
    function destroyCooperationSubject($subjectId,$data) {
        // 先获取详情，判断权限
        if($detailResult = app($this->cooperationSubjectRepository)->getCooperationSubjectDetail($subjectId,$data)) {
            // 判断删除权限
            if(!count($detailResult->subjectHasManyManageForPower) && $detailResult->subject_creater != $data["user_id"]) {
                return ['code' => ['0x000006','common']];
            }
            return $this->deleteCooperationSubjectRealize($subjectId);
        }
    }

    /**
     * 实现删除协作的数据库操作
     * @param  [type] $subjectId [description]
     * @return [type]            [description]
     */
    function deleteCooperationSubjectRealize($subjectId) {
        app($this->cooperationSubjectRepository)->deleteById($subjectId);
        // 删除已有协作主题权限数据
        $where = ['subject_id' => [$subjectId]];
        app($this->cooperationSubjectUserRepository)->deleteByWhere($where);
        app($this->cooperationSubjectRoleRepository)->deleteByWhere($where);
        app($this->cooperationSubjectDepartmentRepository)->deleteByWhere($where);
        app($this->cooperationSubjectManageRepository)->deleteByWhere($where);
        // 删除purview表的权限数据
        app($this->cooperationPurviewRepository)->deleteByWhere($where);
        return "1";
    }

    /**
     * 获取某条协作主题详情
     *
     * @author 丁鹏
     *
     * @param  string            $subjectId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作主题详情数据
     */
    function cooperationSubjectData($subjectId,$data) {
        if($detailResult = app($this->cooperationSubjectRepository)->getCooperationSubjectDetail($subjectId,$data)) {
            if($this->cooperationVerifySubjectPurview($subjectId,$data)) {
                // 判断编辑权限
                if(isset($data["verify"]) && $data["verify"] == "edit") {
                    if(!count($detailResult->subjectHasManyManageForPower) && $detailResult->subject_creater != $data["user_id"]) {
                        return ['code' => ['0x000006','common']];
                    }
                }
                if(count($detailResult->subjectHasManyUser)){
                    $cooperation_user = $detailResult->subjectHasManyUser->pluck("user_id");
                }
                if(count($detailResult->subjectHasManyRole)){
                    $cooperation_role = $detailResult->subjectHasManyRole->pluck("role_id");
                }
                if(count($detailResult->subjectHasManyDept)){
                    $cooperation_dept = $detailResult->subjectHasManyDept->pluck("dept_id");
                }
                if(count($detailResult->subjectHasManyManage)){
                    $cooperation_manage_user = $detailResult->subjectHasManyManage->pluck("user_id");
                }
                $result = $detailResult->toArray();
                if(isset($cooperation_user)) {
                    $result["user_id"] = $cooperation_user;
                }
                if(isset($cooperation_role)) {
                    $result["role_id"] = $cooperation_role;
                }
                if(isset($cooperation_dept)) {
                    $result["dept_id"] = $cooperation_dept;
                }
                if(isset($cooperation_manage_user)) {
                    $result["manage_id"] = $cooperation_manage_user;
                }
                // 如果没有参与人
                if(!isset($cooperation_user) && !isset($cooperation_role) && !isset($cooperation_dept) && $detailResult->subject_user != "all" && $detailResult->subject_dept != "all" && $detailResult->subject_role != "all") {
                    $result["permissionUserInfo"] = [];
                } else {
                    // 查所有范围内的人员
                    $getUserParam = [
                        "fields"     => ["user_id","user_name"],
                        "page"       => "0",
                        "returntype" => "object",
                    ];
                    // 全部人员
                    if($detailResult->subject_user == "all" || $detailResult->subject_dept == "all" || $detailResult->subject_role == "all") {
                    } else {
                        if(isset($cooperation_user)) {
                            $getUserParam["search"]["user_id"] = $cooperation_user;
                        }
                        if(isset($cooperation_role)) {
                            $getUserParam["search"]["role_id"] = $cooperation_role;
                        }
                        if(isset($cooperation_dept)) {
                            $getUserParam["search"]["dept_id"] = $cooperation_dept;
                        }
                    }
                    $permissionUserInfo = app($this->userRepository)->getConformScopeUserList($getUserParam);
                    $result["permissionUserInfo"] = $permissionUserInfo;
                }
                $result["attachments"] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'cooperation_subject', 'entity_id'=>$subjectId]);
                if(isset($data["verify"]) && $data["verify"] == "edit") {
                } else {
                    $result["subject_explain"] = str_replace(["\n","\r"], ["<br>","<br>"], $result["subject_explain"]);
                }
                return $result;
            }
            // 没有权限
            return ['code' => ['0x000006','common']];
            // return ['code' => ['0x007006','cooperation']];
        }
        return ['code' => ['0x007005','cooperation']];
    }

    /**
     * 验证协作主题的权限，用在获取详情前
     * 这是个service内部使用的函数，没有路由调用
     * 这里符合范围的人员包括 有权限参与的、创建者、管理者
     *
     * @author 丁鹏
     *
     * @param  string            $subjectId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return bool 有权限返回true，没权限返回false
     */
    function cooperationVerifySubjectPurview($subjectId,$data) {
        $param["user_id"] = isset($data["user_id"]) ? $data["user_id"]:"";
        $param["role_id"] = isset($data["role_id"]) ? $data["role_id"]:"";
        $param["dept_id"] = isset($data["dept_id"]) ? $data["dept_id"]:"";
        if(count(app($this->cooperationSubjectRepository)->verifySubjectPurview($subjectId,$param))) {
            return true;
        }
        return false;
    }

    /**
     * 更新用户最后查看此协作主题的时间
     *
     * @author 丁鹏
     *
     * @param  array $data 传入 subject_id [协作主题id] , user_id [用户id]
     *
     * @since  2015-10-16 创建
     *
     * @return bool        是否更新成功
     */
    function updateCooperationSubjectViewTime($data,$subjectId) {
        $purviewInfo = app($this->cooperationPurviewRepository)->getCooperationPurviewDetail(['subject_id' => [$subjectId],'user_id' => [$data['user_id']]]);
        if($purviewInfo) {
            app($this->cooperationPurviewRepository)->updateData(['purview_time' => date('Y-m-d H:i:s')], ['purview_id' => $purviewInfo['purview_id']]);
            $where = ['purview_id' => [$purviewInfo['purview_id'],"!="],'subject_id' => [$subjectId],'user_id' => [$data['user_id']]];
            app($this->cooperationPurviewRepository)->deleteByWhere($where);
            return $purviewInfo;
        } else {
            return app($this->cooperationPurviewRepository)->insertData(['purview_time' => date('Y-m-d H:i:s'),'subject_id' => $subjectId,'user_id' => $data['user_id']]);
        }
    }

    /**
     * 将某条协作设为关注/取消关注
     *
     * @author 丁鹏
     *
     * @param  array $data 传入 subject_id [协作主题id] , user_id [用户id]
     *
     * @since  2015-10-16 创建
     *
     * @return bool        是否更新成功
     */
    function followCooperationSubject($data,$subjectId) {
        $important = 0;
        if(isset($data["purview_important"])) {
            $important = $data["purview_important"];
        }
        $purviewInfo = app($this->cooperationPurviewRepository)->getCooperationPurviewDetail(['subject_id' => [$subjectId],'user_id' => [$data['user_id']]]);
        if($purviewInfo) {
            return app($this->cooperationPurviewRepository)->updateData(['purview_import' => $important], ['subject_id' => $subjectId,'user_id' => $data['user_id']]);
        } else {
            return app($this->cooperationPurviewRepository)->insertData(['purview_import' => $important,'subject_id' => $subjectId,'user_id' => $data['user_id'],'purview_time' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * 获取有权限的协作类别列表
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return [type] [description]
     */
    function getPermissionCooperationSort($data) {
        return app($this->cooperationSortRepository)->getPermissionCooperationSortList($data);
    }

    /**
     * 获取协作回复列表
     *
     * @author 丁鹏
     *
     * @param  array            $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array       获取协作回复列表结果数据
     */
    function getCooperationRevertAllService($param,$subjectId) {
        // 先验证协作主题的权限，有主题权限才能获取主题的回复列表
        if($this->cooperationVerifySubjectPurview($subjectId,$param)) {
            $param = $this->parseParams($param);
            $param["subject_id"] = $subjectId;
            $returnData = $this->response(app($this->cooperationRevertRepository),'getCooperationRevertAllRepositoryTotal','getCooperationRevertAllRepository',$param);
            $list = $returnData["list"];
            if($returnData["total"]) {
                foreach ($list as $key => $value) {
                    $list[$key]["attachments"] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'cooperation_revert', 'entity_id'=>$value["revert_id"]]);
                    if(count($value["first_revert_has_many_revert"])) {
                        foreach ($value["first_revert_has_many_revert"] as $revert_key => $revert_value) {
                            $list[$key]["first_revert_has_many_revert"][$revert_key]["attachments"] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'cooperation_revert', 'entity_id'=>$revert_value["revert_id"]]);
                        }
                    }
                }
            }
            $returnData["list"] = $list;
            return $returnData;
        }
        // 没有权限
        return ['code' => ['0x000006','common']];
        // return ['code' => ['0x007006','cooperation']];
    }

    /**
     * 获取协作某条回复详情
     *
     * @author 丁鹏
     *
     * @param  string            $revertId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return json 获取协作某条回复详情信息
     */
    public function getCooperationRevertDeatilService($revertId,$data) {
        if($detailResult = app($this->cooperationRevertRepository)->getCooperationRevertDetail($revertId,$data)) {
            // 这里验证协作主题的权限，有主题权限就能查看协作主题的回复
            if($this->cooperationVerifySubjectPurview($detailResult->subject_id,$data)) {
                return $detailResult->toArray();
            }
            // 没有权限
            return ['code' => ['0x000006','common']];
            // return ['code' => ['0x007006','cooperation']];
        }
        return ['code' => ['0x007008','cooperation']];
    }

    /**
     * 验证协作主题回复的编辑权限
     *
     * @author 丁鹏
     *
     * @param  string            $revertId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return bool 有权限返回true，没权限返回false
     */
    // function cooperationVerifySubjectRevertEditPurview($revertId) {
    //     if($this->loginUserId == 'admin') {
    //         return true;
    //     }
    //     else {
    //         if(count(app($this->cooperationSubjectRepository)->verifySubjectPurview($revertId))) {
    //             return true;
    //         }
    //         return false;
    //     }
    // }

    /**
     * 新建协作回复。支持一级或二级回复
     *
     * @author 丁鹏
     *
     * @param  array            $data [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array       新建回复结果数据
     */
    function createCooperationRevertService($data, $loginUserId) {
        $subjectId                   = $data['subject_id'];
        $insertData                  = $data;
        $revertParent                = isset($data["revert_parent"]) ? $data["revert_parent"]:0;
        $insertData["revert_parent"] = $revertParent;
        $insertData['revert_time']   = date('Y-m-d H:i:s');
        // 创建
        $revertData = array_intersect_key($insertData,array_flip(app($this->cooperationRevertRepository)->getTableColumns()));
        $result = app($this->cooperationRevertRepository)->insertData($revertData);
        $result = $result->toArray();
        // 更新协作主题的最后回复时间
        app($this->cooperationSubjectRepository)->updateData(['last_revert_time' => date('Y-m-d H:i:s')],['subject_id' => $subjectId]);
        // 更新权限表里的用户访问时间为 0000-00-00 00:00:00
        app($this->cooperationPurviewRepository)->updatePurviewData(['purview_time' => "0000-00-00 00:00:00", 'subject_id' => $subjectId], $loginUserId);
        // 附件处理
        if(isset($data['attachments'])) {
            app($this->attachmentService)->attachmentRelation("cooperation_revert",$result["revert_id"],$data['attachments']);
        }
        $subjectDetailResult = app($this->cooperationSubjectRepository)->getCooperationSubjectDetail($subjectId,[]);
        $subjectTitle        = $subjectDetailResult->subject_title;
        if($revertParent > 0) {
            // 回复某条评论，提醒类型为 toReply ，范围为 这条评论的创建者
            // 发送消息提醒
            $revertDetailResult = app($this->cooperationRevertRepository)->getCooperationRevertDetail($revertParent,[]);
            if (empty($revertDetailResult)) {
                return ['code' => ['0x007010','cooperation']];
            }
            $sendData['remindMark']   = "cooperation-toReply";
            $sendData['toUser']       = $revertDetailResult->revert_user;
            $sendData['contentParam'] = ['cooperationName'=>$subjectTitle];
            $sendData['stateParams']  = ["subject_id" => $subjectId];
            Eoffice::sendMessage($sendData);
        } else {
            // 评论某条协作主题，提醒类型为 reply ，范围为 有权限的用户
            // 获取 有权限用户
            $permissionUserArray = app($this->cooperationPurviewRepository)->getPermissionUserBySubjectId($subjectId);
            $permissionUserArray = $permissionUserArray->pluck("user_id")->toArray();
            // 发送消息提醒
            if(count($permissionUserArray)) {
                $sendData['remindMark']   = "cooperation-reply";
                $sendData['toUser']       = $permissionUserArray;
                $sendData['contentParam'] = ['cooperationName'=>$subjectTitle];
                $sendData['stateParams']  = ["subject_id" => $subjectId];
                Eoffice::sendMessage($sendData);
            }
        }
        return $result;
    }

    /**
     * 编辑一级回复
     *
     * @author 丁鹏
     *
     * @param  array            $data [description]
     * @param  string            $revertId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array       编辑一级回复结果数据
     */
    function editCooperationRevertFirstService($data, $revertId) {
        $subjectId = $data['subject_id'];
        $revertData = array_intersect_key($data,array_flip(app($this->cooperationRevertRepository)->getTableColumns()));
        $result = app($this->cooperationRevertRepository)->updateData($revertData, ['revert_id' => $revertId]);
        app($this->cooperationSubjectRepository)->updateData(['last_revert_time' => date('Y-m-d H:i:s')],['subject_id' => $subjectId]);
        // 附件处理
        if(isset($data['attachments'])) {
            app($this->attachmentService)->attachmentRelation("cooperation_revert",$revertId,$data['attachments']);
        }
        // 获取有权限用户
        $permissionUserArray = app($this->cooperationPurviewRepository)->getPermissionUserBySubjectId($subjectId);
        // 发送内部消息
        // $this->sendSms($permissionUserArray);
        return $result;
    }

    /**
     * 删除一级回复
     *
     * @author 丁鹏
     *
     * @param  array            $data [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array       删除一级回复结果数据
     */
    function deleteCooperationRevertFirstService($data,$revertId) {
        $result = app($this->cooperationRevertRepository)->deleteById($revertId);
        app($this->cooperationSubjectRepository)->updateData(['last_revert_time' => date('Y-m-d H:i:s')],['subject_id' => $data["subject_id"]]);
        return $result;
    }
    /**
     * 获取管理人员
     *
     * @author 李旭
     *
     * @param  array            $data [description]
     *
     * @since  2018-06-26 创建
     *
     * @return array
     */
    public function getCooperationManageService($data, $subjectId) {
        $result = app($this->cooperationSubjectRepository)->getCooperationManageUser($subjectId);
        $userId = array_column($result,'user_id');
        $subjectManage = array_unique(array_column($result,'subject_manage'));
        $manageUser = implode(',', array_unique(array_merge($userId, explode(',', isset($subjectManage[0]) ? $subjectManage[0] : ''))));
        return ['subject_manage' => $manageUser];
    }

    /**
     * 获取即将开始的协作
     *
     * @return array 处理后的消息数组
     */
    public function cooperationBeginRemind($interval)
    {
        $start  = date("Y-m-d H:i:s");
        $end    = date("Y-m-d H:i:s", strtotime("+$interval minutes -1 seconds"));
        $messages = [];
        $list = app($this->cooperationSubjectRepository)->listBeginCooperation($start,$end);
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                // 发送消息提醒
                $subjectTitle = isset($value['subject_title']) ? $value['subject_title'] : '';
                $subjectCreater = isset($value['subject_creater']) ? $value['subject_creater'] : '';
                if($subjectCreater) {
                    // 取用户 name
                    $userName = app($this->userRepository)->getUserName($subjectCreater);
                }
                $permissionUserArray = app($this->cooperationPurviewRepository)->getPermissionUserBySubjectId($value['subject_id']);
                $permissionUserArray = $permissionUserArray->pluck("user_id")->toArray();
                $messages[$key] = [
                    'remindMark'     => 'cooperation-start',
                    'toUser'         => $permissionUserArray,
                    'contentParam'   => ['cooperationName'=>$subjectTitle, 'userName'=>$userName],
                    'stateParams'    => ["subject_id" => $value['subject_id']]
                ];
            }
        }
        return $messages;
    }

    //判断是否有查看权限
    public function checkCooperationPermission($subjectId, $own) {
        $data['user_id'] = $own['user_id'] ?? '';
        $data['role_id'] = isset($own['role_id']) ? implode(',', $own['role_id']): '';
        $data['dept_id'] = $own['dept_id'] ?? '';
        $detailResult = app($this->cooperationSubjectRepository)->verifySubjectPurview($subjectId,$data);
        if (!$detailResult->isEmpty()) {
            return '1';
        }
        return '0';
    }

    public function getDiaryCooperation($userId, $date) {
        $deptRole = app($this->userService)->getUserDeptIdAndRoleIdByUserId($userId);
        $param = [
            'user_id' => $userId, 
            'dept_id' => $deptRole['dept_id'] ?? '',
            'role_id' => $deptRole['role_id'] ?? '',
            'date' => $date, 'revert' => true];
        return $this->getCooperationSubjectList($param);
    }

    /**
     * 置顶
     *
     * @author 丁鹏
     *
     * @param  string            $revertId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array       置顶结果数据
     */
    // function createCooperationRevertStickService($revertId) {
    //     return app($this->cooperationRevertRepository)->updateData(['revert_top' => '1'], ['revert_id' => $revertId]);
    // }

    /**
     * 取消置顶
     *
     * @author 丁鹏
     *
     * @param  string            $revertId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array       取消置顶结果数据
     */
    // function createCooperationRevertUnstickService($revertId) {
    //     return app($this->cooperationRevertRepository)->updateData(['revert_top' => '0'], ['revert_id' => $revertId]);
    // }

    /**
     * 获取相关文档列表
     *
     * @author 丁鹏
     *
     * @param  array            $data [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array       获取相关文档列表结果数据
     */
    // function getCooperationAboutDocumentService($data) {
    //     $result = app($this->cooperationRevertRepository)->getCooperationAboutDocumentRepository($data["subject_id"]);
    //     $cooperationSubjectDocumentIdString = "";
    //     foreach ($result as $key => $value) {
    //         $cooperationSubjectDocumentIdString .= $value["revert_document"];
    //     }
    //     $cooperationSubjectDocumentIdStringArray = explode(",",trim($cooperationSubjectDocumentIdString,","));
    //     $cooperationSubjectDocumentIdStringArray = array_unique($cooperationSubjectDocumentIdStringArray);
    //     $cooperationSubjectDocumentIdString      = "'".implode("','",$cooperationSubjectDocumentIdStringArray)."'";
    //     // 调用文档函数，拼接其他文档信息并返回
    //     // $cooperationSubjectDocumentData = getDocumentInfo($cooperationSubjectDocumentIdString);
    //     return $cooperationSubjectDocumentIdString;
    // }

    /**
     * 获取协作主题的某条回复的相关文档列表
     *
     * @author 丁鹏
     *
     * @param  array            $data [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array       获取协作主题的某条回复的相关文档列表结果数据
     */
    // function getCooperationRevertAboutDocumentService($data) {
    //     $result = app($this->cooperationRevertRepository)->getCooperationRevertAboutDocumentRepository($data["revert_id"]);
    //     // 调用文档函数，拼接其他文档信息并返回
    //     // $cooperationSubjectRevertDocumentData = getDocumentInfo($result);
    //     return $result;
    // }

    /**
     * 获取相关附件列表
     *
     * @author 丁鹏
     *
     * @param  array            $data [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array       获取相关附件列表结果数据
     */
    // function getCooperationAboutAttachmentService($data) {
    //     $result = app($this->cooperationRevertRepository)->getCooperationAboutAttachmentRepository($data["subject_id"]);
    //     $cooperationSubjectAttachmentIdString = "";
    //     $cooperationSubjectAttachmentNameString = "";
    //     foreach ($result as $key => $value) {
    //         $cooperationSubjectAttachmentIdString   .= $value["attachment_id"];
    //         $cooperationSubjectAttachmentNameString .= $value["attachment_name"];
    //     }
    //     $cooperationSubjectAttachmentIdArray   = explode(",",trim($cooperationSubjectAttachmentIdString,","));
    //     $cooperationSubjectAttachmentNameArray = explode("*",trim($cooperationSubjectAttachmentNameString,"*"));
    //     // 调用附件函数，拼接其他附件信息并返回
    //     // $cooperationSubjectAttachmentData = getAttachmentInfo([$cooperationSubjectAttachmentIdArray,$cooperationSubjectAttachmentNameArray]);
    //     return [$cooperationSubjectAttachmentIdArray,$cooperationSubjectAttachmentNameArray];
    // }

    /**
     * 获取协作主题的某条回复的相关附件列表
     *
     * @author 丁鹏
     *
     * @param  array            $data [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array       获取协作主题的某条回复的相关附件列表结果数据
     */
    // function getCooperationRevertAboutAttachmentService($data) {
    //     $result = app($this->cooperationRevertRepository)->getCooperationRevertAboutAttachmentRepository($data["revert_id"]);
    //     // 调用附件函数，拼接其他附件信息并返回
    //     // $cooperationSubjectRevertAttachmentData = getAttachmentInfo($result);
    //     return $result;
    // }
}
