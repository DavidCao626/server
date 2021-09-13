<?php

namespace App\EofficeApp\EofficeCase\Services;

use App\EofficeApp\EofficeCase\Services\lib\Utils;

class Attachment
{
    // 工具类
    private $utils;

    // 附件绝对路径
    private $attachmentsDir;

    public function __construct()
    {
        $this->utils = new Utils();
        $config = include(__DIR__ . '/conf/config.php');
        $this->attachmentsDir = $config['eoffice_install_dir'];
        // 创建默认附件根目录
        if (!file_exists($this->attachmentsDir)) {
            mkdir($this->attachmentsDir, 0777, true);
        }
    }

    // 分配附件文件夹
    public function allocAttachmentName($caseId)
    {
        $caseId = str_replace('-', '_', $caseId);
        return 'attachments/eoffice_case_attachment_' . $caseId;
    }

    // 删除附件
    public function deleteAttachment($caseId)
    {
        $eofficeConfigIni = new EofficeConfigIni();
        $caseInfo         = $eofficeConfigIni->getCaseConfigInfo($caseId);
        $attachmentName   = $caseInfo['attachment_dir'] ?? '';

        if (!is_string($attachmentName) || empty($attachmentName)) {
            return false;
        }
        $this->utils->dir_del($this->attachmentsDir . DIRECTORY_SEPARATOR . $attachmentName);
    }

    /**
     * 新增附件
     */
    public function addAttachment($newAttachmentName, $extractDir)
    {
        $attachmentPathTo = $this->attachmentsDir . DIRECTORY_SEPARATOR . $newAttachmentName;
        // win
        @exec("move /y {$extractDir}attachment {$attachmentPathTo}");
        // linux
        // @exec("mv {$extractDir}attachment {$attachmentPathTo}");

        // 修改权限
        // @exec("chmod -R 777 {$attachmentPathTo}");
        return true;
    }

    // 拷贝access
    public function copyAttachment($attachmentName, $copyToPath)
    {
        $attachmentDir = $this->attachmentsDir . DIRECTORY_SEPARATOR . $attachmentName;
        $this->utils->dir_mkdir($copyToPath);
        if (file_exists($attachmentDir)) {
            $this->utils->dir_copy($attachmentDir, $copyToPath);
        }
        return true;
    }

    /**
     * 获取附件大小
     */
    public function getSize($attachmentDir)
    {
        $attachmentDir = $this->attachmentsDir . DIRECTORY_SEPARATOR . $attachmentDir;
        if (file_exists($attachmentDir)) {
            return $this->utils->dirSize($attachmentDir);
        }
        return 0;
    }
}
