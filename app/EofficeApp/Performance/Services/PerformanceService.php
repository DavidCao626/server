<?php

namespace App\EofficeApp\Performance\Services;

use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Calendar\Services\CalendarService;
use App\EofficeApp\Performance\Enums\PerformancePeriods;
use App\EofficeApp\Performance\Enums\PerformancePlanType;
use App\EofficeApp\User\Repositories\UserRepository;
use Carbon\Carbon;
use Eoffice;
use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\EofficeApp\Performance\Helpers\CarbonExtend;
use Illuminate\Support\Arr;
/**
 * 绩效考核模块服务
 *
 * @author  朱从玺
 *
 * @since   2015-10-22
 *
 */
class PerformanceService extends BaseService
{
	/**
	 * [$userService 用户模块服务]
	 *
	 * @var [object]
	 */
	protected $userService;

	/**
	 * [$userRepository user表资源库]
	 *
	 * @var [object]
	 */
	protected $userRepository;

	/**
	 * [$userSuperiorRepository 用户上下级表资源库]
	 *
	 * @var [object]
	 */
	protected $userSuperiorRepository;

	/**
	 * [$departmentService 部门service]
	 *
	 * @var [object]
	 */
	protected $departmentService;

    /**
     * [$planRepository performance_plan表资源库]
     *
     * @var [object]
     */
	protected $planRepository;

	/**
	 * [$tempRepository performance_temp表资源库]
	 *
	 * @var [object]
	 */
	protected $tempRepository;

	/**
	 * [$performerRepository performance_performer表资源库]
	 *
	 * @var [object]
	 */
	protected $performerRepository;

	/**
	 * [$personalRepository performance_personnel表资源库]
	 *
	 * @var [object]
	 */
	protected $personalRepository;

	protected $userMenuService;

	/**
	 * [$tempUserRepository performance_temp_user表资源库]
	 *
	 * @var [object]
	 */
	protected $tempUserRepository;

	protected $attachmentService;

	protected $calendarService;

	public function __construct()
	{
		parent::__construct();

		$this->userService 				= 'App\EofficeApp\User\Services\UserService';
		$this->userRepository 			= 'App\EofficeApp\User\Repositories\UserRepository';
		$this->userSuperiorRepository 	= 'App\EofficeApp\Role\Repositories\UserSuperiorRepository';
		$this->departmentService 		= 'App\EofficeApp\System\Department\Services\DepartmentService';
		$this->planRepository 			= 'App\EofficeApp\Performance\Repositories\PerformancePlanRepository';
		$this->tempRepository 			= 'App\EofficeApp\Performance\Repositories\PerformanceTempRepository';
		$this->performerRepository 		= 'App\EofficeApp\Performance\Repositories\PerformancePerformerRepository';
		$this->personalRepository 		= 'App\EofficeApp\Performance\Repositories\PerformancePersonalRepository';
		$this->tempUserRepository 		= 'App\EofficeApp\Performance\Repositories\PerformanceTempUserRepository';
        $this->userMenuService          = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->attachmentService        = AttachmentService::class;
        $this->calendarService          = CalendarService::class;
	}

    /**
     * [getMyPerform 获取被考核人信息]
     *
     * @author 朱从玺
     *
     * @param  string       $search [查询条件]
     *
     * @since  2015-10-23 创建
     *
     * @return array                [获取的数据]
     */
    public function getMyPerform($userId, $userName, $params)
    {
    	$params = $this->parseParams($params);
    	// 下拉框用
        if(isset($params['search']) && isset($params['search']['user_id'])){
            if($params['search']['user_id'] == [$userId]){
                return [[
                    'user_id' => $userId,
                    'user_name' => $userName
                ]];
            }
        }
		$userApprovers = $this->getApprover($userId, 1);
        //若存在查询条件
        if(isset($params['search']) && $params['search'] != '') {
        	$userName = isset($params['search']['user_name'][0]) ? $params['search']['user_name'][0] : '';
        	$userId   = isset($params['search']['user_id'][0]) ? $params['search']['user_id'][0] : '';
        	$param = array(
        		'fields' => ['user_id', 'user_name'],
        		'limit'	 => 20,
        		'search' => array(
        			'user_id' => [$userId]
        		),
        	);

        	if (!empty($userName)) {
        		$param['search']['user_name'] = [$userName, 'like'];
        		$param['search']['user_name_zm'] = [$userName, 'like'];
        		$param['search']['user_name_py'] = [$userName, 'like'];
        		$param['search']['user_accounts'] = [$userName, 'like'];
        	}
        	$searchUser = app($this->userRepository)->getUserListByOr($param);
        	$result = [];
        	$userids = array_column($userApprovers, 'user_id');
        	if (!empty($searchUser)) {
	        	foreach ($searchUser as $key => $value) {
	        		if(in_array($value['user_id'], $userids)) {
                        if (!empty($value['user_has_one_system_info']) && isset($value['user_has_one_system_info']['user_status']) && $value['user_has_one_system_info']['user_status'] == 2) {
                            $searchUser[$key]['user_name'] .= "[" . trans('performance.quit') . "]";
                        }
                        $result[] = $searchUser[$key];
                    }
	        	}
        	}
        	return $result;
        }

        array_unshift($userApprovers, array('user_id' => $userId, 'user_name' => $userName));

        return $userApprovers;
    }

    /**
     * [getPerformData 获取某个用户某个考核方案的某个月/季/半年/年的考核数据]
     *
     * @author 朱从玺
     *
     * @param  int            $userId       [用户ID]
     * @param  array          $param        [查询条件]
     * @param  string         $loginUserId  [当前登录用户ID]
     *
     * @since  2015-10-23 创建
     *
     * @return array|string                        [查询结果]
     */
	public function getPerformData($userId, $param, $loginUserId, $flag = 2)
	{
		//判断是否有权限
		if($flag == 2){
			$power = $this->verifyPower($userId, $loginUserId);

			if($power !== true) {
				return $power;
			}
		}

		$performTime = $this->getPerformTime($param['check_year'], $param['circle'], $param['month']);

		if ($performTime === false) {
            return 'noPerform';
        }

		//如果考核开始时间小于当前时间,则去查找考核数据
		if(strtotime($performTime['startDate']) <= strtotime(date('Y-m-d'))) {
			$performMonth = $this->getPerformMonth($param['check_year'], $param['circle'], $param['month']);

			$where = array(
				'perform_user' => array($userId),
				'perform_month' => array($performMonth),
			);

			$personalData = app($this->personalRepository)->getPersonelData($where);

			//考核数据存在则返回
			if($personalData) {
                $personalData->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'performance', 'entity_id' => ["entity_id" => [$personalData->id]]]);
				return $personalData;
			}else {
				//若考核结束时间也小于当前时间,则说明是以前的考核,但没有打分
				if(strtotime($performTime['endDate']) < time()) {
					return 'noPerform';
				}

				return [
						'temp_id' => 0,
						'perform_points' => 0,
					];
			}
		}else {
			return 'noPerform';
		}
	}

	public function getLastMonthPerformData($userId, $params = [])
	{
        $year = $params['year'] ?? Carbon::now()->subMonthNoOverflow()->year;
        $month = $params['month'] ?? Carbon::now()->subMonthNoOverflow()->month;
        $param     = [
            'circle'     => 'months',
            'check_year' => $year,
            'month'      => $month,
        ];

        $result = $this->getPerformData($userId, $param, 'admin', 1);
        if ($result == 'noPerform' || empty($result) || !isset($result['perform_point'])) {
            return 0;
        } else {
            return $result['perform_point'];
        }
	}

    /**
     * [getMyTemp 查询某个用户某种方案下的当前模板数据]
     *
     * @author 朱从玺
     *
     * @param  [string]     $userId      [用户ID]
     * @param  [int]	 	$planId      [方案ID]
     * @param  [string]	 	$loginUserId [当前登录人ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]            [模板数据]
     */
	public function getMyTemp($userId, $planId, $loginUserId)
	{
		//判断是否有权限
		$power = $this->verifyPower($userId, $loginUserId);

		if($power !== true) {
			return $power;
		}

		$myTempId = $this->getMyTempId($userId, $planId);

		if($myTempId === false) {
			return '';
		}

		return $this->getTempInfo($myTempId);
	}

	/**
	 * [verifyPower 判断操作权限]
	 *
	 * @author 朱从玺
	 *
	 * @param  [string]     $userId      [用户ID]
	 * @param  [string]    	$loginUserId [当前登录用户ID]
	 *
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [bool]                    [判断结果]
	 */
	public function verifyPower($userId, $loginUserId)
	{
		$userApprover = $this->getApprover($loginUserId);
		$power = false;

		foreach ($userApprover as $key => $value) {
			if($userId == $value['user_id']) {
				$power = true;
			}
		}

		if($userId == $loginUserId) {
			$power = true;
		}

		if(!$power) {
			return array('code' => array('0x010005', 'performance'));
		}

		return $power;
	}

	/**
	 * [verifyPerformData 验证前端传入的考核数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]             $performData [考核数据]
	 * @param  [string]            $loginUserId [当前登录用户ID]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [object]                         [验证结果]
	 */
	public function verifyPerformData($performData, $loginUserId)
	{
		$errorInfo = true;

		//判断权限
		$power = $this->verifyPower($performData['perform_user'], $loginUserId);

		if($power !== true) {
			return $power;
		}
        $performPoints = $performData['perform_points'];
        if (substr($performPoints, -1) == ','){
            $performPoints = substr($performPoints, 0, -1);
        }
        $pointsArray = explode(',', $performPoints);
		foreach ($pointsArray as $point) {
			if(!is_numeric($point)) {
				return ['code' => ['0x010006', 'performance']];
			}

			if($point > $performData['temp_score']) {
				return ['code' => ['0x010007', 'performance']];
			}
		}

		if($performData['perform_point'] > $performData['temp_score']) {
			return ['code' => ['0x010008', 'performance']];
		}

		return $errorInfo;
	}

	/**
	 * [createPerform 创建一条考核数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]         $performData [前端传入的考核数据]
	 * @param  [string]        $loginUserId [当前登录用户ID]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [object]                     [创建结果]
	 */
	public function createPerform($performData, $loginUserId)
	{
		//若被考核人为当前人员,返回false
		if($performData['perform_user'] == $loginUserId) {
			return array('code' => array('0x010005', 'performance'));
		}

		//验证数据
		$verifyResult = $this->verifyPerformData($performData, $loginUserId);

		if($verifyResult !== true) {
			return $verifyResult;
		}

		$performMonth = $this->getPerformMonth($performData['check_year'], $performData['circle'], $performData['month']);

		$where = array(
				'perform_user' => array($performData['perform_user']),
				'perform_month' => array($performMonth),
			);

		//判断数据是否已存在
		$personnel = app($this->personalRepository)->getPersonelData($where);

		if($personnel) {
			return array('code' => array('0x010009', 'performance'));
		}
        $performPoints = $performData['perform_points'];
        if (substr($performPoints, -1) == ','){
            $performPoints = substr($performPoints, 0, -1);
        }
        $performData['perform_points'] = json_encode(explode(',', $performPoints));
		$performData['performer_user'] = $loginUserId;
		$performData['perform_month'] = $performMonth;

		$newPerform = app($this->personalRepository)->insertData($performData);

		if($newPerform) {
		    // 关联附件
            $info = ['wheres' => ['entity_id' => [$newPerform->id]],'entity_id' => [$newPerform->id]];
            if (!empty($performData['attachment_id'])) {
                app($this->attachmentService)->attachmentRelation("performance", $info, $performData['attachment_id']);
            }
            //判断被考核人是否有菜单权限
            $hasMenuPermission = app($this->userMenuService)->judgeMenuPermission(83, $newPerform->perform_user);
            if ($hasMenuPermission == "true") {
                //考核人姓名
                $performUserName = app($this->userRepository)->getUsersNameByIds([$newPerform['performer_user']])[0];
                //考核年份
                $performYear = substr($newPerform->perform_month, 0, 4);

                $sendData['remindMark']     = 'performance-perform';
                $sendData['toUser']         = $newPerform->perform_user;
                $sendData['contentParam']   = ['userName'=>$performUserName];
                $sendData['stateParams']    = ['checkYear'=>$performYear, 'userId'=>$newPerform->perform_user];

                Eoffice::sendMessage($sendData);
            }
		}

		return $newPerform;
	}

	/**
     * [getPlanList 获取考核方案列表]
     *
     * @author 朱从玺
     *
     * @since  2015-10-26 创建
     *
     * @return [array]      [查询结果]
     */
	public function getPlanList()
	{
		$plansList = app($this->planRepository)->getAllPlan();

		$plansList = $plansList->toArray();
		foreach($plansList as $key => &$value) {
			$value['plan_name'] = mulit_trans_dynamic('performance_plan.plan_name.performance_plan_'. $value['id']);
		}
		return $plansList;
	}

	/**
	 * [getPlanInfo 获取考核方案数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]        $planId [考核方案ID]
	 *
	 * @since  2015-10-23 创建
	 *
	 * @return [array]              [查询结果]
	 */
	public function getPlanInfo($planId)
	{
		return app($this->planRepository)->getDetail($planId);
	}

	/**
	 * [modifyPlan 修改考核方案]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]       $planId      [方案ID]
	 * @param  [array]     $newPlanData [修改数据]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [bool]                   [修改结果]
	 */
	public function modifyPlan($planId, $newPlanData)
	{
		$verifyResult = $this->verifyPlanData($newPlanData);

		if($verifyResult !== true) {
			return $verifyResult;
		}

		return app($this->planRepository)->modifyPlan($planId, $newPlanData);
	}

	/**
	 * [verifyPlanData 验证考核方案修改数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]         $newPlanData [修改数据]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [bool]                       [验证结果]
	 */
	public function verifyPlanData($newPlanData)
	{
		$timeVerify = false;

		if($newPlanData["plan_start_type"] == 1 && $newPlanData["plan_end_type"] == 1) {
			if($newPlanData["plan_start_day"] < $newPlanData["plan_end_day"]) {
				$timeVerify = true;
			}
		}elseif($newPlanData["plan_start_type"] == 2 && $newPlanData["plan_end_type"] == 2) {
			if($newPlanData["plan_start_day"] > $newPlanData["plan_end_day"]) {
				$timeVerify = true;
			}
		}elseif($newPlanData["plan_start_type"] == 2 && $newPlanData["plan_end_type"] == 1) {
			$timeVerify = true;
		}

		if($timeVerify) {
			return array('code' => array('0x010003', 'performance'));
		}

		if($newPlanData['plan_is_useed'] == 0 && $newPlanData['plan_is_remind'] == 1) {
			return array('code' => array('0x010004', 'performance'));
		}

		return true;
	}

	/**
	 * [createTemp 创建模板]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]     $inputData [模板数据]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [bool]                 [创建结果]
	 */
	public function createTemp($inputData)
	{
		$insertData = $this->arrangeTempData($inputData);

		if(!isset($insertData['code'])) {
			//插入模板数据
			$lastTemp = app($this->tempRepository)->insertData($insertData);

			//更新模板用户表
			if(isset($insertData['temp_applicable_person'])) {
				$this->arrangeTempApplicable($lastTemp->id, $lastTemp->plan_id, $insertData['temp_applicable_person']);
			}

			return $lastTemp;
		}else {
			return $insertData;
		}
	}

	/**
	 * [modifyTemp 编辑模板]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]       $tempId      [模板ID]
	 * @param  [array]     $newTempData [编辑数据]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [bool]                   [编辑结果]
	 */
	public function modifyTemp($tempId, $newTempData)
	{
		$modifyData = $this->arrangeTempData($newTempData);

		if(!isset($modifyData['code'])) {
			$where = array(
				'id' => array($tempId),
			);

			$result = app($this->tempRepository)->updateDataBatch($modifyData, $where);

			//更新模板用户表
			if(isset($modifyData['temp_applicable_person'])) {
				$this->arrangeTempApplicable($tempId, $modifyData['plan_id'], $modifyData['temp_applicable_person']);
			}

			return true;
		}else {
			return $modifyData;
		}
	}

	/**
	 * [arrangeTempData 将前端传入的模板数据整理为可以直接保存的数组]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]          $inputData [前端传入的模板数据]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [array]                     [整理后的模板数据]
	 */
	protected function arrangeTempData($inputData)
	{
		//为了正确显示'\',使用stripslashes函数
		$optionInfo = stripslashes(substr($inputData['temp_option'], 0, -3));
		$optionNote = stripslashes(substr($inputData['temp_option_note'], 0, -3));
		$weightInfo = stripslashes(substr($inputData['temp_weight'], 0, -3));
		$weightNote = stripslashes(substr($inputData['temp_weight_note'], 0, -3));

		$optionInfos = explode("(`)",$optionInfo);
		$weightInfos = explode("(`)",$weightInfo);
		$optionNotes = explode("(`)",$optionNote);
		$weightNotes = explode("(`)",$weightNote);

		if(in_array('', $weightInfos)) {
			return array('code' => array('0x010015', 'performance'));
		}

		if(array_sum($weightInfos) != 100) {
	 		return array('code' => array('0x010002', 'performance'));
		}

		$tempContent = array();
		foreach($optionInfos as $key => $value) {
		    $tempContent[$key] = array( "optionInfo"=>urlencode($optionInfos[$key]),
										"weightInfo"=>urlencode($weightInfos[$key]),
										"optionNote"=>urlencode($optionNotes[$key]),
										"weightNote"=>urlencode($weightNotes[$key]));
		}

		$tempContentJson = json_encode($tempContent);

		unset($inputData['temp_option']);
		unset($inputData['temp_option_note']);
		unset($inputData['temp_weight']);
		unset($inputData['temp_weight_note']);
		$inputData['temp_content'] = $tempContentJson;

		return $inputData;
	}

	/**
	 * [arrangeTempApplicable 更新模板适用对象,每个用户仅有一个适用模板]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]                   $tempId               [考核模板ID]
	 * @param  [int]                   $planId               [考核方案ID]
	 * @param  [string]                $tempApplicablePerson [更新模板适用对象]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [bool]                                      [处理结果]
	 */
	public function arrangeTempApplicable($tempId, $planId, $tempApplicablePerson)
	{
		if(!is_array($tempApplicablePerson) || $tempApplicablePerson == '') {
			return false;
		}

		$where = [
			'user_id' => [$tempApplicablePerson, 'in'],
			'temp_id' => [$tempId, '!='],
			'plan_id' => [$planId]
		];

		//删除用户以前的模板关联
		app($this->tempUserRepository)->deleteByWhere($where);

		//删除当前模板以前的关联用户
		app($this->tempUserRepository)->deleteByWhere(['temp_id' => [$tempId]]);

		foreach ($tempApplicablePerson as $user) {
			$data = [
				'user_id' => $user,
				'temp_id' => $tempId,
				'plan_id' => $planId
			];

			app($this->tempUserRepository)->createData($data);
		}

		return true;
	}

	/**
	 * [getTempList 获取模板列表]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]      $param [查询条件]
	 *
	 * @since  2015-10-26
	 *
	 * @return [array]             [模板数组]
	 */
	public function getTempList($params)
	{
		if($params) {
			$params = $this->parseParams($params);
			return app($this->tempRepository)->getTempList($params);
		}
		return app($this->planRepository)->getTempList();
	}

	// 10.5 获取模板列表（加入模糊搜搜）
    public function getTempListNew($params)
    {
        $params = $this->parseParams($params);

        // 处理模板列表为空情况，为空则直接返回空数组
        $list =  app($this->planRepository)->getTempList($params);
        $listArr = $list->toArray();
        // 获取四种类型的模板
        $tempList = array_column($listArr, 'performance_temp');
        // 过滤空数组
        $tempFilterList = array_filter($tempList);

        if (!$tempFilterList) {
            return $tempFilterList;
        }

        return $list;
    }

	/**
	 * [getTempInfo 获取指定模板数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]        $tempId [模板ID]
	 *
	 * @since  2015-10-26
	 *
	 * @return [array]              [模板数据]
	 */
	public function getTempInfo($tempId)
	{
		$tempDetail = app($this->tempRepository)->getDetail($tempId);

		if(!$tempDetail) {
			return '';
		}
		$tempDetail = $tempDetail->toArray();

		//查询模板对应的用户
		$where = [
			'temp_id' => [$tempDetail['id']]
		];
		$tempUser = app($this->tempUserRepository)->searchTempUser($where);

		//遍历为数组
		$applicablePerson = [];
		foreach ($tempUser as $key => $value) {
			$applicablePerson[] = $value->user_id;
		}
		$tempDetail['temp_applicable_person'] = $applicablePerson;

		$tempDetail['temp_content'] = json_decode($tempDetail['temp_content'], true);

		foreach ($tempDetail['temp_content'] as $key => $value) {
			$tempDetail['temp_content'][$key]['optionInfo'] = urldecode($value['optionInfo']);
			$tempDetail['temp_content'][$key]['optionNote'] = urldecode($value['optionNote']);
			$tempDetail['temp_content'][$key]['weightInfo'] = urldecode($value['weightInfo']);
			$tempDetail['temp_content'][$key]['weightNote'] = urldecode($value['weightNote']);
		}

		return $tempDetail;
	}

	/**
	 * [copyTemp 复制模板]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]    $tempId [模板ID]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [bool]           [复制结果]
	 */
	public function copyTemp($tempId)
	{
		$temp = app($this->tempRepository)->getDetail($tempId);

		if(!$temp) {
			return array('code' => array('0x010011', 'performance'));
		}

		$tempArray = $temp->toArray();

        $tempArray['temp_name'] = $tempArray['temp_name'].'('.trans('performance.copy').')';

        return app($this->tempRepository)->insertData($tempArray);
	}

	/**
     * [deleteTemp 删除考核模板]
     *
     * @author 朱从玺
     *
     * @param  [int]      $tempId [模板ID]
     *
     * @since  2015-10-26
     *
     * @return [json]             [删除结果]
     */
	public function deleteTemp($tempId)
	{
		$temp = app($this->tempRepository)->getDetail($tempId);

		if($temp) {
			$result = app($this->tempRepository)->deleteById($tempId);

			$where = [
				'temp_id' => [$tempId]
			];

			//删除用户以前的模板关联
			app($this->tempUserRepository)->deleteByWhere($where);
		}

		return true;
	}

	/**
	 * [getMyTempId 获取用户某个考核方案下的考核模板ID]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]      $userId [用户ID]
	 * @param  [int]      $planId [考核方案ID]
	 *
	 * @return [int]              [模板ID]
	 */
	public function getMyTempId($userId, $planId)
	{
		$where = [
			'plan_id' => [$planId],
			'user_id' => [$userId]
		];
		$tempUser = app($this->tempUserRepository)->searchTempUser($where);

		if(isset($tempUser[0]) && $tempUser[0]) {
			return $tempUser[0]->temp_id;
		}

		return '';
	}

	/**
     * [getNoPerformer 获取没有考核人的用户列表]，废弃
     *
     * @author 朱从玺
     *
     * @since  2015-10-26 创建
     *
     * @return [array]         [查询结果]
     */
//	public function getNoPerformerOld()
//	{
//		$param = array(
//			'fields' => array('user_id', 'user_name'),
//		);
//
//		$allUser = app($this->userRepository)->getUserList($param);
//
//		$noPerformerUsers = array();
//
//		foreach($allUser as $key => $user) {
//			$userPerformer = $this->getPerformer($user['user_id']);
//
//			if(!isset($userPerformer['user_performer']) || !$userPerformer['user_performer']) {
//				$noPerformerUsers[$key]['user_id'] = $user['user_id'];
//				$noPerformerUsers[$key]['user_name'] = $user['user_name'];
//			}
//		}
//
//		return array_values($noPerformerUsers);
//	}

    /**
     * [getNoPerformer 获取没有考核人的用户列表]
     *
     * @author nitianhua
     *
     * @since  2019-04-12 创建
     *
     * @return [array]         [查询结果]
     */
    public function getNoPerformer()
    {
//        \DB::connection()->enableQueryLog();
        $user = app($this->userRepository)->entity
            ->select('user.user_id', 'user.user_name')
            ->leftJoin('performance_performer', function ($join) {
                $join->on('performance_performer.performance_user', '=', 'user.user_id');
            })
            ->where('user_accounts', '<>', '')
            ->whereNotNull('user_accounts')
            ->where(function ($query) {
                $query->where(function($query){
                    $query->where('performance_performer.user_performer_status', 1)
                        ->where('performance_performer.user_performer', '=', '');
                })
                    ->orWhere(function ($query){
                        $query->where('performance_performer.user_performer_status', 0)
                            ->whereNotExists(function($query){
                                $query->select(DB::raw(1))
                                    ->from('user_superior')
                                    ->whereRaw('user_superior.user_id = user.user_id');
                            });
                    })
                    ->orWhereNull('performance_performer.user_performer');
            })
            ->get();

        return $user;
    }

	/**
	 * [getPerformer 获取用户考核人]
	 *
	 * @author 朱从玺
	 *
	 * @param  [string]       $userId [用户ID]
	 *
	 * @since 2015-10-26 创建
	 *
	 * @return [array]                [查询结果]
	 */
	public function getPerformer($userId)
	{
		//判断用户是否有考核人数据
		$result = $this->insertNewPerformer($userId);

		$performerInfo = app($this->performerRepository)->getPerformerInfo($userId);

		if($performerInfo->user_performer_status == 1) {
			$performer['user_performer'] = $performerInfo['user_performer'];
			//$performer['user_performer_name'] = app($this->userRepository)->getUserName($performer['id']);
		}else {
			//查询用户默认考核人
			$defaultPerformer = $this->getDefaultPerformer($userId);

			if($defaultPerformer != '' && $defaultPerformer['user_performer']) {
				$defaultPerformerInfo = app($this->performerRepository)->getPerformerInfo($defaultPerformer['user_performer']);

				if($defaultPerformerInfo && $defaultPerformerInfo->user_approve_status == 1) {
					$performer = [];
				}else {
					$performer = $defaultPerformer;
				}
			}
		}

		$performer['user_id'] = $performerInfo->performance_user;
		$performer['user_name'] = $performerInfo->performerHasOneUser['user_name'];

		return $performer;
	}

	/**
	 * [getDefaultPerformer 获取用户默认考核人]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]                 $userId [用户ID]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [array]                       [默认考核人]
	 */
	public function getDefaultPerformer($userId) {
		$superiorArray = app($this->userService)->getSuperiorArrayByUserId($userId);

		if($superiorArray['id']) {
			$performer['user_performer'] = $superiorArray['id'][0];
			$performer['user_performer_name'] = trans('performance.superior').'('.$superiorArray['name'][0].')';
		}else {
			$performer = '';
		}

		return $performer;
	}

	/**
     * [getApprover 获取用户被考核人]
     *
     * @author 朱从玺
     *
     * @param  [string]       $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]                 [查询结果]
     */
	public function getApprover($userId, $withDimission = 1, $param = [])
	{
		$approverArray = array();

		//取出考核人为当前人员且考核人选择状态为手动的所有人员
		$where = array(
			'user_performer' => array($userId),
			'user_performer_status' => array(1),
		);

		$userResult = app($this->performerRepository)->getPerformerUserByWhere($where);

		if($userData = $userResult->toArray()) {

			foreach ($userData as $key => $value) {
				if (empty($value['user_name'])) {
					continue;
				}

				$userName = $value['user_name'];

				if ($withDimission) {
	    			if (!empty($value['user_has_one_system_info']) && isset($value['user_has_one_system_info']['user_status']) && $value['user_has_one_system_info']['user_status'] == 2) {
	    				$userName .= "[".trans('performance.quit')."]";
	    			}
				}

				$approverArray[] = array(
					'user_id' => $value['performance_user'],
					'user_name' => $userName,
				);
			}
		}

		//获取用户考核人设置信息
		$performerInfo = app($this->performerRepository)->getPerformerInfo($userId);
		
		$valuePerformers = app($this->performerRepository)->getPerformerList([]);
		$valuePerformerArray = [];
		foreach ($valuePerformers as $valuePerformer) {
			$valuePerformerArray[$valuePerformer->performance_user] = $valuePerformer->user_performer_status;
		}
		//如果当前人员的信息不存在或者被考核人选择状态为默认状态
		if(is_object($performerInfo) && (!$performerInfo->toArray() || $performerInfo->user_approve_status == 0)) {
			//获取默认下级
			$param['returntype'] = 'list';
			$param['include_leave'] = 1;
			$subordinateArray = app($this->userService)->getSubordinateArrayByUserId($userId,$param);
			if (!empty($subordinateArray)) {
				foreach($subordinateArray as $key => $value) {
					//取出下级在考核人表中的数据
					$valuePerformer = isset($valuePerformerArray[$value['user_id']])?$valuePerformerArray[$value['user_id']]:'';
					if(!$valuePerformer) {
						//查询指定用户默认上级
                        $userName = $value['user_name'];
                        if ($withDimission) {
                            if (!empty($value['user_has_one_system_info']) && isset($value['user_has_one_system_info']['user_status']) && $value['user_has_one_system_info']['user_status'] == 2) {
                                $userName .= "[".trans('performance.quit')."]";
                            }
                        }
							$approverArray[] = array(
								'user_id' => $value['user_id'],
								'user_name' => $userName
							);
					}
				}
			}
		}
		return $approverArray;
	}

	/**
     * [makePerformerEmpty 清空考核人]
     *
     * @author 朱从玺
     *
     * @param  [string]             $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [bool]                       [清空结果]
     */
    public function makePerformerEmpty($userId)
    {
    	//判断用户考核人数据是否存在
    	$result = $this->insertNewPerformer($userId);

    	$newPerformerData = array(
    		'user_performer' => '',
    		'user_performer_status' => 1,
    	);

    	$where = array(
    		'performance_user' => array($userId),
    	);

    	$result = app($this->performerRepository)->updateDataBatch($newPerformerData, $where);

    	return $result;
    }

    /**
     * [makeApproverEmpty 清空被考核人]
     *
     * @author 朱从玺
     *
     * @param  [string]             $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [bool]                       [清空结果]
     */
    public function makeApproverEmpty($userId)
    {
    	//判断用户考核人数据是否存在
    	$result = $this->insertNewPerformer($userId);

    	$newPerformerData = array(
    		'user_approve_status' => 1,
    	);

    	$where = array(
    		'performance_user' => array($userId),
    	);

    	$result = app($this->performerRepository)->updateDataBatch($newPerformerData, $where);

    	//将考核人是当前人员,考核人选择状态为1的所有人考核人选择状态设置0
    	$newPerformerData = array(
			'user_performer_status' => 0,
		);

		$where = array(
    		'user_performer' => array($userId),
    		'user_performer_status' => array(1),
    	);

    	$result = app($this->performerRepository)->updateData($newPerformerData, $where);

    	return true;
    }

    /**
     * [insertNewPerformer 创建用户考核人数据]
     *
     * @author insertNewPerformer
     *
     * @param  [string]             $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [bool]                       [创建结果]
     */
    public function insertNewPerformer($userId)
    {
    	$performerInfo = app($this->performerRepository)->getPerformerInfo($userId);

    	if(!$performerInfo) {
			//如果performer表中没有该用户的数据,则新插入一条
			$newPerformer = array('performance_user' => $userId,
							 	  'user_performer' => '',
							 	  'user_performer_status' => 0,
							 	  'user_approve_status' => 0,
			);

			return app($this->performerRepository)->insertData($newPerformer);
		}

		return $performerInfo;
    }

    /**
     * [setPerformerDefault 设置指定用户的考核人为默认人员]
     *
     * @author 朱从玺
     *
     * @param  [string]             $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]                       [设置结果]
     */
    public function setPerformerDefault($userId)
    {
    	//获取用户的默认上级， 排除离职
		$superiorArray = app($this->userService)->getSuperiorArrayByUserId($userId, ['include_leave' => false]);

    	//判断用户考核人数据是否存在
    	$result = $this->insertNewPerformer($userId);

//    	如果上级不存在
        if(!$superiorArray['id'] || !$superiorArray['id'][0]) {
//         考虑上级离职的情况，把考核人清空
            $newPerformerData = array(
                'user_performer' => '',
                'user_performer_status' => 0,
            );
            $where = array(
                'performance_user' => array($userId),
            );
            app($this->performerRepository)->updateDataBatch($newPerformerData, $where);

            return 'notExit';
        }

    	$newPerformerData = array(
    		'user_performer' => $superiorArray['id'][0],
    		'user_performer_status' => 1,
    	);

    	$where = array(
    		'performance_user' => array($userId),
    	);

    	$result = app($this->performerRepository)->updateDataBatch($newPerformerData, $where);

    	return true;
    }

    /**
     * [setApproverDefault 设置指定用户的被考核人为默认人员]
     *
     * @author 朱从玺
     *
     * @param  [string]             $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [json]                       [设置结果]
     */
    public function setApproverDefault($userId)
    {
    	//判断用户考核人数据是否存在
    	$result = $this->insertNewPerformer($userId);

    	$userPerformer = app($this->performerRepository)->getPerformerInfo($userId);

        //考核人如果为手动改为自动
    	if($userPerformer['user_approve_status'] == 1) {
    		$newPerformerData = [
	    		'user_approve_status' => 0,
	    	];

	    	$where = [
	    		'performance_user' => array($userId),
	    	];

	    	$result = app($this->performerRepository)->updateDataBatch($newPerformerData, $where);
    	}

        //所有被考核人考核为手动且为考核人为$userId的改为自动
        $newPerformerData = ['user_performer_status' => 0];

        $where = [
            'user_performer' => [$userId],
            'user_performer_status' => [1]
        ];

        $result = app($this->performerRepository)->updateData($newPerformerData, $where);

        //修改下属考核状态
    	return $this->modifySubordinatePerformStatus($userId);
    }

    /**
     * 暂时弃用
     * [modifyApproverStatus 将用户被考核人中考核人选择状态为1的人员改为默认]
     *
     * @author 朱从玺
     *
     * @param  [string]               $userId [用户ID]
     *
     * @since  2015-10-26 创建
     *
     * @return [bool]                         [修改结果]
     */
    public function modifyApproverStatus($userId, $param = [])
    {
    	$param['returntype'] = 'id_name';
        //获取默认下级
		$subordinateArray = app($this->userService)->getSubordinateArrayByUserId($userId,$param);

		//默认下级中,考核人设置状态为1的用户数组
		$userArray = [];
		//默认被考核人
		// $defaultApprover = [];

		if (!empty($subordinateArray['id'])) {
			foreach($subordinateArray['id'] as $key => $value) {
				//获取下级的默认上级
				$superiorArray = app($this->userService)->getSuperiorArrayByUserId($value);

				if($superiorArray['id'][0] == $userId) {
					$defaultApprover[] = $value;

					//取出下级在考核人表中的数据
					$valuePerformer = app($this->performerRepository)->getPerformerInfo($value);

					if($valuePerformer && $valuePerformer['user_performer_status'] == 1) {
						$userArray[] = $value;
					}
				}
			}
		}

		//所有考核人是该用户且考核人设置状态为1的数组
		$where = array(
    		'user_performer' => array($userId),
    		'user_performer_status' => array(1),
    	);

    	$userResult = array_column(app($this->performerRepository)->getPerformerUserByWhere($where)->toArray(), 'performance_user');

    	//将需要编辑的用户数组合并去重
    	$updateUser = array_unique(array_merge($userArray, $userResult));

    	//编辑用户考核人状态为默认状态
		$newPerformerData = array(
			'user_performer_status' => 0
		);

    	$where = array(
    		'performance_user' => array($updateUser, 'in'),
    	);

    	$updateResult = app($this->performerRepository)->updateData($newPerformerData, $where);

    	if(isset($subordinateArray['id']) && !empty($subordinateArray['id'][0]) == []) {
    		return 'notExit';
    	}

    	return $updateResult;
    }

    /**
     *
     * [modifySubordinatePerformStatus 设置默认下属考核状态，在职的status=1，离职的status=0且考核人空]
     *
     * @author nitianhua
     *
     * @param $userId   考核人id
     * @param array $param
     * @return string
     */
    public function modifySubordinatePerformStatus($userId, $param = [])
    {

        //获取默认下级(包含离职)
        $param['returntype'] = 'list';
        $param['include_leave'] = true;
        $subordinateArray = app($this->userService)->getSubordinateArrayByUserId($userId, $param);

        //在职id
        $subordinateIdsOnJob = [];
        //离职id
        $subordinateIdsLeave = [];
        foreach($subordinateArray as $key => $value){
            if(isset($value['user_has_one_system_info']['user_status']) && $value['user_has_one_system_info']['user_status'] == 2){
                $subordinateIdsLeave[] = $value['user_id'];
            }else{
                $subordinateIdsOnJob[] = $value['user_id'];
            }
        }

        //在职下级设为自动0
        $newOnJobApproverStatus = ['user_performer_status' => 0];
        $where = ['performance_user' => [$subordinateIdsOnJob, 'in']];
        $result = app($this->performerRepository)->updateData($newOnJobApproverStatus, $where);
        //离职下级设为手动1且考核人空
        $newLeaveApproverStatus = ['user_performer_status' => 1, 'user_performer' => ''];
        $where = ['performance_user' => [$subordinateIdsLeave, 'in']];
        $result = app($this->performerRepository)->updateData($newLeaveApproverStatus, $where);
        //没有在职下级
        if(empty($subordinateIdsOnJob)){
            return 'notExit';
        }
        return $result;
    }

        /**
     * [modifyPerformer 编辑用户考核人]
     *
     * @author 朱从玺
     *
     * @param  [string]          $userId       [用户ID]
     * @param  [array]           $newPerformer [编辑数据]
     *
     * @since  2015-10-26 创建
     *
     * @return [bool]                          [编辑结果]
     */
    public function modifyPerformer($userId, $newPerformer)
    {
    	//判断用户考核人数据是否存在
    	$result = $this->insertNewPerformer($userId);

    	//考核人
    	if(isset($newPerformer['user_performer'])) {
    		$result = $this->performerSave($userId, $newPerformer['user_performer']);

    		if($result !== true) {
    			return $result;
    		}
    	}

    	//被考核人
    	if(isset($newPerformer['user_approver'])) {
    		$result = $this->approverSave($userId, $newPerformer['user_approver']);

    		if($result !== true) {
    			return $result;
    		}
    	}

    	return true;
    }

	/**
	 * [performerSave 保存某个用户的考核人]
	 *
	 * @author 朱从玺
	 *
	 * @param  [string]        $userId      [用户ID]
	 * @param  [string]        $performerId [考核人ID]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [bool]                       [保存结果]
	 */
	public function performerSave($userId, $performerId)
	{
		if($performerId) {
			//考核人不能为自己
			if($userId == $performerId) {
				return array('code' => array('0x010012', 'performance'));
			}

			$performerInfo = app($this->performerRepository)->getPerformerInfo($userId);

			if($performerInfo['user_performer_status'] == 1 && $performerInfo['user_performer'] == $performerId) {
				return true;
			}

			$newPerformerData = array(
				'user_performer' => $performerId,
				'user_performer_status' => 1,
			);

			$where = array(
	    		'performance_user' => array($userId),
	    	);

	    	return app($this->performerRepository)->updateDataBatch($newPerformerData, $where);
		}

		return $this->makePerformerEmpty($userId);
	}

	/**
	 * [approverSave 保存某个用户的被考核人]
	 *
	 * @author 朱从玺
	 *
	 * @param  [string]        $userId    [用户ID]
	 * @param  [string]        $approvers [被考核人ID,用','隔开]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [bool]                     [保存结果]
	 */
	public function approverSave($userId, $approvers)
	{
		//获取当前人员原来的被考核人
		$oldApprover = $this->getApprover($userId);

		$oldApproverId = array_column($oldApprover, 'user_id');

		if($approvers) {
			//在被考核人数组中查找当前人员
			$userKey = array_search($userId, $approvers);

			if($userKey !== false) {
				return array('code' => array('0x010013', 'performance'));
			}

			$diffrend1 = array_diff($approvers, $oldApproverId);
			$diffrend2 = array_diff($oldApproverId, $approvers);

			$diffrend = array_merge($diffrend1, $diffrend2);

			if(empty($diffrend)) {
				return true;
			}

			//将当前人员的被考核人选择状态设置1
			$newPerformerData = array(
				'user_approve_status' => 1,
			);

			$where = array(
	    		'performance_user' => array($userId),
	    	);

	    	$result = app($this->performerRepository)->updateData($newPerformerData, $where);

			//将所有考核人为当前人员的考核人选择状态设为0
			$newPerformerData = array(
				'user_performer_status' => 0,
			);

			$where = array(
	    		'user_performer' => array($userId),
	    		'user_performer_status' => array(1),
	    	);

	    	$result = app($this->performerRepository)->updateData($newPerformerData, $where);

			//设置新的被考核人
			$newPerformerData = array(
				'user_performer' => $userId,
				'user_performer_status' => 1,
			);

			foreach ($approvers as $user) {
				//获取被考核人的设置数据
				$performerData = app($this->performerRepository)->getPerformerInfo($user);

				//如果有数据,则修改
				if($performerData) {
					$where = array(
			    		'performance_user' => array($user),
			    	);
			    	app($this->performerRepository)->updateData($newPerformerData, $where);
				//如果没有数据,则插入一条
				}else {
					$newPerformerData = [
						'performance_user' => $user,
						'user_performer' => $userId,
						'user_performer_status' => 1,
						'user_approve_status' => 0
					];

					app($this->performerRepository)->insertData($newPerformerData);
				}
			}
		}else {
			$result = $this->makeApproverEmpty($userId);

			if($result !== true) {
				return $result;
			}
		}

		return true;
	}

    /**
     * @param $keyword
     * @return array
     */
	protected function getSearchedUserIds($keyword)
    {
        $param = [
            'fields' => ['user_id'],
            'search' => [
                'user_accounts' => [$keyword, 'like'],
                'user_name'		=> [$keyword, 'like'],
                'user_name_py'	=> [$keyword, 'like'],
                'user_name_zm'	=> [$keyword, 'like'],
            ],
        ];
        $users = app($this->userRepository)->getUserListByOr($param);

        return array_column($users, 'user_id');
    }

	/**
	 * [getStatisticList 获取考核统计列表]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]           $param [查询条件]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [array]                  [查询结果]
	 */
	public function getStatisticList($param)
	{
		$param = $this->parseParams($param);

		$default = [
			'check_year'=> date('Y'),
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['user_id'=>'ASC'],
        ];

        $param = array_merge($default, $param);
        //方案ID取出
        if(isset($param['plan_id']) && !empty($param['plan_id'])) {
        	$planId = $param['plan_id'];
        }else {
        	$planId = isset($param['search']['plan_id']) ? $param['search']['plan_id'][0] : 1;
        }

        if(isset($param['search']['user_name']) && $param['search']['user_name'][0]) {
            $userIds = $this->getSearchedUserIds($param['search']['user_name'][0]);
            if(empty($userIds)){
                return [];
            }
            $param['search']['user_id'] = [$userIds, 'in'];
            unset($param['search']['user_name']);
        }

        //部门ID
        if(isset($param['search']['dept_id'])) {
			$deptId = $param['search']['dept_id'][0];

			if($deptId == 0) {
				unset($param['search']['dept_id']);
			}else {
				$deptArray = app($this->departmentService)->getTreeIds($deptId);
				$param['search']['dept_id'] = [$deptArray, 'in'];
			}
		}

//		包含离职
        $param = array_merge($param, ['include_leave' => true]);
		$userList = app($this->userRepository)->getUserDeptName($param);

		foreach ($userList as $key => $user) {
			$userPerform = $this->getUserStatistic($planId, $param['check_year'], $user['user_id']);

			$userList[$key]['userPerform'] = $userPerform;
		}
		return $userList;
	}

    /**
     * 获取考核统计总数
     * @param $param
     * @return mixed
     */
	public function getStatisticCount($param)
    {
        $param = $this->parseParams($param);
        $search = $param['search'] ?? [];
        $params = [];
        if(isset($search['user_name']) && $search['user_name'][0]) {
            $userIds = $this->getSearchedUserIds($search['user_name'][0]);
            if(empty($userIds)){
                return 0;
            }
            $params['user_id'] = $userIds;
        }
        if(isset($search['user_id']) && !empty($search['user_id'][0])) {
            $params['user_id'] = $search['user_id'][0];
        }
        if(isset($search['dept_id']) && !empty($search['dept_id'][0])) {
            $deptId = $param['search']['dept_id'][0];
            $deptArray = app($this->departmentService)->getTreeIds($deptId);
            $params['dept_id'] = $deptArray;
        }

        /** @var UserRepository $userRepository */
        $userRepository = app($this->userRepository);
        return $userRepository->getSimpleUserTotal($params, true);
    }

    /**
     * 获取考核统计总数和列表
     * @param $param
     * @return array
     */
    public function getStatisticListAndCount($param)
    {
        return $this->response($this, 'getStatisticCount', 'getStatisticList', $param);
    }

	/**
	 * [getUserStatistic 获取用户考核数据统计]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]              $planId    [考核方案ID]
	 * @param  [int]              $checkYear [查询年份]
	 * @param  [string]           $userId    [用户ID]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [array]                       [查询结果]
	 */
	public function getUserStatistic($planId, $checkYear, $userId)
	{
		$userPerformStatistic = array();

		if($planId == 1) {
			for($i=1; $i<13; $i++) {
				$i = $i<10 ? '0'.$i : $i;

				$userPerformStatistic[$i] = '';
			}

			$userPerformStatistic['avg'] = '';
		}elseif($planId == 2) {
			for($i=1; $i<5; $i++) {
				$i = '0'.$i;

				$userPerformStatistic[$i] = '';
			}
		}elseif($planId == 3) {
			$userPerformStatistic['01'] = '';
			$userPerformStatistic['02'] = '';
		}elseif($planId == 4) {
			$userPerformStatistic['01'] = '';
		}

		$where = array(
			'plan_id' => array($planId),
			'perform_month' => array($checkYear, 'like'),
			'perform_user' => array($userId),
		);

		$userPerform = app($this->personalRepository)->getPersonelDatas($where);

		$sum = 0;
		$count = 0;
		foreach ($userPerform as $key => $value) {
			$sum += $value->perform_point;
			$count += 1;

			$month = substr($value->perform_month, -3, 2);

			$userPerformStatistic[$month] = $value->perform_point;
		}

		if(array_key_exists('avg', $userPerformStatistic)) {
			if($sum == 0) {
				$userPerformStatistic['avg'] = '0.00';
			}else {
				$userPerformStatistic['avg'] = round($sum / $count, 2);
			}
		}

		return $userPerformStatistic;
	}

	/**
     * [searchUser 用户搜索]
     *
     * @author 朱从玺
     *
     * @param  [string]            $param [查询条件]
     *
     * @since  2015-10-26 创建
     *
     * @return [array]                    [查询结果]
     */
	public function searchUser($param)
	{
		$param = $this->parseParams($param);
		$search = $param['search']['user_name'][0];

		$param = array(
    		'fields' => ['user_id', 'user_name'],
    		'limit'	 => 20,
    		'search' => array(
    			'user_accounts' => array($search, 'like'),
    			'user_name'		=> array($search, 'like'),
    			'user_name_py'	=> array($search, 'like'),
    			'user_name_zm'	=> array($search, 'like'),
    		),
    	);
        $users = app($this->userRepository)->getUserListByOr($param);
        foreach($users as $key => $value){
            if(isset($value['user_has_one_system_info']['user_status']) && $value['user_has_one_system_info']['user_status'] == 2){
                $users[$key]['user_name'] .= '['.trans('performance.quit').']';
            }
        }
        return $users;
	}

	/**
	 * [getPerformMonth 通过传来的参数得出考核数据表中的perform_month字段]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]             $checkYear [要查询的年份]
	 * @param  [string]          $circle    [要查询的考核方案,months/seasons/halfYears/years]
	 * @param  [int]             $month     [要查询的月/季/半年/年度]
	 *
	 * @since  2015-10-23 创建
	 *
	 * @return [string]                     [perform_month字段]
	 */
	public function getPerformMonth($checkYear, $circle, $month)
	{
		if($month < 10) {
			$month = '0'.$month;
		}

		switch ($circle) {
			case 'months':
				$performMonth = $checkYear.$month.'M';
				break;
			case 'seasons':
				$performMonth = $checkYear.$month.'S';
				break;
			case 'halfYears':
				$performMonth = $checkYear.$month.'H';
				break;
			case 'years':
				$performMonth = $checkYear.$month.'Y';
				break;
		}

		return $performMonth;
	}

	/**
	 * [getPerformTime 通过传来的参数计算某月/季/半年度/年度的考核起始和结束时间]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]            $checkYear [要查询的年份]
	 * @param  [string]         $circle    [要查询的考核方案,months/seasons/halfYears/years]
	 * @param  [int]            $month     [要查询的月/季/半年/年度]
	 *
	 * @since  2015-10-23 创建
	 *
	 * @return [array]                     [起始时间数组]
	 */
	public function getPerformTime($checkYear, $circle, $month)
	{
		switch ($circle) {
			case 'months':
				$timeArray = $this->getMonthsPlanTime($checkYear, $month);
				break;
			case 'seasons':
				$timeArray = $this->getSeasonsPlanTime($checkYear, $month);
				break;
			case 'halfYears':
				$timeArray = $this->getHalfYearsPlanTime($checkYear, $month);
				break;
			case 'years':
				$timeArray = $this->getYearsPlanTime($checkYear);
				break;
		}

		if(!$timeArray) {
			return false;
		}

		$startMonth = $timeArray['startMonth'] < 10 ? '0'.$timeArray['startMonth'] : $timeArray['startMonth'];
		$endMonth 	= $timeArray['endMonth'] < 10 ? '0'.$timeArray['endMonth'] : $timeArray['endMonth'];
		$startDay 	= $timeArray['startDay'] < 10 ? '0'.$timeArray['startDay'] : $timeArray['startDay'];
		$endDay 	= $timeArray['endDay'] < 10 ? '0'.$timeArray['endDay'] : $timeArray['endDay'];

		$performTime = array(
			'startDate' => $timeArray['startYear'].'-'.$startMonth.'-'.$startDay,
			'endDate' 	=> $timeArray['endYear'].'-'.$endMonth.'-'.$endDay.' 23:59:59',
		);
		return $performTime;
	}

	/**
	 * [getMonthsPlanTime 获取某月的考核起始及结束时间]
	 *
	 * @author 朱从玺
	 *
	 * @param  [type]            $checkYear [考核年份]
	 * @param  [type]            $month     [考核月份]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [type]                       [时间数组]
	 */
	public function getMonthsPlanTime($checkYear, $month)
	{
		$planInfo = $this->getPlanInfo(1);

		if($planInfo['plan_is_useed']) {
			//判断开始于年份
			if($month == 12 && $planInfo['plan_start_type'] == 2) {
				$startYear = $checkYear + 1;
			}else {
				$startYear = $checkYear;
			}

			//判断结束于年份
			if($month == 12 && $planInfo['plan_end_type'] == 2) {
				$endYear = $checkYear + 1;
			}else {
				$endYear = $checkYear;
			}

			//判断开始月份
			if($planInfo['plan_start_type'] == 1) {
				$startMonth = $month;
			}else {
				$startMonth = $month == 12 ? 1 : $month + 1;
			}

			//判断结束月份
			if($planInfo['plan_end_type'] == 1) {
				$endMonth = $month;
			}else {
				$endMonth = $month == 12 ? 1 :$month + 1;
			}

			//查询的月份总天数
			$days = in_array($month, array('1', '3', '5', '7', '8', '10', '12')) ? 31 : 30;

			if($month == 2) {
				$days = 28;
			}

			//判断开始于哪一天
			if($planInfo['plan_start_type'] == 2) {
				$startDay = $planInfo['plan_start_day'];

				if($month == '2' && $startDay > 28) {
					$startDay = 28;
				}
			}else {
				$startDay = $days - $planInfo['plan_start_day'];

				if($startDay <= 0) {
					$startDay = 1;
				}
			}

			//判断结束于哪一天
			if($planInfo['plan_end_type'] == 2) {
				$endDay = $planInfo['plan_end_day'];
				// 对于二月特殊处理, 如果二月考核方案为之后1-30天,只能到3.28号,
				// if($month == '2' && $endDay > 28) {
				// 	$endDay = 28;
				// }
			}else {
				$endDay = $days - $planInfo['plan_end_day'] + 1;

				if($endDay <= 0) {
					$endDay = 1;
				}
			}

			$monthsPlanTime = array(
				'startYear' => $startYear,
				'startMonth' => $startMonth,
				'startDay' => $startDay,
				'endYear' => $endYear,
				'endMonth' => $endMonth,
				'endDay' => $endDay,
			);

			return $monthsPlanTime;
		}else {
			return false;
		}
	}

	/**
	 * [getSeasonsPlanTime 获取某季度的考核起始及结束时间]
	 *
	 * @author 朱从玺
	 *
	 * @param  [type]            $checkYear [考核年份]
	 * @param  [type]            $month     [考核季度]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [type]                       [时间数组]
	 */
	public function getSeasonsPlanTime($checkYear, $month)
	{
		$planInfo = $this->getPlanInfo(2);

		if($planInfo['plan_is_useed']) {
			//判断起始年份
			if($planInfo['plan_start_type'] == 2 && $month == 4) {
				$startYear = $checkYear + 1;
			}else {
				$startYear = $checkYear;
			}

			//判断结束年份
			if($planInfo['plan_end_type'] == 2 && $month == 4) {
				$endYear = $checkYear + 1;
			}else {
				$endYear = $checkYear;
			}

			//判断起始的月份及日期
			if($planInfo['plan_start_type'] == 1) {
				switch ($month) {
					case 1:
						$startMonth = 3;
						$startDay = 31 - $planInfo['plan_start_day'];
						break;
					case 2:
						$startMonth = 6;
						$startDay = 30 - $planInfo['plan_start_day'];
						break;
					case 3:
						$startMonth = 9;
						$startDay = 30 - $planInfo['plan_start_day'];
						break;
					case 4:
						$startMonth = 12;
						$startDay = 31 - $planInfo['plan_start_day'];
						break;
					default:
						return false;
						break;
				}
			}else {
				switch ($month) {
					case 1:
						$startMonth = 4;
						$startDay = $planInfo['plan_start_day'];
						break;
					case 2:
						$startMonth = 7;
						$startDay = $planInfo['plan_start_day'];
						break;
					case 3:
						$startMonth = 10;
						$startDay = $planInfo['plan_start_day'];
						break;
					case 4:
						$startMonth = 1;
						$startDay = $planInfo['plan_start_day'];
						break;
					default:
						return false;
						break;
				}
			}

			//判断结束月份和日期
			if($planInfo['plan_end_type'] == 1) {
				switch ($month) {
					case 1:
						$endMonth = 3;
						$endDay = 31 - $planInfo['plan_end_day'] + 1;
						break;
					case 2:
						$endMonth = 6;
						$endDay = 30 - $planInfo['plan_end_day'] + 1;
						break;
					case 3:
						$endMonth = 9;
						$endDay = 30 - $planInfo['plan_end_day'] + 1;
						break;
					case 4:
						$endMonth = 12;
						$endDay = 31 - $planInfo['plan_end_day'] + 1;
						break;
					default:
						return false;
						break;
				}
			}else {
				switch ($month) {
					case 1:
						$endMonth = 4;
						$endDay = $planInfo['plan_end_day'];
						break;
					case 2:
						$endMonth = 7;
						$endDay = $planInfo['plan_end_day'];
						break;
					case 3:
						$endMonth = 10;
						$endDay = $planInfo['plan_end_day'];
						break;
					case 4:
						$endMonth = 1;
						$endDay = $planInfo['plan_end_day'];
						break;
					default:
						return false;
						break;
				}
			}

			$startDay = $startDay < 1 ? 1 : $startDay;
			$endDay = $endDay < 1 ? 1 : $endDay;

			$seasonsPlanTime = array(
				'startYear' => $startYear,
				'startMonth' => $startMonth,
				'startDay' => $startDay,
				'endYear' => $endYear,
				'endMonth' => $endMonth,
				'endDay' => $endDay,
			);

			return $seasonsPlanTime;
		}else {
			return false;
		}
	}

	/**
	 * [getHalfYearsPlanTime 获取半年度的考核起始及结束时间]
	 *
	 * @author 朱从玺
	 *
	 * @param  [type]            $checkYear [考核年份]
	 * @param  [type]            $month     [考核半年度]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [type]                       [时间数组]
	 */
	public function getHalfYearsPlanTime($checkYear, $month)
	{
		$planInfo = $this->getPlanInfo(3);

		if($planInfo['plan_is_useed'] == 1) {
			//判断起始年份
			if($planInfo['plan_start_type'] == 2 && $month >= 2) {
				$startYear = $checkYear + 1;
			}else {
				$startYear = $checkYear;
			}

			//判断结束年份
			if($planInfo['plan_end_type'] == 2 && $month >= 2) {
				$endYear = $checkYear + 1;
			}else {
				$endYear = $checkYear;
			}

			//判断起始月份和日期
			if($planInfo['plan_start_type'] == 1) {
				switch ($month) {
					case 1:
						$startMonth = 6;
						$startDay = 30 - $planInfo['plan_start_day'];
						break;
					case 2:
						$startMonth = 12;
						$startDay = 31 - $planInfo['plan_start_day'];
						break;
					default:
						return false;
						break;
				}
			}else {
				switch ($month) {
					case 1:
						$startMonth = 7;
						$startDay = $planInfo['plan_start_day'];
						break;
					case 2:
						$startMonth = 1;
						$startDay = $planInfo['plan_start_day'];
						break;
					default:
						return false;
						break;
				}
			}

			//判断结束月份和日期
			if($planInfo['plan_end_type'] == 1) {
				switch ($month) {
					case 1:
						$endMonth = 6;
						$endDay = 30 - $planInfo['plan_end_day'] + 1;
						break;
					case 2:
						$endMonth = 12;
						$endDay = 31 - $planInfo['plan_end_day'] + 1;
						break;
					default:
						return false;
						break;
				}
			}else {
				switch ($month) {
					case 1:
						$endMonth = 7;
						$endDay = $planInfo['plan_end_day'];
						break;
					case 2:
						$endMonth = 1;
						$endDay = $planInfo['plan_end_day'];
						break;
					default:
						return false;
						break;
				}
			}

			$startDay = $startDay < 1 ? 1 : $startDay;
			$endDay = $endDay < 1 ? 1 : $endDay;

			$halfYearsPlanTime = array(
				'startYear' => $startYear,
				'startMonth' => $startMonth,
				'startDay' => $startDay,
				'endYear' => $endYear,
				'endMonth' => $endMonth,
				'endDay' => $endDay,
			);

			return $halfYearsPlanTime;
		}else {
			return false;
		}
	}

	/**
	 * [getYearsPlanTime 获取某季度的考核起始及结束时间]
	 *
	 * @author 朱从玺
	 *
	 * @param  [type]            $checkYear [考核年份]
	 *
	 * @since  2015-10-26 创建
	 *
	 * @return [type]                       [时间数组]
	 */
	public function getYearsPlanTime($checkYear)
	{
		$planInfo = $this->getPlanInfo(4);

		if($planInfo['plan_is_useed']) {
			//判断起始时间
			if($planInfo['plan_start_type'] == 1) {
				$startYear = $checkYear;
				$startMonth = 12;
				$startDay = 31 - $planInfo['plan_start_day'];
			}else {
				$startYear = $checkYear + 1;
				$startMonth = 1;
				$startDay = $planInfo['plan_start_day'];
			}

			//判断结束时间
			if($planInfo['plan_end_type'] == 1) {
				$endYear = $checkYear;
				$endMonth = 12;
				$endDay = 31 - $planInfo['plan_end_day'] + 1;
			}else {
				$endYear = $checkYear + 1;
				$endMonth = 1;
				$endDay = $planInfo['plan_end_day'];
			}

			$yearsPlanTime = array(
				'startYear' => $startYear,
				'startMonth' => $startMonth,
				'startDay' => $startDay,
				'endYear' => $endYear,
				'endMonth' => $endMonth,
				'endDay' => $endDay,
			);

			return $yearsPlanTime;
		}else {
			return false;
		}
	}

	/**
	 * [exportAttendanceDuty 月度考核统计导出配置]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]                    $param [导出条件]
	 *
	 * @return [array]                           [导出数据]
	 */
	public function exportPerformanceMonths($param)
	{
		$header = [
			'user_name'				=> trans("performance.user_name"),
			'user_has_one_system_info.user_system_info_belongs_to_department.dept_name' => trans("performance.subordinate_department"),
			'userPerform.01'		=> trans("performance.january"),
			'userPerform.02'		=> trans("performance.february"),
			'userPerform.03'		=> trans("performance.march"),
			'userPerform.04'		=> trans("performance.april"),
			'userPerform.05'		=> trans("performance.may"),
			'userPerform.06'		=> trans("performance.june"),
			'userPerform.07'		=> trans("performance.july"),
			'userPerform.08'		=> trans("performance.august"),
			'userPerform.09'		=> trans("performance.september"),
			'userPerform.10'		=> trans("performance.october"),
			'userPerform.11'		=> trans("performance.november"),
			'userPerform.12'		=> trans("performance.december"),
			'userPerform.avg'		=> trans("performance.annually_average"),
		];

		$param['plan_id'] = 1;
		$param['search'] = json_encode($param['search']);

		$monthsData = $this->getStatisticList($param)->toArray();

		$data = [];
		foreach ($monthsData as $value) {
			$data[] = Arr::dot($value);
		}

		return compact('header', 'data');
	}

	/**
	 * [exportAttendanceDuty 季度考核统计导出配置]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]                    $param [导出条件]
	 *
	 * @return [array]                           [导出数据]
	 */
	public function exportPerformanceSeasons($param)
	{
		$header = [
			'user_name'				=> trans("performance.user_name"),
			'user_has_one_system_info.user_system_info_belongs_to_department.dept_name' => trans("performance.subordinate_department"),
			'userPerform.01'		=> trans("performance.first_quarter"),
			'userPerform.02'		=> trans("performance.second_quarter"),
			'userPerform.03'		=> trans("performance.third_quarter"),
			'userPerform.04'		=> trans("performance.fourth_quarter"),
		];

		$param['plan_id'] = 2;
		$param['search'] = json_encode($param['search']);

		$monthsData = $this->getStatisticList($param)->toArray();

		$data = [];
		foreach ($monthsData as $value) {
			$data[] = Arr::dot($value);
		}

		return compact('header', 'data');
	}

	/**
	 * [exportAttendanceDuty 半年度考核统计导出配置]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]                    $param [导出条件]
	 *
	 * @return [array]                           [导出数据]
	 */
	public function exportPerformanceHalfYears($param)
	{
		$header = [
			'user_name'				=> trans("performance.user_name"),
			'user_has_one_system_info.user_system_info_belongs_to_department.dept_nam' => trans("performance.subordinate_department"),
			'userPerform.01'		=> trans("performance.first_half_of_the_year"),
			'userPerform.02'		=> trans("performance.the_next_half_of_the_year")
		];

		$param['plan_id'] = 3;
		$param['search'] = json_encode($param['search']);

		$monthsData = $this->getStatisticList($param)->toArray();

		$data = [];
		foreach ($monthsData as $value) {
			$data[] = Arr::dot($value);
		}

		return compact('header', 'data');
	}

	/**
	 * [exportAttendanceDuty 年度考核统计导出配置]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]                    $param [导出条件]
	 *
	 * @return [array]                           [导出数据]
	 */
	public function exportPerformanceYears($param)
	{
		$header = [
			'user_name'				=> trans("performance.user_name"),
			'user_has_one_system_info.user_system_info_belongs_to_department.dept_name' => trans("performance.subordinate_department"),
			'userPerform.01'		=> trans("performance.the_year")
		];

		$param['plan_id'] = 4;
		$param['search'] = json_encode($param['search']);

		$monthsData = $this->getStatisticList($param)->toArray();

		$data = [];
		foreach ($monthsData as $value) {
			$data[] = Arr::dot($value);
		}

		return compact('header', 'data');
	}

    /***
     * 绩效考核到期提醒
     * @param int $daysEarlier 提前（默认1天）提醒
     * @return array
     */
	public function performanceExpireRemind($daysEarlier = 1)
    {
        /** @var Collection $planList */
        $planList = app($this->planRepository)->getAllPlan();

        $remindInfo = $planList->map(function($plan) use ($daysEarlier) {
            if (!$plan->plan_is_useed || !$plan->plan_is_remind) {
                return false;
            }

            return $this->handlePlanExpireRemind($plan, $daysEarlier);
        })->filter();

        if($remindInfo->isEmpty()){
            return [];
        }

        $toUser = $this->getAllPerformers();
        $messages = $remindInfo->map(function ($item) use ($toUser) {
            return [
                'remindMark' => 'performance-end',
                'toUser' => $toUser,
                'contentParam' => [
                    'attendanceTime' => $item
                ]
            ];
        })->values()->toArray();

        return $messages;
    }

    public function handlePlanExpireRemind($plan, $daysEarlier)
    {
        $endOffset = $plan->plan_end_type == 1 ? - $plan->plan_end_day : $plan->plan_end_day - 1;  //之前负，之后正，之后实际需少算一天

        $adjustDate = CarbonExtend::now()->subDays($endOffset)->addDays($daysEarlier); // 调整日期（包含日程提前量）,判断是否为1号阶段开始

        $year = $adjustDate->year;
        $periodName = '';
        switch ($plan->id) {
            case PerformancePlanType::MONTH:
                if (!$adjustDate->isFirstDayOfMonth()) {
                    return false;
                }
                $performDate = $adjustDate->subMonths(1);
                $year = $performDate->year;
                $period = $performDate->month;
                $periodName = trans('performance.' . $period);
                break;
            case PerformancePlanType::QUARTER:
                if (!$adjustDate->isFirstDayOfQuarter()) {
                    return false;
                }
                $performDate = $adjustDate->subQuarters(1);
                $year = $performDate->year;
                $period = $performDate->quarter;
                $periodName = trans('performance.' . (PerformancePeriods::QUARTERS)[$period]);
                break;
            case PerformancePlanType::HALF_YEAR:
                if (!$adjustDate->isFirstDayOfHalfYear()) {
                    return false;
                }
                $performDate = $adjustDate->subMonths(6);
                $year = $performDate->year;
                $period = $performDate->getHalfYear();
                $periodName = trans('performance.' . (PerformancePeriods::HALF_YEARS)[$period]);
                break;
            case PerformancePlanType::YEAR:
                if (!$adjustDate->isFirstDayOfYear()) {
                    return false;
                }
                $year = $adjustDate->year - 1;
                $periodName = trans('performance.the_year');
                break;
        }
        $attendanceTime = $year . trans('performance.year') . $periodName;

        return $attendanceTime;
    }

    /**
     * 获取所有考核人员
     * @return array
     */
	public function getAllPerformers()
    {
		$empower = app('App\EofficeApp\Empower\Services\EmpowerService')->checkModuleWhetherExpired(82);
		if(!$empower){
			return [];
		}
        //获取有考核权限的人员;
        $usersWithPermission = $this->getPerformerPowerUser();

        //过滤没有菜单权限的用户
        $usersWithMenu = app($this->userMenuService)->getMenuRoleUserbyMenuId(83);
        return array_intersect($usersWithPermission, $usersWithMenu);
    }

    /**
     *  计划任务考核阶段开始前几天发送到日程
     * @param integer $daysEarlier
     */
	public function sendCalenderSchedule($daysEarlier = 2)
    {
        //获取有考核权限的人员;
        $toUser = $this->getAllPerformers();
        if (empty($toUser)) {
            return;
        }

        //获取考核方案数据
        $planList = app($this->planRepository)->getAllPlan();
        foreach ($planList as $plan) {
            if (!$plan->plan_is_useed) {
                continue;
            }
            $this->handlePlanCalender($plan, $daysEarlier, $toUser);
        }
    }

    /**
     * 处理阶段外发日程
     * @param $plan
     * @param integer $daysEarlier 提前发日程天数
     * @param array $toUser
     */
    private function handlePlanCalender($plan, $daysEarlier, $toUser)
    {
        // 以1号为标准与当前日期进行比较
        $startOffset = $plan->plan_start_type == 1 ? - $plan->plan_start_day : $plan->plan_start_day - 1; //之前负，之后正，之后实际需少算一天

        $endOffset = $plan->plan_end_type == 1 ? - $plan->plan_end_day : $plan->plan_end_day - 1;

        $startDate = CarbonExtend::now()->addDays($daysEarlier); // 调整日期（可能是开始日期）

        $adjustDate = CarbonExtend::now()->subDays($startOffset)->addDays($daysEarlier); // 调整日期（包含日程提前量）

        $endDate = CarbonExtend::now()->addDays($daysEarlier)->subDays($startOffset)->addDays($endOffset); // 结束日期

        $year = $adjustDate->year;
        $periodName = '';
        switch ($plan->id) {
            case PerformancePlanType::MONTH:
                if (!$adjustDate->isFirstDayOfMonth()) {
                    return;
                }
                $performDate = $adjustDate->subMonths(1);
                $year = $performDate->year;
                $period = $performDate->month;
                $periodName = trans('performance.' . $period);
                break;
            case PerformancePlanType::QUARTER:
                if (!$adjustDate->isFirstDayOfQuarter()) {
                    return;
                }
                $performDate = $adjustDate->subQuarters(1);
                $year = $performDate->year;
                $period = $performDate->quarter;
                $periodName = trans('performance.' . (PerformancePeriods::QUARTERS)[$period]);
                break;
            case PerformancePlanType::HALF_YEAR:
                if (!$adjustDate->isFirstDayOfHalfYear()) {
                    return;
                }
                $performDate = $adjustDate->subMonths(6);
                $year = $performDate->year;
                $period = $performDate->getHalfYear();
                $periodName = trans('performance.' . (PerformancePeriods::HALF_YEARS)[$period]);
                break;
            case PerformancePlanType::YEAR:
                if (!$adjustDate->isFirstDayOfYear()) {
                    return;
                }
                $year = $adjustDate->year - 1;
                $periodName = trans('performance.the_year');
                break;
        }

        $performInfo = [
            'content' => $year . trans('performance.year') . $periodName . trans('performance.performance_examine'),
            'to_user' => $toUser,
            'begin_time' => $startDate->startOfDay()->format('Y-m-d H:i:s'),
            'end_time' =>   $endDate->endOfDay()->format('Y-m-d H:i:s'),
        ];
        $this->sendCalenderData($performInfo);
    }

    public function sendCalenderData($performInfo)
    {
        $calendarData = [
            'calendar_content' => $performInfo['content'],
            'handle_user'      => $performInfo['to_user'],
            'calendar_begin'   => $performInfo['begin_time'],
            'calendar_end'     => $performInfo['end_time']
        ];
        $relationData = [
            'source_id'     => 0,
            'source_from'   => 'performance-end',
            'source_title'  => $performInfo['content'],
            'source_params' => []
        ];
        app($this->calendarService)->emit($calendarData, $relationData, 'admin');
    }



	/**
     * [getCurrentMonth 判断考核中的月度]
     *
     * @method 朱从玺
     *
     * @param  [string]          $circle [周期,months/seasons/halfYears/years]
     *
     * @return [string]                  [判断结果]
     */
	public function getCurrentMonth($circle,$param)
	{
		$lastCircle = false;
		$thisCircle = false;
		$lastNotStart = false;
		$thisNotStart = false;
		if(isset($param) && !empty($param)){
			if($param['checkYear'] !== '' && $param['month'] !== ''){
				$monthData = $this->getPerformTime($param['checkYear'], 'months', $param['month']);
			}
		}
		switch ($circle) {
			case 'months':
				$monthsPlanInfo = $this->getPlanInfo(1);
				//上月
				if($monthsPlanInfo->plan_end_type == 2
					&& $monthsPlanInfo->plan_end_day >= date('j')
					&& ($monthsPlanInfo->plan_start_type == 1
						|| ($monthsPlanInfo->plan_start_type == 2
							&& date('Y-m-d')>$monthData['startDate']))) {

					$lastCircle = true;
				}elseif($monthsPlanInfo->plan_start_type == 2 && date('Y-m-d')<$monthData['endDate']) {

					$lastNotStart = true;
				}

				//本月
				if($monthsPlanInfo->plan_start_type == 1

					&& $monthData['endDate'] >= date('Y-m-d h:i:s')
					&& ($monthsPlanInfo->plan_end_type == 2
						|| ($monthsPlanInfo->plan_end_type == 1
							&& date('Y-m-d')<=$monthData['endDate']))) {

					$thisCircle = true;
				}elseif ($monthsPlanInfo->plan_end_type == 1 && date('Y-m-d')>$monthData['endDate']) {

					$thisNotStart = true;
				}
				if($monthsPlanInfo->plan_start_type == 2){
					if(date('Y-m-d')>$monthData['endDate']) return 'last';
				}elseif($monthsPlanInfo->plan_start_type == 2 && $monthsPlanInfo->plan_end_type == 2){

					// if(date('Y-m-d')<$monthData['startDate']) return 'thisNotStart';
				}

				break;
			case 'seasons':
				$year = date('Y');
				$month = date('n');

				if($month > 3 && $month <= 6) {
					$season = 2;
				}elseif($month > 6 && $month <= 9) {
					$season = 3;
				}elseif($month > 9 && $month <= 12) {
					$season = 4;
				}else {
					$season = 1;
				}
				switch ($season) {
					case 1:
						$thisSeasonFirstMonth = 1;
						$nextSeasonFirstMonth = 4;
						break;
					case 2:
						$thisSeasonFirstMonth = 4;
						$nextSeasonFirstMonth = 7;
						break;
					case 3:
						$thisSeasonFirstMonth = 7;
						$nextSeasonFirstMonth = 10;
						break;
					case 4:
						$thisSeasonFirstMonth = 10;
						$nextSeasonFirstMonth = 1;
						break;
				}

				if($season == 4) {
					$thisSeasonLastDate = $year . '-12-31';
				}else {
					$thisSeasonLastDate = date('Y-m-d', strtotime($year . '-' . $nextSeasonFirstMonth . '-01') - 24 * 3600);
				}

				//今天是本季度的第几天
				$todaySeasonDays = date('z') - date('z', strtotime($year . '-' . $thisSeasonFirstMonth . '-01')) + 1;
				//本季度一共有多少天
				$totalSeasonDays = date('z', strtotime($thisSeasonLastDate)) - date('z', strtotime($year . '-' . $thisSeasonFirstMonth . '-01'));

				//季度考核方案数据
				$seasonsPlanInfo = $this->getPlanInfo(2);

				//上季度
				if($seasonsPlanInfo->plan_end_type == 2
					&& $seasonsPlanInfo->plan_end_day >= $todaySeasonDays
					&& ($seasonsPlanInfo->plan_start_type == 1
						|| ($seasonsPlanInfo->plan_start_type == 2
							&& $seasonsPlanInfo->plan_start_day <= $todaySeasonDays))) {
					$lastCircle = true;
				}elseif($seasonsPlanInfo->plan_start_type == 2 && $seasonsPlanInfo->plan_start_day > $todaySeasonDays) {
					$lastNotStart = true;
				}

				//本季度
				if($seasonsPlanInfo->plan_start_type == 1
					&& $totalSeasonDays - $seasonsPlanInfo->plan_start_day < $todaySeasonDays
					&& ($seasonsPlanInfo->plan_end_type == 2
						|| ($seasonsPlanInfo->plan_end_type == 1
							&& $totalSeasonDays - $seasonsPlanInfo->plan_end_day >= $todaySeasonDays))) {
					$thisCircle = true;
				}elseif ($seasonsPlanInfo->plan_end_type == 1 && $totalSeasonDays - $seasonsPlanInfo->plan_end_day < $todaySeasonDays) {
					$thisNotStart = true;
				}
				break;
			case 'halfYears':
				$year = date('Y');
				$month = date('n');

				if($month > 6 && $month <= 12) {
					$halfYear = 2;
				}else {
					$halfYear = 1;
				}

				switch ($halfYear) {
					case 1:
						$thisHalfYearFirstMonth = 1;
						$nextHalfYearFirstMonth = 7;
						break;
					case 2:
						$thisHalfYearFirstMonth = 7;
						$nextHalfYearFirstMonth = 1;
						break;
				}

				if($halfYear == 2) {
					$thisHalfYearLastDate = $year . '-12-31';
				}else {
					$thisHalfYearLastDate = date('Y-m-d', strtotime($year . '-' . $nextHalfYearFirstMonth . '-01') - 24 * 3600);
				}

				//今天是本半年度的第几天
				$todayHalfYearDays = date('z') - date('z', strtotime($year . '-' . $thisHalfYearFirstMonth . '-01')) + 1;
				//本半年度一共有多少天
				$totalHalfYearDays = date('z', strtotime($thisHalfYearLastDate)) - date('z', strtotime($year . '-' . $thisHalfYearFirstMonth . '-01'));

				//半年度考核方案数据
				$halfYearPlanInfo = $this->getPlanInfo(3);

				//上半年度
				if($halfYearPlanInfo->plan_end_type == 2
					&& $halfYearPlanInfo->plan_end_day >= $todayHalfYearDays
					&& ($halfYearPlanInfo->plan_start_type == 1
						|| ($halfYearPlanInfo->plan_start_type == 2
							&& $halfYearPlanInfo->plan_start_day <= $todayHalfYearDays))) {
					$lastCircle = true;
				}elseif($halfYearPlanInfo->plan_start_type == 2 && $halfYearPlanInfo->plan_start_day > $todayHalfYearDays) {
					$lastNotStart = true;
				}

				//本半年度
				if($halfYearPlanInfo->plan_start_type == 1
					&& $totalHalfYearDays - $halfYearPlanInfo->plan_start_day < $todayHalfYearDays
					&& ($halfYearPlanInfo->plan_end_type == 2
						|| ($halfYearPlanInfo->plan_end_type == 1
							&& $totalHalfYearDays - $halfYearPlanInfo->plan_end_day >= $todayHalfYearDays))) {
					$thisCircle = true;
				}elseif ($halfYearPlanInfo->plan_end_type == 1 && $totalHalfYearDays - $halfYearPlanInfo->plan_end_day < $todayHalfYearDays) {
					$thisNotStart = true;
				}
				break;
			case 'years':
				$year = date('Y');

				//年度考核方案数据
				$yearPlanInfo = $this->getPlanInfo(4);

				//上一年度
				if($yearPlanInfo->plan_end_type == 2
					&& $yearPlanInfo->plan_end_day >= date('z')
					&& ($yearPlanInfo->plan_start_type == 1
						|| ($yearPlanInfo->plan_start_type == 2
							&& $yearPlanInfo->plan_start_day <= date('z')))) {
					$lastCircle = true;
				}elseif($yearPlanInfo->plan_start_type == 2 && $yearPlanInfo->plan_start_day > date('z')) {
					$lastNotStart = true;
				}

				//本年度
				if($yearPlanInfo->plan_start_type == 1
					&& date('z', strtotime($year . '-12-31')) - $yearPlanInfo->plan_start_day < date('z')
					&& ($yearPlanInfo->plan_end_type == 2
						|| ($yearPlanInfo->plan_end_type == 1
							&& date('z', strtotime($year . '-12-31')) - $yearPlanInfo->plan_end_day >= date('z')))) {
					$thisCircle = true;
				}elseif ($yearPlanInfo->plan_end_type == 1 && date('z', strtotime($year . '-12-31')) - $yearPlanInfo->plan_end_day < date('z')) {
					$thisNotStart = true;
				}
				break;
		}

		if($lastCircle && $thisCircle) {
			return 'lastAndThis';
		}

		if($lastCircle && !$thisCircle) {
			return 'last';
		}

		if(!$lastCircle && $thisCircle) {
			return 'this';
		}

		if($lastNotStart) {
			return 'lastNotStart';
		}

		if(!$lastNotStart && $thisNotStart) {
			return 'thisNotStart';
		}

		return 'null';
	}

	/**
	 * [getPerformerPowerUser 获取所有有考核权限的人]
	 *
	 * @method 朱从玺
	 *
	 * @return [array]                [查询结果]
	 */
	public function getPerformerPowerUser()
	{
		$param = array(
			'fields' => array('user_id'),
			'pageable' => 'notpage'
		);

		//所有用户
		$allUser = array_column(app($this->userRepository)->getUserList($param), 'user_id');

		//有下级的用户
		$superiorUser = array_unique(array_column(app($this->userSuperiorRepository)->getUserSuperiorList([]), 'superior_user_id'));

		//没有下级的用户
		$subordinateUser = array_diff($allUser, $superiorUser);

		//没有下级的用户中,手动设置了被考核人的人员
		$where = array(
			'user_performer' => array($subordinateUser, 'in'),
			'user_performer_status' => array(1),
		);

		//没有下级用户中有考核权限的用户
		$subordinateHasApproverUser = array_unique(array_column(app($this->performerRepository)->getPerformerList($where)->toArray(), 'user_performer'));

		//所有有下级用户的考核人设置数据
		$where = [
			'performance_user' => array($superiorUser, 'in')
		];

		$performerData = app($this->performerRepository)->getPerformerList($where)->toArray();

		$superiorPerformerData = [];
		foreach ($performerData as $value) {
			$superiorPerformerData[$value['performance_user']] = $value;
		}

		//有下级用户中有考核权限的用户
		$superiorHasApproverUser = [];
		foreach ($superiorUser as $key => $userId) {
			//判断有没有手动设置的被考核人
			$where = array(
				'user_performer' => array($userId),
				'user_performer_status' => array(1)
			);

			$approverCount = app($this->performerRepository)->getPerformerCount($where);

			//如果有手动设置的被考核人
			if($approverCount > 0) {
				$superiorHasApproverUser[] = $userId;
				continue;
			}

			//如果用户没有考核人设置数据,或者他的被考核人状态为默认
			//那么当他的默认下级考核人状态为默认且第一个默认上级是该用户的时候,即有考核权限
			if(!isset($superiorPerformerData[$userId]) || $superiorPerformerData[$userId]['user_approve_status'] == 0) {
				//获取默认下级
				$subordinateArray = app($this->userService)->getSubordinateArrayByUserId($userId);

				$hasApprover = false;
				foreach($subordinateArray['id'] as $value) {
					if($hasApprover == true) {
						break;
					}

					//取出下级在考核人表中的数据
					$valuePerformer = app($this->performerRepository)->getPerformerInfo($value);

					if(!$valuePerformer || $valuePerformer->user_performer_status == 0) {
						//查询指定用户默认上级
						$superiorArray = app($this->userService)->getSuperiorArrayByUserId($value);

						if($superiorArray['id'][0] == $userId) {
							$hasApprover = true;
							$superiorHasApproverUser[] = $userId;
						}
					}
				}
			}
		}

		return array_merge($subordinateHasApproverUser, $superiorHasApproverUser);
	}

	//样式类型
	public function getMonthClass($param){
		$classArr = array(1=>'ready-evaluate ',2=>'evaluating active ',3=>'not-evaluate ');
		$res = array();
		$monthsPlanInfo = $this->getPlanInfo(1);
		$m =date("m");
		$date = date("Y-m-d");
		for($index=1;$index<=12;$index++){
			if(!empty($param['checkYear'])){
				if($param['checkYear']<date('Y')){
					$res[$index] = $classArr[1];
					continue;
				}
			}
			if($index<$m-1){
				$res[$index] = $classArr[1];
			}else if($index>$m){
				$res[$index] = $classArr[3];
			}else{
				$info = $this->getOneClass($monthsPlanInfo,date("Y"),$index);
				if($date>$info['end']){
					$res[$index] = $classArr[1];
				}else if($date<$info['start']){
					$res[$index] = $classArr[3];
				}else{
					$res[$index] = $classArr[2];
				}
			}
		}
		return $res;
	}
	// 季度的样式问题
	public function getSeasonClass($param){

		$classArr = array(1=>'ready-evaluate ',2=>'evaluating active ',3=>'not-evaluate ');
		$res = array();
		$seasonPlanInfo = $this->getPlanInfo(2);
		$month =date("m");
		$season 	= 1;

		//当前季度
		if($month > 3 && $month <= 6) {
			$season = 2;
		}elseif($month > 6 && $month <= 9) {
			$season = 3;
		}elseif($month > 9 && $month <= 12) {
			$season = 4;
		}
		$date = date("Y-m-d");
		for($index=1;$index<=4;$index++){
			if(!empty($param['checkYear'])){
				if($param['checkYear']<date('Y')){
					$res[$index] = $classArr[1];
					continue;
				}
			}
			if($index<$season-1){
				$res[$index] = $classArr[1];
			}else if($index>$season){
				$res[$index] = $classArr[3];
			}else{
				$info = $this->getOneSeasonClass($seasonPlanInfo,date("Y"),$index);
				if($date>$info['end']){
					$res[$index] = $classArr[1];
				}else if($date<$info['start']){
					$res[$index] = $classArr[3];
				}else{
					$res[$index] = $classArr[2];
				}
			}
		}
		return $res;
	}
	// 半年的样式问题
	public function getHalfYearClass($param){

		$classArr = array(1=>'ready-evaluate ',2=>'evaluating active ',3=>'not-evaluate ');
		$res = array();
		$yearPlanInfo = $this->getPlanInfo(3);
		$month =date("m");
		$year 	= 1;

		//当前年度
		if($month > 6) {
			$year = 2;
		}
		$date = date("Y-m-d");
		for($index=1;$index<=2;$index++){
			if(!empty($param['checkYear'])){
				if($param['checkYear']<date('Y')){
					$res[$index] = $classArr[1];
					continue;
				}
			}
			if($index<$year-1){
				$res[$index] = $classArr[1];
			}else if($index>$year){
				$res[$index] = $classArr[3];
			}else{
				$info = $this->getOneHalfYearClass($yearPlanInfo,date("Y"),$index);
				if($date>$info['end']){
					$res[$index] = $classArr[1];
				}else if($date<$info['start']){
					$res[$index] = $classArr[3];
				}else{
					$res[$index] = $classArr[2];
				}
			}
		}
		return $res;
	}
	public function getOneClass(&$monthsPlanInfo,$year,$month){
		$res['start'] = "";
		$res['end'] = "";
		$tmp = $month<10?'0'.$month:$month;
		$month_start = ($year.'-'.$tmp.'-01');
		$t = date('t',strtotime($month_start));
		$month_end = ($year.'-'.$tmp.'-'.$t);
		$end = strtotime($month_end);
		if($monthsPlanInfo->plan_start_type == 1){
			$res['start'] = date('Y-m-d',strtotime("-".$monthsPlanInfo->plan_start_day." day",$end));
		}else{
			$res['start'] = date('Y-m-d',strtotime("+".$monthsPlanInfo->plan_start_day." day",$end));
		}
		if($monthsPlanInfo->plan_end_type == 1){
			$res['end'] = date('Y-m-d',strtotime("-".$monthsPlanInfo->plan_end_day." day",$end));
		}else{
			$res['end'] = date('Y-m-d',strtotime("+".$monthsPlanInfo->plan_end_day." day",$end));
		}
		return $res;
	}
	public function getOneHalfYearClass(&$monthsPlanInfo,$year,$month){
		$res['start'] = "";
		$res['end'] = "";
		$tmp = $month<10?'0'.$month:$month;
		switch($month) {
			case 1:
				$month_start = ($year.'-'.'06'.'-01');
				$t = date('t',strtotime($month_start));
				$month_end = ($year.'-'.'06'.'-'.$t);
				$end = strtotime($month_end);
			break;
			case 2:
				$month_start = ($year.'-'.'12'.'-01');
				$t = date('t',strtotime($month_start));
				$month_end = ($year.'-'.'12'.'-'.$t);
				$end = strtotime($month_end);
			break;

		}
		if($monthsPlanInfo->plan_start_type == 1){
			$res['start'] = date('Y-m-d',strtotime("-".$monthsPlanInfo->plan_start_day." day",$end));
		}else{
			$res['start'] = date('Y-m-d',strtotime("+".$monthsPlanInfo->plan_start_day." day",$end));
		}
		if($monthsPlanInfo->plan_end_type == 1){
			$res['end'] = date('Y-m-d',strtotime("-".$monthsPlanInfo->plan_end_day." day",$end));
		}else{
			$res['end'] = date('Y-m-d',strtotime("+".$monthsPlanInfo->plan_end_day." day",$end));
		}
		return $res;
	}

	public function getOneSeasonClass(&$monthsPlanInfo,$year,$month){
		$res['start'] = "";
		$res['end'] = "";
		switch($month) {
			case 1:
				$month_start = ($year.'-'.'03'.'-01');
				$t = date('t',strtotime($month_start));
				$month_end = ($year.'-'.'03'.'-'.$t);
				$end = strtotime($month_end);
			break;
			case 2:
				$month_start = ($year.'-'.'06'.'-01');
				$t = date('t',strtotime($month_start));
				$month_end = ($year.'-'.'06'.'-'.$t);
				$end = strtotime($month_end);
			break;
			case 3:
				$month_start = ($year.'-'.'09'.'-01');
				$t = date('t',strtotime($month_start));
				$month_end = ($year.'-'.'09'.'-'.$t);
				$end = strtotime($month_end);
			break;
			case 4:
				$month_start = ($year.'-'.'12'.'-01');
				$t = date('t',strtotime($month_start));
				$month_end = ($year.'-'.'12'.'-'.$t);
				$end = strtotime($month_end);
			break;

		}
		if($monthsPlanInfo->plan_start_type == 1){
			$res['start'] = date('Y-m-d',strtotime("-".$monthsPlanInfo->plan_start_day." day",$end));
		}else{
			$res['start'] = date('Y-m-d',strtotime("+".$monthsPlanInfo->plan_start_day." day",$end));
		}
		if($monthsPlanInfo->plan_end_type == 1){
			$res['end'] = date('Y-m-d',strtotime("-".$monthsPlanInfo->plan_end_day." day",$end));
		}else{
			$res['end'] = date('Y-m-d',strtotime("+".$monthsPlanInfo->plan_end_day." day",$end));
		}
		return $res;
	}
}
