<?php

namespace App\EofficeApp\IpRules\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\IpRules\Requests\IpRulesRequest;
use App\EofficeApp\IpRules\Services\IpRulesService;

/**
 * 访问控制
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class IpRulesController extends Controller {

    public function __construct(
            Request $request,
            IpRulesService $ipRulesService,
            IpRulesRequest $ipRulesRequest
    ) {
        parent::__construct();
        $this->ipRulesService = $ipRulesService;
        $this->ipRulesRequest = $request;
        $this->formFilter($request, $ipRulesRequest);
    }


    /**
     * 获取访问控制的列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getIpRulesList() {
        $result = $this->ipRulesService->getIpRulesList($this->ipRulesRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 增加访问控制
     *
     * @return int 自增ID
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addIpRules() {
        $result = $this->ipRulesService->addIpRules($this->ipRulesRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 编辑访问控制
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editIpRules() {
        $result = $this->ipRulesService->editIpRules($this->ipRulesRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 删除访问控制
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteIpRules() {
        $result = $this->ipRulesService->deleteIpRules($this->ipRulesRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 获取规则明细
     *
     * @return array
     */
    public function getOneIpRules(){
         $result = $this->ipRulesService->getOneIpRules($this->ipRulesRequest->all());
         return $this->returnResult($result);
     }


}
