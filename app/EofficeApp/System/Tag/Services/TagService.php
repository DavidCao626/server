<?php

namespace App\EofficeApp\System\Tag\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\System\Tag\Repositories\TagRepository;
use App\EofficeApp\System\Tag\Repositories\TagTypeRepository;

/**
 * 系统标签表Service类:提供系统标签表相关服务
 *
 * @author qishaobo
 *
 * @since  2016-05-27 创建
 */
class TagService extends BaseService
{
    /**
     * 系统标签表资源
     * @var object
     */
    private $TagRepository;

    public function __construct(
        TagRepository $tagRepository,
        TagTypeRepository $tagTypeRepository
    ) {
        $this->tagRepository = $tagRepository;
        $this->tagTypeRepository = $tagTypeRepository;
    }

    /**
     * 获取标签列表
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果
     *
     * @author qishaobo
     *
     * @since  2016-05-30 创建
     */
    public function getTagList($param = [], $userId = '')
    {
        $param = $this->parseParams($param);
        if (isset($param['search']['tag_type']) && $param['search']['tag_type'][0] == "private") {
            $param["search"]["tag_creator"] = [$userId];
        }
        $returnData = $this->response($this->tagRepository, 'getTagsTotal', 'getTags', $param);
        return $returnData;
    }

    /**
     * 添加标签
     *
     * @param  array $input 标签数据
     *
     * @return int|array    添加id或状态码
     *
     * @author qishaobo
     *
     * @since  2016-05-30 创建
     */
    public function createTag($data)
    {
        $userNamePyArray         = convert_pinyin($data["tag_name"]);
        $data["tag_name_py"]     = $userNamePyArray[0];
        $data["tag_name_zm"]     = $userNamePyArray[1];
        $data["tag_create_time"] = date("Y-m-d H:i:s",time());

        // 验证重复
        $tagType     = isset($data["tag_type"]) ? $data["tag_type"] : "";
        $tagCreator  = isset($data["tag_creator"]) ? $data["tag_creator"] : "";
        $tagName     = isset($data["tag_name"]) ? $data["tag_name"] : "";
        $uniqueParam = [];
        $uniqueParam["search"] = ["tag_name" => [$tagName]];
        $uniqueParam["returntype"] = "count";
        if($tagType == "private") {
            $uniqueParam["search"]["tag_type"] = [$tagType];
            $uniqueParam["search"]["tag_creator"] = [$tagCreator];
        } else if($tagType == "public") {
            $uniqueParam["search"]["tag_type"] = [$tagType];
        }
        $tagInfo = $this->tagRepository->getTagGeneral($uniqueParam);
        if($tagInfo > 0) {
            return ['code' => ['0x049001','tag']];
        }

        if ($tagObj = $this->tagRepository->insertData($data)) {
            return $tagObj->tag_id;
        }
        return ['code' => ['0x000003','common']];
    }

    /**
     * 编辑标签数据
     *
     * @param   array   $data 编辑数据
     * @param   int     $tagId    标签id
     *
     * @return  array          成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2016-05-30 创建
     */
    public function updateTag($data, $tagId)
    {
        $userNamePyArray     = convert_pinyin($data["tag_name"]);
        $data["tag_name_py"] = $userNamePyArray[0];
        $data["tag_name_zm"] = $userNamePyArray[1];

        // 验证重复
        $tagType     = isset($data["tag_type"]) ? $data["tag_type"] : "";
        $tagCreator  = isset($data["tag_creator"]) ? $data["tag_creator"] : "";
        $tagName     = isset($data["tag_name"]) ? $data["tag_name"] : "";
        $uniqueParam = [];
        $uniqueParam["search"] = ["tag_name" => [$tagName]];
        $uniqueParam["returntype"] = "count";
        if($tagType == "private") {
            $uniqueParam["search"]["tag_type"] = [$tagType];
            $uniqueParam["search"]["tag_creator"] = [$tagCreator];
        } else if($tagType == "public") {
            $uniqueParam["search"]["tag_type"] = [$tagType];
        }
        // $tagInfo = $this->tagRepository->getTagGeneral($uniqueParam);
        $result = $this->tagRepository->getUniqueTag($data);
        // 编辑时候的后端验证，有需要的时候在加上
        if(empty($result) || ($result && $tagId == $result->tag_id)) {
            if ($this->tagRepository->updateData($data, ['tag_id' => $tagId])) {
                return true;
            }
        } else {
            return ['code' => ['0x049001','tag']];
        }
        return ['code' => ['0x000003','common']];
    }

    /**
     * 删除标签
     *
     * @param   int     $tagId    标签id
     *
     * @return  array          成功状态或状态码
     *
     * @author qishaobo
     *
     * @since  2016-05-30 创建
     */
    public function deleteTag($tagIdString)
    {
        foreach (explode(',', trim($tagIdString,",")) as $key=>$tagId) {
            $this->tagRepository->deleteById($tagId);
        }
        return "1";
        // return ['code' => ['0x000003','common']];
    }


    /**
     * 获取标签列表
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果
     *
     * @author qishaobo
     *
     * @since  2016-05-30 创建
     */
    public function getTagExternalList($param = [], $userId = '')
    {
        $param = $this->parseParams($param);
        $publicParam = $param;
        $publicParam["search"]["tag_type"] = ["public"];
        $publicTagList = $this->tagRepository->getTags($publicParam);
        $privateParam = $param;
        $privateParam["search"]["tag_type"] = ["private"];
        $privateParam["search"]["tag_creator"] = [$userId];
        $privateTagList = $this->tagRepository->getTags($privateParam);
        return [
            "public" => $publicTagList,
            "private" => $privateTagList
        ];
    }


}