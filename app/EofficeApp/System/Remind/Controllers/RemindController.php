<?php

namespace App\EofficeApp\System\Remind\Controllers;

use App\EofficeApp\Base\Controller;
use Illuminate\Http\Request;
use App\EofficeApp\System\Remind\Services\SystemRemindService;
use App\EofficeApp\System\Remind\Requests\RemindRequest;
use Lang;

/**
 * 系统提醒设置控制器
 *
 * @author  朱从玺
 *
 * @since  2015-10-28 创建
 */
class RemindController extends Controller
{
	/**
	 * [$service 系统提醒设置service]
	 *
	 * @var [object]
	 */
	protected $service;

	/**
	 * [$request request验证]
	 *
	 * @var [object]
	 */
	protected $request;

	protected $remindRequest;

	public function __construct(
		SystemRemindService $service,
		RemindRequest $remindRequest,
		Request $request
	)
	{	parent::__construct();
		$this->service = $service;
		$this->request = $request;
		$this->remindRequest = $remindRequest;

		$this->formFilter($request, $remindRequest);
		$userInfo = $this->own;
        $this->userId = $userInfo['user_id'];
        
	}

	/**
	 * [getReminds 获取所有提醒方式]
	 *
	 * @author 朱从玺
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [json]     [查询结果]
	 */
	public function getReminds()
	{

		$reminds = $this->service->getReminds($this->userId);

		return $this->returnResult($reminds);
	}
	/**
	 * /
	 * @return [type] [description]
	 */
	public function getAllReminds()
	{
		$modifyData = $this->request->all();
		$reminds = $this->service->getUserChatGroup($modifyData, $this->userId);

		return $this->returnResult($reminds);
	}

	/**
	 * [getRemindsMiddleList 获取提醒设置中间列表]
	 *
	 * @author 朱从玺
	 *
	 * @return [json]               [查询结果]
	 */
	public function getRemindsMiddleList()
	{
		$result = $this->service->getRemindsMiddleList($this->userId, $this->request->all());

		return $this->returnResult($result);
	}

	/**
	 * [getRemindsInfo 获取提醒设置数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]          $remindsId [提醒设置ID]
	 *
	 * @return [json]                    [查询结果]
	 */
	public function getRemindsInfo($remindsId)
	{
		$locale = Lang::getLocale();
		$remindsInfo = $this->service->getRemindsInfo($remindsId, $locale);

		return $this->returnResult($remindsInfo);
	}

	/**
	 * [getRemindByMark 根据提醒标记查询消息提醒数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [string]          $remindMark [提醒标记]
	 *
	 * @return [array]                       [查询结果]
	 */
	public function getRemindByMark($remindMark)
	{
		$remindsInfo = $this->service->getRemindByMark($remindMark);

		return $this->returnResult($remindsInfo);
	}

	/**
	 * [modifyReminds 编辑提醒设置]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]         $remindsId [提醒设置ID]
	 *
	 * @return [bool]                   [编辑结果]
	 */
	public function modifyReminds($remindsId)
	{
		$modifyData = $this->request->all();

		$result = $this->service->modifyReminds($remindsId, $modifyData);

		return $this->returnResult($result);
	}

	public function getSystemRemindsList()
	{
		$result = $this->service->getSystemRemindsList($this->request->all());

		return $this->returnResult($result);
	}
	public function getRemindsTypeMobile()
	{
		$result = $this->service->getRemindsTypeMobile($this->userId);

		return $this->returnResult($result);
	}
	public function postSystemReminds()
	{
		$saveData = $this->request->all();

		$result = $this->service->postSystemReminds($saveData);

		return $this->returnResult($result);
	}
	public function sendReminds()
	{
		$saveData = $this->request->all();

		$result = $this->service->sendNotifyMessage($saveData);

		return $this->returnResult($result);
	}
}