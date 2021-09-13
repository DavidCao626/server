<?php
namespace App\EofficeApp\Document\Services;

use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Attachment\Services\PathDealService;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use Illuminate\Support\Facades\Log;
use App\EofficeApp\Document\Repositories\DocumentContentRepository;
use App\EofficeApp\System\Params\Entities\SystemParamsEntity;
use App\EofficeApp\LogCenter\Facades\LogCenter;
use Request;
use Eoffice;
use DB;
use Illuminate\Support\Facades\Redis;
/**
 * 文档模块服务类
 *
 * @author 李志军
 *
 * @since 2015-11-02
 */
class DocumentService extends BaseService
{
    /** @var object 文档内容资源库对象*/
    private $documentContentRepository;

    /** @var object 文件夹权限资源库对象*/
    private $documentFolderPurviewRepository;

    /** @var object 文件夹资源库对象*/
    private $documentFolderRepository;

    /** @var object 文档样式资源库对象*/
    private $documentModeRepository;

    /** @var object 文档回复资源库对象*/
    private $documentRevertRepository;

    /** @var object 文档共享资源库对象*/
    private $documentShareRepository;
    /** @var object 文档锁定资源库对象*/
    private $documentLockRepository;

    /** @var object 文档标签资源库对象*/
    private $documentTagRepository;

    /** @var object 文档标签资源库对象*/
    private $documentFollowRepository;

    /** @var object 流程步骤资源库对象*/
    private $flowRunStepRepository;

    /** @var object 流程签办反馈资源库对象*/
    private $flowRunFeedbackRepository;

    /** @var object 用户资源库对象*/
    private $userRepository;

    private $userSystemInfoRepository;

    /** @var object 部门资源库对象*/
    private $departmentRepository;

    /** @var object 角色资源库对象*/
    private $roleRepository;

    private $documentLogsRepository;

    private $logRepository;

    private $attachmentService;

    private $logService;

    private $logCenterService;
    /**
     * 注册文档相关的资源库类对象
     *
     * @param \App\EofficeApp\Document\Repositories\DocumentContentRepository $documentContentRepository
     * @param \App\EofficeApp\Document\Repositories\DocumentFolderPurviewRepository $documentFolderPurviewRepository
     * @param \App\EofficeApp\Document\Repositories\DocumentFolderRepository $documentFolderRepository
     * @param \App\EofficeApp\Document\Repositories\DocumentModeRepository $documentModeRepository
     * @param \App\EofficeApp\Document\Repositories\DocumentRevertRepository $documentRevertRepository
     * @param \App\EofficeApp\Document\Repositories\DocumentShareRepository $documentShareRepository
     * @param \App\EofficeApp\User\Repositories\UserRepository $userRepository
     * @param \App\EofficeApp\System\Department\Repositories\DepartmentRepository $departmentRepository
     * @param \App\EofficeApp\Role\Repositories\RoleRepository $roleRepository
     *
     * @author 李志军
     *
     * @since 2015-11-04
     */
    public function __construct() {
        parent::__construct();

        $this->documentContentRepository = 'App\EofficeApp\Document\Repositories\DocumentContentRepository';
        $this->documentFolderPurviewRepository = 'App\EofficeApp\Document\Repositories\DocumentFolderPurviewRepository';
        $this->documentFolderRepository = 'App\EofficeApp\Document\Repositories\DocumentFolderRepository';
        $this->documentModeRepository = 'App\EofficeApp\Document\Repositories\DocumentModeRepository';
        $this->documentRevertRepository = 'App\EofficeApp\Document\Repositories\DocumentRevertRepository';
        $this->documentShareRepository = 'App\EofficeApp\Document\Repositories\DocumentShareRepository';
        $this->flowRunStepRepository = 'App\EofficeApp\Flow\Repositories\FlowRunStepRepository';
        $this->flowRunFeedbackRepository = 'App\EofficeApp\Flow\Repositories\FlowRunFeedbackRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->departmentRepository = 'App\EofficeApp\System\Department\Repositories\DepartmentRepository';
        $this->roleRepository = 'App\EofficeApp\Role\Repositories\RoleRepository';
        $this->documentLogsRepository = 'App\EofficeApp\Document\Repositories\DocumentLogsRepository';
        $this->logRepository = 'App\EofficeApp\LogCenter\Repositories\LogRepository';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->documentLockRepository = 'App\EofficeApp\Document\Repositories\DocumentLockRepository';
        $this->documentTagRepository = 'App\EofficeApp\Document\Repositories\DocumentTagRepository';
        $this->logService = 'App\EofficeApp\System\Log\Services\LogService';
        $this->documentFollowRepository = 'App\EofficeApp\Document\Repositories\DocumentFollowRepository';
        $this->logCenterService = 'App\EofficeApp\LogCenter\Services\LogCenterService';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
    }
    /**
     * 获取样式列表
     *
     * @param array $param
     *
     * @return array 样式列表
     *
     * @author 李志军
     *
     * @since 2015-11-02
     */
    public function listMode($param)
    {
        return $this->response(app($this->documentModeRepository), 'getModeCount', 'listMode', $this->parseParams($param));
    }
    /**
     * 新建样式
     *
     * @param array $data
     *
     * @return array 样式id
     *
     * @author 李志军
     *
     * @since 2015-11-02
     */
    public function addMode($data)
    {
        $modeData = [
            'mode_title'   => $data['mode_title'],
            'mode_content' => $this->defaultValue('mode_content', $data, ''),
        ];

		if (!$result = app($this->documentModeRepository)->insertData($modeData)) {
			return ['code' => ['0x000003', 'common']];
		}

		return ['mode_id' => $result->mode_id];
	}
	/**
	 * 编辑样式
	 *
	 * @param array $data
	 * @param int $modeId
	 *
	 * @return int 编辑结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function editMode($data, $modeId)
	{
		if ($modeId == 0) {
			return ['code' => ['0x041002', 'document']];
		}

		$modeData = [
			'mode_title'	=> $data['mode_title'],
			'mode_content'	=> $this->defaultValue('mode_content', $data, '')
		];

		if (!app($this->documentModeRepository)->updateData($modeData, ['mode_id' => $modeId])) {
			return ['code' => ['0x000003', 'common']];
		}

		return true;
	}
	/**
	 * 获取样式详情
	 *
	 * @param int $modeId
	 *
	 * @return object 样式详情
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function showMode($modeId)
	{
		if ($modeId == 0) {
			return ['code' => ['0x041002', 'document']];
		}

		return app($this->documentModeRepository)->getDetail($modeId);
	}
	/**
	 * 删除样式
	 *
	 * @param int $modeId
	 *
	 * @return int 删除结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function deleteMode($modeId)
	{
		if ($modeId == 0) {
			return ['code' => ['0x041002', 'document']];
		}

		if (in_array($modeId, [1, 2])) {
			return ['code' => ['0x041025', 'document']];
		}

		if (app($this->documentModeRepository)->deleteById(explode(',', $modeId))) {
			return true;
		}

		return ['code' => ['0x000003', 'common']];
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
		if (!in_array($modeId, [1, 2])) {
			return ['code' => ['0x041024', 'document']];
		}

		$mode = app($this->documentModeRepository)->getDetail(0);

		if (app($this->documentModeRepository)->updateData(['mode_content' => $mode->mode_content], ['mode_id' => $modeId])) {
			return true;
		}

		return ['code' => ['0x000003', 'common']];
	}
	/**
	 * 新建文件夹
	 *
	 * @param array $data
	 * @return array 文件夹id
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function addFolder($data, $own)
	{
		$parentId = $this->defaultValue('parent_id', $data, 0);

		$folderType = $this->defaultValue('folder_type', $data, 1);

		if(!$this->hasAddFolderPurview($parentId, $own, $folderType)){
			return ['code' => ['0x041004', 'document']];
		}

		$levelId = $this->getFolderLevelId($parentId);

		$folderData = [
			'folder_level_id'	=> $levelId,
			'parent_id'			=> $parentId,
			'folder_name'		=> trim($data['folder_name']),
			'folder_type'		=> $folderType,
			'user_id'			=> $own['user_id']
		];

		if (!$result = app($this->documentFolderRepository)->insertData($folderData)) {
			return ['code' => ['0x000003', 'common']];
		}

		if (!$this->addFolderPurview($result->folder_id, $parentId, $levelId)) {
			return ['code' => ['0x041006', 'document']];
		}

		return ['folder_id' => $result->folder_id];
	}

	/**
	 * 批量新建文件夹
	 *
	 * @param \App\Http\Requests\DocumentRequest $request
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function batchAddFolder($data, $own)
	{
		$parentId = $this->defaultValue('parent_id', $data, 0);

		if(!$this->hasAddFolderPurview($parentId, $own, 1)){
			return ['code' => ['0x041004', 'document']];
		}

		$parentFolder = app($this->documentFolderRepository)->getFolderInfo($parentId);
		$levelId = $parentId == 0 ? '0' :$parentFolder->folder_level_id . ',' . $parentId;
		$purviewExtends = $parentFolder->purview_extends ?? 0;
		$purviewType = $parentFolder->purview_type ?? 1;

		foreach ($data['folder_names'] as $folderName) {
			$folderData = [
				'folder_level_id'	=> $levelId,
				'parent_id'			=> $parentId,
				'folder_name'		=> trim($folderName),
				'folder_type'		=> 1,
				'user_id'			=> $own['user_id'],
				'purview_extends'   => $purviewType == 1 ? 0 : $purviewExtends,
				'purview_type'		=> $purviewType
			];

			if (!$result = app($this->documentFolderRepository)->insertData($folderData)) {
				return ['code' => ['0x000003', 'common']];
			}

			if (!$this->addFolderPurview($result->folder_id, $parentId, $levelId, $own['user_id'])) {
				return ['code' => ['0x041006', 'document']];
			}
		}

		return true;
	}

	private function hasAddFolderPurview($parentId, $own, $check)
	{
		if($check == 1){
			if (($parentId == 0 && $own['user_id'] != 'admin') || ($parentId != 0 && !$this->hasManagerPurview($parentId, $own)))  {
				return false;
			}
		}

		return true;
	}
	/**
	 * 编辑文件夹
	 *
	 * @param array $data
	 * @param int $folderId
	 *
	 * @return int 编辑结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function editFolder($data, $folderId, $own)
	{
		if ($folderId == 0) {
			return ['code' => ['0x041005', 'document']];
		}

		if (!$this->hasManagerPurview($folderId, $own)) {
			return ['code' => ['0x041007', 'document']];
		}

		if (!app($this->documentFolderRepository)->updateData(['folder_name' => $data['folder_name']], ['folder_id' => $folderId])) {
			return ['code' => ['0x000003', 'common']];
		}

		return true;
	}
	/**
	 * 复制文件夹
	 *
	 * @param array $data
	 * @param int $folderId
	 *
	 * @return int 复制结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function copyFolder($data, $folderId, $own)
	{
		if ($folderId == 0) {
			return ['code' => ['0x041005', 'document']];
		}
		if (!$this->hasManagerPurview($folderId, $own)) {
			return ['code' => ['0x041007', 'document']];
		}
		$folder = app($this->documentFolderRepository)->getFolderInfo($folderId)->toArray();
		$folder['folder_name'] = $data['folder_name'];
        $folder['user_id'] = $own['user_id'] ?? 'admin';
        if(!$data['purview_extends']){
            unset($folder['purview_extends'], $folder['purview_type']);
        }
		unset($folder['folder_id'], $folder['deleted_at'], $folder['created_at'], $folder['updated_at'], $folder['is_default']);
		if (!$result = app($this->documentFolderRepository)->insertData($folder)) {
			return ['code' => ['0x000003', 'common']];
		}
        $purview = $this->getPurviewArray($folderId,true);
        if($data['purview_extends']){
            $purview['folder_id'] = $result->folder_id;
            if (!app($this->documentFolderPurviewRepository)->insertData($purview)) {
                return ['code' => ['0x041006', 'document']];
            }
        }else{
            $newPurview = [
                'folder_id' => $result->folder_id,
                'parent_id' => $purview['folder_id'] ?? 0,
                'folder_level_id' => $purview['folder_level_id'] ?? 0,
                'user_new' => $own['user_id'] ?? 'admin',
            ];
            if (!app($this->documentFolderPurviewRepository)->insertData($newPurview)) {
                return ['code' => ['0x041006', 'document']];
            }
        }
		return true;
	}
	/**
	 * 删除文件夹
	 *
	 * @param int $folderId
	 *
	 * @return int 删除结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function deleteFolder($folderId, $own)
	{
		if ($folderId == 0) {
			return ['code' => ['0x041005', 'document']];
		}

		if (!$this->hasManagerPurview($folderId, $own)) {
			return ['code' => ['0x041007', 'document']];
		}

		if ($this->hasChildrenFolders($folderId)) {
			return ['code' => ['0x041008', 'document']];
		}

		if ($this->hasDocumentsOfFolder($folderId)) {
			return ['code' => ['0x041009', 'document']];
		}

		if (!app($this->documentFolderRepository)->deleteById($folderId)) {
			return ['code' => ['0x041010', 'document']];
		}

		if (!app($this->documentFolderPurviewRepository)->deleteByWhere(['folder_id' => [$folderId]])) {
			return ['code' => ['0x041011', 'document']];
		}

		return true;
	}
	/**
	 * 获取文件夹详情
	 *
	 * @param int $folderId
	 *
	 * @return object 文件夹详情
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function showFolder($folderId, $own, $params=[])
	{
		if ($folderId == 0) {
			return ['code' => ['0x041005', 'document']];
		}

		$folderInfo = app($this->documentFolderRepository)->getFolderInfo($folderId);

		if (isset($params['type']) && $params['type'] == 'add') {
			if ($this->hasCreatePurview($folderId, $own)) {
				return $folderInfo;
			}
		}

		if (!($folderInfo->user_id == $own['user_id'] || app($this->documentFolderPurviewRepository)->hasShowPurview($folderId, $own)
		)) {
			return ['code' => ['0x041026', 'document']];
		}
        $breadcrumbList = [];
        if($folderInfo->folder_level_id){
            $folderLevelIds = explode(',', rtrim($folderInfo->folder_level_id, ','));
            if($folderLevelIds){
                foreach ($folderLevelIds as $folderLevelId){
                    if($folderLevelId){
                        $folderLevelInfo = app($this->documentFolderRepository)->getFolderInfo($folderLevelId);
                        $breadcrumb = ['id' => $folderLevelId, 'name' => $folderLevelInfo->folder_name];
                        array_push($breadcrumbList, $breadcrumb);
                    }
                }
            }
        }
        array_push($breadcrumbList, ['id' => $folderId, 'name' => $folderInfo->folder_name]);
        $folderInfo->breadcrumbList = $breadcrumbList ?? [];

		return $folderInfo;

	}

    /**
     * 获取文件夹详情（无权限）
     * @author yangxingqiang
     * @param $folderId
     * @param $own
     * @param array $params
     * @return array
     */
	public function showFolderInfo($folderId, $own, $params=[])
	{
		if ($folderId == 0) {
			return ['code' => ['0x041005', 'document']];
		}

		$folderInfo = app($this->documentFolderRepository)->getFolderInfo($folderId);

		if (isset($params['type']) && $params['type'] == 'add') {
			if ($this->hasCreatePurview($folderId, $own)) {
				return $folderInfo;
			}
		}

        $breadcrumbList = [];
        if($folderInfo->folder_level_id){
            $folderLevelIds = explode(',', rtrim($folderInfo->folder_level_id, ','));
            if($folderLevelIds){
                foreach ($folderLevelIds as $folderLevelId){
                    if($folderLevelId){
                        $folderLevelInfo = app($this->documentFolderRepository)->getFolderInfo($folderLevelId);
                        $breadcrumb = ['id' => $folderLevelId, 'name' => $folderLevelInfo->folder_name];
                        array_push($breadcrumbList, $breadcrumb);
                    }
                }
            }
        }
        array_push($breadcrumbList, ['id' => $folderId, 'name' => $folderInfo->folder_name]);
        $folderInfo->breadcrumbList = $breadcrumbList ?? [];

		return $folderInfo;

	}
	/**
	 * 文件夹基本设置
	 *
	 * @param array $data
	 * @param int $folderId
	 *
	 * @return int 设置结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function setFolderBaseInfo($data, $folderId, $own)
	{
		if ($folderId == 0) {
			return ['code' => ['0x041005', 'document']];
		}

		if (!$this->hasManagerPurview($folderId, $own)) {
			return ['code' => ['0x041012', 'document']];
		}

		$extends = $this->defaultValue('purview_extends', $data, 0);

		$type = $this->defaultValue('purview_type', $data, 1);

		$mainData = [
			'folder_name' 		=> trim($data['folder_name']),
			'purview_extends' 	=> $extends,
			'purview_type'		=> $type
		];

		if (!app($this->documentFolderRepository)->updateData($mainData, ['folder_id' => $folderId])) {
			return ['code' => ['0x000003', 'common']];
		}

		//更新子文件夹基本设置
		if ($type == 2 || $type == 3) {
			app($this->documentFolderRepository)->updateChildrenData(['purview_extends' => $extends], $folderId);
		}
		//更新子文件夹权限
		if ($type == 3) {
			if(!$this->copyPurviewToChildren($folderId)) {
				return ['code' => ['0x041014', 'document']];
			}
		}

		return true;
	}

	public function editFolderName($folderId,$folderName,$own)
	{
		if ($folderId == 0) {
			return ['code' => ['0x041005', 'document']];
		}

		if (!$this->hasManagerPurview($folderId, $own)) {
			return ['code' => ['0x041012', 'document']];
		}

		if (!app($this->documentFolderRepository)->updateData(['folder_name' => trim($folderName)], ['folder_id' => $folderId])) {
			return ['code' => ['0x000003', 'common']];
		}

		return true;
	}
	/**
	 * 设置文件夹权限
	 *
	 * @param array $data
	 * @param int $folderId
	 *
	 * @return int 设置结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function setPurview($data, $folderId, $own)
	{
		if ($folderId == 0) {
			return ['code' => ['0x041005', 'document']];
		}

		if (!$this->hasManagerPurview($folderId, $own)) {
			return ['code' => ['0x041012', 'document']];
		}

		$purviewTypes = ['manage','edit','delete','new','down','print','revert','view'];

		$purviewMembers = ['user','role','dept'];

		$purview = [
			'all_purview' => $this->defaultValue('all_purview', $data, 0)
		];

		foreach($purviewMembers as $purviewMember) {
			foreach($purviewTypes as $purviewType) {
				$purviewKey = $purviewMember . '_' . $purviewType;
				// 判断是否设置为全体
				if($data[$purviewKey] == 'all'){
					$purview[$purviewKey] = 'all';
				}else{
					$purview[$purviewKey] = implode(',',$this->defaultValue($purviewKey, $data, []));
				}
			}
		}

		if (!app($this->documentFolderPurviewRepository)->updateData($purview, ['folder_id' => $folderId])){
			return ['code' => ['0x000003', 'common']];
        }
        // 更新基础设置，权限继承
        $extends = $this->defaultValue('purview_extends', $data, 0);
        $type = $this->defaultValue('purview_type', $data, 1);

		$mainData = [
            'purview_extends' 	=> $extends,
            'purview_type' => $type,
		];
		if (!app($this->documentFolderRepository)->updateData($mainData, ['folder_id' => $folderId])) {
			return ['code' => ['0x000003', 'common']];
		}
		//更新子文件夹基本设置
		if ($type == 2 || $type == 3) {
			app($this->documentFolderRepository)->updateChildrenData(['purview_extends' => $extends,], $folderId);
		}
		//更新子文件夹权限设置
		if ($type == 3) {
			if(!$this->copyPurviewToChildren($folderId)) {
				return ['code' => ['0x041014', 'document']];
			}
		}

		return true;

    }
    // 批量设置文件夹权限
    public function batchSetPurview($data, $own) {
        if (!isset($data['folder_id']) || empty($data['folder_id'])) {
            return ['code' => ['0x041005', 'document']];
        }

        $folderIds = explode(',', $data['folder_id']);

        $purviewTypes = ['manage','edit','delete','new','down','print','revert','view'];

		$purviewMembers = ['user','role','dept'];

		$purview = [
			'all_purview' => $this->defaultValue('all_purview', $data, 0)
		];

		foreach($purviewMembers as $purviewMember) {
			foreach($purviewTypes as $purviewType) {
				$purviewKey = $purviewMember . '_' . $purviewType;
				// 判断是否设置为全体
				if($data[$purviewKey] == 'all'){
					$purview[$purviewKey] = 'all';
				}else{
					$purview[$purviewKey] = implode(',',$this->defaultValue($purviewKey, $data, []));
				}
			}
		}

		if (!app($this->documentFolderPurviewRepository)->updateData($purview, ['folder_id' => [$folderIds, 'in']])){
			return ['code' => ['0x000003', 'common']];
        }
        // 更新基础设置，权限继承
        $extends = $this->defaultValue('purview_extends', $data, 0);
        $type = $this->defaultValue('purview_type', $data, 1);

		$mainData = [
            'purview_extends' => $extends,
            'purview_type' => $type,
        ];
        
		if (!app($this->documentFolderRepository)->updateData($mainData, ['folder_id' => [$folderIds, 'in']])) {
			return ['code' => ['0x000003', 'common']];
        }

        $manageIds = [];
        if ($type == 2 || $type == 3) {
            foreach (array_filter($folderIds) as $value) {
                $temp = $this->getManageChildrenFolderId($value, $own, true);
                $manageIds = array_merge($manageIds, $temp);
            }
        }
		//更新子文件夹基本设置
		if ($type == 2 || $type == 3) {
			app($this->documentFolderRepository)->updateData(['purview_extends' => $extends], ['folder_id' => [$manageIds, 'in']]);
		}
		//更新子文件夹权限设置
		if ($type == 3) {
			app($this->documentFolderPurviewRepository)->updateChildrenData($purview, $manageIds);
		}

		return true;
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
	public function getPurview($folderId,$view = false)
	{
        $folderInfo = app($this->documentFolderRepository)->getDetail($folderId);
		$purview =  app($this->documentFolderPurviewRepository)->getPurviewInfo(['folder_id' => $folderId]);

		$purviewTypes = ['manage','edit', 'delete','new','down','print','revert','view'];

		$purviewMembers = ['user','role','dept'];

		foreach($purviewMembers as $purviewMember){
			foreach($purviewTypes as $purviewKey){
				$purviewKey = $purviewMember . '_' . $purviewKey;

				$purview->{$purviewKey} = ($purviewMember === 'user' || $purview->{$purviewKey} == 'all')
											? $this->stringToArray($purview->{$purviewKey})
											: $this->stringToArray($purview->{$purviewKey},'int');
				if($view){
					if(isset($purview->{$purviewKey}[0]) && $purview->{$purviewKey}[0] == 'all'){
						$all = trans('document.all');
						$purview->{$purviewKey} = [$all];
					}else{
						$purview->{$purviewKey} = app($this->documentFolderRepository)->getFieldNameByIds($purview->{$purviewKey},$purviewMember . '_name');
					}
				}
			}
        }

        $purview->purview_extends = isset($folderInfo->purview_extends) ? $folderInfo->purview_extends : 0;
        $purview->purview_type = isset($folderInfo->purview_type) ? $folderInfo->purview_type : 1;

		return $purview;
	}
	/**
	 * 设置展示样式
	 *
	 * @param array $data
	 * @param int $folderId
	 *
	 * @return int 设置结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function setShowMode($data, $folderId, $own)
	{
		if ($folderId == 0) {
			return ['code' => ['0x041005', 'document']];
		}

		if (!$this->hasManagerPurview($folderId, $own)) {
			return ['code' => ['0x041012', 'document']];
		}

		$mode = [
			'mode_id' => $this->defaultValue('mode_id', $data, 0)
		];

		app($this->documentFolderRepository)->updateData($mode, ['folder_id' => $folderId]);

		return true;
	}
	public function batchSetModeAndTemplate($data,$own)
	{
		$templateData = [
			'template_id' 	=> $this->defaultValue('template_id', $data, 0),
			'mode_id'		=> $this->defaultValue('mode_id', $data, 0)
		];

		$folderIds = explode(',',rtrim($this->defaultValue('folder_id', $data, ''),','));

		$data = [];

		foreach ($folderIds as $value) {
			$templateData['folder_id'] = $value;

			$data[] = $templateData;
		}

		app($this->documentFolderRepository)->mulitUpdateFolder($data, 'folder_id');

		return true;
	}
	
    public function batchMoveFolder($fromIds, $toId, $own)
	{
		$fromIds = explode(',', rtrim($fromIds,','));
		$folder = app($this->documentFolderRepository)->getFolderInfo($fromIds[0], ['folder_level_id', 'parent_id']);
		if ($folder->parent_id == $toId) {
			return true;
		}
        if ($toId != 0 && !$this->hasManagerPurview($toId, $own)) {
            return ['code' => ['0x041012', 'document']];
        }
		$levelId = $this->getFolderLevelId($toId);
		$updateData = [
			'parent_id' 		=> $toId,
			'folder_level_id' 	=> $levelId
		];
		foreach ($fromIds as $fromId) {
			if (!app($this->documentFolderRepository)->updateData($updateData, ['folder_id' => $fromId])) {
				return false;
			}

			if (!app($this->documentFolderPurviewRepository)->updateData($updateData, ['folder_id' => $fromId])) {
				return false;
			}

			if (!app($this->documentFolderRepository)->updateChildrenLevelId($folder->folder_level_id, $levelId, $fromId)) {
				return false;
			}

			if (!app($this->documentFolderPurviewRepository)->updateChildrenLevelId($folder->folder_level_id, $levelId, $fromId)) {
				return false;
			}
		}

		return true;
	}

	public function batchDeleteFolder($folderIds,$own)
	{
		$folderIds = explode(',', rtrim($folderIds,','));

		if (empty($folderIds) || $folderIds[0] == 0) {
			return ['code' => ['0x041005', 'document']];
		}
		//判断是否有删除权限
		foreach ($folderIds as $folderId) {
            $folder = app($this->documentFolderRepository)->getFolderInfo($folderId, ['is_default']);
            if ($folder->is_default == 1) {
                return ['code' => ['0x041035', 'document']];
            }

			if (!$this->hasManagerPurview($folderId, $own)) {
				return ['code' => ['0x041007', 'document']];
			}

			if ($this->hasChildrenFolders($folderId)) {
				return ['code' => ['0x041008', 'document']];
			}

			if ($this->hasDocumentsOfFolder($folderId)) {
				return ['code' => ['0x041009', 'document']];
			}
		}
		//删除文档目录
		if (!app($this->documentFolderRepository)->deleteById($folderIds)) {
			return ['code' => ['0x041010', 'document']];
		}
		//删除目录权限
		if (!app($this->documentFolderPurviewRepository)->deleteByWhere(['folder_id' => [$folderIds,'in']])) {
			return ['code' => ['0x041011', 'document']];
		}

		return true;
	}

    /**
     * 批量检查文件夹权限
     * @author yangxingqiang
     * @param $folderIds
     * @param $own
     * @return array|bool
     */
	public function batchCheckFolder($folderIds,$own)
	{
		$folderIds = explode(',', rtrim($folderIds,','));
		if (empty($folderIds) || $folderIds[0] == 0) {
			return ['code' => ['0x041005', 'document']];
		}
		//判断是否有删除权限
		foreach ($folderIds as $folderId) {
            $folder = app($this->documentFolderRepository)->getFolderInfo($folderId, ['is_default']);
            if ($folder->is_default == 1) {
                return ['code' => ['0x041035', 'document']];
            }
			if (!$this->hasManagerPurview($folderId, $own)) {
				return ['code' => ['0x041007', 'document']];
			}
			if ($this->hasChildrenFolders($folderId)) {
				return ['code' => ['0x041008', 'document']];
			}
			if ($this->hasDocumentsOfFolder($folderId)) {
				return ['code' => ['0x041009', 'document']];
			}
		}
		return true;
	}

	/**
	 * 文件夹排序
	 *
	 * @param string $folderIds
	 *
	 * @return int 排序结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function sortFolder($folderIds)
	{
		$folderIds = explode(',', rtrim($folderIds,','));

		if (empty($folderIds) || $folderIds[0] == 0) {
			return ['code' => ['0x041005', 'document']];
		}

		$data = [];

		foreach ($folderIds as $key => $value) {
			$data[] = ['folder_sort' => $key + 1, 'folder_id' => $value];
		}

		app($this->documentFolderRepository)->mulitUpdateFolder($data, 'folder_id');

		return true;
	}
	/**
	 * 文件夹转移
	 *
	 * @param int $fromId
	 * @param int $toId
	 *
	 * @return int 转移结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function migrateFolder($fromId, $toId)
	{
		$folderInfo = app($this->documentFolderRepository)->getFolderInfo($fromId, ['folder_level_id', 'parent_id']);

		if ($folderInfo->parent_id == $toId) {
			return true;
		}

		$folderLevelId = $this->getFolderLevelId($toId);

		$updateData = [
			'parent_id' => $toId,
			'folder_level_id' => $folderLevelId
		];

		if (!app($this->documentFolderRepository)->updateData($updateData, ['folder_id' => $fromId])) {
			return ['code' => ['0x000003', 'common']];
		}

		if (!app($this->documentFolderPurviewRepository)->updateData($updateData, ['folder_id' => $fromId])) {
			return ['code' => ['0x000003', 'common']];
		}

		if (!app($this->documentFolderRepository)->updateChildrenLevelId($folderInfo->folder_level_id, $folderLevelId, $fromId)) {
			return ['code' => ['0x000003', 'common']];
		}

		if (!app($this->documentFolderPurviewRepository)->updateChildrenLevelId($folderInfo->folder_level_id, $folderLevelId, $fromId)) {
			return ['code' => ['0x000003', 'common']];
		}

		return true;
	}
	/**
	 * 设置模板
	 *
	 * @param array $data
	 * @param int $folderId
	 *
	 * @return int 设置结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function setTemplate($data, $folderId, $own)
	{
		if ($folderId == 0) {
			return ['code' => ['0x041005', 'document']];
		}

		if (!$this->hasManagerPurview($folderId, $own)) {
			return ['code' => ['0x041012', 'document']];
		}

		$templateData = [
			'template_id' => $this->defaultValue('template_id', $data, 0)
		];

		app($this->documentFolderRepository)->updateData($templateData, ['folder_id' => $folderId]);

		return true;
	}

	public function getAllChildrenFolder($fields, $parentId)
	{
		$children = app($this->documentFolderRepository)->getAllChildrenFolder(['fields' => $fields ? explode(',', $fields) : ['*']], $parentId ? $parentId : 0);

		if (count($children) == 0) {
			return [];
		}

		$folders = [];

		foreach($children as $folder){
			if($this->hasChildrenFolders($folder->folder_id)){
				$folder->has_children = 1;
			}

			$folders[] = $folder;
		}

		return $folders;
	}

	public function listAllFolder($param)
    {
		return $this->response(app($this->documentFolderRepository), 'getFolderCount', 'listFolder', $this->parseParams($param));
	}
	/**
	 * 获取管理列表文件夹
	 *
	 * @param string $fields
	 * @param int $parentId
	 *
	 * @return array 管理列表文件夹
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-06
	 */
	public function getManageChildrenFolder($fields, $parentId, $own)
	{
		$childrens = app($this->documentFolderRepository)->getAllChildrenFolder(['fields' => $fields ? explode(',', $fields) : ['*']], $parentId);

		if (count($childrens) == 0) {
			return [];
		}

		$folders = [];

		if ($own['user_id'] == 'admin') {
			foreach ($childrens as $folder) {
				if($this->hasViewManageFolder($folder->folder_id, $own)) {
					$folder->has_children = 1;
				}

				$folders[] = $folder;
			}

			return $folders;
		}

		$authChildrenFolderId = $this->getManageChildrenFolderId($parentId, $own);

		foreach ($childrens as $folder) {
			if (in_array($folder->folder_id, $authChildrenFolderId)) {
				if($this->hasViewManageFolder($folder->folder_id, $own)) {
					$folder->has_children = 1;
				}

				$folders[] = $folder;
			} else if ($this->hasViewManageFolder($folder->folder_id, $own)) {
				$folder->no_manager_auth = 1;

				$folder->has_children = 1;

				$folders[] = $folder;
			}
		}

		return $folders;
	}
	/**
	 * 获取新建文档列表文件夹
	 *
	 * @param array $fields
	 * @param int $parentId
	 *
	 * @return array 文件夹列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-06
	 */
	public function getCreateChildrenFolder($fields,$parentId, $own)
	{
		$allChildrenFolder = app($this->documentFolderRepository)->getAllChildrenFolder(['fields' => $fields ? explode(',', $fields) : ['*']], $parentId);

		if (count($allChildrenFolder) == 0) {
			return [];
		}

		$authChildrenFolderId = $this->getCreateChildrenFolderId($parentId, $own);

		$createFolder = [];

		foreach ($allChildrenFolder as $folder) {
			if (in_array($folder->folder_id, $authChildrenFolderId)) {
                if($this->hasViewCreateFolder($folder->folder_id, $own)) {
                    $folder->has_children = 1;
                }

				$createFolder[] = $folder;
			} else if ($this->hasViewCreateFolder($folder->folder_id, $own)) {
				$folder->no_create_auth = 1;

                $folder->has_children = 1;

				$createFolder[] = $folder;
			}
		}

		return $createFolder;
	}
	/**
	 * 获取展示文档列表的文件夹列表
	 *
	 * @param array $fields
	 * @param int $parentId
	 *
	 * @return array 文件夹列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-06
	 */
	public function getShowChildrenFolder($params, $parentId, $own)
	{
		$params = $this->parseParams($params);

		$allChildrenFolder = app($this->documentFolderRepository)->getAllChildrenFolder($params, $parentId);

		if (count($allChildrenFolder) == 0) {
			return [];
		}

		$authChildrenFolderId = $this->getShowChildrenFolderId($parentId, $own);

		$showFolder = [];

		foreach ($allChildrenFolder as $folder) {
			if ($folder->folder_id == -1) {
				$showFolder[] = $folder;
			} else if (in_array($folder->folder_id, $authChildrenFolderId)) {

				if($this->hasViewShowFolder($folder->folder_id, $own)){
					$folder->has_children = 1;
				}

				$showFolder[] = $folder;
			} else if ($this->hasShareDocumentOfFolder($folder->folder_id, $own)) {
				$folder->has_share_auth = 1;

				if($this->hasViewShowFolder($folder->folder_id, $own)){
					$folder->has_children = 1;
				}

				$showFolder[] = $folder;
			} else if ($this->hasCreatorDocumentOfFolder($folder->folder_id, $own)) {
				$folder->has_creator_document = 1;

				if($this->hasViewShowFolder($folder->folder_id, $own)){
					$folder->has_children = 1;
				}

				$showFolder[] = $folder;
			} else if ($this->hasViewShowFolder($folder->folder_id, $own)) {
				$folder->no_show_auth = 1;

				$folder->has_children = 1;

				$showFolder[] = $folder;
			}
		}

		return $showFolder;
	}

    /**
     * 获取云盘模式文件夹列表
     * @author yangxingqiang
     * @param $params
     * @param $parentId
     * @param $own
     * @return array
     */
	public function getCouldListFolderId($params, $parentId, $own)
	{
		$params = $this->parseParams($params);
		$allChildrenFolder = app($this->documentFolderRepository)->getFamilyFolder($params, $parentId);
		if (count($allChildrenFolder) == 0) {
			return [];
		}
        $authChildrenFolderId = $this->getShowFamilyFolderId($parentId, $own);
        $showFolder = [];
		foreach ($allChildrenFolder as $folder) {
			if ($folder->folder_id == -1) {
				$showFolder[] = $folder;
			} else if (in_array($folder->folder_id, $authChildrenFolderId)) {
				if($this->hasViewShowFolder($folder->folder_id, $own)){
					$folder->has_children = 1;
				}
				$showFolder[] = $folder;
			} else if ($this->hasShareDocumentOfFolder($folder->folder_id, $own)) {
				$folder->has_share_auth = 1;
				if($this->hasViewShowFolder($folder->folder_id, $own)){
					$folder->has_children = 1;
				}
				$showFolder[] = $folder;
			} else if ($this->hasCreatorDocumentOfFolder($folder->folder_id, $own)) {
				$folder->has_creator_document = 1;
				if($this->hasViewShowFolder($folder->folder_id, $own)){
					$folder->has_children = 1;
				}
				$showFolder[] = $folder;
			} else if ($this->hasViewShowFolder($folder->folder_id, $own)) {
				$folder->no_show_auth = 1;
				$folder->has_children = 1;
				$showFolder[] = $folder;
			}
		}
		return $showFolder;
	}

	// 文档树，文件夹下带有文档，供文档选择器使用
	public function getDocumentTree($parentId, $own) {
		$data 	 = [];
		$folders = $this->getShowChildrenFolder([], $parentId, $own);
		array_map(function($value) use (&$data){
			$data[] = [
				'folder_id' 	=> $value['folder_id'],
				'folder_name' 	=> $value['folder_name'],
				'parent_id' 	=> $value['parent_id'],
				"has_children"  => 1,
			];
		}, $folders);

		if($parentId != 0){
			$param 	   = ['search' => ["folder_id" => [$parentId]]];
			$documents = $this->listDocument($param, $own);
			if(isset($documents['list']) && !empty($documents['list'])){
				array_map(function($value) use (&$data, &$parentId){
					$data[] = [
						'folder_id' 	=> '',
						'folder_name' 	=> $value['subject'],
						'parent_id' 	=> $parentId,
						"has_children"  => 0,
					];
				}, $documents['list']);
			}
		}

		return $data;
	}

	public function listShowFolder($param, $own)
	{
		$param = $this->parseParams($param);

		$showFolderIds = array_column(app($this->documentFolderPurviewRepository)->getAllShowFolderId($own)->toArray(), 'folder_id');

		$shareFolderIds = array_column(app($this->documentShareRepository)->getAllShareDocumentFolder($own), 'folder_id');

		$creatorFolderIds = array_column(app($this->documentContentRepository)->getAllCreatorDocumentFolder($own['user_id']), 'folder_id');

		$param['folder_id'] = array_unique(array_merge($showFolderIds,$shareFolderIds,$creatorFolderIds));

		return $this->response(app($this->documentFolderRepository), 'getFolderCount', 'listFolder', $param);
	}

    public function listCreateFolder($param, $own)
    {
    	$param = $this->parseParams($param);

		$param['folder_id'] = array_column(app($this->documentFolderPurviewRepository)->getAllCreateFolderId($own), 'folder_id');

		return $this->response(app($this->documentFolderRepository), 'getFolderCount', 'listFolder', $param);
	}

	public function listManageFolder($param, $own)
	{
		$param = $this->parseParams($param);

		if($own['user_id'] != 'admin') {
			$param['folder_id'] = array_column(app($this->documentFolderPurviewRepository)->getAllManageFolderId($own), 'folder_id');

        	$param['creator']   = $own['user_id'];
		}

		return $this->response(app($this->documentFolderRepository), 'getFolderCount', 'listFolder', $param);
	}

//    public function listManageFolder($param, $own)
//    {
//        $param = $this->parseParams($param);
//
//        if($own['user_id'] != 'admin') {
//            $param['folder_id'] = array_column(app($this->documentFolderPurviewRepository)->getAllManageFolderId($own), 'folder_id');
//
//            $param['creator']   = $own['user_id'];
//        }
//        $res = $this->response(app($this->documentFolderRepository), 'getFolderCount', 'listFolder', $param);
//        $list = $res['list']->toArray();
//        $list1 = [];
//        foreach ($list as $item){
//            if($item['parent_id'] == 0){
//                $parent_name = '公共文件夹';
//            }else{
//                $parent = app($this->documentFolderRepository)->getFolderInfo($item['parent_id']);
//                $parent_name = $parent['folder_name'] ?? '';
//            }
//            $item['parent_name'] = $item['folder_name'].'（'.$parent_name.'）';
//            array_push($list1,$item);
//        }
//        return ['total' => $res['total'],'list' => $list1];
//    }

    /**
	 * 获取文档列表
	 *
	 * @param array $param
	 *
	 * @return array 文档列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-17
	 */
	public function listDocument($param, $own)
	{
        $param				= $this->handleDocumentSearch($this->parseParams($param));//解析和处理文档搜索参数

		$response           = $this->defaultValue('response', $param, 'both');

        $folderId = 0;

        $hasTagDocumentId = [];

        $follow = isset($param['follow']) ? $param['follow'] : 0;

        $myShared = isset($param['my_shared']) ? $param['my_shared'] : 0;

        $otherShared = isset($param['other_shared']) ? $param['other_shared'] : 0;

        $tagMatch = isset($param['tag_match']) ? $param['tag_match'] : 0;

        if(isset($param['search']) && !empty($param['search'])){
            $search = $param['search'];
            if(isset($search['follow'])){
                unset($param['search']['follow']);
                $follow = $search['follow'];
            }
            if(isset($search['my_shared'])){
                unset($param['search']['my_shared']);
                $myShared = $search['my_shared'];
            }
            if(isset($search['other_shared'])){
                unset($param['search']['other_shared']);
                $otherShared = $search['other_shared'];
            }
            if(isset($search['folder_id'])){
                unset($param['search']['folder_id']);
                if(isset($search['folder_id'][0]) && $search['folder_id'][0] != 0) {
                    $folderId = $search['folder_id'][0];
                }
            }
            if(isset($search['creator'])){
                if(isset($search['creator'][0]) && ($search['creator'][0] == 'mine')){
                    $param['search']['creator'][0] = $own['user_id'];
                }
            }
            if(isset($search['tag_match'])){
                unset($param['search']['tag_match']);
                $tagMatch = $search['tag_match'];
            }
        }

       	// 返回[ $showFolderId(有查看权限的文件夹), $diffFolderId(没有查看权限的文件夹，可能需要过滤掉当前人创建的文件夹) ]
       	$folderIdArray = [[], []];
       	if($folderId == 0){
            if (isset($param['from']) && $param['from'] == 'mobile') {
                return $response == 'both' ? ['total' => 0, 'list' => []] : ($response == 'data' ? [] : 0);
            }
            $folderIdArray = $this->getListDocumentFolderIdArray($own);
       	}else{
            if(is_array($folderId) && !empty($folderId)){
                foreach ($folderId as $value) {
                    $tempArray = $this->getListDocumentFolderIdArray($own, $value, $param);
                    $folderIdArray = [
                        array_unique(array_merge($tempArray[0], $folderIdArray[0])),
                        array_unique(array_merge($tempArray[1], $folderIdArray[1])),
                    ];
                }
            }else{
                $folderIdArray = $this->getListDocumentFolderIdArray($own, $folderId, $param);
            }
       	}

        // 过滤 $diffFolderId 中有共享的文件夹
        $shareDocumentIds = $this->objectColumn(app($this->documentShareRepository)->getShareDocumentsByFolder($folderIdArray[1], $own), 'document_id');

        $count = 0;
        if ($response == 'both' || $response == 'count') {
            $count = app($this->documentContentRepository)->getDocumentCount($param, $own, $folderIdArray[0], $folderIdArray[1], $shareDocumentIds, $follow, $myShared, $otherShared);
        }

        $list = [];

		if ($response == 'both' || $response == 'data') {
			$attrs = [
				'attachment_count' 	=> true,
				'reply_count'		=> true,
				'manage_purview'	=> true,
				'edit_purview'	    => true,
				'delete_purview'	=> true,
				'log_count'			=> true
			];
            //过滤字段
            $fields = $this->defaultValue('fields',$param,[]);
			if(!empty($fields)){
				$this->filterFields($fields, $attrs);
			}

            //获取文档ID列表
            $documentIds = app($this->documentContentRepository)->listDocument($param, $own, $folderIdArray[0], $folderIdArray[1], $shareDocumentIds, $follow, $myShared, $otherShared);
            if($tagMatch && isset($param['search']['tag_id'])){
                $documentIdArr = array_values(array_unique($documentIds));
//                $documentIdArrCount = array_count_values($documentIds);
                foreach ($documentIdArr as $key => $value){
                    $tags = app($this->documentTagRepository)->getTagIdByDocumentId($value);
                    if(count($tags) < count($param['search']['tag_id'][0])){
                        unset($documentIdArr[$key]);
                    }else{
                        if($param['search']['tag_id'][0]){
                            foreach ($param['search']['tag_id'][0] as $tagId){
                                if(!in_array($tagId, array_column($tags, 'tag_id'))){
                                    unset($documentIdArr[$key]);
                                }
                            }
                        }
                    }
                }
                $documentIds = $documentIdArr;
            }else{
                $documentIds = array_values(array_unique($documentIds));
            }
			//根据文档ID获取文档列表
            $documentLists = app($this->documentContentRepository)->listDocumentByDocumentIds($documentIds, $fields);

            //获取文档列表所有文档的日志统计
//            $logCounts = $attrs['log_count'] ? app($this->logRepository)->logViewCounts('document', $documentIds) : [];
            $logCounts = $attrs['log_count'] ? app($this->logCenterService)->getLogCount('document', $documentIds, 'document_content') : [];

            //获取文档列表所有文档的回复统计
            $revertCounts = $attrs['reply_count'] ? app($this->documentRevertRepository)->getRevertCounts($documentIds) : [];
            //获取文档列表所有文档的是否查看标志
//            $logViewCounts = app($this->logRepository)->logViewCounts('document', $documentIds, $own['user_id'], ['view', 'edit']);
            $logViewCounts = app($this->logCenterService)->getLogCount('document', $documentIds, 'document_content', ['userId' => $own['user_id'], 'operate' => ['view', 'edit']]);

            // 获取附件列表
            $attachmentCounts = app($this->documentContentRepository)->attachmentCounts($documentIds);
            //获取文档详情列表
            $detailList = [];

            foreach($documentLists as $key => $document){
                $detailList[$document->document_id] = $this->getDocumentAttr($attrs, $document, $document->document_id, $own, $logCounts, $logViewCounts, $revertCounts, $attachmentCounts);
            }
            //文档排序
			foreach($documentIds as $documentId){
				if (isset($detailList[$documentId])) {
					$list[] = $detailList[$documentId];
				}
			}

		}

		return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
	}
	// 云盘模式列表
	public function getCloudList($param, $own) {
		// 文件夹
		$folders = $this->getCloudFolder($param, $own);
		// 文件
		$documents = $this->getCloudFile($param, $own);
		return array_merge($folders, $documents);
	}

    /**
     * 获取云盘模式文件夹列表
     * @author yangxingqiang
     * @param $param
     * @param $own
     * @return array
     */
	private function getCloudFolder($param, $own) {
		$param = $this->parseParams($param);
        $folders = [];
		$folderId = isset($param['folder_id']) ? $param['folder_id'] : 0;
        $searchName = isset($param['search']['name']) ? $param['search']['name'] : [];
        //排序
		if (isset($param['order_by'])) {
			if (isset($param['order_by']['name'])) {
                $param['order_by']['folder_name'] = $param['order_by']['name'];
				unset($param['order_by']['name']);
			}
			if (isset($param['order_by']['size'])) {
				unset($param['order_by']['size']);
			}
		}
		// 查询
		if (isset($param['search'])) {
			if (isset($param['search']['file_type'])) {
//				return [];
                unset($param['search']['file_type']);
			}
			if (isset($param['search']['name'])) {
				$param['search']['folder_name'] = $param['search']['name'];
				unset($param['search']['name']);
			}
			if (isset($param['search']['creator'])) {
//				$param['search']['user_id'] = $param['search']['creator'];
				unset($param['search']['creator']);
			}
		}
		// 文件夹
		if (!empty($searchName)) {
            $folderList = $this->getCouldListFolderId($param, $folderId, $own);//穿透查询所有子文件夹
        } else {
            $folderList = $this->getShowChildrenFolder($param, $folderId, $own);
		}
		if (!empty($folderList)) {
				foreach ($folderList as $key => $value) {
					$temp = [
						'id' => $value->folder_id,
						'name' => $value->folder_name,
						'parentId' => $value->parent_id,
						'type' => 99,
                        'updatedTime' => ($value->updated_at == '-0001-11-30 00:00:00') ? (($value->created_at == '-0001-11-30 00:00:00') ? '' : date_format($value->created_at, 'Y-m-d H:i:s')) : date_format($value->updated_at, 'Y-m-d H:i:s'),
                        'manage_purview' => 0,
                        'is_default' => $value->is_default
					];
					// 是否有管理权限
					if ($this->hasManagerPurview($value->folder_id, $own)) {
		                $temp['manage_purview'] = 1;
		            }
					$folders[] = $temp;
				}
			}
		return $folders;
	}

	public function getCloudDocument($param, $own) {
		$param = $this->parseParams($param);
		// 文档
		$documents = [];
		$folderId = isset($param['folder_id']) ? $param['folder_id'] : false;
		if ($folderId === 0) {
			return [];
		}
        //排序
        $orders = [];
        $orderSize = '';
		if (isset($param['order_by'])) {
            if (isset($param['order_by']['name'])) {
                $param['order_by']['subject'] = $param['order_by']['name'];
			    unset($param['order_by']['name']);
            }
            if (isset($param['order_by']['size'])) {
            	$orderSize = $param['order_by']['size'];
			    unset($param['order_by']['size']);
            }
            $orders = $param['order_by'];
		} else {
			$orders = ['updated_at' => 'desc'];
		}
		if (isset($param['search'])) {
			if (isset($param['search']['file_type'][0]) && $param['search']['file_type'][0] == '999') {
				$param['search']['file_type'] = [[0, 1, 2, 3, 4, 5, 6, 7], 'not_in'];
			}
			if (isset($param['search']['name'])) {
				$param['search']['subject'] = $param['search']['name'];
				unset($param['search']['name']);
			}
		}
		// 有查看权限文档id
		if ($folderId !== false) {
            if ($folderId == 0) {
                return [];
            }
            $folderIdArray = $this->getListDocumentFolderIdArray($own, $folderId);
		} else {
			$folderIdArray = $this->getListDocumentFolderIdArray($own);
        }
        $shareDocumentIds = $this->objectColumn(app($this->documentShareRepository)->getShareDocumentsByFolder($folderIdArray[1], $own), 'document_id');
        $documentIds = app($this->documentContentRepository)->listDocument($param, $own, $folderIdArray[0], $folderIdArray[1], $shareDocumentIds);

		//根据文档ID获取文档列表
		$fields = [
            'document_content.document_id',
            'document_content.content',
            'document_content.subject',
            'document_content.folder_id',
            'document_content.creator',
            'document_content.created_at',
            'document_content.updated_at',
            'document_content.file_type',
            'is_draft'
        ];
        $attachmentRelation = app($this->attachmentService)->getAttachmentRelations('document_content', ['entity_id', 'attachment_id'], ['entity_id' => [$documentIds, 'in']])->toArray();
        // entity_id 与 attachment_id 的对照
    	$reference = array_column($attachmentRelation, 'attachment_id', 'entity_id');
    	// 获取附件信息
    	$attachmentIds = array_column($attachmentRelation, 'attachment_id');
        $attachments = app($this->attachmentService)->getAttachments(['attach_ids' => $attachmentIds]);
        $attachReference = array_column($attachments, 'attachment_size', 'attachment_id');
        $documentLists = app($this->documentContentRepository)->listDocumentByDocumentIds($documentIds, $fields, $orders);
        // 获取文件夹管理权限
        $folderPurview = app($this->documentFolderPurviewRepository)->hasManagerPurview($folderId, $own) > 0;
        $editPurview = app($this->documentFolderPurviewRepository)->hasEditPurview($folderId, $own) > 0;
        $downPurview = app($this->documentFolderPurviewRepository)->hasDownPurview($folderId,$own);
		if (!$documentLists->isEmpty()) {
			foreach ($documentLists as $key => $value) {
				$attachmentId = $reference[$value->document_id] ?? '';
				$temp = [
					'id' => $value->document_id,
					'name' => $value->subject,
					'parentId' => $value->folder_id,
					'type' => $value->file_type,
                    'updatedTime' => ($value->updated_at == '-0001-11-30 00:00:00') ? (($value->created_at == '-0001-11-30 00:00:00') ? '' : date_format($value->created_at, 'Y-m-d H:i:s')) : date_format($value->updated_at, 'Y-m-d H:i:s'),
					'attachmentId' => $attachmentId ?? '',
					'size' => !empty($attachmentId) ? ($this->formatBytes($attachReference[$attachmentId]) ?? 0) : 0,
					'manage_purview' => 0,
					'edit_purview' => 0,
					'down_purview' => 0,
                    'delete_purview' => 0,
                    'is_draft' => $value->is_draft
				];
				// 是否有管理权限
				if ($this->hasDocumentManagePurview($value->document_id, $own, [], $folderPurview)) {
	                $temp['manage_purview'] = 1;
                }
                // 是否有编辑权限
                if ($this->hasDocumentEditPurview($value->document_id, $own, [], $editPurview)) {
	                $temp['edit_purview'] = 1;
                }
	            // 是否有下载权限
	            if ($this->hasDownPurview($value->document_id, $own, '', [], $downPurview)) {
	            	$temp['down_purview'] = 1;
                }
                // 是否有下载权限
	            if ($this->hasDocumentDeletePurview($value->document_id, $own, $value)) {
	            	$temp['delete_purview'] = 1;
	            }
	            $documents[] = $temp;
			}
        }
        if (!empty($orderSize)) {
        	$sortSize = array_column($documents, 'size');
        	array_multisort($sortSize, $orderSize == 'desc' ? SORT_DESC : SORT_ASC, $documents);
        }
        
        return $documents;
    }

    /**
     * 获取云盘模式文档列表
     * @author yangxingqiang
     * @param $param
     * @param $own
     * @return array
     */
	public function getCloudFile($param, $own){
		$param = $this->parseParams($param);
		// 文档
		$documents = [];
		$folderId = isset($param['folder_id']) ? $param['folder_id'] : 0;
		$searchName = isset($param['search']['name']) ? $param['search']['name'] : [];
		if (empty($searchName) && $folderId == 0) {
			return [];
		}
        $follow = isset($param['follow']) ? $param['follow'] : 0;
        $myShared = isset($param['my_shared']) ? $param['my_shared'] : 0;
        $otherShared = isset($param['other_shared']) ? $param['other_shared'] : 0;
        //排序
        $orders = [];
        $orderSize = '';
		if (isset($param['order_by'])) {
            if (isset($param['order_by']['name'])) {
                $param['order_by']['subject'] = $param['order_by']['name'];
			    unset($param['order_by']['name']);
            }
            if (isset($param['order_by']['size'])) {
            	$orderSize = $param['order_by']['size'];
			    unset($param['order_by']['size']);
            }
            $orders = $param['order_by'];
		} else {
			$orders = ['updated_at' => 'desc'];
		}
		if (isset($param['search'])) {
			if (isset($param['search']['file_type'][0]) && $param['search']['file_type'][0] == '999') {
				$param['search']['file_type'] = [[0, 1, 2, 3, 4, 5, 6, 7], 'not_in'];
			}
			if (isset($param['search']['name'])) {
				$param['search']['subject'] = $param['search']['name'];
				unset($param['search']['name']);
			}
		}
		if(empty($searchName)){
            $folderIdArray = $this->getListDocumentFolderIdArray($own, $folderId);// 有查看权限文档id
        }else{
            $folderIdArray = $this->getCouldListDocumentId($own, $folderId);//穿透查询所有子级文件
        }
        $shareDocumentIds = $this->objectColumn(app($this->documentShareRepository)->getShareDocumentsByFolder($folderIdArray[1], $own), 'document_id');
        $documentIds = app($this->documentContentRepository)->listDocument($param, $own, $folderIdArray[0], $folderIdArray[1], $shareDocumentIds, $follow, $myShared, $otherShared);

		//根据文档ID获取文档列表
		$fields = [
            'document_content.document_id',
            'document_content.content',
            'document_content.subject',
            'document_content.folder_id',
            'document_content.creator',
            'document_content.created_at',
            'document_content.updated_at',
            'document_content.file_type',
            'document_content.is_draft',
            'document_content.folder_type'
        ];
        $attachmentRelation = app($this->attachmentService)->getAttachmentRelations('document_content', ['entity_id', 'attachment_id'], ['entity_id' => [$documentIds, 'in']])->toArray();
        // entity_id 与 attachment_id 的对照
    	$reference = array_column($attachmentRelation, 'attachment_id', 'entity_id');
    	// 获取附件信息
    	$attachmentIds = array_column($attachmentRelation, 'attachment_id');
        $attachments = app($this->attachmentService)->getAttachments(['attach_ids' => $attachmentIds]);
        $attachReference = array_column($attachments, 'attachment_size', 'attachment_id');
        $documentLists = app($this->documentContentRepository)->listDocumentByDocumentIds($documentIds, $fields, $orders);
        // 获取文件夹管理权限
        $folderPurview = app($this->documentFolderPurviewRepository)->hasManagerPurview($folderId, $own) > 0;
        $editPurview = app($this->documentFolderPurviewRepository)->hasEditPurview($folderId, $own) > 0;
        $downPurview = app($this->documentFolderPurviewRepository)->hasDownPurview($folderId,$own);
		if (!$documentLists->isEmpty()) {
			foreach ($documentLists as $key => $value) {
				$attachmentId = $reference[$value->document_id] ?? '';
				$temp = [
					'id' => $value->document_id,
					'name' => $value->subject,
					'parentId' => $value->folder_id,
					'type' => $value->file_type,
                    'updatedTime' => ($value->updated_at == '-0001-11-30 00:00:00') ? (($value->created_at == '-0001-11-30 00:00:00') ? '' : date_format($value->created_at, 'Y-m-d H:i:s')) : date_format($value->updated_at, 'Y-m-d H:i:s'),
					'attachmentId' => $attachmentId ?? '',
					'size' => !empty($attachmentId) ? ($this->formatBytes($attachReference[$attachmentId]) ?? 0) : 0,
					'manage_purview' => 0,
					'edit_purview' => 0,
					'down_purview' => 0,
                    'delete_purview' => 0,
                    'is_draft' => $value->is_draft,
                    'folder_type' => $value->folder_type,
				];
				// 是否有管理权限
				if ($this->hasDocumentManagePurview($value->document_id, $own, [], $folderPurview)) {
	                $temp['manage_purview'] = 1;
                }
                // 是否有编辑权限
                if ($this->hasDocumentEditPurview($value->document_id, $own, [], $editPurview)) {
	                $temp['edit_purview'] = 1;
                }
	            // 是否有下载权限
	            if ($this->hasDownPurview($value->document_id, $own, '', [], $downPurview)) {
	            	$temp['down_purview'] = 1;
                }
                // 是否有下载权限
	            if ($this->hasDocumentDeletePurview($value->document_id, $own, $value)) {
	            	$temp['delete_purview'] = 1;
	            }
	            $documents[] = $temp;
			}
        }
        if (!empty($orderSize)) {
        	$sortSize = array_column($documents, 'size');
        	array_multisort($sortSize, $orderSize == 'desc' ? SORT_DESC : SORT_ASC, $documents);
        }
        return $documents;
    }

    private function formatBytes($size) {
        $units = array(' B', ' KB', ' MB', ' GB', ' TB'); 
        for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
        if($i == 0 || $i == 1){
            return round($size).$units[$i];
        }else{
            return round($size, 2).$units[$i];
        }
    }

	public function cloudUpload($data, $own) {
		return $this->addDocument($data, $own);
	}

    /**
     * 获取文件夹ID
     *
     * @param type $own
     * @param type $folderId
     *
     * @return array
     */
    private function getListDocumentFolderIdArray($own, $folderId = false, $param = [])
    {
        if($folderId){//判断是否有父级文件ID
            if($folderId == -1) {//当文件夹ID为-1时没有子文件。
                return [[],[-1]];
            }

            $showChild = get_system_param('show_child_document');
//            $viewMode = get_system_param('document_view_mode');
            $userSystemInfo = app($this->userSystemInfoRepository)->getInfoByWhere(['user_id' => [$own['user_id']]]);
            $viewMode = $userSystemInfo[0]['document_view_mode'] ?? 1;
            if ($showChild == 0 || $viewMode != 1 || (isset($param['from']) && $param['from'] == 'mobile')) {
                if (app($this->documentFolderPurviewRepository)->hasShowPurview($folderId, $own)) {
                    return [[$folderId], []];
                } else {
                    return [[], [$folderId]];
                }
            }
            $familyFolderId = $this->objectColumn(app($this->documentFolderRepository)->getFamilyFolderId($folderId), 'folder_id');//获取该文件夹下所有子孙文件夹ID

            $showFolderId = $this->objectColumn(app($this->documentFolderPurviewRepository)->getFamilyShowFolderId($folderId, $own), 'folder_id');//获取该文件夹下所有有查看权限子孙文件夹ID

            $diffFolderId = array_diff($familyFolderId, $showFolderId);

            if (app($this->documentFolderPurviewRepository)->hasShowPurview($folderId, $own)) {
                $showFolderId[] = $folderId;
            } else {
                $diffFolderId[] = $folderId;
            }
        } else {
            $allFolderId = app($this->documentFolderRepository)->getAllFloderId();//获取所有文件夹ID

            $showFolderId = $this->objectColumn(app($this->documentFolderPurviewRepository)->getAllShowDocumentFolderId($own), 'folder_id');//获取所有有查看权限子孙文件夹ID

            $diffFolderId = array_diff($allFolderId, $showFolderId);
        }

        return [$showFolderId, $diffFolderId];
    }

    /**
     * 获取云盘模式文档ID
     * @author yangxingqiang
     * @param $own
     * @param $folderId
     * @return array
     */
    private function getCouldListDocumentId($own, $folderId)
    {
        if($folderId != 0){
            if($folderId == -1) { //判断是否有父级文件ID
                return [[],[-1]];//当文件夹ID为-1时没有子文件。
            }
            $familyFolderId = $this->objectColumn(app($this->documentFolderRepository)->getFamilyFolderId($folderId), 'folder_id');//获取该文件夹下所有子孙文件夹ID
            $showFolderId = $this->objectColumn(app($this->documentFolderPurviewRepository)->getFamilyShowFolderId($folderId, $own), 'folder_id');//获取该文件夹下所有有查看权限子孙文件夹ID
            $diffFolderId = array_diff($familyFolderId, $showFolderId);
            if (app($this->documentFolderPurviewRepository)->hasShowPurview($folderId, $own)) {
                $showFolderId[] = $folderId;
            } else {
                $diffFolderId[] = $folderId;
            }
        } else {
            $allFolderId = app($this->documentFolderRepository)->getAllFloderId();//获取所有文件夹ID
            $showFolderId = $this->objectColumn(app($this->documentFolderPurviewRepository)->getAllShowDocumentFolderId($own), 'folder_id');//获取所有有查看权限子孙文件夹ID
            $diffFolderId = array_diff($allFolderId, $showFolderId);
        }
        return [$showFolderId, $diffFolderId];
    }
    /**
     * 获取文档相应的属性
     *
     * @param type $attr
     * @param type $document
     * @param type $documentId
     * @param type $own
     *
     * @return document
     */
    private function getDocumentAttr($attr, $document, $documentId, $own, $logCounts, $logViewCounts, $revertCounts, $attachmentCounts)
    {
        if($attr['attachment_count']){
        	$document->attachment_count = isset($attachmentCounts[$documentId]) ? $attachmentCounts[$documentId] : 0;
        }

        if($attr['reply_count']){
            $document->reply_count = isset($revertCounts[$documentId]) ? $revertCounts[$documentId] : 0;
        }

        if($attr['manage_purview']){
            if ($this->hasDocumentManagePurview($documentId, $own, $document)) {
                $document->manage_purview = 1;
            }
        }
        if($attr['edit_purview']){
            if ($this->hasDocumentEditPurview($documentId, $own, $document)) {
                $document->edit_purview = 1;
            }
        }

        if ($attr['delete_purview']) {
            if ($this->hasDocumentDeletePurview($documentId, $own, $document)) {
                $document->delete_purview = 1;
            }
        }

        if($attr['log_count']){
            $document->log_count = isset($logCounts[$documentId]) ? $logCounts[$documentId] : 0;
        }

        if(isset($logViewCounts[$documentId]) && $logViewCounts[$documentId]){
            $document->isView =1;
        } else {
            $document->isView =0;
        }

        if( app($this->documentFollowRepository)->getTotal([
            'search' => ['user_id' => [$own['user_id']], 
            'document_id' => [$documentId]]
        ]) ) {
            $document->follow = 1;
        } else {
            $document->follow = 0;
        }
        if (isset($document->file_type) && $document->file_type != 0) {
        	$attachment = app($this->attachmentService)->getAttachmentIdsByEntityId([
        		'entity_table' => 'document_content',
        		'entity_id' => $documentId
        	]);
        	$document->attachment_id = $attachment[0] ?? '';
        	$document->down_purview = $this->hasDownPurview($documentId, $own);
        }
        return $document;
    }
    /**
     * 过滤字段
     *
     * @param type $fields
     * @param type $filterFields
     */
	private function filterFields(&$fields, &$filterFields)
	{
		foreach ($filterFields as $key => $value){
			if(in_array($key, $fields)){
				array_splice($fields, array_search($key, $fields), 1);

				$filterFields[$key] = true;
			} else {
				$filterFields[$key] = false;
			}
		}
	}
    /**
     * 从对象数组中获取摸一个属性的值的数组
     *
     * @param type $objects
     * @param type $fields
     *
     * @return array
     */
	private function objectColumn($objects, $fields)
	{
		$columns = [];

		if(count($objects) > 0){
			foreach($objects as $object){
				$columns[] = $object->{$fields};
			}
		}

		return $columns;
	}
    /**
     * 获取无权限的文档列表
     *
     * @param type $param
     *
     * @return type
     */
	public function listNoPurviewDocument($param)
	{
		return $this->response(app($this->documentContentRepository), 'getNoPurDocCount', 'listNoPurDoc', $this->parseParams($param));
	}
    /**
     *
     * @param type $documentId
     * @param type $userId
     *
     * @return type
     */
	public function getDocumentShareMember($documentId)
	{
        $documentInfo = app($this->documentContentRepository)->getDetail($documentId);
		if($shareDocument = app($this->documentShareRepository)->getDocumentShareMember($documentId, $documentInfo->folder_type ?? 1)){
			if($shareDocument->share_all == 0){
				$shareDocument->share_user = $this->stringToArray($shareDocument->share_user);
				$shareDocument->share_dept = $this->stringToArray($shareDocument->share_dept, 'int');
				$shareDocument->share_role = $this->stringToArray($shareDocument->share_role, 'int');
			} else {
				$shareDocument->share_user = [];
				$shareDocument->share_dept = [];
				$shareDocument->share_role = [];
			}

			return $shareDocument;
		}

        return ['share_all' => 0, 'share_user' => [], 'share_dept' => [], 'share_role' => [], 'share_status' => 0, 'share_end_time' => ''];
    }
    /**
     * 新建文档
     *
     * @param array $data
     * @param file $uploadFile
     *
     * @return array 文档id
     *
     * @author 李志军
     *
     * @since 2015-11-06
     */
    public function addDocument($data, $own)
    {
        $folderId = $data['folder_id'];
        $tagId    = isset($data['tag_id']) ? $data['tag_id'] : [];
        if($folderId == 0){
            $default = app($this->documentFolderRepository)->getOneFieldInfo(['is_default' => 1])->toArray();
            $folderId = $default['folder_id'] ?? 0;
        }
		if (!$this->hasCreatePurview($folderId, $own)) {
			return ['code' => ['0x041016', 'document']];
		}

		$subject = $data['subject'];

		$userId = $own['user_id'];

		$documentData = [
			'folder_type'	=> 1,
			'document_type'	=> $this->defaultValue('document_type', $data, 0),
			'file_type'     => $this->defaultValue('file_type', $data, 0),
			'folder_id'		=> $folderId,
			'subject'		=> $subject,
			'content'		=> $this->defaultValue('content', $data, ''),
			'status'		=> $this->defaultValue('status', $data, 0),
			'creator'		=> $userId,
			'is_draft'		=> $this->defaultValue('is_draft', $data, 0),
		];
		if($documentData['file_type']){
            if($documentData['file_type'] == 1){
                $documentData['document_type'] = 1;
            }else if($documentData['file_type'] == 2){
                $documentData['document_type'] = 2;
            }
        }else{
            if($documentData['document_type'] == 1){
                $documentData['file_type'] = 1;
            }else if($documentData['document_type'] == 2){
                $documentData['file_type'] = 2;
            }
        }
        if (!$result = app($this->documentContentRepository)->insertData($documentData)) {
            return ['code' => ['0x000003', 'common']];
        }
        if (!empty($tagId)) {
            $documentTagData = [];
            foreach ($tagId as $value) {
                $documentTagData[] = [
                    'document_id' => $result->document_id,
                    'tag_id'      => $value,
                    'user_id'     => $userId,
                ];
            }
            if (!empty($documentTagData)) {
                app($this->documentTagRepository)->insertMultipleData($documentTagData);
            }
        }

        //上传附件
        app($this->attachmentService)->attachmentRelation("document_content", $result->document_id,$data['attachment_id']);

        // 如果是草稿，不需要消息推送
        if ($documentData['is_draft'] == 0) {
        	//消息推送，需要推送给创建人
            $allShowPurviewId = $this->getAllShowPurviewId($folderId);
            $invalidUserId = app($this->userSystemInfoRepository)->getInvalidUserId();
            $userIds = array_diff($allShowPurviewId, $invalidUserId);
            $toUser = (array_unique(array_merge($userIds, [$userId])));
            $sendMessageArray = [
	        	'remindMark'	=> 'document-submit',
	        	'toUser'		=> $toUser,
	        	'contentParam'	=> ['documentName' => $subject,'userName' => $own['user_name']],
	        	'stateParams'	=> ['document_id' => $result->document_id]
	        ];
	        Eoffice::sendMessage($sendMessageArray);
        }

        if (array_key_exists('share', $data)) {
            $this->shareDocument($data['share'], $result->document_id, $own);
        }

        $this->addLog($result->document_id ,1 , $userId);
        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($result->document_id);

		return ['document_id' => $result->document_id];
    }
    
    // 复制文档
    public function copyDocument($data, $own) {
        $isCopyAttachment = $this->defaultValue('copy_attachment', $data, 0);

        $documentData = [
			'folder_type'	=> 1,
			'document_type'	=> $this->defaultValue('document_type', $data, 0),
			'folder_id'		=> $data['folder_id'],
			'subject'		=> $data['subject'],
			'content'		=> $this->defaultValue('content', $data, ''),
			'status'		=> $this->defaultValue('status', $data, 0),
			'creator'		=> $own['user_id'],
			'is_draft'		=> $this->defaultValue('is_draft', $data, 0),
		];
        if (!$result = app($this->documentContentRepository)->insertData($documentData)) {
            return ['code' => ['0x000003', 'common']];
        }
        // 复制附件
        if ($isCopyAttachment) {
            $attachments = $this->documentAttachment($data['document_id']);
            if (!empty($attachments)) {
                $copyAttachment = [];
                foreach ($attachments as $value) {
                    $copyAttachment[] = [
                        'source_attachment_id' => $value,
                        'attachment_table' => 'document_content'
                    ];
                }
                $copy = app($this->attachmentService)->attachmentCopy($copyAttachment, $own);
                $attachmentIds = array_column($copy, 'attachment_id');
                // 添加文档附件
                app($this->attachmentService)->attachmentRelation("document_content", $result->document_id, $attachmentIds);
            }
        }
        // 添加日志
        $this->addLog($data['document_id'], 7, $own['user_id']);

        return true;
    }

	public function documentLock($documentId,$lockStatus,$userId)
	{
		$data = [
			'lock_status' 	=> $lockStatus,
			'user_id'		=> $userId
		];
        /**
         * 如果文档插件为wps云文档且文档类型时word/excel则不锁定, 允许协同编辑
         */
        $onlineRead = (int) get_system_param('online_read_type');
        /** @var DocumentContentRepository $documentContentRepository */
        $documentContentRepository = app($this->documentContentRepository);
        $document = $documentContentRepository->getDocumentInfo($documentId, []);
        if ($document) {
            if (($onlineRead == SystemParamsEntity::ONLINE_READ_TYPE_WPS) && $document->document_type) {
                app($this->documentLockRepository)->updateData(['lock_status' => 0], ['document_id' => $documentId]);

                return true;
            }
        }

		if(app($this->documentLockRepository)->lockExists($documentId)){
			return app($this->documentLockRepository)->updateData($data,['document_id' => $documentId]);
		} else {
			$data['document_id'] = $documentId;

			return app($this->documentLockRepository)->insertData($data);
		}
	}

	public function documentLockInfo($documentId)
    {
    	$lockInfo = app($this->documentLockRepository)->documentLockInfo($documentId);

    	if($lockInfo) {
    		$lockInfo->user_name = get_user_simple_attr($lockInfo->user_id,'user_name');
    	}

    	return $lockInfo;
    }

    public function applyUnlock($documentId,$userId,$own)
    {
    	$documentDetail = app($this->documentContentRepository)->getDocumentInfo($documentId, ['subject', 'folder_id', 'document_type']);
    	//消息推送
        $sendMessageArray = [
        	'remindMark'	=> 'document-unlock',
        	'toUser'		=> $userId,
        	'contentParam'	=> ['documentName' => $documentDetail->subject,'userName' => $own['user_name']],
        	'stateParams'	=> [
        	    'document_id' => $documentId,
                'folder_id' => $documentDetail->folder_id,
                'document_type' => $documentDetail->document_type,
            ]
        ];

        Eoffice::sendMessage($sendMessageArray);

        return true;
    }
	/**
	 * 编辑文档
	 *
	 * @param array $data
	 * @param file $uploadFile
	 * @param int $documentId
	 *
	 * @return int 编辑结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-06
	 */
	public function editDocument($data, $uploadFile, $documentId, $own)
	{
		if ($documentId == 0) {
			return ['code' => ['0x041017', 'document']];
		}

		if (!($this->hasDocumentManagePurview($documentId, $own) || $this->hasDocumentEditPurview($documentId, $own))) {
			return ['code' => ['0x041018', 'document']];
		}

		$documentInfo = app($this->documentContentRepository)->getDetail($documentId);
		if($documentInfo && isset($documentInfo['is_draft'])){
			$oldStatus = $documentInfo['is_draft'];
		}else{
			return ['code' => ['0x000003', 'common']];
		}

		$folderId = $data['folder_id'];

		$subject  = $data['subject'];

		$documentData = [
			'folder_id'		=> $folderId,
			'subject'		=> $subject,
			'content'		=> $this->defaultValue('content', $data, ''),
			'status'		=> $this->defaultValue('status', $data, 0),
			'is_draft' 		=> $this->defaultValue('is_draft', $data, 0),
		];
        $res = app($this->documentContentRepository)->updateData($documentData, ['document_id' => $documentId]);
        if (array_key_exists('tag_id', $data)) {
            app($this->documentTagRepository)->deleteByWhere(['document_id' => [$documentId], 'user_id' => [$own['user_id']]]);
            if (!empty($data['tag_id'])) {
                $tags = [];
                foreach ($data['tag_id'] as $k => $v) {
                    $tags[] = [
                        'user_id'     => $own['user_id'],
                        'document_id' => $documentId,
                        'tag_id'      => $v,
                    ];
                }

                $result = app($this->documentTagRepository)->insertMultipleData($tags);
            }
        }
        if (array_key_exists('share', $data)) {
            $this->shareDocument($data['share'], $documentId, $own);
        }
		//上传附件
        app($this->attachmentService)->attachmentRelation("document_content", $documentId,$data['attachment_id']);
         //消息推送
        $allShowPurviewId = $this->getAllShowPurviewId($folderId);
        $invalidUserId = app($this->userSystemInfoRepository)->getInvalidUserId();
        $userIds = array_diff($allShowPurviewId, $invalidUserId);
        $toUser = (array_unique(array_merge($userIds, [$own['user_id']])));
        if ($oldStatus && $oldStatus == 1 && $data['is_draft'] == 0) {
        	$sendMessageArray = [
	        	'remindMark'	=> 'document-submit',
        		'toUser'		=> $toUser,
        		'contentParam'	=> ['documentName' => $subject,'userName' => $own['user_name']],
        		'stateParams'	=> ['document_id' => $documentId]
	        ];
        } else {
        	$sendMessageArray = [
	        	'remindMark'	=> 'document-modify',
	        	'toUser'		=> $toUser,
	        	'contentParam'	=> ['documentName' => $subject,'userName' => $own['user_name']],
	        	'stateParams'	=> ['document_id' => $documentId]
	        ];
        }
        if($res){
            $this->addLog($documentId, 2, $own['user_id']);
        }
        // 存草稿
        if(!isset($data['is_draft']) || $data['is_draft'] == 1){
    		return true;
        }

        Eoffice::sendMessage($sendMessageArray);

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($documentId);

		return true;
	}

    /**
     * 文档重命名
     * @author yangxingqiang
     * @param $data
     * @param $documentId
     * @param $own
     * @return array|bool
     */
	public function editDocumentName($data, $documentId, $own)
	{
		if ($documentId == 0) {
			return ['code' => ['0x041017', 'document']];
		}
		if (!($this->hasDocumentManagePurview($documentId, $own) || $this->hasDocumentEditPurview($documentId, $own))) {
			return ['code' => ['0x041018', 'document']];
		}
		$documentInfo = app($this->documentContentRepository)->getDetail($documentId);
		if(!$documentInfo && isset($documentInfo['is_draft'])){
            return ['code' => ['0x000003', 'common']];
		}
		$folderId = $data['folder_id'];
		$subject  = $data['subject'];
		$documentData = [
			'subject'		=> $subject,
		];
        $res = app($this->documentContentRepository)->updateData($documentData, ['document_id' => $documentId]);
         //消息推送
        $allShowPurviewId = $this->getAllShowPurviewId($folderId);
        $invalidUserId = app($this->userSystemInfoRepository)->getInvalidUserId();
        $userIds = array_diff($allShowPurviewId, $invalidUserId);
        $toUser = (array_unique(array_merge($userIds, [$own['user_id']])));
        $sendMessageArray = [
            'remindMark'	=> 'document-modify',
            'toUser'		=> $toUser,
            'contentParam'	=> ['documentName' => $subject,'userName' => $own['user_name']],
            'stateParams'	=> ['document_id' => $documentId]
        ];
        if($res){
            $this->addLog($documentId, 2, $own['user_id']);
        }
        Eoffice::sendMessage($sendMessageArray);
        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($documentId);
		return true;
	}

	/**
	 * 获取文档详情
	 *
	 * @param int $documentId
	 *
	 * @return object 文档详情
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-06
	 */
	public function showDocument($documentId, $own, $typeParam='')
	{
		if ($documentId == 0) {
			return ['code' => ['0x041017', 'document']];
        }

        $result = app($this->documentContentRepository)->getDocumentInfo($documentId, []);
        if (empty($result)) {
            return ['code' => ['0x041033', 'document']];
        }

		if($typeParam != 'relation'){
			if (!$result = $this->hasDocumentShowPurview($documentId, $own)) {
				return ['code' => ['0x041027', 'document']];
			}
		}

		if(empty($result) || !isset($result->folder_id)){
			return ['code' => ['0x041027', 'document']];
		}

		$folder = app($this->documentFolderRepository)->getFolderInfo($result->folder_id, ['folder_name','mode_id']);

		$result->folder_name 		= $folder->folder_name;

		$attachmentParam 			= ['entity_table'=>'document_content', 'entity_id'=>$documentId];
        $result->attachment_id 		= app($this->attachmentService)->getAttachmentIdsByEntityId($attachmentParam);
        $result->attachment_count   = count($result->attachment_id);

        $revertParam 				= ['search' => ['document_id' => [$documentId]]];
        $result->reply_count 		= app($this->documentRevertRepository)->getRevertCount($revertParam);

		$log = app($this->logCenterService)->getLogCount('document', [$documentId], 'document_content', ['operate' => ['view']]);
		$result->log_count = $log[$documentId] ?? 0;

        $result->creator_name 		= $result->creator == 'archive' ? 'archive' : get_user_simple_attr($result->creator, 'user_name');

        $result->manage_purview 	= $this->hasDocumentManagePurview($documentId, $own);
        
		$result->edit_purview 	    = $this->hasDocumentEditPurview($documentId, $own);

        $result->down_purview       = $this->hasDownPurview($documentId, $own, $typeParam, $result);

        $result->print_purview      = $this->hasPrintPurview($documentId, $own, $typeParam, $result);

        if( app($this->documentFollowRepository)->getTotal([
            'search' => ['user_id' => [$own['user_id']], 
            'document_id' => [$documentId]]
        ]) ) {
            $result->is_follow = 1;
        } else {
            $result->is_follow = 0;
        }
    	$param = [
    		'search' =>[
    			'document_id' => [$documentId],
    			'tag_type'    => ['public']
    		],
    		'orSearch' =>[
    			'document_id' => [$documentId],
    			'tag_creator' => [$own['user_id']],
    			'tag_type'    => ['private']
    		]
    	];

    	$result->tag_id = app($this->documentTagRepository)->getDocumentViewTag($param);

        $result->tag_name = $result->tag_id;

        if($typeParam != 'nolog'){
        	$this->addLog($documentId, 3, $own['user_id']);
        }

		return $this->setDocumentMode($result,$folder->mode_id);
	}

	public function documentAttachment($documentId)
	{
		return app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'document_content', 'entity_id'=>$documentId]);
	}

	public function hasReplyPurview($documentId, $own)
	{
		$documentInfo = app($this->documentContentRepository)->getDetail($documentId);

		if(empty($documentInfo)){
			return 0;
		}

		if(app($this->documentFolderPurviewRepository)->hasReplyPurview($documentInfo->folder_id, $own)){
			return 1;
		}elseif($documentInfo->creator == $own['user_id']){
			if($this->getCreatorPurview()){
				return 1;
			}else{
				return 0;
			}
		}else{
			return 0;
		}
    }

    public function hasDownPurview($documentId, $own, $typeParam = '', $documentInfo = [], $hasDownPurview = 0)
	{
		if (empty($documentInfo)) {
            $documentInfo = app($this->documentContentRepository)->getDetail($documentId);
        }
        if(empty($documentInfo)){
            return 0;
        }
		if (empty($hasDownPurview) || $hasDownPurview === 0) {
			$hasDownPurview = app($this->documentFolderPurviewRepository)->hasDownPurview($documentInfo->folder_id,$own) > 0;
		}
		if($hasDownPurview){
			return 1;
		}elseif($documentInfo->creator == $own['user_id']){
			if($this->getCreatorPurview()){
				return 1;
			}
		}

		if(get_system_param('share_document_download')){
			if(app($this->documentShareRepository)->hasSharePurview($documentId,$own)){
				return 1;
			}
			if($typeParam == 'relation'){
				return 1;
			}
		}

		return 0;
    }
    public function hasPrintPurview($documentId, $own, $typeParam = '', $documentInfo = [])
	{
        if (empty($documentInfo)) {
            $documentInfo = app($this->documentContentRepository)->getDetail($documentId);
        }

		if(app($this->documentFolderPurviewRepository)->hasPrintPurview($documentInfo->folder_id, $own)){
			return 1;
		}elseif($documentInfo->creator == $own['user_id']){
			if($this->getCreatorPurview()){
				return 1;
			}else{
				return 0;
			}
		}

		return 0;
	}
	/**
	 * 删除文档
	 *
	 * @param int $documentId
	 *
	 * @return int 删除结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-06
	 */
	public function deleteDocument($documentId, $own)
	{
		$documentIds = explode(',', rtrim($documentId,','));
		$idSize = sizeof($documentIds);
		if($idSize == 0 || $documentIds[0] == 0){
			return ['code' => ['0x041017', 'document']];
		}
		$flag = false;
		if($idSize == 1){
			$documentId = $documentIds[0];
			if(!$documentInfo = app($this->documentContentRepository)->getDetail($documentId)){
				return true;
			}
            if (!$this->hasDocumentManagePurview($documentId,$own) 
                && !$this->hasDocumentDeletePurview($documentId,$own,$documentInfo)
                ) {
				return ['code' => ['0x041018', 'document']];
			}
			if (app($this->documentContentRepository)->deleteById($documentId)) {
                app($this->documentShareRepository)->deleteById($documentId);
                $identifier  = "document.document.delete";
                $content = $own['user_name'] . trans('document.delete') . ($documentInfo->subject ?? '');
                $logParams = $this->handleLogParams($own['user_id'], $content, $documentId, $documentInfo->subject);
                logCenter::info($identifier , $logParams);
				$flag = true;
			}
		} else {
			$filterDocIds = [];
			foreach($documentIds as $documentId){
				if($documentInfo = app($this->documentContentRepository)->getDetail($documentId)){
					if ($this->hasDocumentManagePurview($documentId, $own) || $this->hasDocumentDeletePurview($documentId,$own,$documentInfo)) {
						$filterDocIds[] = $documentId;
                        $identifier  = "document.document.delete";
                        $content = $own['user_name'] . trans('document.delete') . ($documentInfo->subject ?? '');
                        $logParams = $this->handleLogParams($own['user_id'], $content, $documentId, $documentInfo->subject);
                        logCenter::info($identifier , $logParams);
					}
				}
			}
			if (app($this->documentContentRepository)->deleteById($filterDocIds)) {
                app($this->documentShareRepository)->deleteById($filterDocIds);
				$flag = true;
			}
		}
		if($flag){
            // 全站搜索消息队列更新数据
            $this->updateGlobalSearchDataByQueue($documentIds);
			return true;
		}
		return ['code' => ['0x000003', 'common']];
	}

    /**
     * 检查删除文档权限
     * @author yangxingqiang
     * @param $documentId
     * @param $own
     * @return array|bool
     */
	public function deleteCheckDocument($documentId, $own)
	{
		$documentIds = explode(',', rtrim($documentId,','));
		$idSize = sizeof($documentIds);
		if($idSize == 0 || $documentIds[0] == 0){
			return ['code' => ['0x041017', 'document']];
		}
		if($idSize == 1){
			$documentId = $documentIds[0];
			if(!$documentInfo = app($this->documentContentRepository)->getDetail($documentId)){
				return true;
			}
            if (!$this->hasDocumentManagePurview($documentId,$own) && !$this->hasDocumentDeletePurview($documentId,$own,$documentInfo)) {
				return ['code' => ['0x041018', 'document']];
			}
		} else {
			foreach($documentIds as $documentId){
				if($documentInfo = app($this->documentContentRepository)->getDetail($documentId)){
                    if (!$this->hasDocumentManagePurview($documentId,$own) && !$this->hasDocumentDeletePurview($documentId,$own,$documentInfo)) {
                        return ['code' => ['0x041018', 'document']];
                    }
				}
			}
		}
        return true;
	}
	/**
	 * 文档转移
	 *
	 * @param string $documentIds
	 * @param int $folderId
	 *
	 * @return int 转移结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-06
	 */
	public function migrateDocument($documentIds,$folderId,$own)
	{
		if (!$this->hasManagerPurview($folderId,$own)) {
			return ['code' => ['0x041012', 'document']];
		}

		if ($documentIds == 0) {
			return ['code' => ['0x041017', 'document']];
		}

		$flag = true;

		$documentIds = array_filter(explode(',', $documentIds));
        $documents = app($this->documentContentRepository)->listNoPurDoc([
			'search' => ['document_id' => [$documentIds, 'in']]
        ])->toArray();
		$documentArray = array_column($documents, 'folder_id', 'document_id');
		foreach ($documentIds as $documentId) {
            if (!$this->hasDocumentShowPurview($documentId, $own)) {
                $flag = false;
                continue;
            }
            $document = app($this->documentContentRepository)->getDocumentInfo($documentId, ['folder_id','subject']);
            if (!app($this->documentContentRepository)->updateData(['folder_id' => $folderId], ['document_id' => $documentId])) {
				$flag = false;
				continue;
            }
            app($this->documentShareRepository)->updateData(['folder_id' => $folderId], ['document_id' => $documentId]);
			$oldFolderId = $this->defaultValue($documentId, $documentArray, '');
            $identifier  = "document.document.move";
            $content = trans('document.source_folder').'：';
            if (!empty($oldFolderId)) {
                $fromFolder = app('App\EofficeApp\Document\Repositories\DocumentFolderRepository')->getDetail($oldFolderId);
                $content .= $fromFolder->folder_name.'；' ?? trans('document.folder_not_exists_or_deleted').'；';
            } else {
                $content .= trans('document.folder_not_exists_or_deleted').'；';
            }
            $content .= trans('document.destination_folder').'：';
            if (!empty($folderId)) {
                $toFolder = app('App\EofficeApp\Document\Repositories\DocumentFolderRepository')->getDetail($folderId);
                $content .= $toFolder->folder_name.'；' ?? trans('document.folder_not_exists_or_deleted').'；';
            } else {
                $content .= trans('document.folder_not_exists_or_deleted').'；';
            }
            $logParams = $this->handleLogParams($own['user_id'], $content, $documentId, $document->subject);
            logCenter::info($identifier , $logParams);
		}

		if ($flag) {
			return true;
		}

		return ['code' => ['0x041020', 'document']];
	}
	/**
	 * 共享文档
	 *
	 * @param array $data
	 * @param string $documentId
	 *
	 * @return int 共享结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-06
	 */
	public function shareDocument($data, $documentId, $own)
	{
		if ($documentId == 0) {
			return ['code' => ['0x041017', 'document']];
		}

		$shareUser = $this->defaultValue('share_user', $data, []);
		$shareDept = implode(',', $this->defaultValue('share_dept', $data, []));
        $shareRole = implode(',', $this->defaultValue('share_role', $data, []));
        
        $shareData = [
            'log_category'		=> 'share',
            'from_user'		=> $own['user_id'],
            'share_all'		=> $this->defaultValue('share_all', $data, 0),
			'share_user'	=> implode(',', $shareUser),
			'share_dept'	=> $shareDept,
            'share_role'	=> $shareRole,
            'share_status'  => $this->defaultValue('share_status', $data, 0),
            'share_end_time' => $this->defaultValue('share_end_time', $data, '')
        ];

		if(!empty($shareDept)){
			$userByDept = $this->getUserIdByDeptId($shareDept);
			$shareUser = array_unique(array_merge($shareUser, $userByDept));
		}

		if(!empty($shareRole)){
			$userByRole = $this->getUserIdByRoleId($shareRole);
			$shareUser = array_unique(array_merge($shareUser, $userByRole));
        }

		$flag = true;

        $shareDocs = [];
//        $logData = [];
		foreach (explode(',', $documentId) as $v) {
			$document = app($this->documentContentRepository)->getDocumentInfo($v, ['folder_id','subject']);

			$shareDocs[$v] = $document->subject;

			if (app($this->documentShareRepository)->shareDocumentExists($v)) {
				if (!app($this->documentShareRepository)->updateData($shareData, ['document_id' => $v])) {
					$flag = false;
				}
			} else {
				$shareData['document_id'] = $v;

				$shareData['folder_id']	= $document->folder_id;

				if (!app($this->documentShareRepository)->insertData($shareData)) {
					$flag = false;
				}
            }
            $identifier  = "document.document.share";
//            $content = $shareData;
            $content = trans('document.shared_member').'：';
            if($shareData['share_all']){
                $content .= trans('document.all_member').'；';
            }else{
                if(!empty($shareData['share_user'])){
                    $content .= trans('document.user').'：';
                    $paramUser['search']['user_id'] = [$shareData['share_user'],'in'];
                    $responseUser = app('App\EofficeApp\User\Services\UserService')->userSystemList($paramUser);
                    if($responseUser['list']){
                        foreach ($responseUser['list'] as $user) {
                            if($user){
                                $content .= $user['user_name'].'；';
                            }
                        }
                    }
                }
                if(!empty($shareData['share_dept'])){
                    $content .= trans('document.department').'；';
                    $paramDept['search'] = json_encode(['dept_id' => [[$shareData['share_dept']],'in']]);
                    $responseDept = app('App\EofficeApp\System\Department\Services\DepartmentService')->listDept($paramDept);
                    if($responseDept['list']){
                        foreach ($responseDept['list'] as $dept) {
                            if($dept){
                                $content .= $dept['dept_name'].'；';
                            }
                        }
                    }
                }
                if(!empty($shareData['share_role'])){
                    $content .= trans('document.role').'；';
                    $paramRole['search'] = json_encode(['role_id' => [[$shareData['share_role']],'in']]);
                    $responseRole = app('App\EofficeApp\Role\Services\RoleService')->getRoleList($paramRole);
                    if($responseRole['list']){
                        foreach ($responseRole['list'] as $role) {
                            if($role){
                                $content .= $role['role_name'].'；';
                            }
                        }
                    }
                }
            }
            if(!$shareData['share_status']){
                $content .= trans('document.the_time_limit_is_permanent').'；';
            }else if($shareData['share_end_time']){
                $content .= trans('document.the_time_limit_valid_until').$shareData['share_end_time'].'；';
            }
            $logParams = $this->handleLogParams($own['user_id'], $content, $documentId, $document->subject);
            logCenter::info($identifier , $logParams);
		}

		if ($flag) {
            $toUser = $shareData['share_all'] == 1 ? explode(',', app($this->userRepository)->getAllUserIdString()) : $shareUser;

			foreach($shareDocs as $key => $subject){
				$sendMessageArray = [
		        	'remindMark'	=> 'document-share',
		        	'toUser'		=> $toUser,
		        	'contentParam'	=> ['documentName' => $subject,'userName' => $own['user_name']],
		        	'stateParams'	=> ['document_id' => $key]
		        ];

		        Eoffice::sendMessage($sendMessageArray);
	        }

			return true;
		}

		return ['code' => ['0x041021', 'document']];
	}
	/**
	 * 获取所有对该文件夹有查看权限的用户，部门，角色
	 *
	 * @param int $folderId
	 *
	 * @return array 所有对该文件夹有查看权限的用户，部门，角色
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-06
	 */
	public function showAllViewPurviewMember($folderId, $documentId)
	{
        $purviewInfo = app($this->documentFolderPurviewRepository)->getPurviewInfo(['folder_id' => $folderId], ['all_purview','dept_view', 'user_view', 'role_view','dept_manage', 'user_manage', 'role_manage']);
        
        $memberKeys = [
            ['user', 'user_view', 'user_manage'],
            ['dept', 'dept_view', 'dept_manage'],
            ['role', 'role_view', 'role_manage']
        ];
        foreach ($memberKeys as $value) {
            $var = $value[0];
            if ($purviewInfo->{$value[1]} == 'all' || $purviewInfo->{$value[2]} == 'all') {
                $$var = 'all';
            } else {
                $view = explode(',', $purviewInfo->{$value[1]});
                $manage = explode(',', $purviewInfo->{$value[2]});
                $$var = array_unique(array_merge($manage, $view));
            }
        }

        if ($documentId != 0) {
            if ($documentInfo = app($this->documentContentRepository)->getDetail($documentId)) {
                if ($documentInfo->folder_type == 5 && !empty($documentInfo->flow_manager)) {
                    $flowManager = explode(',', $documentInfo->flow_manager);
                    $user = array_unique(array_merge($user, $flowManager));
                }
            }
        }

		//获取用户列表
		$userParam = [
			'user_id'		=> $user,
			'fields'		=> ['user_id', 'user_name'],
			'getDataType'	=> ''
		];
		$users = $this->getIdNameArrayAndStr($userParam, $user);

		$depts = $this->getIdNameArrayAndStr($dept, $dept, 'dept');

        $roles = $this->getIdNameArrayAndStr($role, $role, 'role');

		return ['user' => $users,'dept' => $depts,'role' => $roles,'all_purview' => $purviewInfo->all_purview];
	}

	private function getIdNameArrayAndStr($param, $view = false, $type = 'user')
	{
		if(!$view) {
			return [];
        }

        if ($view == 'all') {
            return [
                $type . '_id_str' => '',
                $type . '_name_str' => trans('document.all'),
                $type . '_array' => []
            ];
        }

		if($type == 'role'){
			$lists = app($this->roleRepository)->getRoleAttrByIds($param, ['role_id', 'role_name']);
		} else if($type == 'dept') {
			$lists = app($this->departmentRepository)->getDepartmentByIds($param);
		} else {
			$lists = app($this->userRepository)->getBatchUserInfoRepository($param);
		}

		$data = [];

		if (count($lists) > 0) {
			$names = $ids = '';

			$array = [];

			foreach ($lists as $list) {
				$name = $list->{$type . '_name'};

				$id = $list->{$type . '_id'};

				$names .= $name . ',';

				$ids .= $id . ',';

				$array[] = [$id, $name];
			}

			$data[$type . '_id_str']	= rtrim($ids, ',');

			$data[$type . '_name_str']	= rtrim($names, ',');

			$data[$type . '_array']		= $array;
		}

		return $data;
	}
	/**
	*	获取某文件夹的所有有查看
	*/
    public function getAllShowPurviewId($folderId)
    {
        $userPurview	= app($this->documentFolderPurviewRepository)->getPurviewInfo(['folder_id' => $folderId], ['all_purview','user_manage','user_view','dept_manage','dept_view','role_manage','role_view']);
        if($userPurview->all_purview == 1){
            return app($this->userRepository)->getAllUserIdString(['return_type' => 'array']);
        }
//        $folderInfo 	= app($this->documentFolderRepository)->getFolderInfo($folderId, ['user_id']);

        if($userPurview->user_manage == 'all'){
        	$user = [];
    		$users = app($this->userRepository)->getAllUsers([]);
    		if(!empty($users)){
    			foreach($users as $key => $value){
    				$user[] = $value->user_id;
    			}
    		}
    		$user_manage = $user;
        }else{
        	$user_manage = explode(',', rtrim($userPurview->user_manage, ','));
        }

//		$user_manage[] 		= $folderInfo->user_id;

		if($userPurview->user_view == 'all'){
        	$user = [];
    		$users = app($this->userRepository)->getAllUsers([]);
    		if(!empty($users)){
    			foreach($users as $key => $value){
    				$user[] = $value->user_id;
    			}
    		}
    		$user_view = $user;
        }else{
        	$user_view = explode(',', rtrim($userPurview->user_view, ','));
        }

        //获取有管理权限的部门用户id
        $dept_manage_user   = $this->getUserIdByDeptId($userPurview->dept_manage);
        //获取有查看权限的部门用户id
        $dept_view_user     = $this->getUserIdByDeptId($userPurview->dept_view);
        //获取有管理权限的角色用户id
        $role_manage_user   = $this->getUserIdByRoleId($userPurview->role_manage);
        //获取有查看权限的角色用户id
        $role_view_user     = $this->getUserIdByRoleId($userPurview->role_view);

		return array_filter(array_unique(array_merge($user_manage, $user_view, $dept_manage_user, $dept_view_user, $role_manage_user, $role_view_user)));
    }
    /**
     * 获取部门下所有用户id
     *
     * @param string $documentId
     *
     * @return array 用户ID列表
     *
     * @author 牛晓克
     *
     * @since 2017-07-28
     */
    public function getUserIdByDeptId($deptIds)
    {
    	if (empty($deptIds)) {
        	return [];
        }

    	if($deptIds == 'all'){
    		$user = [];
    		$users = app($this->userRepository)->getAllUsers([]);
    		if(!empty($users)){
    			foreach($users as $key => $value){
    				$user[] = $value->user_id;
    			}
    		}
    		return $user;
    	}
        $dept = explode(',', rtrim($deptIds, ','));

        $dept_manage_users   = app($this->userSystemInfoRepository)->getUserIdByDeptId($dept);

        if(!empty($dept_manage_users)){
            return array_column($dept_manage_users, 'user_id');
        }else{
            return [];
        }
    }
    /**
     * 获取角色下所有用户id
     *
     * @param string $documentId
     *
     * @return array 用户ID列表
     *
     * @author 牛晓克
     *
     * @since 2017-07-28
     */
    public function getUserIdByRoleId($deptIds)
    {
    	if($deptIds == 'all'){
    		$user = [];
    		$users = app($this->userRepository)->getAllUsers([]);
    		if(!empty($users)){
    			foreach($users as $key => $value){
    				$user[] = $value->user_id;
    			}
    		}
    		return $user;
    	}
        $roleId = explode(',', rtrim($deptIds, ','));

        $userIds = [];
        if(!empty($roleId)){
            foreach ($roleId as $value){
                if($value !== ''){
                    $userId = app($this->userRepository)->getUserIdByRole([$value]);
                    if(!empty($userId)){
                        foreach($userId as $v){
                            if(isset($v['user_id']) && $v['user_id'] !== ''){
                                $userIds[] = $v['user_id'];
                            }
                        }
                    }else{
                        continue;
                    }
                }else{
                    continue;
                }
            }
        }else{
            return [];
        }

        return array_filter(array_unique($userIds));

    }
	/**
	 * 获取文档附件
	 *
	 * @param int $documentId
	 *
	 * @return array 附件列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-08
	 */
	public function getDocumentAttachment($documentId)
	{

	}

	public function getPrvOrNextDocument($documentId, $own, $mark, $params)
	{
        $params = $this->parseParams($params);
//        $mode = get_system_param('document_view_mode');
        $userSystemInfo = app($this->userSystemInfoRepository)->getInfoByWhere(['user_id' => [$own['user_id']]]);
        $mode = $userSystemInfo[0]['document_view_mode'] ?? 1;
        $idKey = 'document_id';
        if (isset($params['from']) && $params['from'] == 'cloud' && $mode != 1) {
            $idKey = 'id';
            $list = $this->getCloudDocument($params, $own);
        } else {
            $lists = $this->listDocument($params, $own);
            if (isset($lists['total']) && $lists['total']>0 && isset($lists['list'])) {
                $list = $lists['list'];
            } else {
                $list = [];
            }
        }
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                if ($value[$idKey] == $documentId) {
                    if ($mark == '>') {
                        // 下一篇
                        if (isset($list[$key + 1])) {
                            return ['document_id' => $list[$key + 1][$idKey] ?? '', 'params' => $params];
                        } else {
                            $params['page']++;
                            if (isset($param['from']) && $param['from'] != 'mine' && $mode != 1) {
                                $tempList = $this->getCloudDocument($param, $own);
                            } else {
                                $tempLists = $this->listDocument($params, $own);
                                $tempList = $tempLists['list'] ?? [];
                            }
//                            if ($tempList['total'] > 0) {
                            if (count($tempList) > 0) {
                                return ['document_id' => $tempList[0][$idKey] ?? '', 'params' => $params];
                            } else {
                                return [];
                            }
                        }
                        
                    }
                    if ($mark == '<') {
                        // 上一篇
                        if (isset($list[$key - 1])) {
                            return ['document_id' => $list[$key - 1][$idKey] ?? '', 'params' => $params];
                        } else {
                            if ($params['page'] == 1) {
                                return [];
                            } else {
                                $params['page']--;
                                if (isset($param['from']) && $param['from'] != 'mine' && $mode != 1) {
                                    $tempList = $this->getCloudDocument($param, $own);
                                } else {
                                    $tempLists = $this->listDocument($params, $own);
                                    $tempList = $tempLists['list'] ?? [];
                                }
                                if (count($tempList) > 0) {
                                    return ['document_id' => $tempList[9][$idKey], 'params' => $params];
                                } else {
                                    return [];
                                }
                            }
                        }
                    }
                }
            }

            if(isset($params['follow']) && $params['follow'] == 1){
                if ($mark == '>') {
                    // 下一篇
                    if (isset($list[0])) {
                        return ['document_id' => $list[0][$idKey] ?? '', 'params' => $params];
                    } else {
                        $params['page']++;
                        if (isset($param['from']) && $param['from'] != 'mine' && $mode != 1) {
                            $tempList = $this->getCloudDocument($param, $own);
                        } else {
                            $tempLists = $this->listDocument($params, $own);
                            $tempList = $tempLists['list'] ?? [];
                        }
                        if (count($tempList) > 0) {
                            return ['document_id' => $tempList[0][$idKey] ?? '', 'params' => $params];
                        } else {
                            return [];
                        }
                    }
                }
                if ($mark == '<') {
                    // 下一篇
                    if (isset($list[0])) {
                        return ['document_id' => $list[0][$idKey] ?? '', 'params' => $params];
                    } else {
                        if ($params['page'] == 1) {
                            return [];
                        } else {
                            $params['page']--;
                            if (isset($param['from']) && $param['from'] != 'mine' && $mode != 1) {
                                $tempList = $this->getCloudDocument($param, $own);
                            } else {
                                $tempLists = $this->listDocument($params, $own);
                                $tempList = $tempLists['list'] ?? [];
                            }
                            if (count($tempList) > 0) {
                                return ['document_id' => $tempList[9][$idKey], 'params' => $params];
                            } else {
                                return [];
                            }
                        }
                    }
                }
            }
        }
        return ['code' => ['0x041027', 'document']];
	}
	/**
	 * 新建文档回复
	 *
	 * @param int $documentId
	 *
	 * @return array 回复id
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-08
	 */
	public function addRevert($data,$documentId, $own)
	{
		if ($documentId == 0) {
			return ['code' => ['0x041017', 'document']];
		}

		if (!$this->hasRevertPurviewOfDocuemnt($documentId, $own)) {
			return ['code' => ['0x041022', 'document']];
		}

        $revertData = array_intersect_key($data, array_flip(app($this->documentRevertRepository)->getTableColumns()));

		if (!$result = app($this->documentRevertRepository)->insertData($revertData)) {
			return ['code' => ['0x000003', 'common']];
		}

		if(isset($data['attachments'])){
			app($this->attachmentService)->attachmentRelation("document_revert", $result->revert_id, $data['attachments']);
		}

		return $result;
	}
	/**
	 * 获取文档回复列表
	 *
	 * @param array $param
	 * @param int $documentId
	 *
	 * @return array 文档回复列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-08
	 */
	public function listRevert($param, $documentId, $own)
	{
		if (!$result = $this->hasDocumentShowPurview($documentId, $own)) {
			return ['code' => ['0x041027', 'document']];
		}
		$param = $this->parseParams($param);

		// $getSourceIds = app($this->documentContentRepository)->getSourceId($documentId);

		// if(isset($getSourceIds[0]) && isset($getSourceIds[0]['source_id']) && $getSourceIds[0]['source_id'] !== ''){
		// 	$sourceId = $getSourceIds[0]['source_id'];
		// }else{
		// 	$sourceId = '';
		// }

		// $data = [];
		// // 流程归档文档
		// if($sourceId){
		// 	$param['run_id'] = $sourceId;
		// 	$data = $this->response(app($this->flowRunFeedbackRepository), 'getFeedbackListTotal', 'getFlowFeedbackListRepository', $param);
		// 	if(count($data['list']) > 0){
	 //            foreach($data['list'] as $key => $revert){
	 //                $data['list'][$key]['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'feedback', 'entity_id'=>$revert['feedback_id']]);
	 //                $data['list'][$key]['revert_id'] = $revert['feedback_id'];
	 //                $data['list'][$key]['user_name'] = $revert['flow_run_feedback_has_one_user']['user_name'];
	 //                $data['list'][$key]['revert_content'] = $revert['content'];
	 //                $data['list'][$key]['created_at'] = $revert['edit_time'];
	 //            }
	 //        }
		// }

		$param['search']['document_id'] = [$documentId];
		$data = $this->response(app($this->documentRevertRepository), 'getRevertCount', 'listRevert', $param);
        if($data['total'] > 0){
            foreach($data['list'] as $key => $value){
                $data['list'][$key]->attachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'document_revert', 'entity_id'=>$value->revert_id]);
                if(isset($value["first_revert_has_many_revert"]) && is_array($value["first_revert_has_many_revert"])) {
                    foreach ($value["first_revert_has_many_revert"] as $revertKey => $revertValue) {
                        $list[$key]["first_revert_has_many_revert"][$revertKey]["attachments"] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'document_revert', 'entity_id'=>$revertValue["revert_id"]]);
                    }
                }
            }
        }

        return $data;
    }

    // 编辑回复
    public function editRevert($documentId, $revertId, $data, $own) {
        if ($documentId == 0) {
			return ['code' => ['0x041017', 'document']];
		}

		if (!$this->hasRevertPurviewOfDocuemnt($documentId, $own)) {
			return ['code' => ['0x041022', 'document']];
        }
        $revertData = array_intersect_key($data, array_flip(app($this->documentRevertRepository)->getTableColumns()));

        if (!$result = app($this->documentRevertRepository)->updateData($revertData, ['revert_id' => $revertId])) {
			return ['code' => ['0x000003', 'common']];
		}

		if(isset($data['attachments'])){
			app($this->attachmentService)->attachmentRelation("document_revert", $revertId, $data['attachments']);
		}

        return true;
    }

    // 删除回复
    public function deleteRevert($documentId, $revertId, $own) {
        if ($documentId == 0) {
			return ['code' => ['0x041017', 'document']];
		}

		if (!$this->hasRevertPurviewOfDocuemnt($documentId, $own)) {
			return ['code' => ['0x041022', 'document']];
        }

        return app($this->documentRevertRepository)->deleteById($revertId);
    }

	public function getRevertInfo($documentId, $revertId)
	{
		$revertInfo =  app($this->documentRevertRepository)->getDetail($revertId);

		$revertInfo->user_name = get_user_simple_attr($revertInfo->user_id);

		return $revertInfo;
	}

    public function shareDownloadSet($shareDownload)
    {
        if(set_system_param('share_document_download', $shareDownload)){
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    public function getShareDownload()
    {
        return get_system_param('share_document_download');
    }

	public function listLogs($param, $documentId)
	{
		if ($documentId == 0) {
			return ['code' => ['0x041017', 'document']];
		}
        $param = $this->parseParams($param);
        if (!isset($param['search'])) {
            $param['search'] = [];
        }
        if (isset($param['search']['user_name']) && $param['search']['user_name'] == []) {
            unset($param['search']['user_name']);
        }
        if (isset($param['search']['operate_type']) && $param['search']['operate_type'] == []) {
            unset($param['search']['operate_type']);
        }
        $param['search']['document_id'] = [$documentId];
        $data = $this->response(app($this->documentLogsRepository), 'logCount', 'listLog', $param);
        if (isset($param['search']['operate_type']) && isset($param['search']['operate_type'][0])) {
            $operateType = $param['search']['operate_type'][0];
            if (!empty($data['list'])) {
	            // 共享日志处理
	            if ($operateType == 5) {
	                foreach ($data['list'] as &$value) {
	                	$logInfo = !empty($value->log_info) ? json_decode($value->log_info, true) : [];
	                    $value->share_all = $this->defaultValue('share_all', $logInfo, 1);
	                    $value->share_user = $this->defaultValue('share_user', $logInfo, []);
	                    $value->share_dept = $this->defaultValue('share_dept', $logInfo, []);
	                    $value->share_role = $this->defaultValue('share_role', $logInfo, []);
	                    $value->share_status = $this->defaultValue('share_status', $logInfo, 0);
	                    $value->share_end_time = isset($logInfo['share_end_time']) && !empty($logInfo['share_end_time']) ? date('Y-m-d H:i', strtotime($logInfo['share_end_time'])) : '';
	                }
	            }
	            // 移动日志处理
	            if ($operateType == 6) {
	            	foreach ($data['list'] as &$value) {
	            		$logInfo = !empty($value->log_info) ? json_decode($value->log_info, true) : [];
	            		$value->from_id = $this->defaultValue('from_id', $logInfo, '');
	            		if (!empty($value->from_id)) {
                            $fromFolder = app($this->documentFolderRepository)->getDetail($value->from_id);
                            $value->from_name = $fromFolder->folder_name ?? trans('document.folder_not_exists_or_deleted');
                        } else {
                            $value->from_name = trans('document.folder_not_exists_or_deleted');
                        }
	            		$value->to_id = $this->defaultValue('to_id', $logInfo, '');
	            		if (!empty($value->to_id)) {
	            			$toFolder = app($this->documentFolderRepository)->getDetail($value->to_id);
	            			$value->to_name = $toFolder->folder_name ?? trans('document.folder_not_exists_or_deleted');
	            		} else {
	            			$value->to_name = trans('document.folder_not_exists_or_deleted');
	            		}
	            	}
	            }
                // 下载日志处理
                if ($operateType == 8) {
                    foreach ($data['list'] as &$value) {
                        $logInfo = !empty($value->log_info) ? json_decode($value->log_info, true) : [];
                        $value->attachment_name = $this->defaultValue('attachment_name', $logInfo, '') ?? '';
                    }
                }
            }
        }else{
            if (!empty($data['list'])) {
                // 全部日志
                foreach ($data['list'] as &$value) {
                    $operateType = ['', trans('document.new'), trans('document.edit'), trans('document.read'), '', trans('document.share'), trans('document.transfer'), '', trans('document.download'), trans('document.print')];
                    $value->operate_type_name = isset($value->operate_type) && !empty($value->operate_type) ? $operateType[$value->operate_type] : '';
                }
            }
        }
        return $data;
	}
	/**
	*文档归档
	*/
	public function archiveDocument($data, $source = 'flowRun')
	{
		$documentId = $this->{$source . 'Archive'}($data);

		$user = "";
		if(isset($data["document"])) {
			$user = isset($data["document"]['creator']) && $data["document"]['creator'] ? $data["document"]['creator'] : 'archive';
		}
		if($documentId){
            $this->addLog($documentId ,1 , $user);
			return ['document_id' => $documentId];
		}

		return 0;
	}
	/**
	*流程归档
	*/
	private function flowRunArchive($data)
	{
		//归档文档

		if (!$documentContentresult = app($this->documentContentRepository)->insertData($data['document'])) {
			return false;
		}

		$documentId = $documentContentresult->document_id;
		//添加附件
		if(isset($data['attachment']) && !empty($data['attachment'])){
        	app($this->attachmentService)
        		->attachmentRelation("document_content", $documentId, $data['attachment']);
		}
		//添加回复
		if(isset($data['revert']) && !empty($data['revert'])){
			$reverts = [];

			foreach($data['revert'] as $value) {
				$revert = [
					'document_id' 		=> $documentId,
					'user_id' 			=> $value['user_id'],
					'revert_content' 	=> $value['revert_content'],
					'extra_fields' 		=> $value['extra_fields'],
					'created_at' 		=> $value['created_at'],
				];

				$documentRevertresult = app($this->documentRevertRepository)->insertData($revert);

				if(isset($value['attachments'])){
					app($this->attachmentService)->attachmentRelation("document_revert", $documentRevertresult->revert_id, $value['attachments']);
				}
				// $reverts[] = $revert;
			}
			// app($this->documentRevertRepository)->mulitAddRevert($reverts);
		}
        //添加共享文档
		if(isset($data['user']) && $data['user']){
			$share = [
				'document_id' 	=> $documentId,
				'folder_id'		=> $documentContentresult->folder_id,
				'from_user'		=> $data['current_user'],
				'share_all'		=> 0,
				'share_user'	=> $data['user'],
				'share_dept'	=> '',
				'share_role'	=> ''
			];

			app($this->documentShareRepository)->insertData($share);
		}

		return $documentId;
	}
	/**
	 * 为文档设置样式
	 *
	 * @param object $document
	 *
	 * @return object 文档对象
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-06
	 */
    private function setDocumentMode($document,$modeId)
    {
        if(!empty($document->tag_name)){
            $tagId = array_column($document->tag_name, 'tag_id');
            $document->tags = app($this->documentTagRepository)->getTagName($tagId, $document->document_id);
            $document->tag_name = array_unique(array_column($document->tags, 'tag_name'));
            $document->tag_name = implode(' ', $document->tag_name);
        }else{
            $document->tag_name = '';
            $document->tags = [];
        }

        if($modeId){
            $mode = app($this->documentModeRepository)->getDetail($modeId);

            if(!$mode){
                $mode = app($this->documentModeRepository)->getDetail(0);
            }
        } else {
            $mode = app($this->documentModeRepository)->getDetail(0);
        }

        if(!$mode){
            $document->source_content = $document->content;
            if(empty($document->source_content) && ($document->file_type == 1 || $document->file_type == 2)){
                $document->source_content = is_array($document->attachment_id) && isset($document->attachment_id[0]) ? $document->attachment_id[0] : '';
            }
            return $document;
        }

        $find = [
            '[***DocumentCreateTime***]',
            '[***DocumentCreator***]',
            '[***DocumentTitle***]',
            '[***DocumentTag***]',
            '[***DocumentContent***]',
            '[***DocumentAttachment***]'
        ];

        $replace = [
            $document->created_at,
            $document->creator_name,
            $document->subject,
            $document->tag_name,
            $document->content,
            '<div id="commonAttachment"></div>'
        ];

        $document->source_content = $document->content;
        if(empty($document->source_content) && ($document->file_type == 1 || $document->file_type == 2)){
            $document->source_content = is_array($document->attachment_id) && isset($document->attachment_id[0]) ? $document->attachment_id[0] : '';
        }
        $document->content = str_replace($find, $replace, $mode->mode_content);

        return $document;
    }
	/**
	 * 获取有管理权限的子文件夹id
	 *
	 * @param int $parentId
	 *
	 * @return array 有管理权限的子文件夹id
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-06
	 */
	private function getManageChildrenFolderId($parentId, $own, $isAll = false)
	{
		$manageFolderId = $creatorFolderId = [];

		if ($creatorFolder = app($this->documentFolderRepository)->getCreatorChildrenFolderId($parentId,$own['user_id'], $isAll)) {
			$creatorFolderId = array_column($creatorFolder->toArray(), 'folder_id');
		}

		if ($manageFolder = app($this->documentFolderPurviewRepository)->getManageChildrenFolderId($parentId, $own, $isAll)) {
			$manageFolderId = array_column($manageFolder->toArray(), 'folder_id');
		}

		return array_unique(array_merge($manageFolderId, $creatorFolderId));
	}
	/**
	 * 获取有新建权限的子文件夹id
	 *
	 * @param int $parentId
	 *
	 * @return array 有新建权限的子文件夹id
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	private function getCreateChildrenFolderId($parentId, $own)
	{
		if (!$folders = app($this->documentFolderPurviewRepository)->getCreateChildrenFolderId($parentId, $own)) {
			return [];
		}

		return array_column($folders->toArray(), 'folder_id');
	}
	/**
	 * 获取有查看权限的子文件夹id
	 *
	 * @param int $parentId
	 *
	 * @return array 有查看权限的子文件夹id
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	private function getShowChildrenFolderId($parentId, $own)
	{
		if (!$folders = app($this->documentFolderPurviewRepository)->getShowChildrenFolderId($parentId, $own)) {
			return [];
		}

		return $folders->pluck('folder_id')->toArray();
	}

    private function getShowFamilyFolderId($parentId, $own)
    {
        if (!$folders = app($this->documentFolderPurviewRepository)->getShowFamilyFolderId($parentId, $own)) {
            return [];
        }

        return $folders->pluck('folder_id')->toArray();
    }
	/**
	 * 判断文件夹在管理列表下是否可显示
	 *
	 * @param int $folderId
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	private function hasViewManageFolder($folderId, $own)
	{
		return app($this->documentFolderRepository)->getFamilyCreatorFolderCount($folderId, $own['user_id']) > 0 || app($this->documentFolderPurviewRepository)->getFamilyManagerFolderCount($folderId, $own) > 0;
	}
	/**
	 * 获取某文件夹下的共享文档
	 *
	 * @param int $folderId
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	private function hasShareDocumentOfFolder($folderId, $own)
	{
		return app($this->documentShareRepository)->hasShareDocumentOfFolder($folderId, $own) > 0;
	}
	/**
	 * 获取某文件夹下创建人的文档
	 *
	 * @param int $folderId
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	private function hasCreatorDocumentOfFolder($folderId, $own)
	{
		return app($this->documentContentRepository)->hasCreatorDocumentOfFolder($folderId, $own) > 0;
	}
	/**
	 * 判断文件夹在新建文档列表下可否显示
	 *
	 * @param int $folderId
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	private function hasViewCreateFolder($folderId, $own)
	{
		return app($this->documentFolderPurviewRepository)->getFamilyCreateFolderCount($folderId,$own) > 0;
	}
	/**
	 * 判断文件夹在查看文档列表下可否显示
	 *
	 * @param int $folderId
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	private function hasViewShowFolder($folderId, $own)
	{
        if(app($this->documentFolderPurviewRepository)->getFamilyShowFolderCount($folderId, $own) > 0){
            return true;
        } else {
            $folderIds = array_column(app($this->documentFolderPurviewRepository)->getAllFamilyFolderId($folderId), 'folder_id');

            if(!empty($folderIds)) {
                if(app($this->documentShareRepository)->hasShareDocumentInFolder($folderIds, $own) > 0){
                    return true;
                }
            }
        }

        return false;
	}
	/**
	 * 新增文件夹权限数据
	 *
	 * @param int $folderId
	 * @param int $parentId
	 * @param string $folderLevelId
	 *
	 * @return object
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	private function addFolderPurview($folderId, $parentId, $folderLevelId, $userId = '')
	{
		$data = [
			'folder_id'			=> $folderId,
			'parent_id'			=> $parentId,
			'folder_level_id'	=> $folderLevelId,
			'user_new'			=> $userId == '' ? '' : $userId
		];

		if($parentId != 0){
			$folder = app($this->documentFolderRepository)->getFolderInfo($parentId, ['purview_extends']);

			if ($folder && ($folder->purview_extends == 1)) {
				$data = array_merge($data, $this->getPurviewArray($parentId));
			}
		}

		return app($this->documentFolderPurviewRepository)->insertData($data);
	}
	/**
	 * 获取层级结构id
	 *
	 * @param int $folderId
	 *
	 * @return string 层级结构id
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	private function getFolderLevelId($folderId)
	{
		if ($folderId == 0) {
			return '0';
		}

		$folder = app($this->documentFolderRepository)->getFolderInfo($folderId, ['folder_level_id']);

		return $folder->folder_level_id . ',' . $folderId;
	}
	/**
	 * 判断文件夹管理下的管理权限
	 *
	 * @param int $folderId
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	public function hasManagerPurview($folderId, $own)
	{
		if ($own['user_id'] == 'admin') {
			return true;
		}

		if($folder = app($this->documentFolderRepository)->getFolderInfo($folderId, ['user_id'])) {
			return $folder->user_id == $own['user_id'] || app($this->documentFolderPurviewRepository)->hasManagerPurview($folderId, $own) > 0;
		}

		return false;
	}
	/**
	 * 判断某文档是否有回复权限
	 *
	 * @param int $documentId
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	private function hasRevertPurviewOfDocuemnt($documentId, $own)
	{
		$document = app($this->documentContentRepository)->getDocumentInfo($documentId, ['creator','folder_id']);

		if(app($this->documentFolderPurviewRepository)->hasRevertPurview($document->folder_id, $own) > 0){
			return true;
		}elseif($document->creator == $own['user_id']){
			if($this->getCreatorPurview()){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
    }
    /**
     * 判断文档管理下的管理权限
     *
     * @param int $documentId
     *
     * @return boolean
     *
     * @author 李志军
     *
     * @since 2015-11-05
     */

    public function hasDocumentManagePurview($documentId, $own, $document=[], $folderPurview = 0)
    {
        if (empty($document)) {
            if (!$document = app($this->documentContentRepository)->getDetail($documentId)) {
                return false;
            }
        }
		
		if ($document->is_draft == 1 && $own['user_id'] != $document->creator) {
        	return false;
        }
        if (empty($folderPurview) || $folderPurview === 0) {
        	$folderPurview = app($this->documentFolderPurviewRepository)->hasManagerPurview($document->folder_id, $own) > 0;
        }
        if($document->creator == $own['user_id']){
            if ($document->is_draft == 1) {
                return true;
            }elseif($folderPurview){
        		return true;
        	}elseif($this->getCreatorPurview()){
        		return true;
        	}else{
    			return false;
    		}
    	}else{
    		return $folderPurview;
    	}
    }
    // 是否有编辑权限
    public function hasDocumentEditPurview($documentId, $own, $document=[], $editPurview=0){
        if (empty($document)) {
            if (!$document = app($this->documentContentRepository)->getDetail($documentId)) {
                return false;
            }
        }

        if ($document->is_draft == 1 && $own['user_id'] != $document->creator) {
        	return false;
        }

        if (empty($editPurview) || $editPurview === 0) {
            $editPurview = app($this->documentFolderPurviewRepository)->hasEditPurview($document->folder_id, $own) > 0;
        }
        
        if($document->creator == $own['user_id']){
            if ($document->is_draft == 1) {
                return true;
            }elseif($editPurview){
        		return true;
        	}elseif($this->getCreatorPurview()){
        		return true;
        	}else{
    			return false;
    		}
    	}else{
    		return $editPurview;
    	}
    }
    
    // 判断文档的删除权限
    public function hasDocumentDeletePurview($documentId, $own, $document) {
        if (empty($document)) {
            if (!$document = app($this->documentContentRepository)->getDetail($documentId)) {
                return false;
            }
        }

		if ($document->is_draft == 1 && $own['user_id'] != $document->creator) {
        	return false;
        }

        if($document->creator == $own['user_id']){
        	if(app($this->documentFolderPurviewRepository)->hasDeletePurview($document->folder_id, $own) > 0){
        		return true;
        	}elseif($this->getCreatorPurview()){
        		return true;
        	}else{
    			return false;
    		}
    	}else{
    		return app($this->documentFolderPurviewRepository)->hasDeletePurview($document->folder_id, $own) > 0;
    	}
    }
    /**
     * 判断文档的查看权限
     *
     * @param int $documentId
     *
     * @return boolean
     *
     * @author 李志军
     *
     * @since 2015-11-05
     */
    public function hasDocumentShowPurview($documentId, $own)
    {
        if (!$documentInfo = app($this->documentContentRepository)->getDetail($documentId)) {
            return false;
        }
        if ($documentInfo->is_draft == 1 && $own['user_id'] != $documentInfo->creator) {
        	return false;
        }

        if ($documentInfo->creator == $own['user_id']
            || app($this->documentFolderPurviewRepository)->hasShowPurview($documentInfo->folder_id, $own) > 0
            || app($this->documentShareRepository)->hasSharePurview($documentId, $own) > 0
            || ($documentInfo->source_id != 0 && in_array($own['user_id'], explode(',', $documentInfo->flow_manager)))) {
            return $documentInfo;
        }

		return false;
	}
	/**
	 * 判断文件夹是否有新建文档权限
	 *
	 * @param int $folderId
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	public function hasCreatePurview($folderId,$own)
	{
		return app($this->documentFolderPurviewRepository)->hasCreatePurview($folderId,$own) > 0;
	}
	/**
	 * 判断是否有子文件夹
	 *
	 * @param int $folderId
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	private function hasChildrenFolders($folderId)
	{
		return app($this->documentFolderRepository)->getChildrenFoldersCount($folderId) >= 1;
	}
	/**
	 * 判断某文件夹下是否有文档
	 *
	 * @param int $folderId
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-05
	 */
	private function hasDocumentsOfFolder($folderId)
	{
		return app($this->documentContentRepository)->getDocumentsCountByFolderId($folderId) >= 1;
	}
	/**
	 * 为参数赋予默认值
	 *
	 * @param string $key 键值
	 * @param array $data 原来的数据
	 * @param int|string $default 默认值
	 *
	 * @return string|int 处理后的值
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-17
	 */
	private function defaultValue($key, $data, $default)
	{
		return isset($data[$key]) ? $data[$key] : $default;
	}
	/**
	 * 处理文档查询
	 *
	 * @param array $param
	 *
	 * @return array 文档查询参数
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-17
	 */
	private function handleDocumentSearch($param)
	{
		if (!isset($param['search']) || empty($param['search'])) {
			return $param;
		}

		$and = $or = [];

		$filter = ['content','subject','attachment_name'];

		foreach ($param['search'] as $k => $v) {
			if(in_array($k, $filter)) {
                $or[$k] = $k == 'attachment_name' ? [$this->getDocumentIdByAttachmentName($v)] : $v;
			} else {
				$and[$k] = $v;
			}
		}

		$param['search'] = $and;

		$param['orSearch'] = $or;

		return $param;
	}
	/**
	 * 根据附件名称获取文档id
	 *
	 * @param array $param
	 *
	 * @return array 文档id
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-17
	 */
	private function getDocumentIdByAttachmentName($param)
	{
        return app($this->attachmentService)->getEntityIdsByAttachmentName($param[0], 'document_content', false);
	}
	/**
	 * 新建文档操作日志
	 *
	 * @param int $documentId
	 * @param int $operateType
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-17
	 */
    public function addLog($documentId, $operateType = 1, $currentUserId, $logInfo = '')
	{
	    $document = app($this->documentContentRepository)->getDocumentInfo($documentId, ['folder_id','subject']) ?? '';
        if($document){
            $user_name = app($this->userService)->getUserName($currentUserId) ?? '';
            $content = $user_name . trans('document.read') . $document->subject;
            $identifier  = "document.document.view";
            if($operateType == 1){
                $identifier  = "document.document.add";
                $content =  $user_name . trans('document.new') . $document->subject;
            }else if($operateType == 2){
                $identifier  = "document.document.edit";
                $content = $user_name . trans('document.edit') . $document->subject;
            }else if($operateType == 3){
                $identifier  = "document.document.view";
                $content = $user_name . trans('document.read') . $document->subject;
            }else if($operateType == 8){
                $identifier  = "document.document.download";
                $logInfo = json_decode($logInfo, true);
                $content = trans('document.attachment_name').'：';
                if (!empty($logInfo['attachment_name'])) {
                    $content .= $logInfo['attachment_name'].'；';
                }
            }else if($operateType == 9){
                $identifier  = "document.document.print";
                $content = $user_name . trans('document.print') . $document->subject;
            }
            $logParams = $this->handleLogParams($currentUserId, $content, $documentId, $document->subject);
            logCenter::info($identifier , $logParams);
        }
        return true;
	}

    public function addLogs($data, $own)
    {
        $document = app($this->documentContentRepository)->getDocumentInfo($data['document_id'], ['folder_id','subject']) ?? '';
        if($document){
            $content = $own['user_name'] . trans('document.read') . $document->subject;
            $identifier  = "document.document.view";
            if($data['operate_type'] == 1){
                $identifier  = "document.document.add";
                $content =  $own['user_name'] . trans('document.new') . $document->subject;
            }else if($data['operate_type'] == 2){
                $identifier  = "document.document.edit";
                $content = $own['user_name'] . trans('document.edit') . $document->subject;
            }else if($data['operate_type'] == 3){
                $identifier  = "document.document.view";
                $content = $own['user_name'] . trans('document.read') . $document->subject;
            }else if($data['operate_type'] == 8){
                $identifier  = "document.document.download";
                $logInfo = json_decode($data['log_info'], true);
                $content = trans('document.attachment_name').'：';
                if (!empty($logInfo['attachment_name'])) {
                    $content .= $logInfo['attachment_name'].'；';
                }
            }else if($data['operate_type'] == 9){
                $identifier  = "document.document.print";
                $content = $own['user_name'] . trans('document.print') . $document->subject;
            }
            $logParams = $this->handleLogParams($own['user_id'], $content, $data['document_id'], $document->subject);
            logCenter::info($identifier , $logParams);
        }
        return true;
    }

	private function getPurviewArray($folderId, $copy = false)
	{
		$fields = ['all_purview','dept_manage','dept_edit','dept_delete','dept_new','dept_down','dept_print','dept_revert','dept_view',
							'role_manage','role_edit','role_delete','role_new','role_down','role_print','role_revert','role_view',
							'user_manage','user_edit','user_delete','user_new','user_down','user_print','user_revert','user_view'];
		if($copy){
			$fields[] = 'parent_id';
			$fields[] = 'folder_level_id';
		}

		return app($this->documentFolderPurviewRepository)->getPurviewInfo(['folder_id' => $folderId],$fields)->toArray();
	}

	private function copyPurviewToChildren($folderId)
	{
		$data = $this->getPurviewArray($folderId);

		if ($childrens = app($this->documentFolderRepository)->getChildrenInfo($folderId, ['folder_id'])) {
			$folderIds = array_column($childrens->toArray(), 'folder_id');

			if(!empty($folderIds)) {
				return app($this->documentFolderPurviewRepository)->updateChildrenData($data, $folderIds);
			}
		}

		return true;
	}

	private function stringToArray($string, $typed = 'default', $delimiter = ',')
	{
		return (!$string || $string == '') ? [] : ($typed == 'int' ? $this->stringArrayInteger(explode($delimiter, $string)) : explode($delimiter, $string));
	}

	private function stringArrayInteger($data)
	{
		for($i = 0; $i < count($data); $i++){
			$data[$i] = intval($data[$i]);
		}

		return $data;
	}

	/**
	 * 文档报表
	 *
	 * @param string $datasource_group_by
     * @param array $datasource_data_analysis
     * @param array $chart_search
	 *
	 * @author niuxiaoke
	 *
	 * @since 2017-08-04
	 */
	public function getDocumentReportData($datasource_group_by='creator',$datasource_data_analysis,$chart_search)
	{
		$data = [];

		$creator = array_column(app($this->documentContentRepository)->getAllCreator(), 'creator');

		if(isset($chart_search['dept_id'])){
			$deptId = explode(',', $chart_search['dept_id']);

			$userIdByDept = [];
			if(!empty($deptId)){
				foreach($deptId as $key => $value){
					$dept = array_column(app($this->departmentRepository)->getALLChlidrenByDeptId($value), 'dept_id');

					if(!empty($dept)){
                        $dept[] = $value;

                        $depts = implode(',', $dept);

                        $userIdByDept = array_merge($userIdByDept, $this->getUserIdByDeptId($depts));
                    }else{
                        $userIdByDept = array_merge($userIdByDept, $this->getUserIdByDeptId($value));
                    }
				}
			}
    	}else{
    		$userIdByDept = [];
    	}

    	if(isset($chart_search['role_id'])){
    		$userIdByRole = $this->getUserIdByRoleId($chart_search['role_id']);
    	}else{
    		$userIdByRole = [];
    	}

    	if(isset($chart_search['dept_id']) && isset($chart_search['role_id'])){
    		$userIds = array_unique(array_intersect($userIdByDept, $userIdByRole, $creator));
    	}elseif(isset($chart_search['dept_id']) && !isset($chart_search['role_id'])){
    		$userIds = array_unique(array_intersect($creator,$userIdByDept));
    	}elseif(isset($chart_search['role_id']) && !isset($chart_search['dept_id'])){
    		$userIds = array_unique(array_intersect($creator,$userIdByRole));
    	}else{
    		$userIds = $creator;
    	}

    	$userNames = app($this->userRepository)->getUserNames($userIds);

    	$user = [];

    	if(!empty($userNames)){
    		foreach($userNames as $key => $value){
	    		$user[] = ['user_id'=>$value['user_id'],'name'=>$value['user_name'], 'y'=>0];
	    	}
	    	if(!isset($chart_search['role_id']) && !isset($chart_search['dept_id'])){
	    		$user[] = ['user_id'=>'archive','name'=>trans('document.flow_archive'), 'y'=>0];
	    	}
    	}

		// 创建文档数量
		if(isset($datasource_data_analysis['createCount'])){
			$data1 = [
	            'name'     => trans('document.create_count'),
	            'group_by' => trans('document.creator'),
	            'data'     => []
	        ];

	        $createCount = app($this->documentContentRepository)->getCreateCount($userIds, $chart_search);

	        if($createCount){
	        	$createCounts = [];

	        	foreach($createCount as $key => $value){
		        	$createCounts[$value['creator']] = $value['count'];
		        }

		        $userTmp = $user;
	        	foreach($userTmp as $key => $value){
	        		if(isset($createCounts[$value['user_id']])){
	        			$userTmp[$key]['y'] = $createCounts[$value['user_id']];
	        		}else{
	        			$userTmp[$key]['y'] = 0;
	        		}
	        		unset($userTmp[$key]['user_id']);
	        	}
	        	$data1['data'] = $userTmp;
	        }
	    	$data[] = $data1;
    	}
        // 已阅读数量
        if(isset($datasource_data_analysis['readCount'])){
        	$data3 = [
	            'name'     => trans('document.read_count'),
	            'group_by' => trans('document.creator'),
	            'data'     => []
        	];

        	if(!empty($userIds)){
                $readCounts = app($this->logCenterService)->getRead('document', $userIds, 'document_content', $chart_search);
                if($readCounts){
                    $userTmp = $user;
		        	foreach($userTmp as $key => $value){
		        		if($value['user_id'] == 'archive'){
		        			$userTmp[$key]['y'] = 0;
		        		}else{
		        			$userTmp[$key]['y'] = isset($readCounts[$value['user_id']]) ? $readCounts[$value['user_id']] : 0;
		        		}

		        		unset($userTmp[$key]['user_id']);
		        	}
		        	$data3['data'] = $userTmp;
		        }
        	}

        	$data[] = $data3;
        }

        // 回复数量
        if(isset($datasource_data_analysis['replyCount'])){
        	$data4 = [
	            'name'     => trans('document.apply_count'),
	            'group_by' => trans('document.creator'),
	            'data'     => []
        	];

        	$replyCount = app($this->documentRevertRepository)->getAllRevertCount($userIds, $chart_search);
			$diff = array_diff($userIds, array_column($replyCount, 'user_id'));
        	if($replyCount){
		        	$replyCounts = [];

		        	foreach($replyCount as $key => $value){
			        	$replyCounts[$value['user_id']] = $value['count'];
			        }

			        $userTmp = $user;
		        	foreach($userTmp as $key => $value){
		        		if(in_array($value['user_id'], $diff)){
		        			$userTmp[$key]['y'] = 0;
		        		}else{
		        			if(isset($replyCounts[$value['user_id']])){
		        				$userTmp[$key]['y'] = $replyCounts[$value['user_id']];
		        			}
		        		}

		        		unset($userTmp[$key]['user_id']);
		        	}
		        	$data4['data'] = $userTmp;
		        }

        	$data[] = $data4;
        }

        return $data;

	}

	/**
	 * 获取可查看文档id
	 *
	 * @param string $own
	 *
	 * @author niuxiaoke
	 *
	 * @since 2017-08-07
	 */
	public function getViewDocumentId($own)
	{
		$param = [];

		$folderIdArray = $this->getListDocumentFolderIdArray($own);


        $shareDocumentIds = $this->objectColumn(app($this->documentShareRepository)->getShareDocumentsByFolder($folderIdArray[1], $own), 'document_id');

		$documentIds = app($this->documentContentRepository)->listDocument($param, $own, $folderIdArray[0], $folderIdArray[1], $shareDocumentIds);

		return $documentIds;
	}

	/**
     * 删除文档标签
     *
     * @param array $param
     *
     * @return boolean
     *
     * @author niuxiaoke
     *
     * @since 2017-08-01
     */
    public function delDocumentTags($param, $userId)
    {
        $param                      = $this->parseParams($param);

        // $param['search']['user_id'] = [$userId];

        // $param['search']['tag_type'] = ["public"];

        // $inputTag = $param['search']['tag_id'][0];

        // $commonTags = app($this->documentTagRepository)->getDocumentTags($param['search']);

        return app($this->documentTagRepository)->deleteByWhere($param['search']);
    }

    /**
     * 添加文档标签
     *
     * @param array $param
     *
     * @return boolean
     *
     * @author niuxiaoke
     *
     * @since 2017-08-01
     */
    public function addDocumentTags($data, $userId)
    {
        $params = [
            'user_id'     => [$userId],
            'document_id' => [$data['document_id']],
            'document_tag.tag_id'      => [$data['tag_id'], 'in'],
        ];

        $have = app($this->documentTagRepository)->getDocumentTags($params);
        if (!empty($have)) {
            $haveTag        = array_column($have, 'tag_id');
            $data['tag_id'] = array_diff($data['tag_id'], $haveTag);
        }

        $tags = [];
        foreach ($data['tag_id'] as $k => $v) {
            $tags[] = [
                'user_id'     => $userId,
                'document_id' => $data['document_id'],
                'tag_id'      => $v,
            ];
        }

        $result = app($this->documentTagRepository)->insertMultipleData($tags);

        if (!empty($have)) {
            return ['code' => ['0x041028', 'document']];
        }

        return $result;
    }

    /**
     * 设置创建人权限
     *
     * @param int $param
     *
     * @return boolean
     *
     * @author niuxiaoke
     *
     * @since 2017-08-02
     */
    public function creatorPurviewSet($creatorPur)
    {
        if (set_system_param('document_creator_purview', $creatorPur)) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

	/**
     * 设置创建人权限
     *
     * @return boolean
     *
     * @author niuxiaoke
     *
     * @since 2017-08-02
     */
    public function getCreatorPurview()
    {
        return get_system_param('document_creator_purview');
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
    	return app($this->documentFolderRepository)->getParentId($folderId);
    }

    /**
     * 获取子文件夹id
     *
     * @return array
     *
     * @author niuxiaoke
     *
     * @since 2017-09-04
     */
    public function getChildrenId($folderId)
    {
    	return app($this->documentFolderRepository)->getChildrenId($folderId);
    }

    public function hasDownloadPermissions($attachmentId, $documentId, $params, $own)
    {
        if(isset($params['operate']) && ($params['operate'] == "download")){
            return $this->hasDownPurview($documentId, $own);
        }else{
            return $this->hasDocumentShowPurview($documentId, $own);
    	}
    }

    public function hasRevertAttachmentDownload()
    {
    	return $this->hasDownPurview($documentId, $own);
    }

     // 永中在线阅读转化
    public function transOfficeToHtml($param, $own)
    {
    	// 获取tomcat地址
    	$tomcatAddr    = get_system_param('tomcat_addr');
    	$tomcatAddrOut = get_system_param('tomcat_addr_out');
    	$port          = get_system_param('apache_listen_port');
	    $deploy = get_system_param('deploy_service', 0);
//	    $oaAddress = $deploy == 1 ? get_system_param('oa_addr_out') : 'http://127.0.0.1:'.$port;
        if ($deploy == 1) {
            // 在不同服务器部署
            $oaIntranet = get_system_param('oa_addr_inner'); // oa内网地址
            $oaExtranet = get_system_param('oa_addr_out'); // oa外网地址
            $oaIntranetIp = strpos($oaIntranet, '://') !== false ? substr($oaIntranet, strpos($oaIntranet, "://") + 3) : $oaIntranet;
            $oaExtranetIp = strpos($oaExtranet, '://') !== false ? substr($oaExtranet, strpos($oaExtranet, "://") + 3) : $oaExtranet;
            if ($_SERVER['HTTP_HOST'] == $oaIntranetIp) {
                $oaAddress = $oaIntranet;
                $tomcatAddrOut = $tomcatAddr;
            } elseif ($_SERVER['HTTP_HOST'] == $oaExtranetIp) {
                $oaAddress = $oaIntranet;
            } else {
                $oaAddress = $oaIntranet;
//                $oaAddress = 'http://127.0.0.1:'.$port; // 访问本地会出现问题
            }
        } else {
            $oaAddress = 'http://127.0.0.1:'.$port;
        }
    	if(empty($tomcatAddr)){
    		return ['code' => ['0x011026', 'upload']];
    	}
	    if (empty($oaAddress)) {
            return ['code' => ['0x011022', 'upload']];
        }
    	$addrFilter = strpos($tomcatAddr, "http://");
        if ($addrFilter !== 0) {
            $tomcatAddr = "http://" . $tomcatAddr;
        }
    	$url = trim($tomcatAddr, '/')."/dcs.web/onlinefile";
    	try{
            file_get_contents($url);
        } catch (\Exception $e) {
            return ['code' => ['0x011025', 'upload']];
        }
		$documentId	= $param['document_id'];
		$document   = DB::table('document_content')->where('document_id', $documentId)->first();
		if(empty($document)){
			return ['code' => ['0x000003', 'common']];
		}
        $attachment = app($this->attachmentService)->getOneAttachmentById($document->content);
		if(empty($attachment)){
			return ['code' => ['0x011017', 'upload']];
		}
        // ppt格式禁止复制和水印只能二选一
        $isPPT = false;
        if (isset($attachment['attachment_type']) &&
            in_array($attachment['attachment_type'], ['ppt', 'pptx', 'PPT', 'PPTX'])) {
            $isPPT = true;
        }

		$domain = OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST;
		$token = Request::input('api_token');
		if (empty($token)) {
            $token = Request::bearerToken();
        }
        if (empty($token)) {
            $token = Request::getPassword();
        }
        $uri = $oaAddress.'/eoffice10/server/public/api/attachment/index/'.$attachment['attachment_id'].'?api_token='.$token.'&encrypt=0';
		// 从中间表查询是否有转换记录
		$record    = DB::table('yozo_translate')->where('attachment_id', $attachment['attachment_id'])->first();
		$logCounts = DB::table('document_logs')->where('document_id', $document->document_id)->where('operate_type', 2)->count();
		$addr      = '';
		$flag      = false;

		/** @var AttachmentService $attachmentService */
		$attachmentService = app($this->attachmentService);

		if(!empty($record) && isset($record->operate_count)){
			if($logCounts > $record->operate_count){
				// 文档修改过，重新转换
                $addr = $attachmentService->doTran(
                    $url,
                    $uri,
                    $domain,
                    $attachment['attachment_id'],
                    $token,
                    $own,
                    $isPPT
                );
                if (isset($addr['code'])) {
                    return $addr;
                }
				DB::table('yozo_translate')->where('id', $record->id)->update(['attachment_addr'=>$addr, 'operate_count'=>$logCounts]);
			}else{
				// 未修改，获取上次转换的文件
				$addr = $record->attachment_addr;
                $file = trim($tomcatAddr, '/') . '/' . $addr;
                if (strpos($file, '?') !== false) {
                    $url = substr($file,0,strpos($file, '?'));
                }
            	if ($resource = @fopen($url, 'r')) {
                    fclose($resource);
                    $file = str_replace($tomcatAddr, $tomcatAddrOut, $file);
                    // 动态水印需替换 ?watermark_txt=水印内容
                    $documentWatermark = $attachmentService->getDocumentWatermark($own);
                    $watermarkConfig = $documentWatermark['watermarkConfig'];
                    if ($documentWatermark['isShowWatermark'] && get_system_param('tomcat_watermark', 0)) {
                        if (strpos($file,'watermark_txt')) {
                            // 存在动态水印则替换
                            /** @var PathDealService $service */
                            $service = app('App\EofficeApp\Attachment\Services\PathDealService');
                            $file = $service->replaceUrlParam($file,'watermark_txt',urlencode($watermarkConfig['wmContent']));
                            $file = $service->replaceUrlParam($file,'watermark_alpha',$watermarkConfig['wmTransparency']);
                            $file = $service->replaceUrlParam($file,'watermark_angle',$watermarkConfig['wmRotate']);
                        }
                    } else {
                        if (strpos($file,'?watermark_txt')) {
                            // 如果存在动态水印参数则清空
                            /** @var PathDealService $service */
                            $service = app('App\EofficeApp\Attachment\Services\PathDealService');
                            $file = $service->replaceUrlParam($file,'watermark_txt', '');
//                            $file = $service->deleteUrlParam($file,'watermark_txt');
//                            $file = $service->deleteUrlParam($file,'watermark_alpha');
//                            $file = $service->deleteUrlParam($file,'watermark_angle');
                        }
                    }
                    return $file;
	            }else{
	                $flag = true;
	                DB::table('yozo_translate')->where('id', $record->id)->delete();
                    $url = trim($tomcatAddr, '/')."/dcs.web/onlinefile";
	            }
			}
		}else{
			$flag = true;

		}
		if($flag){
			$addr = $attachmentService->doTran(
			    $url,
                $uri,
                $domain,
                $attachment['attachment_id'],
                $token,
                $own,
                $isPPT
            );
        	if (isset($addr['code'])) {
                return $addr;
            }
			DB::table('yozo_translate')->insert(['attachment_id'=>$attachment['attachment_id'], 'attachment_addr'=>$addr, 'operate_count'=>$logCounts]);
		}
        $str = trim($tomcatAddr, '/').'/'.$addr;
        return str_replace($tomcatAddr, $tomcatAddrOut, $str);
    }

    /**
     * @deprecated 因为和attachmentService中方法重复, 已废弃
     *
     * @param $url
     * @param $uri
     * @param $domain
     * @param $attachmentId
     * @param $token
     * @return array|bool|false|mixed|string
     */
    private function doTran(&$url, &$uri, &$domain, &$attachmentId ,&$token)
    {
		$result = $this->onlinefile($url, $uri);
		if(isset($result['code'])){
			return $result;
		}
		if(is_string($result)){
			$result = json_decode($result, true);
		}

		if(!isset($result['data'][0])){
			$uri = trim($domain, '/').'/eoffice10/server/public/api/attachment/index/'.$attachmentId.'?api_token='.$token.'&encrypt=0';
            $result = $this->onlinefile($url, $uri);
            if(isset($result['code'])){
                return $result;
            }
            if(is_string($result)){
                $result = json_decode($result, true);
            }
            if(!isset($result['data'][0])){
                $error = ['code' => ['0x011024', 'upload']];
                if (isset($result['message']) && !empty($result['message'])) {
                    $error['dynamic'] = $result['message'];
                }
                return $error;

            }
		}
		$year = date('Y');
		return strchr($result['data'][0],$year);
    }

    /**
     * @deprecated 因为和attachmentService中方法重复, 已废弃
     * @param $url
     * @param $uri
     * @return array|bool|false|string
     */
    public function onlinefile(&$url, &$uri)
    {
    	$curl = curl_init();
        $timeout = 5;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl,CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.82 Safari/537.36");
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        $postData = array('downloadUrl'=> $uri,'convertType'=> '1');
        $postData = http_build_query($postData);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl,CURLOPT_BINARYTRANSFER,true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        $file_contents = curl_exec($curl);
        curl_close($curl);
        return $file_contents;

    	$data = array('downloadUrl'=> $uri,'convertType'=> '1');
		$data = http_build_query($data);
		$params = [
			'http' => [
				'method'  => 'POST',
				'header'  => "Content-type: application/x-www-form-urlencoded",
				'content' => $data
			]
		];
		// print_r($params);
		$ctx = stream_context_create($params);
		try{
            $fp  = file_get_contents($url, false, $ctx);
        } catch (\Exception $e) {
            return ['code' => ['0x011025', 'upload']];
        }

		if (!$fp) {
			return false;
		}
		return $fp;
    }
    // 永中插件上传文档
  //   public function send_file($url, $post = '', $file = '') {
		// $eol 		   = "\r\n";
		// $mime_boundary = md5(time());
		// $data 		   = '';
		// $confirmation  = '';

		// date_default_timezone_set("Asia/Beijing");
		// $time = date("Y-m-d H:i:s");

		// $post ["filename"] = $file['filename'];
		// foreach ( $post as $key => $value ) {
		// 	$data .= '--' . $mime_boundary . $eol;
		// 	$data .= 'Content-Disposition: form-data; ';
		// 	$data .= "name=" . $key . $eol . $eol;
		// 	$data .= $value . $eol;
		// }

		// $data .= '--' . $mime_boundary . $eol;
		// $data .= 'Content-Disposition: form-data; name=' . $file['name'] . '; filename=' . $file['filename'] . $eol;
		// $data .= 'Content-Type: text/plain' . $eol;
		// $data .= 'Content-Transfer-Encoding: binary' . $eol . $eol;
		// $data .= $file['filedata'] . $eol;
		// $data .= "--" . $mime_boundary . "--" . $eol . $eol;
		// $params = [
		// 	'http' => [
		// 		'method' => 'POST',
		// 		'header' => 'Content-Type: multipart/form-data;boundary=' . $mime_boundary . $eol,
		// 		'content' => $data
		// 	]
		// ];

		// $ctx      = stream_context_create($params);
		// $response = file_get_contents($url, FILE_TEXT, $ctx);
		// return $response;
  //   }
 
    public function getDocumentReadCount($own) {
        $documentIds = $this->getViewDocumentId($own);
        $total = $this->listDocument(['response' => 'count'], $own);
        $logViewCounts = app($this->logCenterService)->getLogCount('document', $documentIds, 'document_content', ['userId' => $own['user_id'], 'operate' => ['view', 'edit']]);
        $read = 0;
        if(!empty($logViewCounts)){
            foreach ($logViewCounts as $count){
                if($count != 0){
                    $read++;
                }
            }
        }
        return [
            'total'  => $total,
            'unRead' => $total - $read >= 0 ? $total - $read : 0
        ];
    }
    public function getAllManageFolder($own) {
    	$createFolder = app($this->documentFolderRepository)->getCreateFolder($own);

    	$manageFolder = array_column(app($this->documentFolderPurviewRepository)->getAllManageFolderId($own), 'folder_id');

    	return array_values(array_unique(array_merge($createFolder, $manageFolder)));
    }

    public function getDocumentBaseSet() {
    	return [
    		'creator_purview' => (int) get_system_param('document_creator_purview'),
    		'share_download'  => (int) get_system_param('share_document_download'),
//    		'document_view_mode' => (int) get_system_param('document_view_mode'),
    		'show_child_document' => (int) get_system_param('show_child_document'),
    	];
    }
    public function documentBaseSet($data) {
        if (!isset($data['creator_purview']) || !isset($data['share_download'])
            || !isset($data['show_child_document'])
        ) {
    		return ['code' => ['0x000003', 'common']];
    	}

    	if(set_system_param('share_document_download', $data['share_download']) 
    		&& set_system_param('document_creator_purview', $data['creator_purview'])
    		&& set_system_param('show_child_document', $data['show_child_document'])
    	){
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 更新文档视图模式
     * @author yangxingqiang
     * @param $data
     * @param $own
     * @return array|bool
     */
    public function documentViewModeSet($data, $own) {
        if (!isset($data['document_view_mode'])) {
            return ['code' => ['0x000001', 'common']];
        }
        if (app($this->userSystemInfoRepository)->updateData(['document_view_mode'=>$data['document_view_mode']], ['user_id' => $own['user_id']])) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取文档视图模式
     * @author yangxingqiang
     * @param $own
     * @return array
     */
    public function getDocumentViewModeSet($own) {
        $userSystemInfo = app($this->userSystemInfoRepository)->getInfoByWhere(['user_id' => [$own['user_id']]]);
        return [
            'document_view_mode' => $userSystemInfo[0]['document_view_mode'] ?? 1
        ];
    }

    public function getDocumentRelationDairy($userId, $date) {
        $params = [
    		'search' => [
    			'creator'    => [$userId],
    			'log_time' => [$date.' 00:00:00', $date.' 23:59:59']
            ],
            'fields' => [
                'eo_log_document.relation_title',
                'eo_log_document.relation_id',
                'eo_log_document.log_time',
                'eo_log_document.log_operate',
                'document_content.subject',
            ],
        ];
        $logs = [];
        $logList = app($this->logRepository)->documentLogs('document', $params);
        // 新建，编辑，查看, 删除
        $operateType = ['view' => trans('document.read'), 'add' => trans('document.new'), 'edit' => trans('document.edit'), 'delete' => trans('document.delete')];
        if (!empty($logList)) {
        	foreach ($logList as $key => $value) {
                $logs[$key]['subject'] = $value->subject ?? '';
                $logs[$key]['document_id'] = $value->relation_id ?? '';
                $logs[$key]['created_at'] = $value->log_time ?? '';
                $logs[$key]['operate_type'] = $operateType[$value->log_operate] ?? '';
            }
        }
		if (!empty($logs)) {
			// 二维数组按照created_at排序
        	array_multisort(array_column($logs,'created_at'), SORT_DESC, $logs);
		}
        return ['list' => $logs, 'total' => count($logs)];
    }
    /**
     * 使用消息队列更新全站搜索数据
     *
     * @param   string|int  $id
     */
    public function updateGlobalSearchDataByQueue($ids)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchDocumentMessage($ids);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    public function topDocument($documentId, $params) {
        $topEndTime = isset($params['top_end_time']) ? $params['top_end_time'] : '';
        $topBeginTime = date('Y-m-d H:i:s');

        if ($documentId == 0) {
            return ['code' => ['0x001005', 'news']];
        }

        return app($this->documentContentRepository)->updateData([
            'top' => 1, 
            'top_end_time' => $topEndTime, 
            'top_begin_time' => $topBeginTime
        ], ['document_id' => $documentId]);
    }
    // 取消置顶
    public function cancelTopDocument($documentId) {
        if ($documentId == 0) {
            return ['code' => ['0x001005', 'news']];
        }

        return app($this->documentContentRepository)->updateData([
            'top' => 0, 
            'top_end_time' => '', 
            'top_begin_time' => ''
        ], ['document_id' => $documentId]);
    }
    // 置顶到期去除
    public function cancelOutTimeDocument() {
        return app($this->documentContentRepository)->cancelOutTimeDocument();
    }
    // 关注文档
    public function followDocument($documentId, $own) {
    	return app($this->documentFollowRepository)->insertData([
            'document_id' => $documentId,
            'user_id' => $own['user_id']
        ]);
    }
    // 取消关注
    public function cancelFollow($documentId, $own) {
    	return app($this->documentFollowRepository)->deleteByWhere([
            'document_id' => [$documentId],
            'user_id' => [$own['user_id']]
        ]);
    }
    // 文档查阅情况
    public function documentReadList($documentId, $own) {
        $documentInfo = app($this->documentContentRepository)->getDetail($documentId);
        if (empty($documentInfo)) {
            return [];
        }
        
        $viewUser = [];
        $userPurview = app($this->documentFolderPurviewRepository)->getPurviewInfo(['folder_id' => $documentInfo->folder_id], ['all_purview','user_manage','user_view','dept_manage','dept_view','role_manage','role_view']);
        if($userPurview->all_purview == 1){
            $viewUser = app($this->userRepository)->getAllUserIdString(['return_type' => 'array']);
        } else {
            // 分享的
            $shareDocument = app($this->documentShareRepository)->getDocumentShareMember($documentId);
            if (!empty($shareDocument)) {
                if ($shareDocument->share_all == 1) { 
                    $viewUser = app($this->userRepository)->getAllUserIdString(['return_type' => 'array']);
                } else {
                    // 有查看权限的用户
                    $viewUser = $this->getAllShowPurviewId($documentInfo->folder_id);

                    $shareUser = explode(',', $shareDocument->share_user);
                    if (!empty($shareDocument->share_dept)) {
                        $shareUserByDept = $this->getUserIdByDeptId($shareDocument->share_dept);
                        $shareUser = array_merge($shareUser, $shareUserByDept);
                    }
                    if (!empty($shareDocument->share_role)) {
                        $shareUserByRole = $this->getUserIdByRoleId($shareDocument->share_role);
                        $shareUser = array_merge($shareUser, $shareUserByRole);
                    }

                    $viewUser = array_unique(array_filter(array_merge($viewUser, $shareUser)));
                }
            } else {
                // 有查看权限的用户
                $viewUser = $this->getAllShowPurviewId($documentInfo->folder_id);
            }
        }
        if (empty($viewUser)) {
            return [];
        }

        $list = app($this->documentLogsRepository)->documentReadList($documentId, $viewUser);
        $list['subject'] = $documentInfo->subject;
        return $list;
    }
    //云盘文件删除 
    public function cloudDelete($params, $own) {
		$folders = $params['folders'] ?? '';
		$files = $params['files'] ?? '';

		$deleteFolder = true;
		$deleteFiles = true;
		if (!empty($folders)) {
			$deleteFolder = $this->batchDeleteFolder($folders, $own);
			if (isset($deleteFolder['code'])) {
				return $deleteFolder;
			}
		}
		if (!empty($files)) {
			$deleteFiles = $this->deleteDocument($files, $own);
			if (isset($deleteFiles['code'])) {
				return $deleteFiles;
			}
		}
		return $deleteFolder && $deleteFiles;
	}

    /**
     * 云盘文件检查是否可以删除
     * @author yangxingqiang
     * @param $params
     * @param $own
     * @return array|bool|int
     */
    public function cloudCheckDelete($params, $own) {
        $folders = $params['folders'] ?? '';
        $files = $params['files'] ?? '';

        $deleteFolder = true;
        $deleteFiles = true;
        if (!empty($folders)) {
            $deleteFolder = $this->batchCheckFolder($folders, $own);
            if (isset($deleteFolder['code'])) {
                return $deleteFolder;
            }
        }
        if (!empty($files)) {
            $deleteFiles = $this->deleteCheckDocument($files, $own);
            if (isset($deleteFiles['code'])) {
                return $deleteFiles;
            }
        }
        return $deleteFolder && $deleteFiles;
    }

	public function cloudMove($data, $own) {
		$folders = $data['folders'] ?? '';
		$files = $data['files'] ?? '';

		if (!isset($data['parent_id'])) {
			return ['code' => ['0x041030', 'document']];
		}
		$parentId = $data['parent_id'];
		if ($parentId !== 0 && $parentId !== '0' && empty($parentId)) {
			return ['code' => ['0x041030', 'document']];
		}

		$moveFolder = true;
		$moveFiles = true;
		if (!empty($folders)) {
            $foldersArray = array_filter(explode(',', $folders));
			if (in_array($parentId, $foldersArray)) {
				return ['code' => ['0x041031', 'document']];
			}
			$childrens = $this->getChildrenId($parentId);
			if (!empty($childrens) && in_array($parentId, $childrens)) {
				return ['code' => ['0x041032', 'document']];
			}
			$moveFolder = $this->batchMoveFolder($folders, $parentId, $own);
			if (isset($moveFolder['code'])) {
				return $moveFolder;
			}
		}
		if (!empty($files)) {
			$moveFiles = $this->migrateDocument($files, $parentId, $own);
			if (isset($moveFiles['code'])) {
				return $moveFiles;
			}
		}

		return $moveFolder && $moveFiles;
	}
	// 获取用户某文件夹的权限
	public function getFolderPurviewByUser($folderId, $own) {
		return [
			'add' => $this->hasCreatePurview($folderId, $own),
			'manage' => $this->hasManagerPurview($folderId, $own)
		];
	}
	// 获取文档列表初始化参数
	public function getDocumentListInit($folderId, $own) {
        $baseSet = $this->getDocumentBaseSet();
        $purview = $this->getFolderPurviewByUser($folderId, $own);
        $viewMode = DB::table('user_system_info')->select('document_view_mode')->where('user_id', $own['user_id'])->first();
        $baseSet['document_view_mode'] = $viewMode->document_view_mode ?? 1;
        return array_merge($baseSet, $purview);
    }
    // 获取默认文件夹
    public function getDefaultFolder() {
        return app($this->documentFolderRepository)->getOneFieldInfo(['is_default' => 1]);
    }
    // 导入样式模板
    public function importDocumentStyleTemplate($data) {
        $from = isset($data['from']) ? $data['from'] : '';
        if ($from == 'online') {
            if (isset($data['content']) && !empty($data['content'])) {
                $name = isset($data['content']['name']) && !empty($data['content']['name']) ? $data['content']['name'] : trans('portal.unknown');
                $fileContent = isset($data['content']['file_content']) ? $data['content']['file_content'] : '';
                return $this->handleImport($fileContent, $name);
            }
        } else {
            if (isset($data['attachment_id']) && !empty($data['attachment_id'])) {
                $attachmentFile = app($this->attachmentService)->getOneAttachmentById($data['attachment_id']);
                $name = isset($attachmentFile['attachment_name']) && !empty($attachmentFile['attachment_name']) ? trim($attachmentFile['attachment_name'],'.style') : trans('portal.unknown');
                $fileContent = '';
                if (isset($attachmentFile['temp_src_file'])) {
                    $fileContent = convert_to_utf8(file_get_contents($attachmentFile['temp_src_file']));
                }
                return $this->handleImport($fileContent, $name);
            }
        }
        return ['code' => ['0x000003', 'common']];
    }
    private function handleImport($fileContent, $name) {
        list($modeTitle, $modeContent, $version) = $this->parseContent($fileContent);
        if (!$version) {
            return [ 'code' => ['0x041034', 'document'], 'dynamic' => ['【'.$name.'】'.trans('document.0x041034')] ];
        }
        return $this->addMode(['mode_title' => $modeTitle, 'mode_content' => $modeContent]);
    }
    // 解析导入文件
    private function parseContent($fileContent) {
        $title = '';
        $content = '';
        $version = '';
        if (!empty($fileContent)) {
            if (!is_array($fileContent)) {
                $fileContent = json_decode($fileContent, true);
            }
            $title = $fileContent['name'] ?? '';
            $content = $fileContent['content'] ?? '';
            $version = $fileContent['version'] ?? '';
        }
        /**
         * 当解析到版本时：
            * 素材版本大于当前e-office版本时，不允许导入，提示：素材版本不支持当前e-office版本，请升级当前e-office系统！
            * 素材版本小于或等于当前e-office版本时，允许正常导入。
         * 当未解析到版本时：
            * 直接导入
         */
        $version = empty($version) ? true : ($version > version() ? false : true);
        
        return [$title, $content, $version];
    }
    // 导出样式模板
    public function exportDocumentStyleTemplate($modeId) {
        $template = app($this->documentModeRepository)->getDetail($modeId);
        return json_encode([
            'name' => $template->mode_title,
            'content' => $template->mode_content,
            'version' => version(),
            'cover' => '',
            'description' => ''
        ]);
    }
    // 获取我的文档筛选字段
    public function getMyDocumentFields($own) {
        $myCreate = $this->listDocument(['search' => ['creator' => [$own['user_id']]], 'response' => 'count'], $own);
        $myFollowed = $this->listDocument(['follow' => 1, 'response' => 'count'], $own);
        $myShared = $this->listDocument(['my_shared' => 1, 'response' => 'count'], $own);
        $otherShared = $this->listDocument(['other_shared' => 1, 'response' => 'count'], $own);
        $myDraft = $this->listDocument(['search' => ['creator' => [$own['user_id']], 'is_draft' => [1]], 'response' => 'count'], $own);
        return [
            'all' => $myCreate,
            'followDocument' => $myFollowed,
            'myShared' => $myShared,
            'otherShared' => $otherShared,
            'draft' => $myDraft,
        ];
    }

    // 获取我的文档筛选标签数字
    public function getMyDocumentFieldsCount($own) {
        $myCreate = $this->listDocument(['search' => ['creator' => [$own['user_id']]], 'response' => 'count'], $own);
        $myFollowed = $this->listDocument(['follow' => 1, 'response' => 'count'], $own);
        $myShared = $this->listDocument(['my_shared' => 1, 'response' => 'count'], $own);
        $otherShared = $this->listDocument(['other_shared' => 1, 'response' => 'count'], $own);
        $myDraft = $this->listDocument(['search' => ['creator' => [$own['user_id']], 'is_draft' => [1]], 'response' => 'count'], $own);
        $data['data'] = [
            ['fieldKey' => 'all', 'total' => $myCreate],
            ['fieldKey' => 'followDocument', 'total' => $myFollowed],
            ['fieldKey' => 'myShared', 'total' => $myShared],
            ['fieldKey' => 'otherShared', 'total' => $otherShared],
            ['fieldKey' => 'draft', 'total' => $myDraft]
        ];
        return json_encode($data);
    }

    // 取消到期共享
    public function cancelShareDocument() {
        return app($this->documentShareRepository)->cancelShareDocument();
    }

    public function handleLogParams($user , $content , $relation_id ='' , $relation_title='')
    {
        $data = [
            'creator' => $user,
            'content' => $content,
            'relation_table' => 'document_content',
            'relation_id' => $relation_id,
            'relation_title' => $relation_title
        ];
        return $data;
    }

    /**
     * 获取流程归档文档
     * @author yangxingqiang
     * @param $runId
     * @return mixed
     */
    public function getFlowRunDocument($runId)
    {
        return app($this->documentContentRepository)->getDocumentInfoByRunId($runId, []);
    }
}
