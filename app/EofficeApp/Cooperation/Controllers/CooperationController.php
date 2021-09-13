<?php
namespace App\EofficeApp\Cooperation\Controllers;

use App\EofficeApp\Base\Controller;
use Illuminate\Http\Request;
use App\EofficeApp\Cooperation\Services\CooperationService;
use App\EofficeApp\Cooperation\Requests\CooperationRequest;

/**
 * 协作 controller
 * 这个类，用来：1、验证request；2、组织数据；3、调用service实现功能；[4、组织返回值]
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationController extends Controller
{
    /** @var object 协作区服务 */
    private $cooperationService;

    public function __construct(
        Request $request,
        CooperationService $cooperationService,
        CooperationRequest $cooperationRequest
    ) {
        parent::__construct();
        $this->cooperationService = $cooperationService;
        $this->formFilter($request, $cooperationRequest);
        $this->request = $request;
    }

    /**
     * 获取协作分类的列表，没有查询
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作分类数据
     */
    public function getCooperationSort(){
        $result = $this->cooperationService->getCooperationSortList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 新建协作分类
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作分类数据
     */
    public function createCooperationSort(){
        $data = $this->request->all();
        $result = $this->cooperationService->addCooperationSort($data);
        return $this->returnResult($result);
    }

    /**
     * 编辑协作分类
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作分类数据
     */
    public function editCooperationSort($sortId){
        $data = $this->request->all();
        $result = $this->cooperationService->modifyCooperationSort($data,$sortId);
        return $this->returnResult($result);
    }

    /**
     * 删除协作分类
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 删除结果
     */
    public function deleteCooperationSort($sortId){
        $result = $this->cooperationService->destroyCooperationSort($sortId);
        return $this->returnResult($result);
    }

    /**
     * 获取协作分类详情
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作分类结果
     */
    public function getCooperationSortDetail($sortId){
        $result = $this->cooperationService->cooperationSortData($sortId);
        return $this->returnResult($result);
    }

    /**
     * 获取协作主题的列表
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作主题数据
     */
    public function getCooperationSubject(){
        $param = [];
        $param = $this->request->all();
        $userInfo = $this->own;
        $param['user_id'] = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        $param['role_id'] = implode(',', isset($userInfo['role_id']) ? $userInfo['role_id'] : '');
        $param['dept_id'] = isset($userInfo['dept_id']) ? $userInfo['dept_id'] : '';
        $result = $this->cooperationService->getCooperationSubjectList($param);
        return $this->returnResult($result);
    }

    /**
     * 获取有权限的【协作主题列表】所属的【协作类别列表】【这个路由，用在新建协作页面，在有权限的类别下新建协作】
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作主题数据
     */
    public function getPermissionSubjectRelationSortList(){
        $param = $this->request->all();
        $userInfo = $this->own;
        $param['user_id'] = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        $param['role_id'] = implode(',', isset($userInfo['role_id']) ? $userInfo['role_id'] : '');
        $param['dept_id'] = isset($userInfo['dept_id']) ? $userInfo['dept_id'] : '';
        $result = $this->cooperationService->getPermissionSubjectRelationSortList($param);
        return $this->returnResult($result);
    }

    /**
     * 新建协作主题
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作主题数据
     */
    public function createCooperationSubject(){
        $data = $this->request->all();
        $result = $this->cooperationService->addCooperationSubject($data);
        return $this->returnResult($result);
    }

    /**
     * 编辑协作主题
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作主题数据
     */
    public function editCooperationSubject($subjectId){
        $data = $this->request->all();
        $result = $this->cooperationService->modifyCooperationSubject($data,$subjectId);
        return $this->returnResult($result);
    }

    /**
     * 删除协作主题
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 删除结果
     */
    public function deleteCooperationSubject($subjectId){
        $data = $this->request->all();
        $result = $this->cooperationService->destroyCooperationSubject($subjectId,$data);
        return $this->returnResult($result);
    }

    /**
     * 获取协作主题详情
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 协作主题结果
     */
    public function getCooperationSubjectDetail($subjectId){
        $param = [];
        $param = $this->request->all();
        $userInfo = $this->own;
        $param['user_id'] = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        $param['role_id'] = implode(',', isset($userInfo['role_id']) ? $userInfo['role_id'] : '');
        $param['dept_id'] = isset($userInfo['dept_id']) ? $userInfo['dept_id'] : '';
        $result = $this->cooperationService->cooperationSubjectData($subjectId, $param);
        return $this->returnResult($result);
    }

    /**
     * 获取有权限的协作类别列表
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 有权限的协作类别列表结果
     */
    public function getPermissionCooperationSortList(){
        $data = $this->request->all();
        $userInfo = $this->own;
        $data['user_id'] = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        $data['role_id'] = implode(',', isset($userInfo['role_id']) ? $userInfo['role_id'] : '');
        $data['dept_id'] = isset($userInfo['dept_id']) ? $userInfo['dept_id'] : '';
        $result = $this->cooperationService->getPermissionCooperationSort($data);
        return $this->returnResult($result);
    }

    /**
     * 更新用户最后查看此协作主题的时间，传入 subject_id [协作主题id] , user_id [用户id]
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return bool        是否更新成功
     */
    public function updateCooperationSubjectViewTime($subjectId) {
        $data = $this->request->all();
        $result = $this->cooperationService->updateCooperationSubjectViewTime($data,$subjectId);
        return $this->returnResult($result);
    }

    /**
     * 将某条协作设为关注/取消关注，传入 subject_id [协作主题id] , user_id [用户id]
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return bool        是否更新成功
     */
    public function followCooperationSubject($subjectId) {
        $data = $this->request->all();
        $result = $this->cooperationService->followCooperationSubject($data,$subjectId);
        return $this->returnResult($result);
    }


    /**
     * 获取协作回复列表
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 获取协作回复列表结果
     */
    public function getCooperationRevertAll($subjectId) {
        $data = $this->request->all();
        $userInfo = $this->own;
        if (isset($data['user_id'])) {
            $data['user_id'] = $data['user_id'];
        } else {
            $data['user_id'] = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        }
        $data['role_id'] = implode(',', isset($userInfo['role_id']) ? $userInfo['role_id'] : '');
        $data['dept_id'] = isset($userInfo['dept_id']) ? $userInfo['dept_id'] : '';
        
        $result = $this->cooperationService->getCooperationRevertAllService($data,$subjectId);
        return $this->returnResult($result);
    }

    /**
     * 获取协作某条回复详情
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 获取协作某条回复详情信息
     */
    public function getCooperationRevertDeatil($revertId) {
        $data = $this->request->all();
        $result = $this->cooperationService->getCooperationRevertDeatilService($revertId,$data);
        return $this->returnResult($result);
    }

    /**
     * 新建一级回复
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 新建一级回复结果
     */
    public function createCooperationRevert() {
        $data = $this->request->all();
        $result = $this->cooperationService->createCooperationRevertService($data, $this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 编辑一级回复
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 编辑一级回复结果
     */
    public function editCooperationRevertFirst($revertId) {
        $data = $this->request->all();
        $result = $this->cooperationService->editCooperationRevertFirstService($data,$revertId);
        return $this->returnResult($result);
    }

    /**
     * 删除一级回复
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 删除一级回复结果
     */
    public function deleteCooperationRevertFirst($revertId) {
        $data = $this->request->all();
        $result = $this->cooperationService->deleteCooperationRevertFirstService($data,$revertId);
        return $this->returnResult($result);
    }
    /**
     * 获取管理人员
     *
     * @author 李旭
     *
     * @since  2018-06-26 创建
     *
     * @return json
     */
    public function getCooperationManage($revertId) {
        $data = $this->request->all();
        $result = $this->cooperationService->getCooperationManageService($data,$revertId);
        return $this->returnResult($result);
    }

    public function checkCooperationPermission($subjectId) {
        $result = $this->cooperationService->checkCooperationPermission($subjectId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 置顶
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 置顶结果
     */
    // public function createCooperationRevertStick($revertId) {
    //     $result = $this->cooperationService->createCooperationRevertStickService($revertId);
    //     return $this->returnResult($result);
    // }

    /**
     * 取消置顶
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 取消置顶结果
     */
    // public function createCooperationRevertUnstick($revertId) {
    //     $result = $this->cooperationService->createCooperationRevertUnstickService($revertId);
    //     return $this->returnResult($result);
    // }

    /**
     * 获取相关文档列表
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 获取相关文档列表结果
     */
    // public function getCooperationAboutDocument() {
    //     $data = $this->request->all();
    //     $result = $this->cooperationService->getCooperationAboutDocumentService($data);
    //     return $this->returnResult($result);
    // }

    /**
     * 获取协作主题的某条回复的相关文档列表
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 获取协作主题的某条回复的相关文档列表结果
     */
    // public function getCooperationRevertAboutDocument() {
    //     $data = $this->request->all();
    //     $result = $this->cooperationService->getCooperationRevertAboutDocumentService($data);
    //     return $this->returnResult($result);
    // }

    /**
     * 获取相关附件列表
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 获取相关附件列表结果
     */
    // public function getCooperationAboutAttachment() {
    //     $data = $this->request->all();
    //     $result = $this->cooperationService->getCooperationAboutAttachmentService($data);
    //     return $this->returnResult($result);
    // }

    /**
     * 获取协作主题的某条回复的相关附件列表
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return json 获取协作主题的某条回复的相关附件列表结果
     */
    // public function getCooperationRevertAboutAttachment() {
    //     $data = $this->request->all();
    //     $result = $this->cooperationService->getCooperationRevertAboutAttachmentService($data);
    //     return $this->returnResult($result);
    // }
}
