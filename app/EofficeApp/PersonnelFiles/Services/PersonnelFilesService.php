<?php

namespace App\EofficeApp\PersonnelFiles\Services;


use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use App\EofficeApp\FormModeling\Services\FormModelingService;
use App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesEntity;
use App\Exceptions\ErrorMessage;
use DB;
use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
/**
 * 人事档案service
 *
 * @author  朱从玺
 *
 * @since  2015-10-28 创建
 */
class PersonnelFilesService extends BaseService
{
    const TABLE_KEY = 'personnel_files';

	/**
	 * [$personnelFilesRepository personnel_files表资源库]
	 *
	 * @var [object]
	 */
	protected $personnelFilesRepository;

	/**
	 * [$personnelFilesSubRepository personnel_files_sub表资源库]
	 *
	 * @var [object]
	 */
	protected $personnelFilesSubRepository;

	/**
	 * [$comboboxFieldRepository system_combobox_field表资源库]
	 *
	 * @var [object]
	 */
	protected $comboboxFieldRepository;

	/**
	 * [$formModelingService 字段表service]
	 *
	 * @var [object]
	 */
	protected $formModelingService;

	/**
	 * [$attachmentService 附件service]
	 *
	 * @var [object]
	 */
	protected $attachmentService;

	/**
	 * [$departmentService 部门service]
	 *
	 * @var [object]
	 */
	protected $departmentService;

	/**
	 * [$systemSecurityService 系统管理-性能安全设置service]
	 *
	 * @var [object]
	 */
	protected $systemSecurityService;

	/**
	 * [$menuService 用户菜单service]
	 *
	 * @var [object]
	 */
	protected $menuService;
	protected $userRepository;
	protected $userInfoRepository;
	protected $userSystemInfoRepository;
	protected $departmentRepository;
	protected $userStatusRepository;
    private $personnelFilesPermission;

    public function __construct()
	{
		parent::__construct();

        $this->personnelFilesRepository 	= 'App\EofficeApp\PersonnelFiles\Repositories\PersonnelFilesRepository';
        $this->personnelFilesSubRepository 	= 'App\EofficeApp\PersonnelFiles\Repositories\PersonnelFilesSubRepository';
        $this->comboboxFieldRepository 		= 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxFieldRepository';
        $this->attachmentService 			= 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->departmentService 			= 'App\EofficeApp\System\Department\Services\DepartmentService';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
		// $this->deptService = 'App\EofficeApp\System\Department\Services\DepartmentService';
		$this->roleService = 'App\EofficeApp\Role\Services\RoleService';
        $this->systemSecurityService 		= 'App\EofficeApp\System\Security\Services\SystemSecurityService';
        $this->menuService 					= 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->userRepository 		        = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userInfoRepository 			= 'App\EofficeApp\User\Repositories\UserInfoRepository';
        $this->userSystemInfoRepository 	= 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->departmentRepository 		= 'App\EofficeApp\System\Department\Repositories\DepartmentRepository';
		$this->userStatusRepository 		= 'App\EofficeApp\User\Repositories\UserStatusRepository';
		$this->formModelingService          = FormModelingService::class;
		$this->personnelFilesPermission     = PersonnelFilesPermission::class;
		$this->vacationService = 'App\EofficeApp\Vacation\Services\VacationService';
		$this->systemComboboxService = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
	}

    /**
     * [getPersonnelFilesList 获取人事档案列表]
     *
     * @param $params
     * @param $own
     * @param bool $manage
     * @return array [array]                        [查询结果]
     *
     */
	public function getPersonnelFilesList($params, $own, $manage = false)
	{
		$params = $this->parseParams($params);

        /** @var PersonnelFilesPermission $permission */
        $permission = app($this->personnelFilesPermission);

        $deptIds = $manage ? $permission->getManagePermittedDepts($own)
            : $permission->getQueryPermittedDepts($own);

        PermissionHandler::mergeDeptPermitParams($deptIds, $params);

        /** @var FormModelingService $formModeling */
        $formModeling = app($this->formModelingService);

        return $formModeling->getCustomDataLists($params, 'personnel_files', $own);
	}

	/**
	 * [getPersonnelFile 获取某条人事档案]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]            $fileId [档案ID]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [array]                   [查询结果]
	 */
	public function getPersonnelFile($fileId)
	{
		$systemFieldsData = app($this->personnelFilesRepository)->getPersonnelFilesDetail($fileId);

		if(!$systemFieldsData) {
			return '';
		}

		$customFieldsData = app($this->personnelFilesSubRepository)->getOneCustom($fileId);

		$returnArray = '';
		if(!$customFieldsData) {
			$returnArray = $systemFieldsData->toArray();
		}else {
			$returnArray = array_merge($systemFieldsData->toArray(), $customFieldsData->toArray());
		}

		foreach ($returnArray as $key => $value) {
			if($value === '0000-00-00') {
				$returnArray[$key] = '';
			}
		}

		//获取附件ID
		$attachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'personnel_files', 'entity_id'=>$fileId]);
		$returnArray['attachments'] = $attachments;

		$renewFields = $this->getNeedRenewFields($returnArray);

		$returnArray = $this->renewFields($returnArray, $renewFields);

		return $returnArray;
	}

	/**
	 * [getNeedRenewFields 获取某条数据中需要更新的字段数组]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]             $data [档案数据]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [array]                   [要更新的字段]
	 */
	public function getNeedRenewFields($data)
	{
		//需要更新的字段
		$needRenew = array('sex', 'status', 'marry', 'education', 'politics');

		$fields = array();
		foreach ($data as $key => $value) {
			if(in_array($key, $needRenew)) {
				$fields[] = $key;
			}
		}

		return $fields;
	}

	/**
	 * [renewFields 更新人事档案中数字代表的字段]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]      $data   [要更新的某条数据]
	 * @param  [array]      $fields [需要更新的字段数组]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [array]              [更新后的数据]
	 */
	public function renewFields($data, $fields)
	{
		//性别
		if(in_array('sex', $fields)) {
			$data['sex_name'] = $data['sex']==1?'男':'女';
		}

		//在职状态
		if(in_array('status', $fields)) {
			$statusDetail = app($this->userStatusRepository)->getDetail($data['status']);
			if($statusDetail) {
				$statusDetail = $statusDetail->toArray();
			}
			$data['status_name'] = $statusDetail['status_name'];
		}

		//婚姻状态
		if(in_array('marry', $fields)) {
			$data['marry_name'] = $this->getComboboxName(5, $data['marry']);
		}

		//学历
		if(in_array('education', $fields)) {
			$data['education_name'] = $this->getComboboxName(6, $data['education']);
		}

		//政治面貌
		if(in_array('politics', $fields)) {
			$data['politics_name'] = $this->getComboboxName(7, $data['politics']);
		}

		return $data;
	}

	/**
	 * [getComboboxName 获取下拉框字段名]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]             $comboboxId [下拉框ID]
	 * @param  [int]             $fieldValue [下拉框值]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [string]                      [下拉框字段名]
	 */
	public function getComboboxName($comboboxId, $fieldValue)
	{
		$where = array('combobox_id'=>array($comboboxId), 'field_value'=>array($fieldValue));

		$comboboxData = app($this->comboboxFieldRepository)->getComboboxFieldsName([], $where);

		if($comboboxData) {
			return $comboboxData->field_name;
		}else {
			return '';
		}
	}

    /**
     * @param $data
     * @param $own
     * @return array|bool|string|\string[][]
     * @throws ErrorMessage
     * @author 朱从玺
     */
	public function createPersonnelFile($data, $own)
	{
	    if($data['dept_id']) {
	        /** @var PersonnelFilesPermission $permission */
	        $permission = app($this->personnelFilesPermission);
	        $permission->checkDeptPermissionByOwn($data['dept_id'], $own, true);
        }
	    /** @var FormModelingService $formModeling */
        $formModeling = app($this->formModelingService);

        $lastId = $formModeling->addCustomData($data, self::TABLE_KEY);
        self::updateGlobalSearchDataByQueue($lastId);

        return $lastId;
	}


    /**
     * [modifyPersonnelFile 通过userId编辑人事档案]
     *
     * @param $data
     * @param $own
     * @return \string[][] [bool]                                [编辑结果]
     * @throws ErrorMessage
     *
     */
	public function modifyPersonnelFileByUserId($data, $own)
	{
		if(isset($data['user_id'])) {
			$personnelFileId = app($this->personnelFilesRepository)->getPersonnelFilesIdByUserId($data['user_id']);
			if($personnelFileId) {
				$personnelFileId = $personnelFileId[0]['id'];

				$this->modifyPersonnelFile($personnelFileId, $data, $own);

                self::updateGlobalSearchDataByQueue($personnelFileId);

			}else{
				return array('code'=>array('0x000003', 'common'));
			}
		}else{
			return array('code'=>array('0x000003', 'common'));
		}
	}

    /**
     * [modifyPersonnelFile 编辑人事档案]
     *
     * @param $personnelFileId
     * @param $data
     * @param $own
     * @return array|bool|\string[][]
     * @throws ErrorMessage
     * @author 朱从玺
     * @since  2015-10-28 创建
     */
	public function modifyPersonnelFile($personnelFileId, $data, $own)
	{
		$isEditVacation = false;
		if (isset($data['user_id']) && !empty($data['user_id'])) {
			$detail = app($this->formModelingService)->getCustomDataDetail('personnel_files', $personnelFileId);
			if (isset($detail['code'])) {
				return $detail;
			}
			// 修改人事档案入职时间等信息时，需要更新假期信息
			$profiles = [
				'old' => [
			        'join_date' => $detail['join_date'] ?? '',
			        'work_date' => $detail['work_date'] ?? '',
			        'birthday' => $detail['birthday'] ?? '',
			    ],
			    'new' => [
                    'join_date' => isset($data['join_date']) ? $data['join_date'] : (isset($detail['join_date']) ? $detail['join_date'] : ''),
                    'work_date' => isset($data['work_date']) ? $data['work_date'] : (isset($detail['work_date']) ? $detail['work_date'] : ''),
			        'birthday' => $data['birthday'] ?? '',
			    ]
			];
			if ($profiles['old']['join_date'] != $profiles['new']['join_date']
				|| $profiles['old']['work_date'] != $profiles['new']['work_date']
				|| $profiles['old']['birthday'] != $profiles['new']['birthday']) {
				$isEditVacation = true;
			}
		}

	    $oldData = PersonnelFilesEntity::select('id', 'dept_id')->find($personnelFileId);
	    if(!$oldData){
	        return ['code' => ['0x022011', 'personnelFiles']];
        }

        /** @var PersonnelFilesPermission $permission */
        $permission = app($this->personnelFilesPermission);
        $permission->checkDeptPermissionByOwn($oldData['dept_id'], $own, true);

        if(isset($data['dept_id']) && $data['dept_id']) {
            $permission->checkDeptPermissionByOwn($data['dept_id'], $own, true);
        }

        /** @var FormModelingService $formModeling */
        $formModeling = app($this->formModelingService);

        $result = $formModeling->editCustomData($data, self::TABLE_KEY, $personnelFileId);
		if (isset($result['code'])) {
			return $result;
		}

		if ($isEditVacation) {
			return app($this->vacationService)->onProfileChange($data['user_id'], $profiles);
		}

		return true;
	}

	/**
	 * [verifyPersonnelData 数据验证]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]              $data [传入的数据]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [bool] 	                  [验证结果]
	 */
	public function verifyPersonnelData($data)
	{
		$verifyArray = array();

		//劳务合同结束时间必须大于开始时间
		if(isset($data['labor_start_time']) && $data['labor_start_time'] && isset($data['labor_end_time']) && $data['labor_end_time']) {
			if(strtotime($data['labor_start_time']) >= strtotime($data['labor_end_time'])) {
				$verifyArray['code'] = array(array('0x022001'), 'personnelFiles');
			}
		}

		return $verifyArray;
	}

    /**
     * [deletePersonnelFile 删除一条数据]
     *
     * @param  [int]                  $personnelFileId [人事档案ID]
     * @param  [string]               $userId           [人事档案ID]
     * @param  [string]               $loginIp           [人事档案ID]
     *
     * @return [bool]                               [删除结果]
     * @throws ErrorMessage
     * @author 朱从玺
     *
     * @since  2015-10-28 创建
     *
     */
	public function deletePersonnelFile($personnelFileId, $own, $loginIp='')
	{
		$ids = explode(',', $personnelFileId);
		if(!empty($ids)){
			foreach ($ids as $id) {
				$data = PersonnelFilesEntity::select('id', 'dept_id')->find($id);
				if(!$data){
					return ['code' => ['0x022011', 'personnelFiles']];
				}

				/** @var PersonnelFilesPermission $permission */
				$permission = app($this->personnelFilesPermission);
				$permission->checkDeptPermissionByOwn($data['dept_id'], $own, true);

				/** @var FormModelingService $formModeling */
				$formModeling = app($this->formModelingService);
				$formModeling->deleteCustomData(self::TABLE_KEY, $id);

			}
		}
		return true;


//		if($result) {
//			//添加日志
//			$data = [
//			    'log_content'         => '删除一条人事档案',
//			    'log_type'            => 2,
//			    'log_creator'         => $own['user_id'],
//			    'log_ip'              => $loginIp,
//			     'log_relation_table' => 'personnel_files',
//			     'log_relation_id'    => $personnelFileId,
//			];
//
//			add_system_log($data);
//
//			//删除自定义字段数据
//			app($this->personnelFilesSubRepository)->customDelete($personnelFileId);
//
//			//删除相关附件
//			$deleteWhere = ['entity_table' => 'personnel_files', 'entity_id' => $personnelFileId];
//			app($this->attachmentService)->deleteAttachmentByEntityId($deleteWhere);
//
//			return true;
//		}else {
//			return array('code'=>array('0x000003', 'common'));
//		}
	}

	/**
	 * [exportPersonnelFiles 导出人事档案]
	 *
	 * @author 朱从玺
	 *
	 * @param  [type]               $params [导出条件]
	 *
	 * @return [type]                       [导出数据]
	 */
	public function exportPersonnelFiles($builder, $params)
	{
		$own = $params['user_info'];
		$init = ['order_by' => ['id' => 'asc']];
		$param = array_merge($init, $params);

		/** @var PersonnelFilesPermission $permission */
		$permission = app($this->personnelFilesPermission);
		$deptIds = $permission->getManagePermittedDepts($own);

		PermissionHandler::mergeDeptPermitParams($deptIds, $param);

        return app($this->formModelingService)->export($builder, $param);
	}

	/**
	 * 获取系统下拉框对应的列表
	 * @param  string $comboboxValue josn数据中对应的下拉框种类
	 * @return [array]               数据源下拉框列表
	 */
	public function getComboboxId($comboboxValue,$bool = false)
	{
		// 根据名称查询combobox 的id
		$comboboxNum = DB::table('system_combobox')->select('combobox_id')->where('combobox_identify',$comboboxValue)->first();
		if ($comboboxNum) {
			$comboboxId = $comboboxNum->combobox_id;
		}
		$comboboxList = $this->getCombobox($comboboxId,$bool);
		return $comboboxList;
	}

	/**
	 * [getImportPersonnelFilesFields 获取导入人事档案字段]
	 *
	 * @author longmiao
	 *
	 * @return [type]                        [导入字段]
	 */
	public function getImportPersonnelFilesFields($param)
	{
        return app($this->formModelingService)->getImportFields('personnel_files',$param, trans('personnelFiles.personnel_file_import_template'));
	}

	/**
	 * [getComboboxName 获取下拉框字段名]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]             $comboboxId [下拉框ID]
	 * @param  [int]             $fieldValue [下拉框值]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [string]                      [下拉框字段名]
	 */
	public function getCombobox($comboboxId,$bool = false)
	{
		$where = ['search'=>['combobox_id'=>[$comboboxId]]];

		$comboboxData = app($this->comboboxFieldRepository)->getFieldsList($where);
		if ($bool) {
			return $comboboxData;
		}
		$list = '';
		if($comboboxData) {

			foreach ($comboboxData as $key => $value) {
				$list .= '  '.$value['field_value']."|".$value['field_name'];
			}
			return $list;
		}else {
			return '';
		}
	}
	/**
	 * [importPersonnelFiles 导入人事档案]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]               $data  [导入数据]
	 * @param  [array]               $param [用户信息]
	 *
	 * @return [bool]                       [导入结果]
	 */
	public function importPersonnelFiles($data, $params)
	{
		app($this->formModelingService)->importCustomData('personnel_files', $data, $params);
        return ['data' => $data];
	}

	/**
	 * [importPersonnelFilesFilter 筛选导入数据,添加错误信息]
	 *
	 * @method 朱从玺
	 *
	 * @param  [array]                     $data [导入数据]
	 *
	 * @return [array]                           [添加错误信息后的导入数据]
	 */
	public function importPersonnelFilesFilter($data, $param = [])
	{
	    $tempUser = [];
	    $type = $param['type'];
	    $primaryKey = $param['primaryKey'];

	    /** @var PersonnelFilesPermission $permission */
	    $permission = app($this->personnelFilesPermission);
        $permittedDepts = $permission->getManagePermittedDepts($param['user_info']);
        $service = app($this->formModelingService);
		foreach ($data as $key => &$value) {
			$result = $service->importDataFilter('personnel_files', $value, $param);

            if (!empty($result)) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail($result);
                continue;
            }

            $data[$key]['importResult'] = importDataSuccess();
            //如果关联了用户，验证用户id不存在用户档案则同步用户信息
            $userId = Arr::get($value, 'user_id', '');
            if ($userId) {
                // 模板自身关联用户重复
                if (in_array($userId, $tempUser)) {
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('personnelFiles.0x022006'));
                    continue;
                }
                // 关联用户不存在
                if (!app($this->userSystemInfoRepository)->getDetail($userId)) {
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('personnelFiles.0x022007'));
                    continue;
                }
                $tempUser[] = $userId;
                // 判断数据库已存在关联用户
                if ($type == 1){
                    // 新增数据
                    if ($this->existsPersonnelFile($userId)) {
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail(trans('personnelFiles.0x022006'));
                        continue;
                    }
                }else{
                    // type 2/4 更新数据
                    $primaryValue = (string) $value[$primaryKey];
                    $record = app($this->personnelFilesRepository)->entity
                        ->where($primaryKey, $primaryValue)
                        ->first();
                    if($record){
                        if($record->user_id != $value['user_id'] && $this->existsPersonnelFile($userId)){
                            $data[$key]['importResult'] = importDataFail();
                            $data[$key]['importReason'] = importDataFail(trans('personnelFiles.0x022006'));
                            continue;
                        }
                    }else{
						if($type == 2){
							$data[$key]['importResult'] = importDataFail();
                            $data[$key]['importReason'] = importDataFail(trans('personnelFiles.data_not_exist_cannot_update'));
                            continue;
						}
                        if($type == 4){
                            if ($this->existsPersonnelFile($userId)) {
                                $data[$key]['importResult'] = importDataFail();
                                $data[$key]['importReason'] = importDataFail(trans('personnelFiles.0x022006'));
                                continue;
                            }
                        }
                    }
                }
			}else{
				if($type == '2'){
					$primaryValue = (string) $value[$primaryKey];
					$record = app($this->personnelFilesRepository)->entity
						->where($primaryKey, $primaryValue)
						->first();
						if(empty($record)){
							$data[$key]['importResult'] = importDataFail();
							$data[$key]['importReason'] = importDataFail(trans('personnelFiles.data_not_exist_cannot_update'));
							continue;
						}

				}
			}

            $this->checkImportDept($param, $value, $permittedDepts);

			$data[$key]['created_at'] = date('Y-m-d H:i:s');
		    $data[$key]['updated_at'] = date('Y-m-d H:i:s');
        }
		return $data;
	}

	private function checkImportDept($param,  &$value, $permittedDepts)
    {
        if ($permittedDepts == 'all') {
            return;
        }
        if (!in_array($value['dept_id'], $permittedDepts)){
            $value['importResult'] = importDataFail();
            $value['importReason'] = importDataFail(trans('personnelFiles.0x022010'));
        }
		$primaryKey = $param['primaryKey'];
		if(!$primaryKey){
			return;
		}
        $primaryValue = (string) isset($value[$primaryKey])?$value[$primaryKey]:'';
        $oldData = app($this->personnelFilesRepository)->entity
            ->where($primaryKey, $primaryValue)
            ->first();
        switch ($param['type']){
            case 2:
                if(!$oldData){
                    $value['importResult'] = importDataFail();
                    $value['importReason'] = importDataFail(trans('personnelFiles.0x022011'));
                }
                if (!in_array($oldData['dept_id'], $permittedDepts)){
                    $value['importResult'] = importDataFail();
                    $value['importReason'] = importDataFail(trans('personnelFiles.0x022010'));
                }
                break;
            case 4:
                if($oldData){
                    if (!in_array($oldData['dept_id'], $permittedDepts)){
                        $value['importResult'] = importDataFail();
                        $value['importReason'] = importDataFail(trans('personnelFiles.0x022010'));
                    }
                }
        }
    }

	/**
	 * [laborRemind 劳务合同到期提醒]
	 *
	 * @author 朱从玺
	 *
	 * @return [array]      [提醒数据]
	 */
	public function laborRemind()
	{
		$advanceDays = app($this->systemSecurityService)->getSecurityOption('contract')['labour_contract_period'];

		//获取需要提醒的人事档案
		$searchLaborEndTime = date('Y-m-d', time() + $advanceDays * 24 * 3600);
		$params = [
			'search' => [
				'labor_end_time' => [$searchLaborEndTime]
			]
		];
		$params['include_leave'] = false;
		$remindFiles = app($this->personnelFilesRepository)->getPersonnelFilesList($params);

		//获取发送对象,所有有人事档案管理菜单的用户
		// $toUser = app($this->menuService)->getMenuRoleUserbyMenuId(417);

		$message = [];
        foreach ($remindFiles as $files) {
			$toUser = app($this->personnelFilesPermission)->getManger($files['dept_id']);
			$invalidUserId = app($this->userSystemInfoRepository)->getInvalidUserId();
			$toUser = array_unique(array_diff($toUser, $invalidUserId));
            $message[] = [
                'remindMark'     => 'personnel_files-end',
                'toUser'         => $toUser,
                'contentParam'   => [
                    'filesName' => $files['user_name'],
                ],
                'stateParams'    => ['filesId' => $files['id']],
            ];
        }

		return $message;
	}

	// 系统数据源获取人事档案用户性别
	public function getPersonnelFileSex($id)
	{
		if(!isset($id)) {
			return '';
		}
		if (strpos($id, ',')) {
			return '';
		};
		$sex = app($this->personnelFilesRepository)->getPersonnelFileSex($id);
		if (isset($sex) && $sex['sex'] == '0') {
			$sex['sex'] = trans('personnelFiles.woman');
		} elseif (isset($sex) && $sex['sex'] == '1') {
			$sex['sex'] = trans('personnelFiles.man');
		} else {
			$sex['sex'] = trans('personnelFiles.unknown');
		}
		return $sex;
	}

	// 流程表单里面获取系统数据源，所有人事档案信息
	public function getPersonnelFileAll($params)
	{
        $params = $this->parseParams($params);
		$params['fields'] = ['id','user_name'];
		$params['order_by'] = ['id' => 'asc'];
		$result = $this->response(app($this->personnelFilesRepository), 'getPersonnelFilesCount', 'getPersonnelFilesList', $params);
		return $result;
	}

	// 为了人事档案外发准备的方法
	public function createPersonnelFiles($data)
	{
		$result = array('code'=>array('0x000003', 'common'));
		// 先把$data里面的下拉框值解析成下拉框对应的值
		if (isset($data['data']) && isset($data['tableKey'])) {
			// $data['data'] = app($this->formModelingService)->parseOutsourceData($data['data'],$data['tableKey']);
			//如果数据为空，则unset这个字段，因为自定义字段需要判断必填非必填，（自定义字段定义的规则是isset判断）
			// if (isset($data['data']) && !empty($data['data'])) {
			// 	foreach ($data['data'] as $k => $v) {
			// 		if ($v === "") {
			// 			unset($data['data'][$k]);
			// 		}
			// 	}
			// }
			/** @var PersonnelFilesPermission $permission */
			$permission = app($this->personnelFilesPermission);
			$currentUserId = $data['current_user_id'] ?? '';
			$own = own() ?? [];
	        $userInfo = app($this->userRepository)->getUserAllData($currentUserId)->toArray();
	        if (!$own && $userInfo) {
	            $own = $userInfo;
	        }
			$deptIds = $permission->getManagePermittedDepts($own);
			if($deptIds != 'all'){
                if(isset($data['data']['dept_id']) && !in_array($data['data']['dept_id'], $deptIds)){
                    return ['code' => ['0x022010', 'personnelFiles']];
                }
            }
			$result = app($this->formModelingService)->addCustomData($data['data'],$data['tableKey']);
		}

		if(isset($result['code'])){
		    return $result;
		}
		if(!$result){
			return  ['code' => ['0x022012', 'common']];
		}

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'personnel_files',
                    'field_to' => 'id',
                    'id_to' => $result
                ]
            ]
        ];
	}

    /**
     * 外发更新人事档案
     * @param $data
     * @return array
     */
	public function flowOutUpdatePersonnelFiles($data)
    {
		if (isset($data['unique_id']) && $data['unique_id']) {
			try {
				$currentUserId = $data['current_user_id'] ?? [];
		        $own = (array)app($this->userService)->getLoginUserInfo($currentUserId);
				$res = $this->modifyPersonnelFile($data['unique_id'], $data['data'], $own);
				if(isset($res['code'])){
					return $res;
				}else{
					return [
						'status' => 1,
						'dataForLog' => [
							[
								'table_to' => 'personnel_files',
								'field_to' => 'id',
								'id_to' => $data['unique_id'],
							],

						],
					];
				}
			} catch (\Throwable $th) {
				return ['code' => ['0x022010', 'personnelFiles']];
			}
		}else{
			return ['code' => ['0x022014', 'personnelFiles']];
		}



    }

    /**
     * 外发删除人事档案
     * @param $data
     * @return array
     */
    public function flowOutDeletePersonnelFiles($data)
    {
		if (isset($data['unique_id']) && $data['unique_id']) {
			try {
				$currentUserId = $data['current_user_id'] ?? [];
		        // $own = own() ?? [];
		        $own = (array)app($this->userService)->getLoginUserInfo($currentUserId);
				$res = $this->deletePersonnelFile($data['unique_id'], $own);
				if(isset($res['code'])){
					return $res;
				}else{
					return [
						'status' => 1,
						'dataForLog' => [
							[
								'table_to' => 'personnel_files',
								'field_to' => 'id',
								'id_to' => $data['unique_id'],
							],

						],
					];
				}
			} catch (\Throwable $th) {
				return ['code' => ['0x022010', 'personnelFiles']];
			}
		}else{
			return ['code' => ['0x022014', 'personnelFiles']];
		}

    }

	/**
	 * 获取当前登录用户的人事档案id
	 * @param  string $userId 用户id
	 * @return string 人事档案id或者空字符串
	 */
	public function getOnePersonnelFile($userId)
	{
//		$userId = isset($userId['user_id']) ? $userId['user_id'] : '';
		$result = DB::table('personnel_files')->select('id')->where('user_id',$userId)->first();
		$personnelFileId = isset($result->id) ? $result->id : '';
		return $personnelFileId;
	}

    public function filterPersonnelFilesAdd($data)
    {
        //如果关联了用户，验证用户id不存在用户档案则同步用户信息
        $userId = Arr::get($data, 'user_id', '');
        if ($userId) {
            if ($this->existsPersonnelFile($userId)) {
                return ['code'=>array('0x022006', 'personnelFiles')];
            }

            $this->updateUserRelationTables($userId, $data);
        }
    }

	/**
	 * [filterPersonnelFilesEdit description]
	 * @return [type] [description]
	 */
	public function filterPersonnelFilesEdit($data)
	{
		if (isset($data['id'])) {
			$result = DB::table('personnel_files')->select('user_id')->where('id', $data['id'])->first();
			$userId = isset($result->user_id) && !empty($result->user_id) ? $result->user_id : '';

			//是否变更关联用户
			$newUserId = Arr::get($data, 'user_id', '');
            if ($userId != $newUserId) {
                if ($newUserId) {
                    if ($this->existsPersonnelFile($newUserId)) {
                        return ['code' => ['0x022006', 'personnelFiles']];
                    }
                    $userId = $newUserId;
                } else {
                    $userId = '';
                }
            }

            if(!empty($userId)) {
                $this->updateUserRelationTables($userId, $data);
            }
		}
	}

    /**
     * 验证是否存在档案
     * @param $userId
     * @return bool
     */
    private function existsPersonnelFile($userId)
    {
        $personnelFiles = app($this->personnelFilesRepository)->getPersonnelFilesIdByUserId($userId);

        return !empty($personnelFiles);
    }

    /**
     * 验证权限
     * 注：查看他人档案菜单权限优先验证，否则验证是否是own的档案
     * @param $dataId
     * @param $own
     * @return bool
     */
	public function personnelFilePower($dataId){
        // 拥有档案查询、档案管理权限均可查看他人资料
        $own = own();
        $menu = [416, 417];
        if(isset($own['menus']['menu']) && array_intersect($menu, $own['menus']['menu'])){
            return true;
        }

        $user_id['user_id'] = $own['user_id'] ?? 'admin';
        $PersonnelFileID = $this->getOnePersonnelFile($user_id);
	    // 我的档案
	    if($PersonnelFileID == $dataId){
	        return true;
        }

	    return false;
    }

    public function personnelFilesUserIdDirector($data)
    {
        $userIds = app($this->personnelFilesRepository)->allUserId();
        //编辑时返回所有用于回填
        $searchUserId = Arr::get($data, 'search.user_id.0', '');
        if ($searchUserId) {
            return "";
        }

        return ['user_id' => $userIds, 'isNotIn' => false];
    }

    /**
     * 更新用户相关的表数据
     * @param $userId
     * @param array $data
     */
    private function updateUserRelationTables($userId, array $data): void
    {
        $userSystemInfo = app($this->userSystemInfoRepository)->getDetail($userId);
        if($userSystemInfo) {
            //关联更新user表的数据
            $userData = [];
            $this->extractExistData($userData, $data, 'user_name');
            if (isset($data['status']) && $data['status'] == 2) {
                $userData['user_accounts'] = '';
            }
            if ($userData) {
                app($this->userRepository)->updateData($userData,['user_id' => [$userId]]);
            }

            //关联更新user_info表的数据
            $userInfoData = [];
            $this->extractExistData($userInfoData, $data, 'sex');
            $this->extractExistData($userInfoData, $data, 'status', 'user_status');
            $this->extractExistData($userInfoData, $data, 'dept_id');
            $this->extractExistData($userInfoData, $data, 'birthday');
            $this->extractExistData($userInfoData, $data, 'home_addr', 'home_address');
            $this->extractExistData($userInfoData, $data, 'home_tel', 'home_phone_number');
            $this->extractExistData($userInfoData, $data, 'email');
            $this->extractExistData($userInfoData, $data, 'resume', 'notes');
            if ($userInfoData) {
                app($this->userInfoRepository)->updateData($userInfoData, ['user_id' => [$userId]]);
            }

            //关联更新user_system_info的数据
            $userSystemInfoData = [];
            $userSystemInfoData['is_autohrms'] = 1;
            $this->extractExistData($userSystemInfoData, $data, 'status', 'user_status');
            $this->extractExistData($userSystemInfoData, $data, 'dept_id');
            if ($userSystemInfoData) {
                app($this->userSystemInfoRepository)->updateData($userSystemInfoData, ['user_id' => [$userId]]);
            }
        }
    }

    /**
     * 提取已存在的数据至目标数组
     * @param array $inData 目标数组
     * @param array $outData 被提取数据的数组
     * @param int|string $key 被提取的key
     * @param int|string|null $newKey 提取后的新key,默认与$key相等
     */
    private function extractExistData(
    	array &$inData,
    	array $outData,
    	$key,
        $newKey = null
    ){
    	$newKey = $newKey ?: $key;
    	if (isset($outData[$key])) {
    		$inData[$newKey] = $outData[$key];
    	}
    }

    /**
     * @param $primaryValue
     * @param string $primaryKey
     * @return bool
     * 判断导入的数据是否为新增的
     */
    public function isDataImportedNew($primaryValue, $primaryKey = 'no')
    {
        $record =  app($this->personnelFilesRepository)->entity
            ->where($primaryKey, $primaryValue)
            ->count();

        return $record < 1;
    }
    /**
     * 使用消息队列更新全站搜索数据
     *
     * @param   string|int    $id
     */
    public function updateGlobalSearchDataByQueue($ids)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchPersonnelFilesMessage($ids);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

	public function getSecurityOption()
	{
		return app($this->systemSecurityService)->getSecurityOption('contract');
	}

	public function modifySecurityOption($data)
	{
		return app($this->systemSecurityService)->modifySecurityOption('contract',$data);
	}
    /**
     * 获取组织架构树成员
     *
     * @param type $deptId
     * @param type $params
     * @param type $own
     *
     * @return array
     */
    public function getOrganizationPersonnelMembers($deptId, $params, $own)
    {
        $departments = app($this->departmentService)->children($deptId, $params, $own);
        $params = $this->parseParams($params);
        /**
        | ---------------------------------------------------
        | 将param里的search单独拿出来使用，没有附初始值为空数组
        | ---------------------------------------------------
        */
        $search = $params['search'] ?? [];
        $params['search'] = $search;
        /**
        | ---------------------------------------------------
        | 查询当前展开的父节点下的人员列表
        | ---------------------------------------------------
        */
        $users = [];
		if ($deptId != 0 && $deptId != 'no_dept') {
            if (isset($search['dept_id']) && !empty($search['dept_id'])) {
                if (in_array($deptId, $search['dept_id'][0])) {
                    $users = $this->getUserList($deptId, $own, $params);
                }
            } else {
                $users = $this->getUserList($deptId, $own, $params);
            }
        }
        // else if ($deptId == 'no_dept') {
        // 	// 查找部门id为0的人事档案
	       //  $param['search']['personnel_files.dept_id'] = [0, '='];
	       //  $noDeptUsers = app($this->personnelFilesRepository)->getPersonnelFilesTreeList($param);
	       //  array_unshift($users, $noDeptUsers);
        // }
        /**
        | ---------------------------------------------------
        | 处理组织架构部门，判断该部门下是否有用户或子部门。
        | ---------------------------------------------------
        */
        $depts = [];
        if (!$departments->isEmpty()) {
            $noCheckDeptIds = $deptsTemp = $deptGroups = [];

            foreach ($departments as $dept) {
                $temp = $this->handleOrganizationDepartment($dept, $params, $search, $own, function($dept, $params, $own) {
                    // if (!$this->handleHasPrivOrg($dept->dept_id, $own, $params)) {
                    //     $dept->prv_check = 1;
                    //     $dept->has_children = 0;
                    // }

                    return $dept;
                });
                if (!$temp->prv_check) {
                    $noCheckDeptIds[] = $temp->dept_id;
                }
                $deptsTemp[] = $temp;
            }

            unset($params['search']['dept_id']);
            if (!empty($noCheckDeptIds)) {
                $deptGroups = app($this->personnelFilesRepository)->getUserCountGroupByDeptId($own, $params, $noCheckDeptIds)
                        ->mapWithKeys(function ($item) {
                            return [$item->dept_id => $item->count];
                        });
            }
            if (empty($deptGroups)) {
                $depts = $deptsTemp;
            } else {
                $depts = array_map(function($dept) use($deptGroups) {
                    if (isset($deptGroups[$dept->dept_id]) && $deptGroups[$dept->dept_id] > 0) {
                        $dept->has_children = 1;
                    }
                    return $dept;
                }, $deptsTemp);
            }
        }
        return ['dept' => $depts, 'user' => $users];
    }
    /**
     * 处理组织架构部门
     *
     * @param type $dept
     * @param type $params
     * @param type $search
     * @param type $own
     * @param type $handle
     *
     * @return object
     */
    private function handleOrganizationDepartment($dept, $params, $search, $own, $handle)
    {
        if($dept->has_children == 1) {
            $dept->prv_check = 1;

            return $dept;
        }

        if (isset($search['dept_id']) && !empty($search['dept_id'])) {
            if (in_array($dept->dept_id, $search['dept_id'][0])) {
                return $handle($dept, $params, $own);
            }

            return $dept;
        }

        return $handle($dept, $params, $own);
    }
    /**
     * 处理带权限的组织架构
     *
     * @param type $deptId
     * @param array $own
     * @param array $params
     *
     * @return boolean
     */
    private function handleHasPrivOrg($deptId, array $own, array $params)
    {
        if (isset($params['manage']) && $params['manage'] == 1) {
            if ($own['user_id'] == 'admin') {
                if (isset($params['search']['dept_id'][0])) {
                    $searchDeptId = $params['search']['dept_id'][0];

                    $manageDeptIds = is_array($searchDeptId) ? $searchDeptId : explode(',', rtrim($searchDeptId, ','));
                    if (!empty($manageDeptIds) && !in_array($deptId, $manageDeptIds)) {
                        return false;
                    }
                }
            } else {
                $deptIds = $this->getScopeDeptIds($params, $own);
                if($deptIds == 'all'){
                    return true;
                }
                if (empty($deptIds) || !in_array($deptId, $deptIds)) {
                    return false;
                }
            }
        }

        return true;
    }
    /**
     * 获取用列表
     *
     * @param type $deptId
     * @param array $own
     * @param array $params
     *
     * @return array
     */
    public function getUserList($deptId, array $own, array $params)
    {
        // if (!$this->handleHasPrivOrg($deptId, $own, $params)) {
        //     return [];
        // }

        $params['search']['personnel_files.dept_id'] = [$deptId, '='];

        $users = app($this->personnelFilesRepository)->getPersonnelFilesTreeList($params);
        if (!empty($users)) {
            /**
            | ---------------------------------------------------
            | 获取用户角色信息
            | ---------------------------------------------------
            */
            $userIds = array_column($users, 'user_id');
            $roles = app('App\EofficeApp\Role\Repositories\UserRoleRepository')->getUserRole(['user_id' => [$userIds, 'in']], 1);
            $allRoleInfos = app('App\EofficeApp\Role\Repositories\RoleRepository')->getAllRoles(['fields' => ['role_id', 'role_name']]);
            $map = $allRoleInfos->mapWithKeys(function ($item) {
                return [$item->role_id => $item->role_name];
            });
        }
        return $users;
    }
    /**
     * 获取用管理权限的部门id
     *
     * @param type $search
     * @param type $own
     *
     * @return array
     */
    private function getPrivManageDeptId($search, $own)
    {
        $manageDeptIds = app($this->userService)->getUserCanManageDepartmentId($own);
        $deptIds = [];
        if (!empty($manageDeptIds)) {
            //查询参数与固定的管理范围和角色权限级别取交集
            if (isset($search['dept_id'][0]) && $manageDeptIds != 'all') {
                $searchDeptId = $search['dept_id'][0];
                if (is_array($searchDeptId)) {
                    $deptIds = array_intersect($searchDeptId, $manageDeptIds);
                } else {
                    $deptIds = $searchDeptId == 0 ? $manageDeptIds : array_intersect(explode(',', rtrim($searchDeptId, ',')), $manageDeptIds);
                }
            } else {
                return $manageDeptIds;
            }
        }

        return $deptIds;
    }
    /**
     * 获取有效数据的id
     * 把传入的$userId(人事id)转换成用户id
     * 目前没有路由调用此函数，只有 SalaryEntryService 调用了
     * 20201223-1、原来这里返回一个混合了人事id和用户id的数组；2、新需求要求这里返回人事id=>用户id的对应关系
     * @param  [arr] $fileIds [档案ID]
     * @return [arr] $result  [有效的数据]
     */
    public function checkPersonnelFiles($fileIds, $type='id', $returnType='')
    {
    	if (!$fileIds) {
    		return [];
    	}
    	$result = app($this->personnelFilesRepository)->getPersonnelFilesExpire($fileIds, $type);
    	$fileId = [];
    	if ($result) {
    		$fileId = array_map(function($file) use($result, $returnType) {
    			// 用一个新的字段 trans_id 来承载需要返回出去的数据
    			$file['trans_id'] = $file['id'];
                if (!in_array($file['user_status'], [0,2])) {
                	$file['trans_id'] = $file['user_id'];
                } else {
        	    	if($returnType == 'relate_array') {
        	    		$file['trans_id'] = '';
        	    	}
                }
                return $file;
            }, $result);
    	}
    	if($returnType == 'relate_array') {
    		return array_column($fileId, 'trans_id', 'id');
    	} else {
	    	return array_column($fileId, 'trans_id');
    	}
    }
    /**
     * 功能函数，调人事档案service，将人事档案id混合用户id的串，翻译成 人事档案id串
     * 传入混合id数组，传出一个人事档案id的数组
     * @param  [arr] $fileIds [档案ID]
     * @return [arr] $result  [有效的数据]
     */
    public function transUserPersonnelFileIds($fileIds)
    {
    	if (!$fileIds) {
    		return [];
    	}
    	$result = app($this->personnelFilesRepository)->getPersonnelFilesExpire($fileIds, 'user');
    	$relateArray = array_column($result,'id','user_id');
    	foreach ($fileIds as $key => $value) {
    		if(isset($relateArray[$value])) {
    			$fileIds[$key] = $relateArray[$value];
    		}
    	}
    	return $fileIds;
    }
    public function multiRemoveDept($userIds, $deptId) {
    	if (empty($userIds)) {
    		return ['code' => ['0x000003', 'common']];
    	}
    	return app($this->personnelFilesRepository)->multiRemoveDept($userIds, $deptId);
    }
}
