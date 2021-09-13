<?php
namespace App\EofficeApp\Notify\Controllers;

use App\EofficeApp\Notify\Services\NotifyService;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Notify\Requests\NotifyRequest;
use \Illuminate\Http\Request;
/**
 * 公告模块控制器，用于公告模块前端和后端数据层的调度。
 *
 * @author 李志军
 *
 * @since 2015-10-21
 */
class NotifyController extends Controller
{
	/** @var object 公告模块服务类对象 */
	private $notifyService;

	/**
	 * 注册公共服务类对象
	 *
	 * @param \App\EofficeApp\Notify\Services\NotifyService $notifyService
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-21
	 */
	public function __construct(
            NotifyService $notifyService,
            Request $request,
            NotifyRequest $notifyRequest
            )
	{
		parent::__construct();

		$this->notifyService = $notifyService;
        $this->request = $request;
        $this->formFilter($request, $notifyRequest);
	}
	/**
	 * 获取公告类型列表
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return json 公告类型列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-21
	 */
	public function listNotifyType()
	{
		return $this->returnResult($this->notifyService->listNotifyType($this->request->all()));
	}
	/**
	 * 获取公告类型下拉列表
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return array 公告类型列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-21
	 */
	public function listNotifyTypeForSelect()
	{
		return $this->returnResult($this->notifyService->listNotifyTypeForSelect($this->request->all(), $this->own));
	}
	/**
	 * 新建公告类别
	 *
	 * @param \App\Http\Requests\NotifyRequest $request
	 *
	 * @return json 公告类别id
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-21
	 */
	public function addNotifyType()
	{
		return $this->returnResult($this->notifyService->addNotifyType($this->request->all()));
	}
	/**
	 * 编辑公告类别
	 *
	 * @param \App\Http\Requests\NotifyRequest $request
	 * @param int $notifyTypeId 公告类别id
	 *
	 * @return json 编辑结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-21
	 */
	public function editNotifyType($notifyTypeId)
	{
		return $this->returnResult($this->notifyService->editNotifyType($this->request->all(), $notifyTypeId));
	}
	/**
	 * 获取公告类别详情
	 *
	 * @param int $notifyTypeId 公告类别id
	 *
	 * @return json 公告类别详情
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-21
	 */
	public function showNotifyType($notifyTypeId)
	{
		return $this->returnResult($this->notifyService->showNotifyType($notifyTypeId));
	}
	/**
	 * 删除公告类别
	 *
	 * @param type $notifyTypeId 公告类别id
	 *
	 * @return json 删除结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-21
	 */
	public function deleteNotifyType($notifyTypeId)
	{
		return $this->returnResult($this->notifyService->deleteNotifyType($notifyTypeId));
	}

    /**
     * 获取公告列表数据
     * @apiTitle 获取公告列表
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {int} page 页码
     * @param {json} order_by 排序
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *  autoFixPage: 1,
     *  limit: 10,
     *  page: 2,
     *  order_by: {"begin_date":"desc"}, // 按照开始日期倒序
     *  search: {"subject":['公告标题',"like"]} // 公告标题查询
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 37,
     *         "list": [
     *             {
     *                 "notify_id": 65, // 公告ID
     *                 "from_id": "admin", // 公告发布人ID
     *                 "priv_scope": 1, // 权限范围，1表示全部，0表示选择具体范围
     *                 "dept_id": 2, // 发布范围_部门
     *                 "role_id": "", // 发布范围_角色
     *                 "user_id": "", // 发布范围_用户
     *                 "subject": "测试公告", // 公告主题
     *                 "begin_date": "2018-05-18", // 公告开始日期
     *                 "end_date": "2018-05-24", // 公告结束日期
     *                 "publish": 1, // 1_正式发布 0_草稿
     *                 "notify_type_id": 9, // 公告分类ID
     *                 "status": 0, // 状态(生效_1 终止_2)
     *                 "last_check_time": "0000-00-00 00:00:00", // 最后查看审核公告的时间
     *                 "created_at": "2018-05-18 13:36:35", // 公告创建时间
     *                 "user_name": "系统管理员", // 公告发布人姓名
     *                 "content": "测试公告内容", // 公告内容
     *                 "notify_type_name": "公告分类", // 公告分类名称
     *                 "top": 0, // 是否置顶
     *                 "top_end_time": "0000-00-00 00:00:00", // 置顶结束时间
     *                 "top_create_time": "0000-00-00 00:00:00", // 置顶开始时间
     *                 "dept_name": "研发部",
     *                 "has_purview": true, // 是否有权限
     *                 "readerExists": 0 // 判断是否已读
     *             },
     *             ....更多数据
     *         ]
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
	public function listNotify()
	{
		return $this->returnResult($this->notifyService->listNotify($this->request->all(),$this->own));
	}

    /**
     * 新建公告
     *
     * @apiTitle 新建公告
     * @param {boolean} allow_reply 允许评论，0不允许，1允许
     * @param {array} attachment_id 形象图片附件,空为""
     * @param {date} begin_date 生效日期
     * @param {date} end_date 结束日期
     * @param {string} content 内容
     * @param {int} notify_type_id 公告类别id
     * @param {boolean} publish 是否发布，0不发布，1发布
     * @param {string} subject 标题
     * @param {boolean} top 是否置顶，0不置顶，1置顶
     * @param {datetime} top_end_time 置顶结束时间
     * @param {boolean} priv_scope 发布范围，0不是所有人员，1所有人员
     * @param {array} user_id 发布范围用户
     * @param {array} dept_id 发布范围部门
     * @param {array} role_id 发布范围角色
     *
     * @paramExample {string} 参数示例
     * {
     *  allow_reply: 1
     *  attachment_id: ["7a2c5cc450e8ac1a8737d6e39591b50e"]
     *  begin_date: "2019-03-06"
     *  content: "<p>测试内容</p>"
     *  dept_id: [84]
     *  end_date: "2019-03-14"
     *  notify_type_id: 22
     *  priv_scope: 0
     *  publish: 1
     *  role_id: [66]
     *  subject: "测试标题"
     *  top: 1
     *  top_end_time: "2019-03-14 00:00"
     *  user_id: ["WV00000011"]
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "notify_id": 233, // 公告id
     *     },
     *     "runtime": 0.1
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
	public function addNotify()
	{
		return $this->returnResult($this->notifyService->addNotify($this->request->all(),$this->own));
	}
	/**
	 * 编辑公告
	 *
	 * @param \App\Http\Requests\NotifyRequest $request
	 * @param int $notifyId 公告id
	 *
	 * @return json  编辑结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-21
	 */
	public function editNotify($notifyId)
	{
		return $this->returnResult($this->notifyService->editNotify($this->request->all(), $notifyId, $this->own));
	}
	/**
	 * 删除公告
	 *
	 * @param int $notifyId 公告id
	 *
	 * @return json 删除结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-21
	 */
	public function deleteNotify($notifyId)
	{
		return $this->returnResult($this->notifyService->deleteNotify($notifyId, $this->own));
	}
	/**
	 * 立即生效当前公告
	 *
	 * @param type $notifyId
	 *
	 * @return json 操作结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-26
	 */
	public function imediateNotify($notifyId)
	{
		return $this->returnResult($this->notifyService->imediateNotify($notifyId,$this->own['user_id']));
	}
	/**
	 * 立即终止当前公告
	 *
	 * @param type $notifyId
	 *
	 * @return json 操作结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-26
	 */
	public function endNotify($notifyId)
	{
		return $this->returnResult($this->notifyService->endNotify($notifyId, $this->own['user_id']));
	}

	/**
	 * 获取审核公告列表
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return json 审核公告列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-26
	 */
	public function listVerifyNotify()
	{
		return $this->returnResult($this->notifyService->listVerifyNotify($this->request->all(),$this->own));
	}
	/**
	 * 拒绝公告
	 *
	 * @param int $notifyId 公告id
	 *
	 * @return json 拒绝结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-26
	 */
	public function refuseNotify($notifyId)
	{
		return $this->returnResult($this->notifyService->refuseNotify($notifyId,$this->own));
	}
	/**
	 * 批准公告
	 *
	 * @param int $notifyId 公告id
	 *
	 * @return json 批准结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-26
	 */
	public function approveNotify($notifyId)
	{
		return $this->returnResult($this->notifyService->approveNotify($notifyId,$this->own));
	}
	/**
	 * 获取公告详情
	 *
	 * @param int $notifyId
	 *
	 * @return json 公告详情
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-26
	 */
	public function showNotify($notifyId)
	{
		return $this->returnResult($this->notifyService->showNotify($notifyId,$this->own));
	}
	/**
	 * 获取审核公告详情
	 *
	 * @param int $notifyId
	 *
	 * @return json 审核公告详情
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-26
	 */
	public function showVerifyNotify($notifyId)
	{
		return $this->returnResult($this->notifyService->showVerifyNotify($notifyId,$this->own['user_id'],$this->own));
	}
	/**
	 * 获取公告查阅情况
	 *
	 * @param int $notifyId
	 *
	 * @return json 公告查阅情况
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-26
	 */
	public function showReaders($notifyId)
	{
		return $this->returnResult($this->notifyService->showReaders($notifyId,$this->own['user_id'],$this->own));
	}

    public function showReadersBySign($notifyId)
    {

        return $this->returnResult($this->notifyService->showReadersBySign($notifyId,$this->own['user_id'],$this->own,$this->request->all()));
    }

    public function getReadersCount($notifyId){
        return $this->returnResult($this->notifyService->getReadersCount($notifyId));

    }
	/**
	 * 获取公告报表分类依据
	 *
	 */
	public function getGroupAnalyze()
	{
		return $this->returnResult($this->notifyService->getGroupAnalyze($this->request->all()));
	}

	/**
	 * 公告置顶
	 *
	 */
	public function top($notifyId)
	{
		return $this->returnResult($this->notifyService->top($notifyId, $this->request->all()));
	}

	/**
	 * 取消置顶
	 *
	 */
	public function cancelTop($notifyId)
	{
		return $this->returnResult($this->notifyService->cancelTop($notifyId));
	}

    /**
     *  撤回公告
     */
	public function withdraw($notifyId)
    {
        return $this->returnResult($this->notifyService->withdraw($notifyId));
    }

	public function modifyType($notifyId){

		return $this->returnResult($this->notifyService->modifyType($notifyId, $this->request->all()));
	}


     //获取公告评论列表
    public function commentList($notifyId)
    {
        return $this->returnResult($this->notifyService->getCommentList($notifyId, $this->request->all()));
    }

    public function getChildrenComments($commentId)
    {
        return $this->returnResult($this->notifyService->getChildrenComments($commentId));
    }

    //发表评论
    public function addComment($notifyId)
    {
        return $this->returnResult($this->notifyService->addComment($this->request->all(),$notifyId,$this->own['user_id']));
    }

     //删除评论
    public function deleteComment($commentId)
    {
        return $this->returnResult($this->notifyService->deleteComment($commentId,$this->own['user_id'])); //如果该条评论的父id为0，删除该条评论及其子评论；如果不为0则删除该评论本身。
    }

    //编辑评论
    public function editComment($commentId)
    {
        return $this->returnResult($this->notifyService->editComment($this->request->all(),$commentId,$this->own['user_id']));
    }
    //获取评论详情
    public function getCommentDetail($notifyId, $commentId)
    {
        return $this->returnResult($this->notifyService->getCommentDetail($notifyId, $commentId));
    }

    //手动提醒未读者
    public function remindUnreaders($notifyId)
    {
        return $this->returnResult($this->notifyService->remindUnreaders($notifyId, $this->own));
    }

    public function remindOneUnreader()
    {

        return $this->returnResult($this->notifyService->remindOneUnreader($this->request->all(), $this->own));
    }
    //是否有手动提醒权限
    public function canRemind($notifyId)
    {
        return $this->returnResult($this->notifyService->canRemind($notifyId, $this->own));
    }

    //是否有审核权限
    public function canCheck()
    {
        return $this->returnResult($this->notifyService->canCheck($this->own));
    }

    // 统计我的公告总数和未读
    public function countMyNotify()
    {
		return $this->returnResult($this->notifyService->countMyNotify($this->own));
	}

    // 登录打开的公告列表
    public function getAfterLoginOpenList()
    {
        return $this->returnResult($this->notifyService->getAfterLoginOpenList($this->own));
    }

    // 登录打开的公告详情
    public function getAfterLoginDetail($notifyId)
    {
        $params['is_login'] = true;
        return $this->returnResult($this->notifyService->showNotify($notifyId,$this->own, $params));
    }

    // 确认已阅
    public function commitRead($notifyId)
    {
        return $this->returnResult($this->notifyService->commitRead($notifyId, $this->own['user_id']));
    }

    // 获取过期可见性配置
    public function getExpiredVisibleSettings()
    {
        return $this->returnResult($this->notifyService->getExpiredVisibleSettings());
    }

    // 修改过期可见性配置
    public function setExpiredVisibleSettings()
    {
        return $this->returnResult($this->notifyService->setExpiredVisibleSettings($this->request->all()));
    }

    // 判断用户是否可以查看过期公告
    public function checkCanReadExpiredNotify()
    {
        return $this->returnResult($this->notifyService->checkCanReadExpiredNotify($this->own) ? 'true' : 'false');
    }

    // 催促审核公告
    public function urgeReview($notifyId)
    {
        return $this->returnResult($this->notifyService->urgeReview($notifyId, $this->own));
    }

    public function notifyReadRange($notifyId)
    {
        
       return $this->returnResult($this->notifyService->getNotifyReadRange($notifyId));
    }

}
