<?php

namespace App\EofficeApp\OpenApi\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\OpenApi\Requests\OpenApiRequest;
use App\EofficeApp\OpenApi\Services\OpenApiService;

class OpenApiController extends Controller
{
    private $openApiService;
    private $request;

    public function __construct(
        Request $request,
        OpenApiRequest $openApiRequest,
        OpenApiService $openApiService
    ) {
        parent::__construct();
        $this->request = $request;
        $this->openApiService = $openApiService;
        $this->formFilter($request, $openApiRequest);
    }

    /**
     * 获取openapi Token
     * @return array
     * @author [dosy]
     */
    public function openApiToken()
    {
        $param = $this->request->all();
        $data = $this->openApiService->openApiToken($param);
        return $this->returnResult($data);
    }

    /**
     * 通过refreshToken换取token
     * @return array
     * @author [dosy]
     */
    public function openApiRefreshToken(){
        $param = $this->request->all();
        $data = $this->openApiService->openApiRefreshToken($param);
        return $this->returnResult($data);
    }

    /**
     * 添加应用
     * @return array
     * @author [dosy]
     */
    public function setApplication()
    {
        $param = $this->request->all();
        $data = $this->openApiService->setApplication($param);
        return $this->returnResult($data);
    }

    /**
     * 获取应用Secret
     * @return array
     * @author [dosy]
     */
    public function getApplicationSecret()
    {
        $data = $this->openApiService->getApplicationSecret();
        return $this->returnResult($data);
    }

    /**
     * 刷新应用秘钥
     * @param $id
     * @return array
     * @author [dosy]
     */
    public function refreshApplicationSecret($id)
    {
        $userId = $this->own['user_id'];
        $data = $this->openApiService->refreshApplicationSecret($id, $userId);
        return $this->returnResult($data);
    }

    /**
     * 获取应用列表
     * @return array
     * @author [dosy]
     */
    public function getApplicationList()
    {
        $userId = $this->own['user_id'];
        $param = $this->request->all();
        $data = $this->openApiService->getApplicationList($param, $userId);
        return $this->returnResult($data);
    }

    /**
     * 注册应用
     * @return array
     * @author [dosy]
     */
    public function registerApplication()
    {
        $userId = $this->own['user_id'];
        $param = $this->request->all();
        $data = $this->openApiService->registerApplication($param, $userId);
        return $this->returnResult($data);
    }

    /**
     * 删除应用
     * @param $id
     * @return array
     * @author [dosy]
     */
    public function deleteApplication($id)
    {
        $userId = $this->own['user_id'];
        $data = $this->openApiService->deleteApplication($id, $userId);
        return $this->returnResult($data);
    }

    /**
     * 获取应用详情
     * @param $id
     * @return array
     * @author [dosy]
     */
    public function getApplicationDetail($id)
    {
        $userId = $this->own['user_id'];
        $data = $this->openApiService->getApplicationDetail($id, $userId);
        return $this->returnResult($data);
    }

    /**
     * 获取单应用日志列表
     * @return array
     * @author [dosy]
     */
    public function getApplicationLogList()
    {
        $param = $this->request->all();
        $data = $this->openApiService->getApplicationLogList($param);
        return $this->returnResult($data);
    }

    /**
     * 获取案例列表
     * @return array
     * @author [dosy]
     */
    public function getOpenCase()
    {
        $param = $this->request->all();
        $data = $this->openApiService->getOpenCase($param);
        return $this->returnResult($data);
    }

    /**
     * 获取单个案例
     * @param $id
     * @return array
     * @author [dosy]
     */
    public function getOneCase($id)
    {
        $data = $this->openApiService->getOneCase($id);
        return $this->returnResult($data);
    }
}
