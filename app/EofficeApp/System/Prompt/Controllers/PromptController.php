<?php

namespace App\EofficeApp\System\Prompt\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Prompt\Services\PromptService;
use App\EofficeApp\System\Prompt\Requests\PromptRequest;

/**
 * 提示语控制器:提供提示语相关外部请求并提供返回值
 *
 * @author qishaobo
 *
 * @since  2016-12-28 创建
 */
class PromptController extends Controller
{
    /**
     * 提示语service
     *
     * @var object
     */
    private $systemComboboxService;

    public function __construct(
        PromptService $promptService,
        Request $request,
        PromptRequest $promptRequest
    ) {
        parent::__construct();
        $this->request = $request;
        $this->promptService = $promptService;
        // $this->formFilter($request, $promptRequest);
    }

    /**
     * 获取提示语类型列表
     *
     * @return  array   查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function getPromptTypes()
    {
        $result = $this->promptService->getPromptTypes($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 查询提示语类型详情
     *
     * @param   int     $typeId 提示语类型id
     *
     * @return  array   成功状态或错误码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function getPromptType($typeId)
    {
        $result = $this->promptService->getPromptType($typeId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 添加提示语类型
     *
     * @return  array   成功状态或错误码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function createPromptType()
    {
        $data = [
            'prompt_type_name' => $this->request->input('prompt_type_name'),
            'prompt_type_status' => $this->request->input('prompt_type_status', 1)
        ];

        $result = $this->promptService->createPromptType($data);
        return $this->returnResult($result);
    }

    /**
     * 编辑提示语类型
     *
     * @param   int     $typeId 提示语类型id
     *
     * @return  array       成功状态或错误码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function editPromptType($typeId)
    {
        $input = $this->request->all();

        if (isset($input['prompt_type_name'])) {
            $data['prompt_type_name'] = $input['prompt_type_name'];
        }

        if (isset($input['prompt_type_status'])) {
            $data['prompt_type_status'] = $input['prompt_type_status'];
        }

        $result = $this->promptService->updatePromptType($data, $typeId);
        return $this->returnResult($result);
    }

    /**
     * 删除提示语类型
     *
     * @param   int     $typeId 提示语类型id
     *
     * @return  array       查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function deletePromptType($typeId)
    {
        $result = $this->promptService->deletePromptType($typeId);
        return $this->returnResult($result);
    }

    /**
     * 获取提示语列表
     *
     * @return  array   查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function getPrompts()
    {
        $result = $this->promptService->getPrompts($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取登录提示语列表
     *
     * @return  array   查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-12-30 创建
     */
    public function getLoginPrompts()
    {
        $result = $this->promptService->getLoginPrompts();
        return $this->returnResult($result);
    }

    /**
     * 新建提示语
     *
     * @return  array       正确码或错误码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function addPrompt()
    {
        $data = $this->request->all();
        $result = $this->promptService->createPrompt($data);
        return $this->returnResult($result);
    }

   /**
     * 删除提示语
     *
     * @param   int     $id 提示语id
     *
     * @return  bool        是否删除
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function deletePrompt($id)
    {
        $result = $this->promptService->deletePrompt($id);
        return $this->returnResult($result);
    }

    /**
     * 编辑提示语
     *
     * @param   int     $id 提示语id
     *
     * @return  array       正确码或错误码
     *
     * @author qishaobo
     *
     * @since  2016-12-28 创建
     */
    public function editPrompt($id)
    {
        $data = $this->request->only(['prompt_content', 'prompt_type_id']);
        $result = $this->promptService->updatePrompt($data, $id);
        return $this->returnResult($result);
    }

    /**
     * 获取模块新手指引触发标识
     *
     * @param   $route     路由，用英文逗号分隔，这里用于标识在哪个页面
     *
     * @return  int        0:需要提示，1:不需要提示
     *
     * @author miaochenchen
     *
     * @since  2020-04-08 创建
     */
    public function getNewUserGuideFlag($route)
    {
        $currentUserId = $this->own['user_id'] ?? '';
        if (empty($currentUserId) || empty($route)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        return $this->promptService->getNewUserGuideFlag($currentUserId, $route);
    }

    /**
     * 设置模块新手指引触发标识
     *
     * @param   {route}     路由，用英文逗号分隔，这里用于标识在哪个页面
     *
     * @return  boolean
     *
     * @author miaochenchen
     *
     * @since  2020-04-08 创建
     */
    public function setNewUserGuideFlag()
    {
        $data = $this->request->all();
        $currentUserId = $this->own['user_id'] ?? '';
        $route = $data['route'] ?? '';
        if (empty($currentUserId) || empty($route)) {
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $data = [
            'user_id' => $currentUserId,
            'route'   => $route,
        ];
        $result = $this->promptService->setNewUserGuideFlag($data);
        return $this->returnResult($result);
    }
}