<?php


namespace App\EofficeApp\Attachment\Services\AttachmentContent;

use Illuminate\Support\Facades\DB;

/**
 * 获取附件内容
 * TODO 暂时先支持txt和json 后面等es分支合并再支持其他格式
 * Class AttachmentContentManager
 * @package App\EofficeApp\Attachment\Services\AttachmentContent
 */
class AttachmentContentManager
{
    public function readContent($attachmentId)
    {
        $relInfo = $this->getAttachmentRelTableInfo($attachmentId);
        if (!$relInfo) {
            return '';
        }
        $attachmentInfo = $this->getAttachmentTableInfo($relInfo->year, $relInfo->month, $relInfo->rel_id);

        if (!$attachmentInfo) {
            return '';
        }
        $filePath = getAttachmentDir().$attachmentInfo->attachment_path.$attachmentInfo->affect_attachment_name;

        if (is_file($filePath)) {
            // TODO 暂时只支持txt和json
            if ($attachmentInfo->attachment_type == 'txt') {
                return $this->getTxtAttachment($filePath);
            } elseif ($attachmentInfo->attachment_type == 'json') {
                return $this->getJsonAttachment($filePath);
            }
        }

       return '';
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
     * 获取txt类型附件内容
     *
     * @param $realPath     //附件路劲
     * @return false|string|string[]|null
     */
    public function getTxtAttachment($realPath)
    {
        if (file_exists($realPath)) {
            try {
                $content = file_get_contents($realPath);
                return transEncoding($content, 'UTF-8');
            } catch (\Exception $e) {
                return '';
            }
        }

        return '';
    }

    /**
     * 获取json类型附件内容
     *
     * @param $realPath     //附件路劲
     * @return false|string|string[]|null
     */
    public function getJsonAttachment($realPath)
    {
        if (file_exists($realPath)) {
            try {
                $content = file_get_contents($realPath);
//                return json_decode($content, true);
                return $content;
            } catch (\Exception $e) {
                return '';
            }
        }

        return '';
    }
}