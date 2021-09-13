<?php


namespace App\EofficeApp\Attachment\Services;


use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Facades\Log;

class WPSNpApiService extends BaseService
{
    const WPS_NPAPI_DOCUMENT_TYPE = 'wps'; // Word类型
    const WPS_NPAPI_EXCEL_TYPE = 'et'; // Excel类型
    const WPS_NPAPI_PPT_TYPE = 'wpp'; // PPT类型

    public $attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';

    /**
     * 初始化wps的npapi插件所需缓存
     *
     * @param string $attachmentId
     *
     * @return array 附件信息
     */
    public function wpsNpApiInit($attachmentId)
    {
        /** @var AttachmentService $attachmentService */
        $attachmentService = app($this->attachmentService);
        $attachmentInfo = $attachmentService->getOneAttachmentById($attachmentId);
        if (!$attachmentInfo) {
            return ['code' => ['0x011017', 'upload']];
        }

        // 使用$_SERVER['DOCUMENT_ROOT'] 作为根目录  $_SERVER['HTTP_HOST']为域名  $_SERVER['REQUEST_SCHEME']
        $attachmentFile = $attachmentInfo['temp_src_file'];
        $cacheDir = $_SERVER['DOCUMENT_ROOT'].'/cache/'.$attachmentId;

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        $cacheFile = $cacheDir.'/'.$attachmentInfo['affect_attachment_name'];
        if (file_exists($attachmentFile) == false) {
            return ['code' => ['0x011017', 'upload']];
        }

        $result = copy($attachmentFile, $cacheFile);

        if ($result) {
            // 缓存文档
//            $domain =  get_system_param('wps_npapi_domain', '');
//            $remoteFilePath = $domain.'/cache/'.$attachmentId.'/'.$attachmentInfo['affect_attachment_name'];
            $remoteFilePath = '/cache/'.$attachmentId.'/'.$attachmentInfo['affect_attachment_name'];

            // 获取文档类型, npapi插件根据 wps/et/wpp 有不同的初始化插件
            $attachmentType = isset($attachmentInfo['attachment_type']) ? $attachmentInfo['attachment_type'] : '';
            $attachmentType = strtolower($attachmentType);
            $wordType = ['doc', 'wps', 'docx','dotm'];
            $excelType = ['xls','et', 'xlsx'];
            $pptType = ['ppt', 'pptx', 'dpt', 'dps'];

            if (in_array($attachmentType, $wordType)) {
                $documentType = self::WPS_NPAPI_DOCUMENT_TYPE;
            } elseif (in_array($attachmentType, $excelType)) {
                $documentType = self::WPS_NPAPI_EXCEL_TYPE;
            } elseif (in_array($attachmentType, $pptType)) {
                $documentType = self::WPS_NPAPI_PPT_TYPE;
            } else {
                return ['code' => ['0x011017', 'upload']];
            }

            $uploadScripts = $this->getUploadScripts();
            $info = array_merge([
                'remoteFilePath' => $remoteFilePath,  // 可访问的服务端文档地址
                'documentType' => $documentType,
            ], $uploadScripts);

            return $info;
        }

        return ['code' => ['0x011017', 'upload']];
    }

    /**
     * 将www下生成的缓存保存到附件目录
     */
    public function saveCacheFile($attachmentId)
    {
        /** @var AttachmentService $attachmentService */
        $attachmentService = app($this->attachmentService);
        $attachmentInfo = $attachmentService->getOneAttachmentById($attachmentId);
        $content = file_get_contents("php://input");
        // 将上传文档替换缓存目录中的文档
        $cacheDir = $_SERVER['DOCUMENT_ROOT'].'/cache/'.$attachmentId;
        $cacheFile = $cacheDir.'/'.$attachmentInfo['affect_attachment_name'];
        if (is_file($cacheFile)) {
            file_put_contents($cacheFile, $content);
            // 将缓存目录文档移动到附件目录中
            if (copy($cacheFile, $attachmentInfo['temp_src_file'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * 清空根目录下缓存目录中指定附件缓存
     *
     * @param string $attachmentId
     */
    public function emptyTargetCacheDir($attachmentId)
    {
        /** @var AttachmentService $attachmentService */
        $attachmentService = app($this->attachmentService);
        $attachmentInfo = $attachmentService->getOneAttachmentById($attachmentId);
        if ($attachmentInfo) {
            $cacheDir = $_SERVER['DOCUMENT_ROOT'].'/cache/'.$attachmentId;
            $cacheFile = $cacheDir.'/'.$attachmentInfo['affect_attachment_name'];
            if (is_file($cacheFile)) {
                unlink($cacheFile);
                rmdir($cacheDir);

                return true;
            }
        }

        return false;
    }

    /**
     * 获取np初始化信息, 包括
     *  1. 上传脚本
     *
     * @return array
     */
    public function getWpsNpInitInfo()
    {
        return $this->getUploadScripts();
    }

    /**
     * 获取上传脚本
     */
    public function getUploadScripts()
    {
        $indexScript = $_SERVER['SCRIPT_NAME'];
        $indexScriptPath = preg_replace('/index.php/', '', $indexScript);
        $windowsUploadScript = $indexScriptPath.'integrationCenter/onlineRead/wpsNpApi/upload_w.php';
        $linuxUploadScript = $indexScriptPath.'integrationCenter/onlineRead/wpsNpApi/upload_l.php';

        return [
            'remoteWindowsUploadScript' => $windowsUploadScript,    // windows下文档上传脚本
            'remoteLinuxUploadScript' => $linuxUploadScript,        // linux下文档上传脚本
        ];
    }

    /**
     * 保存附件
     *
     * @return bool
     */
    public function dealCreateAttachment($attachmentId, $fileName, $userId, $file = null): bool
    {
        try {
            // 存储
            /** @var AttachmentService $attachmentService */
            $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
            $customDir = $attachmentService->createCustomDir($attachmentId);
            if (isset($customDir['code'])) {
                throw new \Exception(trans('attachment.0x000003'));
            }
            $md5FileName = $attachmentService->getMd5FileName($fileName);
            $fileType = pathinfo($fileName, PATHINFO_EXTENSION);
            $fileFullName = $customDir.DIRECTORY_SEPARATOR.$md5FileName;
            $attachmentPaths = $attachmentService->parseAttachmentPath($fileFullName);
            
            $out = @fopen($fileFullName, "wb");
            if (!empty($file) && is_uploaded_file($file)) {
                $in = @fopen($file, "rb");
            } else {
                $in = @fopen("php://input", "rb");
            }
            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }
            @fclose($out);
            @fclose($in);

            // 插入数据
            $attachmentInfo = [
                "attachment_id" => $attachmentId,
                "attachment_name" => $fileName,
                "affect_attachment_name" => $md5FileName,
                'new_full_file_name' => $fileFullName,
                "thumb_attachment_name" => '',
                "attachment_size" => filesize($fileFullName),
                "attachment_type" => $fileType,
                "attachment_create_user" => $userId,
                "attachment_base_path" => $attachmentPaths[0],
                "attachment_path" => $attachmentPaths[1],
                "attachment_mark" => $attachmentService->getAttachmentMark($fileType),
                "relation_table" => $data["attachment_table"] ?? '',
                "rel_table_code" => ''
            ];
            $res = $attachmentService->handleAttachmentDataTerminal($attachmentInfo);

            return (bool)$res;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return false;
        }
    }
}