<?php


namespace App\EofficeApp\Elastic\Services\Document;

use App\EofficeApp\Elastic\Repositories\AttachmentContentRepository;
use DB;

class DocumentManager
{
    /**
     * @var AttachmentContentRepository $repository
     */
    private $repository;

    public function __construct()
    {
        $this->repository = app('App\EofficeApp\Elastic\Repositories\AttachmentContentRepository');
    }

    /**
     * 更新附件内容表
     *
     * @param string $category
     * @param string|int $entityId
     */
    public function updateAttachmentContentByCategoryAndEntityId($category, $entityId)
    {
        // 先清空原附件内容
        $this->deleteAttachmentByCateGoryAndEntityId($category, $entityId);

        // 读取附件内容
        $rows = $this->getAttachmentRelationTableInfo($category, $entityId);

        if (!$rows) {
            return;
        }

        foreach ($rows as $row) {
            $this->updateAttachmentContentByRow($row, $category, $entityId);
        }
    }

    /**
     * 清空原分类和id对应的附件内容
     *
     * @param string $category
     * @param string $entityId
     */
    private function deleteAttachmentByCateGoryAndEntityId($category, $entityId)
    {
        $where = [
            'attachment_relation_table' => [$category, '='],
            'attachment_relation_entity_id' => [$entityId, '='],
        ];

        return $this->repository->reallyDeleteByWhere($where, $category, $entityId);
    }

    /**
     * 更新AttachmentContent表记录
     *
     * @param object $info
     * @param string $category
     * @param string $entityId
     */
    private function updateAttachmentContentByRow($info, $category, $entityId)
    {
       $this->updateAttachmentRepositoryByAttachmentId($info->attachment_id, $category, $entityId);
    }

    /**
     * 根据附件id更新附件内容表
     *
     * @param string $attachmentId
     * @param string $category
     * @param string $entityId
     */
    public function updateAttachmentRepositoryByAttachmentId($attachmentId, $category = '', $entityId = '')
    {
        $relInfo = $this->getAttachmentRelTableInfo($attachmentId);
        if (!$relInfo) {
            return;
        }
        $attachmentInfo = $this->getAttachmentTableInfo($relInfo->year, $relInfo->month, $relInfo->rel_id);

        if (!$attachmentInfo) {
            return;
        }
        $filePath = getAttachmentDir().$attachmentInfo->attachment_path.$attachmentInfo->affect_attachment_name;

        $content = [
            'content' => '',
            'imageInfo' => '',
        ];
        if (is_file($filePath)) {
            $fileSize = filesize($filePath);
            // 临时处理大附件问题 内存溢出且es的 Elasticsearch\Connections\Connection::buildCurlCommand() 请求体过大
            if ($attachmentInfo->attachment_type == 'xls' || $attachmentInfo->attachment_type == 'xlsx') {
                // excel小于5M对内容进行读取
                // TODO  后续需分批存储
                if ($fileSize <= 5000000) {
                    $content =  $this->readContent(['type' => $attachmentInfo->attachment_type, 'path' => $filePath]);
                }
            } else {
                $content =  $this->readContent(['type' => $attachmentInfo->attachment_type, 'path' => $filePath]);
            }

            // 默认大小是1M 超过1M则暂时不存在 后续分批存储
            if (strlen($content['content']) >= 1024 * 1024 / 3) {
                $content['content'] = '';
            }
        } else {
            return;
        }

        // 所有附件均可查询
//        if (!$content['content'] && !$content['imageInfo']) {
//            return;
//        }

        // 更新 attachment_content 表
        $where = [
            'attachment_id' => $attachmentId,
        ];

        $data = [
            'name' => $attachmentInfo->attachment_name,
            'type' => $attachmentInfo->attachment_type,
            'size' => $attachmentInfo->attachment_size,
            'content' => trim($content['content']),
            'image_info' =>  json_encode($content['imageInfo']),
        ];

        if ($category && $entityId) {
            $relationInfo = [
                'attachment_relation_table' => $category,
                'attachment_relation_entity_id' => $entityId,
                'attachment_id' => $attachmentId,
            ];
            $data = array_merge($data, $relationInfo);
        }

        $this->repository->updateContentByCategoryAndEntityId($where, $data);
    }
    /**
     * 根据附件关联表和entity获取附件内容
     *
     * @param string $category
     * @param string|int $entityId
     * @deprecated 暂未使用
     *
     * @return array
     */
    public function getAttachmentContentByCategoryAndEntityId($category, $entityId)
    {
        // 判断是否为有效分类
        if (!in_array($category, ['document_content', 'email', 'flow_run', 'notify', 'personnel_files'])) {
            return ['content' => '', 'imageInfo' => []];
        }
        /**
         * 根据分类获取附件信息(附件类型和路径)
         *  1. 找到对应的 attachment_relataion_* 表
         *  2. 根据表中的根据表中 attachment_id 在 attachment_rel 中获取 年/月
         *  3. 根据 年/月 和 attachment_id 在 attachment_年_月 表中确定类型和路径
         */
        $info = $this->getAttachmentRelationTableInfo($category, $entityId);    // TODO 这里存在问题 暂未用到 后续优化
        $relInfo = $this->getAttachmentRelTableInfo($info->attachment_id);
        $attachmentInfo = $this->getAttachmentTableInfo($relInfo->year, $relInfo->month, $relInfo->rel_id);

        $filePath = getAttachmentDir().$attachmentInfo->attachment_path.$attachmentInfo->affect_attachment_name;

        return $this->readContent(['type' => $attachmentInfo->attachment_type, 'path' => $filePath]);
    }

    /**
     * 获取附件关联表信息
     *
     * @param string $category
     * @param string|int $entityId
     *
     * @return array
     */
    public function getAttachmentRelationTableInfo($category, $entityId)
    {
        $attachmentRelationTable = 'attachment_relataion_'.$category;
        $entity_id = $category !== 'flow_run' ? 'entity_id' : 'run_id';
        $info = DB::table($attachmentRelationTable)->where($entity_id, $entityId)->get()->toArray();

        return $info;
    }

    /**
     * 获取附件相关表信息
     *
     * @param string $attachmentId
     *
     * @return array
     */
    public function getAttachmentRelTableInfo($attachmentId)
    {
        $relInfo = DB::table('attachment_rel')->where('attachment_id', $attachmentId)->get()->first();

        return $relInfo;
    }

    /**
     * 获取附件目录表信息
     *
     * @param int $year
     * @param int $month
     * @param int $relId
     *
     * @return array
     */
    public function getAttachmentTableInfo($year, $month, $relId)
    {
        $attachmentInfoTable = 'attachment_'.$year.'_'.$month;
        $attachmentInfo = DB::table($attachmentInfoTable)->where('rel_id', $relId)->get()->first();

        return $attachmentInfo;
    }

    /**
     * 根据$attachmentInfo['type']和$attachmentInfo['path']获取附件内容
     *
     * @param array $attachmentInfo
     *
     * @return array
     */
    public function readContent($attachmentInfo)
    {
        $imageTypes = self::getImageTypes();//图片格式
        if (in_array($attachmentInfo['type'], $imageTypes)) {
            $type = 'image';
        } else {
            $type = $attachmentInfo['type'];
        }

        $reader = ReaderFactory::getReader($type);

        $content = '';
        if ($reader) {
            $content = $reader->readContent($attachmentInfo['path']);
        }

        $imageInfo = [];
        if ($type == 'image') {
            $imageInfo = $reader->getLastImageInfoOnce();
        }

        return ['content' => $content, 'imageInfo' => $imageInfo];
    }

    /**
     * 获取图片类型数组
     *
     * @return array
     */
    public static function getImageTypes()
    {
        return ['jpg', 'png'];
    }
}