<?php

namespace App\EofficeApp\HtmlSignature\Services;

use App;
use App\EofficeApp\Base\BaseService;

/**
 * 签章模块，权限验证service
 *
 * @author dingp
 *
 * @since  2019-03-21 创建
 */
class HtmlSignaturePermissionService extends BaseService
{
    public function __construct(
    ) {
        parent::__construct();
        $this->goldgridSignatureKeysnRepository = 'App\EofficeApp\HtmlSignature\Repositories\GoldgridSignatureKeysnRepository';
        $this->goldgridSignatureSetRepository = 'App\EofficeApp\HtmlSignature\Repositories\GoldgridSignatureSetRepository';
        $this->flowPermissionService = 'App\EofficeApp\Flow\Services\FlowPermissionService';
    }

    /**
     * 验证签章功能里，对签章的获取列表操作的权限验证
     *
     * @author dingpeng
     *
     * @param   [type]  $params  [document_id run_id ;user_id 用户id]
     *
     * @return  [type]           [true 有权限；false 没权限]
     */
    public function verifyHtmlSignatureListPermission($params)
    {
        $documentId = isset($params["document_id"]) ? $params["document_id"] : "";
        $user_id = isset($params["user_id"]) ? $params["user_id"] : "";
        if ($documentId < 0) {
            return "";
        }
        return app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "view", "run_id" => $documentId, "user_id" => $user_id]);
    }

    /**
     * 验证签章功能里，对签章的[添加删除编辑]操作的权限验证
     *
     * @author dingpeng
     *
     * @param   [type]  $params  [document_id run_id ;user_id 用户id]
     *
     * @return  [type]           [true 有权限；false 没权限]
     */
    public function verifyHtmlSignaturePermission($params)
    {
        $documentId = isset($params["document_id"]) ? $params["document_id"] : "";
        $user_id = isset($params["user_id"]) ? $params["user_id"] : "";
        if ($documentId < 0) {
            return "";
        }
        return app($this->flowPermissionService)->verifyFlowHandleViewPermission(["type" => "handle", "run_id" => $documentId, "user_id" => $user_id]);
    }
}
