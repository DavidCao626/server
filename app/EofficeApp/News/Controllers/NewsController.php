<?php
/**
 * @新闻控制器
 */
namespace App\EofficeApp\News\Controllers;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\News\Services\NewsService;
use App\EofficeApp\News\Requests\NewsRequest;
use Illuminate\Http\Request;
class NewsController extends Controller
{
    private $newsService;		//NewsService对象

    private $request;

    /**
     * @注册NewsService对象
     * @param NewsService $newsService
     * @param Request $request
     * @param NewsRequest $newRequest
     */
	public function __construct(
            NewsService $newsService,
            Request $request,
            NewsRequest $newRequest
            )
	{
        parent::__construct();
        $this->newsService = $newsService;
        $this->request = $request;
        $this->formFilter($request, $newRequest);
	}

    /**
     * 获取新闻列表数据
     * @apiTitle 获取新闻列表数据
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *  autoFixPage: 1,
     *  limit: 10,
     *  page: 2,
     *  search: {"title":['新闻标题',"like"]} // 新闻标题查询
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *     "status": 1,
     *     "data": {
     *         "total": 43, // 新闻列表数量
     *         "list": [ // 新闻列表数据
     *             {
     *                 "news_id": 76, // 新闻ID
     *                 "title": "测试新闻1", // 新闻标题
     *                 "news_desc": "测试新闻1", // 新闻简介
     *                 "content": "<p>测试新闻1</p>", // 新闻内容
     *                 "news_type_id": 1, // 新闻分类
     *                 "comments": 0, // 评论数
     *                 "views": 0, // 浏览量
     *                 "top": 1, // 置顶
     *                 "top_end_time": "0000-00-00 00:00:00", // 置顶结束时间
     *                 "top_create_time": "2018-05-28 13:29:01", // 置顶开始时间
     *                 "publish": 1, // 公开发布（0代表草稿、1代表正式发布、2代表提交审核）
     *                 "allow_reply": 1, // 是否允许回复（1是，0否）
     *                 "creator": "admin", // 新闻创建人ID
     *                 "publish_time": "2018-05-28 13:29:01", // 新闻发布时间
     *                 "deleted_at": null,
     *                 "created_at": "2017-04-19 10:19:03",
     *                 "updated_at": "2018-04-20 16:49:44",
     *                 "user_id": "admin",
     *                 "user_accounts": "admin",
     *                 "user_name": "系统管理员", // 新闻创建人姓名
     *                 "user_job_number": "2058",
     *                 "list_number": null,
     *                 "password": "$1$Uv0.dq4.$v/QkDUVBUsO8v/thaDxSU.",
     *                 "user_name_py": "xitongguanliyuan",
     *                 "user_name_zm": "xtgly",
     *                 "user_position": 4,
     *                 "news_type_parent": 0,
     *                 "news_type_name": "签约新闻", // 新闻分类名称
     *                 "sort": 0,
     *                 "has_purview": 1,
     *                 "readerExists": 0 // 判断某用户是否已经阅读了
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
	public function getList()
	{
		return $this->returnResult($this->newsService->getList($this->request->all(),$this->own['user_id']));
	}
	/**
	 * @获取门户新闻列表数据
	 * @param \Illuminate\Http\Request $request
	 * @return json 新闻列表 | 错误信息
	 */
	public function getProtalList()
	{
		return $this->returnResult($this->newsService->getProtalList($this->request->all(),$this->own['user_id']));
	}

   /**
    * 新建新闻
    *
    * @apiTitle 新建新闻
    * @param {boolean} allow_reply 允许评论，0不允许，1允许
    * @param {array} attachment_id 形象图片附件,空为""
    * @param {string} content 内容
    * @param {array} content_attachment 内容附件,空为""
    * @param {string} news_desc 简介
    * @param {int} news_type_id 新闻类别id
    * @param {boolean} publish 是否发布，0不发布，1发布
    * @param {string} title 标题
    * @param {boolean} top 是否置顶，0不置顶，1置顶
    * @param {datetime} top_end_time 置顶结束时间
    *
    * @paramExample {string} 参数示例
    * {
    *  allow_reply: 1
    *  attachment_id: ["c887347bfcaf9d64e7ab2e19a32eaf0a"]
    *  content: "<p>测试内容测试内容</p>"
    *  content_attachment: ["c887347bfcaf9d64e7ab2e19a32eaf0a"]
    *  news_desc: "测试简介测试简介"
    *  news_type_id: 8
    *  publish: 1
    *  title: "测试标题"
    *  top: 1
    *  top_end_time: "2019-03-12 00:00"
    * }
    *
    * @success {boolean} status(1) 接入成功
    * @successExample {json} Success-Response:
    * {
    *     "status": 1,
    *     "data": {
    *         "news_id": 233, // 新闻id
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
    public function addNews()
    {
        return $this->returnResult($this->newsService->addNews($this->request->all(),$this->own));
	}
	/**
	 * @保存编辑数据
	 * @param \App\Http\Requests\StoreNewsRequest $request
	 * @param type $newsId 新闻Id
	 * @return json 成功信息 | 失败信息
	 */
    public function editNews($newsId)
    {
        return $this->returnResult($this->newsService->editNews($this->request->all(), $newsId,$this->own));
	}
	/**
	 * @获取新闻详情
	 * @param type $newsId
	 * @return json 新闻详情
	 */
    public function newsDetail($newsId)
    {
		return $this->returnResult($this->newsService->getNewsInfo($newsId,$this->own['user_id'],$this->own));
	}
	/**
	 * @获取审核新闻详情
	 * @param type $newsId
	 * @return json 审核新闻详情
	 */
    public function showVerifyNews($newsId)
    {
		return $this->returnResult($this->newsService->showVerifyNews($newsId,$this->own['user_id'],$this->own));
	}
	/**
	 * @单个删除、批量删除新闻
	 * @param type $ids 新闻ID；多个id用英文“,”隔开
	 * @return json 成功信息 | 失败信息
	 */
    public function deleteNews($newsId)
    {
		return $this->returnResult($this->newsService->deleteNews($newsId));
	}
	/**
	 * @新闻置顶
	 * @param type $newsId 新闻ID
	 * @return json 成功信息 | 失败信息
	 */
    public function top($newsId)
    {
		return $this->returnResult($this->newsService->top($newsId,$this->request->all()));
	}
    /**
     * @取消置顶
	 * @param type $newsId 新闻ID
	 * @return json 成功信息 | 失败信息
     */
    public function cancelTop($newsId)
    {
		return $this->returnResult($this->newsService->cancelTop($newsId));
	}
    /**
     * @撤消发布新闻
     * @param type $newsId 新闻ID
	 * @return json 成功信息 | 失败信息
     */
    public function cancelPublish($newsId)
    {
		return $this->returnResult($this->newsService->cancelPublish($newsId));
	}
    /**
     * @发布新闻
     * @param type $newsId 新闻Id
	 * @return json 成功信息 | 失败信息
     */
    public function publish($newsId)
    {
		return $this->returnResult($this->newsService->publish($newsId));
	}
	/**
	 * @获取审核新闻列表
	 * @param \Illuminate\Http\Request $request
	 * @return json 审核信息列表 | 错误信息
	 */
    public function verify()
    {
		return $this->returnResult($this->newsService->verify($this->request->all(),$this->own['user_id']));
    }
    /**
     * @批准审核新闻
	 * @param type $ids 新闻Id；多个id用英文“,”隔开
	 * @return json 成功信息 | 失败信息
     */
    public function approveNews($newsIds)
    {
		return $this->returnResult($this->newsService->approveNews($newsIds,$this->own));
	}
	/**
	 * @拒绝审核新闻
	 * @param type $ids 新闻Id；多个id用英文“,”隔开
	 * @return json 成功信息 | 失败信息
	 */
    public function refuseNews($newsIds)
    {
		return $this->returnResult($this->newsService->refuseNews($newsIds,$this->own));
	}
	/**
	 * @获取新闻评论列表
	 * @param \Illuminate\Http\Request $request
	 * @param type $newsId 新闻Id
	 * @return json 评论列表 | 错误信息
	 */
    public function commentList($newsId)
    {
		return $this->returnResult($this->newsService->getCommentList($newsId, $this->request->all()));
	}
	public function getChildrenComments($commentId)
	{
		return $this->returnResult($this->newsService->getChildrenComments($commentId));
	}
	/**
	 * @发表新闻评论
	 * @param \App\Http\Requests\StoreNewsCommentRequest $request
	 * @param type $newsId 新闻Id
	 * @return json 评论ID | 失败信息
	 */
    public function addComment($newsId)
    {
		return $this->returnResult($this->newsService->addComment($this->request->all(),$newsId,$this->own['user_id']));
	}
    /**
     * @删除新闻评论
     * @param type $commentId 评论Id
     * @return json 成功信息 | 失败信息
     */
    public function deleteComment($commentId)
    {
		return $this->returnResult($this->newsService->deleteComment($commentId,$this->own['user_id'])); //如果该条评论的父id为0，删除该条评论及其子评论；如果不为0则删除该评论本身。
	}
	 /**
     * @编辑新闻评论
     * @param type $commentId 评论Id
     * @return json 成功信息 | 失败信息
     */
    public function editComment($commentId)
    {
		return $this->returnResult($this->newsService->editComment($this->request->all(),$commentId,$this->own['user_id']));
	}
	public function getCommontDetail($newsId,$commentId)
	{
		return $this->returnResult($this->newsService->getCommontDetail($newsId,$commentId));
	}
	/**
	 * @查看新闻获取新闻分类筛选项
	 * @param \Illuminate\Http\Request $request
	 * @return array 新闻类别列表
	 */
    public function newsTypeList()
    {
		return $this->returnResult($this->newsService->getNewsTypeList($this->request->all(), $this->own));
	}
	/**
	 * @新建新闻获取新闻类别下拉框数据
	 * @param \Illuminate\Http\Request $request
	 * @return json 新闻类别列表
	 */
    public function getNewsTypeListForSelect()
    {
		return $this->returnResult($this->newsService->getNewsTypeListForSelect($this->request->all()));
	}
    /**
     * @新建新闻获取新闻类别下拉框数据
     * @param \Illuminate\Http\Request $request
     * @return array 新闻类别列表
     */
    public function getNewsTypeListForCascader($parentId)
    {
        return $this->returnResult($this->newsService->getNewsTypeListForCascader($parentId, $this->request->all(), $this->own));
    }

	/**
	 * @新闻分类获取列表
	 * @param \Illuminate\Http\Request $request
	 * @return json 新闻类别列表
	 */
    public function newsTypeLists()
    {
		return $this->returnResult($this->newsService->getNewsTypeLists($this->request->all()));
	}

	/**
	 * @创建新闻类别
	 * @param \App\Http\Requests\StoreNewsTypeRequest $request
	 * @return json 类别ID | 失败信息
	 */
    public function addNewsType()
    {
        return $this->returnResult($this->newsService->addNewsType($this->request->all()));
	}
	/**
	 * @编辑新闻类别
	 * @param \App\Http\Requests\StoreNewsTypeRequest $request
	 * @param type $newsTypeId 新闻类别ID
	 * @return json 成功信息 | 失败信息
	 */
    public function editNewsType($newsTypeId)
    {
		return $this->returnResult($this->newsService->editNewsType($this->request->all(), $newsTypeId));
	}
	/**
	 * 获取新闻类别信息
	 * @param type $newsTypeId 新闻类别ID
	 * @return json 新闻类别信息 |错误信息
	 */
	public function newsTypeDetail($newsTypeId)
	{
		return $this->returnResult($this->newsService->newsTypeDetail($newsTypeId));
	}
    /**
     * @删除新闻类别；删除新闻类别将其类别的新闻的类别都改为0.
     * @param type $newsTypeId 新闻类别ID
     * @return json 成功信息 | 失败信息
     */
    public function deleteNewsType($newsTypeId)
    {
		return $this->returnResult($this->newsService->deleteNewsType($newsTypeId));
	}
     /**
     * @获取新闻类别排序当前最大值
     * @param
     * @return json 当前最大值
     */
    public function getMaxsort()
    {
        return $this->returnResult($this->newsService->getMaxsort());
    }

    public function countMyNews()
    {
        return $this->returnResult($this->newsService->countMyNews($this->own['user_id']));
    }

    public function setNewsSettings(){
        return $this->returnResult($this->newsService->setNewsSettings($this->request->all()));
    }

    public function getNewsSettings(){
        return $this->returnResult($this->newsService->getNewsSettings());
    }


}
