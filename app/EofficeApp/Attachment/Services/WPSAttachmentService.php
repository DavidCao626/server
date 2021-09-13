<?php


namespace App\EofficeApp\Attachment\Services;


use App\EofficeApp\Attachment\Repositories\AttachmentRelRepository;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Document\Services\WPS\WPSAuthService;
use Illuminate\Support\Facades\Log;

class WPSAttachmentService extends BaseService
{
    /**
     * @var AttachmentRelRepository $attachmentRelRepository
     */
    private $attachmentRelRepository;

    public function __construct(AttachmentRelRepository $repository)
    {
        parent::__construct();
        $this->attachmentRelRepository = $repository;
    }

    /**
     * 保存wps创建的模板文档
     *
     * @return bool
     */
    public function saveNewTemplateFile($attachmentId, $file, $fileName, $userId): bool
    {
        /**
         * 1. 验证文件类型 TODO
         * 2. 本地保存附件
         * 3. 插入相关数据
         */
        try {
            // 存储
            /** @var AttachmentService $attachmentService */
            $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
            $customDir = $attachmentService->createCustomDir($attachmentId);
            if (isset($customDir['code'])) {
                throw new \Exception('非法附件ID');
            }
            $md5FileName = $attachmentService->getMd5FileName($fileName);
            $fileType = pathinfo($fileName, PATHINFO_EXTENSION);
            $limitSuffix = config('eoffice.uploadDeniedExtensions');
            if (in_array($fileType, $limitSuffix)) {
                throw new \Exception('非法类型');
            }
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

    /**
     * 创建本地文档
     *
     * @param string $attachmentId
     * @param string $type
     * @param string $userId
     *
     * @return bool
     */
    public function createLocalDocument($attachmentId, $type, $userId): bool
    {
        try {
            $suffix = $type === WPSAuthService::W_TYPE ? WPSAuthService::WORD_SUFFIX : WPSAuthService::EXCEL_SUFFIX;
            $fileName = trans('document.new').$suffix;
            /** @var AttachmentService $attachmentService */
            $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
            $customDir = $attachmentService->createCustomDir($attachmentId);
            if (isset($customDir['code'])) {
                throw new \Exception('非法附件ID');
            }
            $md5FileName = $attachmentService->getMd5FileName($fileName);
            $fileType = pathinfo($fileName, PATHINFO_EXTENSION);
            $fileFullName = $customDir.DIRECTORY_SEPARATOR.$md5FileName;
            $attachmentPaths = $attachmentService->parseAttachmentPath($fileFullName);
            if ($type === WPSAuthService::W_TYPE ) {
                $phpWord = new \PhpOffice\PhpWord\PhpWord();
                $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                $objWriter->save($fileFullName);
            } else {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
                $writer->save($fileFullName);
            }

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