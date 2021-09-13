<?php

namespace App\EofficeApp\Diary\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Diary\Requests\DiaryRequest;
use App\EofficeApp\Diary\Services\DiaryService;

/**
 * 微博控制器:提供微博模块相关请求
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class DiaryController extends Controller
{
    /**
     * 微博日志数据对象
     * @var object
     */
    private $diaryService;

    public function __construct(
        Request $request,
        DiaryService $diaryService,
        DiaryRequest $diaryRequest
        )
    {
        parent::__construct();
        $userInfo = $this->own;
        $this->userId = $userInfo['user_id']; // 用户id
        $this->request = $request;
        $this->diaryService = $diaryService;
        $this->formFilter($request, $diaryRequest);
    }

	/**
	 * 查询微博日志关注人
     *
	 * @return array 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
	 */
	public function getIndexDiaryAttention()
	{
        $result = $this->diaryService->getDiaryAttentionList($this->request->all(),$this->own);
        return $this->returnResult($result);
	}

    /**
     * 添加微博日志关注人
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function createDiaryAttention()
    {
        $result = $this->diaryService->createDiaryAttention($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 更新微博日志关注人
     *
     * @param  int|string $attentionIds 关注表id,多个用逗号隔开
     *
     * @return int|array 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function editDiaryAttention($attentionIds)
    {
        $result = $this->diaryService->updateDiaryAttention($attentionIds, $this->request->input('attention_status'), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 删除微博日志关注人
     *
     * @param  int|string $attentionIds 关注表id,多个用逗号隔开
     *
     * @return int|array 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function deleteDiaryAttention($attentionIds)
    {
        $result = $this->diaryService->deleteDiaryAttention($attentionIds, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 查询微博日志浏览记录
     *
     * @return json 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getIndexDiaryVisits()
    {
        $result = $this->diaryService->getDiaryVisitList($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 添加微博日志浏览记录
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function createDiaryVisits()
    {
        $result = $this->diaryService->saveDiaryVisit($this->request->input('visit_to_person'), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 查询微博日志
     *
     * @return json 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getIndexDiarys()
    {
        $result = $this->diaryService->getDiaryList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 查询我的微博日志
     *
     * @return json 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getMineDiarys()
    {
        $result = $this->diaryService->getMineDiarys($this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 添加微博日志
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function createDiarys()
    {
        $result = $this->diaryService->createDiary($this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 编辑微博日志
     *
     * @param  $diaryId 微博日志id
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function editDiarys($diaryId)
    {
        $result = $this->diaryService->updateDiary($diaryId, $this->request->input('diary_content'));
        return $this->returnResult($result);
    }

    /**
     * 删除微博日志
     *
     * @param  in|string $diaryId 微博日志id,多个用逗号隔开
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function deleteDiarys($diaryId)
    {
        $result = $this->diaryService->deleteDiarys($diaryId,$this->own);
        return $this->returnResult($result);
    }

    /**
     * 查询微博日志详情
     *
     * @param  $diaryId 微博日志id
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getDiarys($diaryId)
    {
        $result = $this->diaryService->getDiaryDetail($diaryId, $this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 查询微博日志报表
     *
     * @param  $diaryId 微博日志id
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getIndexDiaryReports()
    {
        $result = $this->diaryService->getDiaryReport($this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**getUserDiaryPlan
     * 查询微博日志回复
     *
     * @param  $diaryId 微博日志id
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getIndexDiaryReplys($diaryId)
    {
        $result = $this->diaryService->getDiaryReplysList($diaryId, $this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 添加微博日志回复
     *
     * @param  $diaryId 微博日志id
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function createDiaryReplys($diaryId)
    {
        $result = $this->diaryService->createDiaryReply($diaryId, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 删除微博日志回复
     *
     * @param  $diaryId 微博日志id
     * @param int $replyId 返回结果
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function deleteDiaryReplys($diaryId, $replyId)
    {
        $result = $this->diaryService->deleteReplys($diaryId,$replyId,$this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取我的关注
     *
     * @param  string $userId 用户id
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-12-09
     */
    public function getMyAttention($userId)
    {
        $param = $this->request->all();
        $result = $this->diaryService->getMyAttention($this->userId, $param);
        return $this->returnResult($result);
    }

    /**
     * 获取访问记录
     *
     * @param  string $userId 用户id
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-12-21
     */
    public function getVisitRecord($userId)
    {
        $result = $this->diaryService->getVisitRecord($this->userId,$this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 添加微博便签
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-04-15
     */
    public function createDiaryMemo()
    {
        $data = [
            'memo_content' => $this->request->input('memo_content', ''),
            'memo_creator' => $this->request->input('memo_creator', $this->userId),
        ];
        $result = $this->diaryService->createDiaryMemo($data);
        return $this->returnResult($result);
    }

    /**
     * 获取微博便签详情
     *
     * @param  string $userId 创建人id
     *
     * @return array 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-04-15
     */
    public function getDiaryMemo($userId)
    {
        $result = $this->diaryService->getDiaryMemo($userId);
        return $this->returnResult($result);
    }

    /**
     * 编辑微博便签
     *
     * @param  string $userId 创建人id
     *
     * @return array 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-04-15
     */
    public function editDiaryMemo($userId)
    {
        $data = [
            'memo_content' => $this->request->input('memo_content', ''),
            'memo_creator' => $userId,
        ];
        $result = $this->diaryService->editDiaryMemo($data);
        return $this->returnResult($result);
    }

    /**
     * 计划模板设置，获取计划类型
     * @return [type] [description]
     */
    public function getDiaryTemplateType()
    {
        $result = $this->diaryService->getDiaryTemplateType($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 模板设置，获取模板设置信息
     * @return [type] [description]
     */
    public function getDiaryTemplateSetList()
    {
        $result = $this->diaryService->getDiaryTemplateSetList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 模板设置，保存模板设置信息
     * @return [type] [description]
     */
    public function saveDiaryTemplateSet()
    {
        $result = $this->diaryService->saveDiaryTemplateSet($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 模板设置，获取用户模式下的模板设置信息
     * @return [type] [description]
     */
    public function getUserModalDiaryTemplateSetList()
    {
        $result = $this->diaryService->getUserModalDiaryTemplateSetList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 模板设置，保存用户模式下的模板设置信息
     * @return [type] [description]
     */
    public function saveUserModalDiaryTemplateSet()
    {
        $result = $this->diaryService->saveUserModalDiaryTemplateSet($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 工作计划，获取某条工作计划
     * @return [type] [description]
     */
    public function getUserDiaryPlan()
    {
        $result = $this->diaryService->getUserDiaryPlan($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 工作计划，保存工作计划
     * @return [type] [description]
     */
    public function saveUserDiaryPlan()
    {
        $result = $this->diaryService->saveUserDiaryPlan($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 工作计划，获取某个用户的计划模板
     * @return [type] [description]
     */
    public function getUserDiaryPlanTemplate()
    {
        $result = $this->diaryService->getUserDiaryPlanTemplate($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取web端微博客户联系记录,
     * @param  [type] $userId    当前微博列表数据的创建者用户id
     * @param  [type] $diaryDate 日志日期
     * @return [type]            联系记录数据
     */
    public function getContactRecord($recordId,$diaryDate)
    {
        $userInfo = $this->own;     // 用户信息
        $result = $this->diaryService->getContactRecord($recordId,$diaryDate);
        return $this->returnResult($result);
    }

    /**
     * 改变微博点赞状态 
     * @return [type] [description]
     */
    public function getDiaryLike()
    {
        $result = $this->diaryService->getDiaryLike($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取微博设置
     * @param $params
     * @return \App\EofficeApp\Base\json
     */
    public function getPermission()
    {
        $result = $this->diaryService->getPermission($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * @param $params
     * @return \App\EofficeApp\Base\json
     */
    public function modifyPermission()
    {
        $result = $this->diaryService->modifyPermission($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 新增关注分组
     * @return \App\EofficeApp\Base\json
     */
    public function addAttentionGroup()
    {
        $result = $this->diaryService->addAttentionGroup($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取关注分组列表
     * @return \App\EofficeApp\Base\json
     */
    function getAttentionGroupList()
    {
        $result = $this->diaryService->getAttentionGroupList($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取关注分组信息
     * @param $group_id
     * @return \App\EofficeApp\Base\json
     */
    function getAttentionGroupInfo($groupId)
    {
        $result = $this->diaryService->getAttentionGroupInfo($groupId,$this->own);
        return $this->returnResult($result);
    }

    /**
     * 保存关注分组信息
     * @param $group_id
     * @return \App\EofficeApp\Base\json
     */
    function saveAttentionGroupInfo($groupId)
    {
        $result = $this->diaryService->saveAttentionGroupInfo($groupId,$this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 删除关注分组信息
     * @param $group_id
     * @return \App\EofficeApp\Base\json
     */
    function deleteAttentionGroup($groupId)
    {
        $result = $this->diaryService->deleteAttentionGroup($groupId,$this->own);
        return $this->returnResult($result);
    }

    /**
     * 关注分组增加用户
     * @return \App\EofficeApp\Base\json
     */
    function addAttentionGroupUser()
    {
        $result = $this->diaryService->addAttentionGroupUser($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取多个用户的多个分组信息
     * @return \App\EofficeApp\Base\json
     */
    function getUsersAttentionGroupsInfo()
    {
        $result = $this->diaryService->getUsersAttentionGroupsInfo($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取模板内容
     * @return \App\EofficeApp\Base\json
     */
    function getTemplateContent()
    {
        $result = $this->diaryService->getTemplateContent($this->request->all(),$this->own);
        return $this->returnResult($result);
    }
     /**
     * 微博权限保存
     * @return \App\EofficeApp\Base\json
     */
    function saveDiaryPurview()
    {
        $result = $this->diaryService->saveDiaryPurview($this->request->all());
        return $this->returnResult($result);
    }
     /**
     * 微博权限获取详情
     * @return \App\EofficeApp\Base\json
     */
    function getDiaryPurviewDetail($groupId)
    {
        $result = $this->diaryService->getDiaryPurviewDetail($groupId,$this->request->all());
        return $this->returnResult($result);
    }
     /**
     * 微博权限列表
     * @return \App\EofficeApp\Base\json
     */
    function getDiaryPurviewLists()
    {
        $result = $this->diaryService->getDiaryPurviewLists($this->request->all());
        return $this->returnResult($result);
    }
     /**
     * 微博权限列表删除
     * @return \App\EofficeApp\Base\json
     */
    function deleteDiaryPurview($groupId)
    {
        $result = $this->diaryService->deleteDiaryPurview($groupId);
        return $this->returnResult($result);
    }
     /**
     * 微博默认关注用户
     * @return \App\EofficeApp\Base\json
     */
    function getDefaultAttention()
    {
        $result = $this->diaryService->getDefaultAttention($this->own,$this->request->all());
        return $this->returnResult($result);
    }
     /**
     * 微博系统工作记录
     * @return \App\EofficeApp\Base\json
     */
    public function getSystemWorkRecord()
    {
        $result = $this->diaryService->getSystemWorkRecord($this->own,$this->request->all());
        return $this->returnResult($result);
    }
     /**
     * 快速保存
     * @return \App\EofficeApp\Base\json
     */
    public function quickSave()
    {
        $result = $this->diaryService->quickSave($this->request->all());
        return $this->returnResult($result);
    }

}