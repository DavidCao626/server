<?php

namespace App\EofficeApp\System\Signature\Controllers;

use Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Signature\Services\SignatureService;

/**
 * 印章管理控制器:提供印章管理理模块请求的实现方法
 *
 * @author qishaobo
 *
 * @since  2016-01-22 创建
 */
class SignatureController extends Controller
{
    /** @var object 印章service对象*/
    private $signatureService;

    public function __construct(
        SignatureService $signatureService
    ) {
        parent::__construct();
        $this->signatureService = $signatureService;
    }

    /**
     * 新建印章
     *
     * @return int 新建印章id
     *
     * @author qishaobo
     *
     * @since  2016-01-22
     */
    function createSignature()
    {
        $result = $this->signatureService->createSignature(Request::all());
        return $this->returnResult($result);
    }

    /**
     * 删除印章
     *
     * @param  int|string $signatureId 印章id,多个用逗号隔开
     *
     * @return bool 操作是否成功
     *
     * @author qishaobo
     *
     * @since  2016-01-22
     */
    function deleteSignature($signatureId)
    {
        $result = $this->signatureService->deleteSignature($signatureId);
        return $this->returnResult($result);
    }

    /**
     * 编辑印章
     *
     * @param  int $signatureId 印章id
     *
     * @return array 印章详情
     *
     * @author: qishaobo
     *
     * @since：2016-01-22
     */
    function editSignature($signatureId)
    {
        $result = $this->signatureService->editSignature($signatureId, Request::all());
        return $this->returnResult($result);
    }

    /**
     * 查询印章详情
     *
     * @param  int 印章id
     *
     * @return array 印章详情
     *
     * @author qishaobo
     *
     * @since  2016-01-22
     */
    function getSignature($signatureId)
    {
        $result = $this->signatureService->getSignatureDetail($signatureId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取印章列表
     *
     * @return array 印章列表
     *
     * @author qishaobo
     *
     * @since  2016-01-22
     */
    function getIndexSignature()
    {
        $result = $this->signatureService->getSignatureList(Request::all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 访问不存在方法处理
     *
     * @return string 提示信息
     *
     * @author: qishaobo
     *
     * @since：2016-01-22
     */
    public function __call($name, $param)
    {
        return 'function '.$name.' not exist';
    }

    public function isAdmin()
    {
        $result =  $this->own['user_id'] == 'admin' ? 1 : 0;
        return $this->returnResult($result);
    }
}
