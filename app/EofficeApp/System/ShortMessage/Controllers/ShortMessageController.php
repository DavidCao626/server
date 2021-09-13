<?php

namespace App\EofficeApp\System\ShortMessage\Controllers;

use Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\ShortMessage\Services\ShortMessageService;

/**
 * 公司信息控制器:提供公司信息相关外部请求并提供返回值
 *
 * @author qishaobo
 *
 * @since  2017-03-06 创建
 */
class ShortMessageController extends Controller
{
    /**
     * 公司信息service
     *
     * @var object
     */
    private $shortMessageService;

    public function __construct(ShortMessageService $shortMessageService)
    {
        parent::__construct();
        $this->shortMessageService = $shortMessageService;
    }

	/**
	 * 获取手机服务器信息
     *
	 * @return array 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2017-03-06 创建
	 */
	public function getSMSSetList()
	{
        $result = $this->shortMessageService->getSMSSetList(Request::all());
        return $this->returnResult($result);
	}

    /**
     * 新建手机短信配置
     * @param  array $data 保存的数据
     * @return string 保存成功的id或者错误码
     */
    public function addSms()
    {
        $result = $this->shortMessageService->addSms(Request::all());
        return $this->returnResult($result);
    }

    /**
     * 修改手机服务器信息
     *
     * @return int|array 成功状态码|错误码
     *
     * @author qishaobo
     *
     * @since  2017-03-06 创建
     */
    public function editSMSSet($smsId)
    {
        $result = $this->shortMessageService->editSMSSet($smsId, Request::all());
        return $this->returnResult($result);
    }

    /**
     * 发送手机信息
     *
     * @return int|array 成功状态码|错误码
     *
     * @author qishaobo
     *
     * @since  2017-03-06 创建
     */
    public function sendSMS()
    {
        $userInfo = $this->own;
        $param = Request::all();
        $param['user_from'] = $userInfo['user_id'];
        $param['mobile_from'] = isset($userInfo['phone_number'])?$userInfo['phone_number']:"";
		if(empty($param['mobile_from'])&& isset($this->phone_number)){
			$param['mobile_from'] = $this->phone_number;
		}
        $result = $this->shortMessageService->sendSMS($param, $userInfo['role_id']);
        return $this->returnResult($result);
    }

    /**
     * 获取我的手机短信
     *
     * @return array 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2017-03-07 创建
     */
    public function getMineSMSList()
    {
        $userInfo = $this->own;
        $result = $this->shortMessageService->getSMSs(Request::all(), $userInfo['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 获取手机短信
     *
     * @return array 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2017-03-07 创建
     */
    public function getSMSList()
    {
        $result = $this->shortMessageService->getSMSs(Request::all());
        return $this->returnResult($result);
    }

    /**
     * 删除手机短信
     *
     * @return array 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2017-03-07 创建
     */
    public function deleteSMS($id)
    {
        $userInfo = $this->own;
        $result = $this->shortMessageService->deleteSMS($id, $userInfo);
        return $this->returnResult($result);
    }

    /**
     * 手机短信详情
     *
     * @return array 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2017-03-07 创建
     */
    public function getSMS($id)
    {
    	$userInfo = $this->own;
        $result = $this->shortMessageService->getSMS($id, $userInfo);
        return $this->returnResult($result);
    }

    /**
     * 获取不限制用户
     *
     * @param array $param 查询条件
     *
     * @return rray 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-07
     */
    public function getCommunicateUsers()
    {
        $result = $this->shortMessageService->getCommunicateUsers($this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取短信息发送种类
     * @return array 种类数组
     */
    public function getSMSType()
    {
        $result = $this->shortMessageService->getSMSType();
        return $this->returnResult($result);
    }

    /**
     * 获取短信配置列表
     * @return array
     */
    public function getSMSSetsList()
    {
        $result = $this->shortMessageService->getSMSList(Request::all());
        return $this->returnResult($result);
    }

    /**
     * 获取webservice里面的所有方法
     * @return array
     */
    public function getWebServiceFunction()
    {
        $result = $this->shortMessageService->getWebServiceFunction(Request::all());
        return $this->returnResult($result);
    }

    /**
     * 删除手机短信配置
     * @return bool
     */
    public function smsSetDelete()
    {
        $result = $this->shortMessageService->smsSetDelete(Request::all());
        return $this->returnResult($result);
    }

    /**
     * 编辑手机短信系统配置
     * @return string
     */
    public function editSmsConfig()
    {
        $result = $this->shortMessageService->editSmsConfig(Request::all());
        return $this->returnResult($result);
    }

    /**
     * 获取手机短信系统配置
     * @return string
     */
    public function getSmsConfig()
    {
        $result = $this->shortMessageService->getSmsConfig(Request::all());
        return $this->returnResult($result);
    }

    /**
     * 获取webservice方法对应的参数
     * @return [type] [description]
     */
    public function getSMSFunctionParams()
    {
        $result = $this->shortMessageService->getSMSFunctionParams(Request::all());
        return $this->returnResult($result);
    }

    public function getSMSTemplate()
	{
        $result = $this->shortMessageService->getSMSTemplate();
        return $this->returnResult($result);
	}
}