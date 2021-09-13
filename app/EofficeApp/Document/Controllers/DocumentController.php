<?php
namespace App\EofficeApp\Document\Controllers;

use App\EofficeApp\Attachment\Repositories\AttachmentRelRepository;
use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Attachment\Services\WPSAttachmentService;
use App\EofficeApp\Document\Repositories\DocumentContentRepository;
use App\EofficeApp\Document\Services\WPS\WPSAuthService;
use App\EofficeApp\Document\Services\WPS\WPSFileService;
use App\EofficeApp\System\Security\Services\SystemSecurityService;
use App\EofficeApp\User\Repositories\UserRepository;
use App\EofficeApp\User\Services\UserService;
use \Illuminate\Http\Request,
	\App\EofficeApp\Document\Requests\DocumentRequest,
	\App\EofficeApp\Document\Services\DocumentService,
	\App\EofficeApp\Base\Controller;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\HeaderBag;
use \DB;

/**
 * @文档管理控制器
 *
 * @author 李志军
 *
 * @since 2015-11-02
 */
class DocumentController extends Controller
{
	/** @var object 文档服务类对象 */
	private $documentService;

	/**
	 * 注册文档服务类对象
	 *
	 * @param \App\EofficeApp\Document\Services\DocumentService $documentService
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function __construct(
            DocumentService $documentService,
            DocumentRequest $documentRequest,
            Request $request
            )
	{
		parent::__construct();

		$this->documentService = $documentService;
        $this->request = $request;
        $this->formFilter($request, $documentRequest);
	}
	/**
	 * 获取文档样式列表
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return json 文档样式列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function listMode()
	{
		return $this->returnResult($this->documentService->listMode($this->request->all()));
	}
	/**
	 * 新建文档样式
	 *
	 * @param \App\Http\Requests\DocumentRequest $request
	 *
	 * @return json 文档样式id
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function addMode()
	{
		return $this->returnResult($this->documentService->addMode($this->request->all()));
	}

    public function addLog()
    {
        return $this->returnResult($this->documentService->addLogs($this->request->all(), $this->own));
    }
	/**
	 * 编辑文档样式
	 *
	 * @param \App\Http\Requests\DocumentRequest $request
	 * @param int $modeId
	 *
	 * @return json 编辑结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function editMode($modeId)
	{
		return $this->returnResult($this->documentService->editMode($this->request->all(), $modeId));
	}
	/**
	 * 获取文档样式详情
	 *
	 * @param int $modeId
	 *
	 * @return json 文档样式详情
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function showMode($modeId)
	{
		return $this->returnResult($this->documentService->showMode($modeId));
	}
	/**
	 * 删除文档样式
	 *
	 * @param int $modeId
	 *
	 * @return json 删除结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function deleteMode($modeId)
	{
		return $this->returnResult($this->documentService->deleteMode($modeId));
	}
	/**
	 * 恢复默认文档样式
	 *
	 * @param int $modeId
	 *
	 * @return json 恢复结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function recoverDefaultMode($modeId)
	{
		return $this->returnResult($this->documentService->recoverDefaultMode($modeId));
	}
	/**
	 * 新建文件夹
	 *
	 * @param \App\Http\Requests\DocumentRequest $request
	 *
	 * @return json 文件夹id
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function addFolder()
	{
		return $this->returnResult($this->documentService->addFolder($this->request->all(),$this->own));
	}
	/**
	 * 批量新建文件夹
	 *
	 * @param \App\Http\Requests\DocumentRequest $request
	 *
	 * @return json 文件夹id
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function batchAddFolder()
	{
		return $this->returnResult($this->documentService->batchAddFolder($this->request->all(), $this->own));
	}
	public function batchSetModeAndTemplate()
	{
		return $this->returnResult($this->documentService->batchSetModeAndTemplate($this->request->all(), $this->own));
	}
	public function batchMoveFolder($fromIds, $toId)
	{
		return $this->returnResult($this->documentService->batchMoveFolder($fromIds, $toId, $this->own));
	}
	
	public function batchDeleteFolder($folderIds)
	{
		return $this->returnResult($this->documentService->batchDeleteFolder($folderIds,$this->own));
	}
	/**
	 * 编辑文件夹
	 *
	 * @param \App\Http\Requests\DocumentRequest $request
	 * @param int $folderId
	 *
	 * @return json 编辑结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function editFolder($folderId)
	{
		return $this->returnResult($this->documentService->editFolder($this->request->all(), $folderId, $this->own));
	}
	/**
	 * 复制文件夹
	 *
	 * @param \App\Http\Requests\DocumentRequest $request
	 * @param int $folderId
	 *
	 * @return json 复制结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function copyFolder($folderId)
	{
		return $this->returnResult($this->documentService->copyFolder($this->request->all(), $folderId, $this->own));
	}
	/**
	 * 删除文件夹
	 *
	 * @param int $folderId
	 *
	 * @return json 删除结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function deleteFolder($folderId)
	{
		return $this->returnResult($this->documentService->deleteFolder($folderId, $this->own));
	}
	/**
	 * 获取文件夹详情
	 *
	 * @param int $folderId
	 *
	 * @return json 文件夹详情
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function showFolder($folderId)
	{
		return $this->returnResult($this->documentService->showFolder($folderId, $this->own, $this->request->all()));
	}

    /**
     * 获取文件夹详情（无权限）
     * @author yangxingqiang
     * @param $folderId
     * @return array
     */
    public function showFolderInfo($folderId)
    {
        return $this->returnResult($this->documentService->showFolderInfo($folderId, $this->own, $this->request->all()));
    }
	/**
	 * 文件基本设置
	 *
	 * @param \App\Http\Requests\DocumentRequest $request
	 * @param int $folderId
	 *
	 * @return json 设置结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function setFolderBaseInfo($folderId)
	{
		return $this->returnResult($this->documentService->setFolderBaseInfo($this->request->all(), $folderId, $this->own));
	}
	public function editFolderName($folderId)
	{
		return $this->returnResult($this->documentService->editFolderName($folderId,$this->request->input('folder_name'),  $this->own));
	}
	/**
	 * 文件夹权限设置
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param int $folderId
	 *
	 * @return json 设置结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function setPurview($folderId)
	{
		return $this->returnResult($this->documentService->setPurview($this->request->all(), $folderId, $this->own));
	}
	/**
	 * 获取文件夹权限
	 *
	 * @param int $folderId
	 *
	 * @return json 文件夹权限
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getPurview($folderId)
	{
		return $this->returnResult($this->documentService->getPurview($folderId,$this->request->input('view',false)));
	}
	/**
	 * 文件夹样式设置
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param int $folderId
	 *
	 * @return json 设置结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function setShowMode($folderId)
	{
		return $this->returnResult($this->documentService->setShowMode($this->request->all(), $folderId, $this->own));
	}
	/**
	 * 文件夹排序
	 *
	 * @param string $folderIds
	 *
	 * @return json 排序结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function sortFolder($folderIds)
	{
		return $this->returnResult($this->documentService->sortFolder($folderIds));
	}
	/**
	 * 文件加转移
	 *
	 * @param int $fromId
	 * @param int $toId
	 *
	 * @return json 转移结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function migrateFolder($fromId, $toId)
	{
		return $this->returnResult($this->documentService->migrateFolder($fromId, $toId));
	}
	/**
	 * 设置文件夹模板
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param int $folderId
	 *
	 * @return json 设置结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function setTemplate($folderId)
	{
		return $this->returnResult($this->documentService->setTemplate($this->request->all(), $folderId, $this->own));
	}
	/**
	 * 获取管理文件夹列表的子文件夹
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param int $parentId
	 *
	 * @return json 文件夹列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getManageChildrenFolder($parentId)
	{
		return $this->returnResult($this->documentService->getManageChildrenFolder($this->request->input('fields',''), $parentId, $this->own));
	}
	public function getAllChildrenFolder($parentId){
		return $this->returnResult($this->documentService->getAllChildrenFolder($this->request->input('fields',''), $parentId));
	}
	public function listAllFolder(){
		return $this->returnResult($this->documentService->listAllFolder($this->request->all()));
	}
	/**
	 * 获取新建文档的子文件夹列表
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param int $parentId
	 *
	 * @return json 文件夹列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getCreateChildrenFolder($parentId)
	{
		return $this->returnResult($this->documentService->getCreateChildrenFolder($this->request->input('fields',''), $parentId, $this->own));
	}

	/**
     * 用于显示选中文件夹的子文件夹列表
     *
     * @apiTitle 获取指定子文件夹列表
     * @param {int} parentId 文件夹ID
     *
     * @paramExample {string} 参数示例
     * api/document/folder/children/85/show
     *
     * @success {boolean} status(1) 接入成功
     *
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": [
     *          [
     			"folder_id": 103, //子文件夹id
     *          "folder_level_id": '0,85', //层级结构id
     *          "folder_name": "知识", //文件夹名称
     *          "folder_sort": 1, //文件夹序号
     *          "folder_type": 0, //文件夹类型1公共文件夹，5流程归档文件夹
     *          "has_children": 0, //是否有子文件夹，0-没有子文件夹，1-有子文件夹
     *          "mode_id": 40, //显示样式id
     *          "template_id": 1, //模板id
     *          "parent_id": 85, //父文件夹id
     *          "purview_extends": 0, //是否继承父级权限，0-否，1-是
     *          "user_id": 'WV00000007', //文档创建人
     *			....其他信息
 	 *			],
 	 *			...
     *      ]
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
	public function getShowChildrenFolder($parentId)
	{
		return $this->returnResult($this->documentService->getShowChildrenFolder($this->request->all(), $parentId, $this->own));
	}

	/**
     * 获取文档列表
     *
     * @apiTitle 获取文档列表
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *	autoFixPage: 1
	 *	limit: 10
	 *	order_by: {"created_at":"desc"}
	 *	page: 2
	 *	search: {"folder_id":[81,"="]}
	 * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": [
     *          [
     *			"create_name": "小李", //创建人名称
     *          "created_at": '2018-04-09 11:13:43', //创建时间
     *          "document_id": 103, //文档id
     *          "document_type": 0, //文档类型，0-html,1-word,2-excel
     *          "folder_id": 62, //文件夹id
     *          "folder_name": "知识", //文件夹名称
     *          "folder_type": 0, //文件夹类型1公共文件夹，5流程归档文件夹
     *          "isView": 1, //是否已查看，1-已查看，0-未查看
     *          "is_draft": 0, ////是否是草稿，0-不是，1-是
     *          "log_count": 9, //操作日志数量
     *          "manage_purview": 1, //是否有管理权限，1-有，0-无
     *          "reply_count": 0, //回复数量
     *          "subject": "文档101", //文档标题
     *          "creator": 'WV00000007', //文档创建人
     *          "attachment_count": 1, //附件数量
 	 *			],
 	 *			...
     *      ]
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
	public function listDocument()
	{
		return $this->returnResult($this->documentService->listDocument($this->request->all(), $this->own));
	}
	/**
	 * 获取文档列表
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return json 无权限文档列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function listNoPurviewDocument()
	{
		return $this->returnResult($this->documentService->listNoPurviewDocument($this->request->all()));
	}
	/**
	 * 新建文档
	 *
	 * @param \App\Http\Requests\DocumentRequest $request
	 *
	 * @return json 文档id
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function addDocument()
	{
		return $this->returnResult($this->documentService->addDocument($this->request->all(), $this->own));
	}
	/**
	 * 复制文档
	 *
	 * @return json
	 *
	 * @author nxk
	 *
	 * @since 2020-02-18
	 */
	public function copyDocument()
	{
		return $this->returnResult($this->documentService->copyDocument($this->request->all(), $this->own));
	}
	/**
	 * 置顶文档
	 *
	 * @return json 
	 *
	 * @author nxk
	 *
	 * @since 2020-02-18
	 */
	public function topDocument($documentId)
	{
		return $this->returnResult($this->documentService->topDocument($documentId, $this->request->all()));
	}
	public function cancelTopDocument($documentId)
	{
		return $this->returnResult($this->documentService->cancelTopDocument($documentId));
	}
	/**
	 * 编辑文档
	 *
	 * @param \App\Http\Requests\DocumentRequest $request
	 * @param int $documentId
	 *
	 * @return json 编辑结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function editDocument($documentId)
	{
		return $this->returnResult($this->documentService->editDocument($this->request->all(),$this->request->file('upload_file'), $documentId, $this->own));
	}

    public function editDocumentName($documentId)
    {
        return $this->returnResult($this->documentService->editDocumentName($this->request->all(),$documentId, $this->own));
    }

	/**
     * 用于获取文档详情
     *
     * @apiTitle 用于获取文档详情
     * @param {int} documentId 文档ID
     *
     * @paramExample {string} 参数示例
     * api/document/164
     *
     * @success {boolean} status(1) 接入成功
     *
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          "document_id": 164, //文档id
     *          "folder_type": 1, //文件夹类型，1-普通文件夹，5-流程归档
     *          "document_type": 0, //文档类型，0-html,1-word,2-excel
     *          "folder_id": 62, //文件夹id
     *          "source_id": 0, //来源id，即归档流程的run_id，非流程归档的文档值为0
     *          "source_seq": {json}, //归档相关流程数据
     *          "flow_manager": 'WV00000007,WV00000022,WV00000001', //流程办理人
     *          "subject": 'xxxxxxxx', //文档标题
     *          "source_content": 'xxxxxxxx', //文档内容
     *          "status": 0, //文档状态
     *          "creator": 'WV00000007', //文档创建人
     *          "attachment_count": 1, //附件数量
     *          "attachment_id": ['61bb77aa7c4eb8e3bb8b20c0ac72ab1e',...], //附件id集合
     *          "manage_purview": true, //是否有管理权限
     *          "reply_count": 2, //回复数量
     *          "tag_id": [1, 2, 3,...], //标签id集合
     *          "down_purview": 1, //下载权限，1-有权限，0-无权限
     *          "is_draft": 0, //是否是草稿，0-不是，1-是
     *          "created_at": "2018-05-31 16:40:30", //创建时间
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x041027","message":"您没有该文档的查看权限或该文档已删除"}] }
     */
	public function showDocument($documentId)
	{
		return $this->returnResult($this->documentService->showDocument($documentId, $this->own));
	}
	// 获取关联文档详情
	public function showDocumentByRelation($documentId)
	{
		return $this->returnResult($this->documentService->showDocument($documentId, $this->own, $typeParam='relation'));
	}
	// 获取文档详情不记录日志
	public function showDocumentNoLog($documentId)
	{
		return $this->returnResult($this->documentService->showDocument($documentId, $this->own, $typeParam='nolog'));
	}
	/**
	 * 删除文档
	 *
	 * @param int $documentId
	 *
	 * @return json 删除结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function deleteDocument($documentId)
	{
		return $this->returnResult($this->documentService->deleteDocument($documentId, $this->own));
	}
	/**
	 * 文档转移
	 *
	 * @param string $documentIds
	 * @param int $folderId
	 *
	 * @return json 转移结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function migrateDocument($documentIds,$folderId)
	{
		return $this->returnResult($this->documentService->migrateDocument($documentIds, $folderId,$this->own));
	}
	/**
	 * 共享文档
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param string $documentId
	 *
	 * @return json 共享结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function shareDocument($documentId)
	{
		return $this->returnResult($this->documentService->shareDocument($this->request->all(), $documentId,$this->own));
	}
	/**
	 * 获取拥有某个文件夹查看权限的所有成员
	 *
	 * @param int $folderId
	 *
	 * @return json 拥有某个文件夹查看权限的所有成员
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function showAllViewPurviewMember($folderId, $documentId)
	{
		return $this->returnResult($this->documentService->showAllViewPurviewMember($folderId, $documentId));
	}
	/**
	 * 获取文档附件
	 *
	 * @param int $documentId
	 *
	 * @return json 附件列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-08
	 */
	public function getDocumentAttachment($documentId)
	{
		return $this->returnResult($this->documentService->getDocumentAttachment($documentId));
	}
	/**
	 * 新建文档回复
	 *
	 * @param int $documentId
	 *
	 * @return json 回复id
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-08
	 */
	public function addRevert($documentId)
	{
		return $this->returnResult($this->documentService->addRevert($this->request->all(), $documentId,$this->own));
	}
	/**
	 * 获取文档回复列表
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param int $documentId
	 *
	 * @return json 文档回复列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-08
	 */
	public function listRevert($documentId)
	{
		return $this->returnResult($this->documentService->listRevert($this->request->all(), $documentId, $this->own));
    }
    // 编辑文档回复
    public function editRevert($documentId, $revertId) {
        return $this->returnResult($this->documentService->editRevert($documentId, $revertId, $this->request->all(), $this->own)); 
    }
    // 删除文档回复
    public function deleteRevert($documentId, $revertId) {
        return $this->returnResult($this->documentService->deleteRevert($documentId, $revertId, $this->own)); 
    }
	public function getRevertInfo($documentId,$revertId)
	{
		return $this->returnResult($this->documentService->getRevertInfo($documentId,$revertId));
	}

	public function listLogs($documentId)
	{
		return $this->returnResult($this->documentService->listLogs($this->request->all(),$documentId));
	}
	public function listShowFolder()
	{
		return $this->returnResult($this->documentService->listShowFolder($this->request->all(), $this->own));
	}
	public function listManageFolder()
	{
		return $this->returnResult($this->documentService->listManageFolder($this->request->all(), $this->own));
	}
	public function listCreateFolder()
	{
		return $this->returnResult($this->documentService->listCreateFolder($this->request->all(), $this->own));
	}
    public function shareDownloadSet($shareDownload)
    {
        return $this->returnResult($this->documentService->shareDownloadSet($shareDownload));
    }
    public function getShareDownload()
    {
        return $this->returnResult($this->documentService->getShareDownload());
    }
    public function getPrvDocument($documentId)
    {
    	return $this->returnResult($this->documentService->getPrvOrNextDocument($documentId,$this->own, '<', $this->request->all()));
    }
    public function getNextDocument($documentId)
    {
    	return $this->returnResult($this->documentService->getPrvOrNextDocument($documentId,$this->own, '>', $this->request->all()));
    }
    public function documentLock($documentId,$lockStatus)
    {
    	return $this->returnResult($this->documentService->documentLock($documentId,$lockStatus,$this->own['user_id']));
    }
    public function documentLockInfo($documentId)
    {
    	return $this->returnResult($this->documentService->documentLockInfo($documentId));
    }
    public function applyUnlock($documentId,$userId)
    {
    	return $this->returnResult($this->documentService->applyUnlock($documentId,$userId,$this->own));
    }
    public function documentAttachment($documentId)
    {
    	return $this->returnResult($this->documentService->documentAttachment($documentId));
    }
    public function hasReplyPurview($documentId)
    {
    	return $this->returnResult($this->documentService->hasReplyPurview($documentId,$this->own));
    }
    public function hasDownPurview($documentId)
    {
    	return $this->returnResult($this->documentService->hasDownPurview($documentId,$this->own));
    }
    public function getDocumentShareMember($documentId)
    {
    	return $this->returnResult($this->documentService->getDocumentShareMember($documentId));
    }
    /**
     * 删除文档标签
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool 操作是否成功
     *
     * @author niuxiaoke
     *
     * @since 2017-08-01
     */
    public function delDocumentTags()
    {
        return $this->returnResult($this->documentService->delDocumentTags($this->request->all(), $this->own['user_id']));
    }
    /**
     * 添加文档标签
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool 操作是否成功
     *
     * @author niuxiaoke
     *
     * @since 2017-08-01
     */
    public function addDocumentTags()
    {
        return $this->returnResult($this->documentService->addDocumentTags($this->request->all(), $this->own['user_id']));
    }
    /**
     * 设置创建人权限
     *
     * @param int
     *
     * @return bool
     *
     * @author niuxiaoke
     *
     * @since 2017-08-02
     */
    public function creatorPurviewSet($creatorPur)
    {
        return $this->returnResult($this->documentService->creatorPurviewSet($creatorPur));
    }
    /**
     * 获取创建人权限
     *
     * @return bool
     *
     * @author niuxiaoke
     *
     * @since 2017-08-02
     */
    public function getCreatorPurview()
    {
        return $this->returnResult($this->documentService->getCreatorPurview());
    }
    /**
     * 获取父文件夹id
     *
     * @return array
     *
     * @author niuxiaoke
     *
     * @since 2017-08-24
     */
    public function getParentId($folderId)
    {
    	return $this->returnResult($this->documentService->getParentId($folderId));
    }
    /**
     * 获取父文件夹id
     *
     * @return array
     *
     * @author niuxiaoke
     *
     * @since 2017-09-04
     */
    public function getChildrenId($folderId)
    {
    	return $this->returnResult($this->documentService->getChildrenId($folderId));
    }

    public function transOfficeToHtml()
    {
    	return $this->returnResult($this->documentService->transOfficeToHtml($this->request->all(),$this->own));
    }

    public function getDocumentTree($parentId) {
    	return $this->returnResult($this->documentService->getDocumentTree($parentId, $this->own));
    }

    public function getDocumentReadCount() {
    	return $this->returnResult($this->documentService->getDocumentReadCount($this->own));
    }

    public function getDocumentBaseSet() {
    	return $this->returnResult($this->documentService->getDocumentBaseSet());
    }

    public function documentBaseSet() {
    	return $this->returnResult($this->documentService->documentBaseSet($this->request->all()));
    }

    /**
     * 更新文档视图模式
     * @author yangxingqiang
     * @return array
     */
    public function documentViewModeSet() {
        return $this->returnResult($this->documentService->documentViewModeSet($this->request->all(),$this->own));
    }

    /**
     * 获取文档视图模式
     * @author yangxingqiang
     * @return array
     */
    public function getDocumentViewModeSet() {
        return $this->returnResult($this->documentService->getDocumentViewModeSet($this->own));
    }

    /**
     * 获取wps模板列表
     */
    public function getTemplateList()
    {
        /** @var WPSAuthService $service */
        $service = app('App\EofficeApp\Document\Services\WPS\WPSAuthService');
        $userId = $this->own['user_id'];
        $type = $this->request->get('type');
        $attachmentId = $this->request->get('attachmentId');

        // 验证type是否为有效值( 仅w/s有效 )
        if ($service->isValidateTemplateType($type)) {
            $url = $service->getTemplateListUrl($userId, $type, ['_w_fileId' => $attachmentId]);
            $token = $service->getToken($userId);
            return $this->returnResult(['url' => $url, 'token' => $token]);
        }

        // TODO 非法文档参数
        return ['code' => ['0x041017', 'document']];
    }

    /**
     * wps云文档回调: 获取文件元数据
     *  1.获取文件元数据，包括当前文件信息和当前用户信息。
     *  2.当user的permission参数返回“write”时，进入在线编辑模式，返回“read”时进入预览模式。
     */
    public function getWPSFileInfo()
    {
        try {
            /** @var HeaderBag $headers */
            $headers = $this->request->headers;
            $userId = $this->request->get('_w_userid');
            $model = $this->request->get('_w_model', 'read');
            /** @var WPSAuthService $authService */
            $authService = app('App\EofficeApp\Document\Services\WPS\WPSAuthService');
            // 验证头部信息
            $authService->authWPSUserAgent($headers, $userId);
            /** @var WPSFileService $fileService */
            $fileService = app('App\EofficeApp\Document\Services\WPS\WPSFileService');
            $fileId = $fileService->getFileIdFromHeaders($headers);

            // 判断是否有权限
            if (!$fileService->isPermission($userId, $fileId)) {
                return response()->json('Illegal request', 403);
            }
            // 获取返回文件对象
            $file = $fileService->getFileObject($fileId);
            // 设置权限
            /**
             * 设置权限
             *  1. 只读模式打开时不允许重命名
             */
            $rename = $model === 'read' ? 0 : 1;
            $file->setUserAcl([
                'rename'=> $rename,    //重命名权限，1为打开该权限，0为关闭该权限，默认为0
                'history'=> 1    //历史版本权限，1为打开该权限，0为关闭该权限,默认为1
            ]);

            // 设置水印
            // 获取用户信息
            /** @var UserRepository $userRepository */
            $userRepository= app('App\EofficeApp\User\Repositories\UserRepository');
            $userName = $userRepository->getUserName($userId);
            $own = $fileService->getUserOwn($userId);

            /** @var SystemSecurityService $securityService */
            $securityService = app('App\EofficeApp\System\Security\Services\SystemSecurityService');
            $watermarkInfo = $securityService->getWatermarkSettingInfo(['parse' => 1], ['all'], $own);

            // 若未设置水印或者未开启水印 则水印不设置
            $isWatermarkInfoOpen = true;
            if (!isset($watermarkInfo['toggle']) || ($watermarkInfo['toggle'] === 'off')) {
                $isWatermarkInfoOpen = false;
            }

            if ($isWatermarkInfoOpen &&
                isset($watermarkInfo['scope']['document']) &&
                ($watermarkInfo['scope']['document'] == 1)) {
                $value = $watermarkInfo['content_parse'] ?? $userId.$userName;
                $date = date("Y-m-d");
                $time = date("H:i:s");
                $value = str_replace("[DATE]", "{$date}", $value); // 当前日期
                $value = str_replace("[TIME]", "{$time}", $value); // 当前时间
                $file->setWatermark([
                    'type'=> 1,		 		//水印类型， 0为无水印； 1为文字水印
                    'value' => $value,  //文字水印的文字，当type为1时此字段必选
                ]);
            } else {
                $file->setWatermark([
                    'type'=> 0,		 		//水印类型， 0为无水印； 1为文字水印
                ]);
            }

            // 获取返回用户对象
            $user = $fileService->getUserObject($userId, $fileId, $model);

            $data = [
                'file' => $file->convertToArray(),
                'user' => $user->convertToArray(),
            ];

        } catch (\Exception $exception) {
            $code = $exception->getCode() ? $exception->getCode() : 500;
            // TODO 日志报警 邮件/短信通知管理员
            Log::error($exception->getMessage());

            return response()->json($exception->getMessage(), $code);
        }

        return response()->json($data);
    }

    /**
     * wps云文档回调: 下载文档
     */
    public function getWPSFileDownload()
    {
        try {
            $fid = $this->request->get('fid');
            /** @var WPSFileService $fileService */
            $fileService = app('App\EofficeApp\Document\Services\WPS\WPSFileService');
            $fileInfo = $fileService->downloadFile($fid);
            return  response()->download($fileInfo['file'], $fileInfo['fileName']);
        } catch (\Exception $exception) {
            $code = $exception->getCode() ? $exception->getCode() : 500;
            // TODO 日志报警 邮件/短信通知管理员
            Log::error($exception->getMessage());

            return response()->json($exception->getMessage(), $code);
        }
    }

    /**
     * wps云文档回调: 获取用户头像
     */
    public function getWPSUserAvatar()
    {
        try {
            $uid = $this->request->get('uid');
            /** @var WPSFileService $fileService */
            $fileService = app('App\EofficeApp\Document\Services\WPS\WPSFileService');
            $fileService->getUserAvatar($uid);

        } catch (\Exception $exception) {
            $code = $exception->getCode() ? $exception->getCode() : 500;
            // TODO 日志报警 邮件/短信通知管理员
            Log::error($exception->getMessage());

            return response()->json($exception->getMessage(), $code);
        }

        return response('');
    }

    /**
     * wps云文档回调: 获取用户信息
     *  批量获取当前正在编辑和编辑过文档的用户信息，以数组的形式返回响应。
     */
    public function getWPSUserInfo()
    {
        try {
            $ids = $this->request->get('ids');

            /** @var WPSFileService $fileService */
            $fileService = app('App\EofficeApp\Document\Services\WPS\WPSFileService');
            // 获取返回用户对象
            $users = $fileService->getUsersInfo($ids);

        } catch (\Exception $exception) {
            $code = $exception->getCode() ? $exception->getCode() : 500;
            // TODO 日志报警 邮件/短信通知管理员
            Log::error($exception->getMessage());

            return response()->json($exception->getMessage(), $code);
        }

        return  response()->json(['users'=> $users]);
    }

    /**
     * wps云文档回调: 通知此文件目前有那些人正在协作
     *  当有用户加入或者退出协作的时候 ，上传当前文档协作者的用户信息，可以用作上下线通知。
     */
    public function getWPSFileOnline()
    {
        try {
            /** @var HeaderBag $headers */
            $headers = $this->request->headers;
            /** @var WPSAuthService $authService */
            $authService = app('App\EofficeApp\Document\Services\WPS\WPSAuthService');
            // 验证头部信息

        } catch (\Exception $exception) {
            $code = $exception->getCode() ? $exception->getCode() : 500;
            Log::error($exception->getMessage());

            return response()->json($exception->getMessage(), $code);
        }

        return  response()->json();
    }

    /**
     * wps云文档回调: 上传文件新版本
     *  当文档在线编辑并保存之后，上传该文档最新版本到对接模块，同时版本号进行相应的变更，需要对接方内部实现文件多版本管理机制。
     */
    public function saveWPSFile()
    {
        try {
            /** @var HeaderBag $headers */
            $headers = $this->request->headers;
            /** @var WPSAuthService $authService */
            $authService = app('App\EofficeApp\Document\Services\WPS\WPSAuthService');

            /** @var WPSFileService $fileService */
            $fileService = app('App\EofficeApp\Document\Services\WPS\WPSFileService');
            $fileId = $fileService->getFileIdFromHeaders($headers);
            // 获取上传文件
            $file = $this->request->file('file');
            $fileService->saveFileWithNewVersion($fileId, $file);

            // 获取返回文件对象
            $file = $fileService->getFileObject($fileId);
            $file->setVersion(2); // 更新版本信息
            $data = [
                'file' => $file->convertToArray(),
            ];
        } catch (\Exception $exception) {
            $code = $exception->getCode() ? $exception->getCode() : 500;
            Log::error($exception->getMessage());

            return response()->json($exception->getMessage(), $code);
        }

        return  response()->json($data);
    }

    /**
     * wps云文档回调: 获取特定版本信息
     *  在历史版本预览和回滚历史版本的时候，获取特定版本文档的文件信息。
     */
    public function getWPSFileVersion()
    {
        try {
            /** @var HeaderBag $headers */
            $headers = $this->request->headers;

            /** @var WPSFileService $fileService */
            $fileService = app('App\EofficeApp\Document\Services\WPS\WPSFileService');
            $fileId = $fileService->getFileIdFromHeaders($headers);
            // 获取返回文件对象
            $file = $fileService->getFileObject($fileId);

            $data = [
                'file' => $file->convertToArray(),
            ];

        } catch (\Exception $exception) {
            $code = $exception->getCode() ? $exception->getCode() : 500;
            Log::error($exception->getMessage());

            return response()->json($exception->getMessage(), $code);
        }

        return response()->json($data);
    }

    /**
     * wps云文档回调: 文件重命名
     *  用户在h5页面修改了文件名后，把新的文件名上传到服务端保存。
     */
    public function renameWPSFile()
    {
        try {
            /** @var HeaderBag $headers */
            $headers = $this->request->headers;
            /** @var WPSAuthService $authService */
            $authService = app('App\EofficeApp\Document\Services\WPS\WPSAuthService');

            /** @var WPSFileService $fileService */
            $fileService = app('App\EofficeApp\Document\Services\WPS\WPSFileService');
            $fileId = $fileService->getFileIdFromHeaders($headers);

            // 获取返回文件对象
            $newName = $this->request->get('name');
            if ($newName) {
                // 更新文件名
                /** @var AttachmentRelRepository $relRepository */
                $relRepository = app('App\EofficeApp\Attachment\Repositories\AttachmentRelRepository');
                /** @var AttachmentService $attachmentService */
                $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
                $attachmentRel = $relRepository->getOneAttachmentRel(['attachment_id' => [$fileId]]);
                if ($attachmentRel) {
                    $tableName = $attachmentService->getAttachmentTableName($attachmentRel->year, $attachmentRel->month);
                    DB::table($tableName)->where('rel_id',$attachmentRel->rel_id)->update(['attachment_name' => $newName]);
                }
            }

            return response()->json(['name' => $newName]);
        } catch (\Exception $exception) {
            $code = $exception->getCode() ? $exception->getCode() : 500;
            Log::error($exception->getMessage());

            return response()->json($exception->getMessage(), $code);
        }
    }

    /**
     * wps云文档回调: 获取所有历史版本文件信息
     *  获取当前文档所有历史版本的文件信息，以数组的形式,按版本号从大到小的顺序返回响应。(会影响历史版本相关的功能)
     */
    public function getWPSFileHistory()
    {
        try {
            /** @var HeaderBag $headers */
            $headers = $this->request->headers;
            $userId = $this->request->get('_w_userid');
            /** @var WPSAuthService $authService */
            $authService = app('App\EofficeApp\Document\Services\WPS\WPSAuthService');

            /** @var WPSFileService $fileService */
            $fileService = app('App\EofficeApp\Document\Services\WPS\WPSFileService');
            $fileId = $fileService->getFileIdFromHeaders($headers);
            $fileArr = $fileService->getHistoryFileArr($fileId, $userId);
            $data = [
                $fileArr
            ];

            return response()->json(['histories' => $data]);
        } catch (\Exception $exception) {
            $code = $exception->getCode() ? $exception->getCode() : 500;
            Log::error($exception->getMessage());

            return response()->json($exception->getMessage(), $code);
        }
    }

    /**
     * wps云文档回调: 新建文件
     *  在模板页选择对应的模板后，将新创建的文件上传到对接模块，返回访问此文件的跳转url。
     */
    public function createWPSFileNew()
    {
        // 获取上传文件
        $file = $this->request->file('file');
        $fileName = $this->request->get('name');
        $fileId = $this->request->get('_w_fileId');
        $userId = $this->request->get('_w_userid');

        /** @var WPSAttachmentService $attachmentService */
        $attachmentService = app('App\EofficeApp\Attachment\Services\WPSAttachmentService');
        $attachmentService->saveNewTemplateFile($fileId, $file,$fileName, $userId);

        /** @var WPSAuthService $AuthService */
        $AuthService = app('App\EofficeApp\Document\Services\WPS\WPSAuthService');
        $url = $AuthService->getUrl($fileId, $userId,  ['_w_model' => 'write']);

        $response = [
            'redirect_url'=> $url, //根据此url，可以访问到对应创建的文档
            'user_id'=> $userId  //创建此文档的用户id
        ];
        return response()->json($response);
    }

    /**
     * wps云文档回调: 回调通知
     *  若打开一个未打开的文件,将会回调两个通知:企业已经打开文件总数和错误信息。
     */
    public function getWPSOnNotify()
    {
        try {
            /** @var HeaderBag $headers */
            $headers = $this->request->headers;
            /** @var WPSAuthService $authService */
            $authService = app('App\EofficeApp\Document\Services\WPS\WPSAuthService');
            
            /** @var WPSFileService $fileService */
            $fileService = app('App\EofficeApp\Document\Services\WPS\WPSFileService');
            $result = $fileService->createNotifyLog($this->request->all());

            return  response()->json(['message'=> 'success']);
        } catch (\Exception $exception) {
            $code = $exception->getCode() ? $exception->getCode() : 500;
            Log::error($exception->getMessage());

            return response()->json($exception->getMessage(), $code);
        }
    }

    /**
     * wps根据文档id获取访问地址
     */
    public function getWpsDocumentUrl()
    {
        $userId = $this->own['user_id'];
        $documentId = $this->request->get('documentId');
        $model = $this->request->get('operation', 'read');

        /** @var DocumentContentRepository $repository */
        $repository = app('App\EofficeApp\Document\Repositories\DocumentContentRepository');
        $document = $repository->getDocumentInfo($documentId, []);
        if (!isset($document)) {
            return  $this->returnResult(['code' => ['0x041027', 'document']]);
        }
        // html类型则直接返回
        if (!$document->document_type) {
            return $this->returnResult(['url' => '', 'token' => '']);
        }
        $attachmentId = $document->content;
        if(empty($attachmentId) && ($document->file_type == 1 || $document->file_type == 2)){
            $attachmentParam = ['entity_table'=>'document_content', 'entity_id'=>$documentId];
            $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
            $document->attachment_id = $attachmentService->getAttachmentIdsByEntityId($attachmentParam);
            $attachmentId = is_array($document->attachment_id) && isset($document->attachment_id[0]) ? $document->attachment_id[0] : '';
        }
        if(empty($document) || !$attachmentId){
            return  $this->returnResult(['code' => ['0x041027', 'document']]);
        }

        /** @var WPSAuthService $authService */
        $authService = app('App\EofficeApp\Document\Services\WPS\WPSAuthService');
        // 获取wps请求地址
        $url = $authService->getUrl($attachmentId, $userId, ['_w_model' => $model]);
        $token = $authService->getToken($userId);

        return $this->returnResult(['url' => $url, 'token' => $token]);
    }

    /**
     * 本地创建word/excel空白文档
     */
    public function wpsCreateDocument()
    {
        $attachmentId = $this->request->get('attachmentId');
        $type = $this->request->get('type', WPSAuthService::W_TYPE);
        $userId = $this->own['user_id'];
        // TODO 验证文档类型

        /** @var WPSAttachmentService $attachmentService */
        $attachmentService = app('App\EofficeApp\Attachment\Services\WPSAttachmentService');
        $createResult = $attachmentService->createLocalDocument($attachmentId, $type, $userId);
        // 生成文档访问地址
        /** @var WPSAuthService $service */
        $service = app('App\EofficeApp\Document\Services\WPS\WPSAuthService');
        // 获取wps请求地址
        $url = $service->getUrl($attachmentId, $userId, ['_w_model' => 'write']);
        $token = $service->getToken($userId);

        return $this->returnResult(['url' => $url, 'token' => $token]);
    }

 public function followDocument($documentId) {
    	return $this->returnResult($this->documentService->followDocument($documentId, $this->own));
    }

    public function cancelFollow($documentId) {
    	return $this->returnResult($this->documentService->cancelFollow($documentId, $this->own));
    }

    public function batchSetPurview() {
        return $this->returnResult($this->documentService->batchSetPurview($this->request->all(), $this->own));
    }

    public function documentReadList($documentId) {
        return $this->returnResult($this->documentService->documentReadList($documentId, $this->own));
    }
    public function getCloudList() {
    	return $this->returnResult($this->documentService->getCloudList($this->request->all(), $this->own));
    }
    public function cloudMove() {
		return $this->returnResult($this->documentService->cloudMove($this->request->all(), $this->own));
	}
	public function cloudDelete() {
		return $this->returnResult($this->documentService->cloudDelete($this->request->all(), $this->own));
	}
    public function cloudCheckDelete() {
        return $this->returnResult($this->documentService->cloudCheckDelete($this->request->all(), $this->own));
    }
	// 云盘上传文件
	public function cloudUpload() {
		return $this->returnResult($this->documentService->cloudUpload($this->request->all(), $this->own));
	}
	// 获取用户某文件夹的权限
	public function getFolderPurviewByUser($folderId) {
		return $this->returnResult($this->documentService->getFolderPurviewByUser($folderId, $this->own));
	}
	// 获取文档列表初始化参数
	public function getDocumentListInit($folderId) {
        return $this->returnResult($this->documentService->getDocumentListInit($folderId, $this->own));
	}
	// 获取云盘下的文件
	public function getCloudDocument() {
		return $this->returnResult($this->documentService->getCloudDocument($this->request->all(), $this->own));
    }
    // 获取默认文件夹
    public function getDefaultFolder() {
        return $this->returnResult($this->documentService->getDefaultFolder());
    }
    // 导入样式模板
    public function importDocumentStyleTemplate() {
        return $this->returnResult($this->documentService->importDocumentStyleTemplate($this->request->all()));
    }
    // 导出样式模板
    public function exportDocumentStyleTemplate($modeId) {
        return $this->returnResult($this->documentService->exportDocumentStyleTemplate($modeId));
    }
    // 获取我的文档筛选字段
    public function getMyDocumentFields() {
        return $this->returnResult($this->documentService->getMyDocumentFields($this->own));
    }

    // 获取我的文档筛选字段标签数字
    public function getMyDocumentFieldsCount() {
        return $this->documentService->getMyDocumentFieldsCount($this->own);

    }
}
