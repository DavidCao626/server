<?php

namespace App\EofficeApp\System\Tag\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Tag\Requests\TagRequest;
use App\EofficeApp\System\Tag\Services\TagService;

/**
 * 系统下拉框控制器:提供系统下拉框相关外部请求并提供返回值
 *
 * @author qishaobo
 *
 * @since  2016-05-30 创建
 */
class TagController extends Controller
{
    /**
     * 系统下拉框service
     *
     * @var object
     */
    private $tagService;

    public function __construct(
        TagService $tagService,
        Request $request,
        TagRequest $tagRequest
    ) {
        parent::__construct();
        $this->request = $request;
        $this->tagService = $tagService;
        $this->formFilter($request, $tagRequest);
        $userInfo = $this->own;
        $this->userId = $userInfo['user_id'];
    }

    /**
     * 获取标签列表
     *
     * @return  array   查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-05-30 创建
     */
    public function getTagList()
    {
        $result = $this->tagService->getTagList($this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 添加标签
     *
     * @return  array   成功状态或错误码
     *
     * @author qishaobo
     *
     * @since  2016-05-30 创建
     */
    public function createTag()
    {
        $data = $this->request->all();
        $result = $this->tagService->createTag($data);
        return $this->returnResult($result);
    }

    /**
     * 编辑标签
     *
     * @param   int     $id 标签id
     *
     * @return  array       正确码或错误码
     *
     * @author qishaobo
     *
     * @since  2016-05-30 创建
     */
    public function editTag($id)
    {
        $result = $this->tagService->updateTag($this->request->all(), $id);
        return $this->returnResult($result);
    }

   /**
     * 删除标签
     *
     * @param   int     $id 标签id
     *
     * @return  bool        是否删除
     *
     * @author qishaobo
     *
     * @since  2016-05-30 创建
     */
    public function deleteTag($id)
    {
        $result = $this->tagService->deleteTag($id);
        return $this->returnResult($result);
    }

    /**
     * 外部获取标签列表
     *
     * @return  array   查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-05-30 创建
     */
    public function getTagExternalList()
    {
        $result = $this->tagService->getTagExternalList($this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

}