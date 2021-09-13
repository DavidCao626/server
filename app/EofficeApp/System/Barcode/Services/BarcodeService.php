<?php

namespace App\EofficeApp\System\Barcode\Services;

use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\System\Barcode\Repositories\BarcodeValueRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Milon\Barcode\Facades\DNS1DFacade;

class BarcodeService extends BaseService
{

    /**
     * @var BarcodeValueRepository
     */
    private $barcodeValueRepository;
    /**
     * @var AttachmentService
     */
    private $attachmentService;

    public function __construct(BarcodeValueRepository $barcodeValueRepository, AttachmentService $attachmentService)
    {
        parent::__construct();
        $this->barcodeValueRepository = $barcodeValueRepository;
        $this->attachmentService = $attachmentService;
        $this->setStorPath();
    }

    /**
     * [setStorPath 设置barCode临时路径]
     */
    public function setStorPath()
    {
        $storPath = public_path('barCode');
        if (!is_dir($storPath)) {
            mkdir($storPath, 0777, true);
            chmod($storPath, 0777);
        }
        DNS1DFacade::setStorPath($storPath);
    }

    /**
     * 生成条形码
     * @param $param
     * @return array|mixed|\string[][]
     */
    public function generateBarcode($param)
    {
        $type = $param['type']; // 类型。1 自定义数据源类型 0 标准条形码类型
        switch ($type) {
            case 1:
                return $this->generateBarcodeForCustomData($param);
                break;
            case 0:
                return $this->generateBarcodeForStandardType($param);
                break;
            default:
                break;
        }
        return [];
    }


    /**
     * 自定义数据源条码生成
     * @param $param
     * @return mixed|\string[][]
     */
    private function generateBarcodeForCustomData($param)
    {
        $value = $param['value'];
        $hideCode = $param['hide_code'];
        $result = $this->barcodeValueRepository->getOneFieldInfo(['value' => [$value], 'hide_code' => $hideCode]);
        if (!$result) {
            $key = $this->getUniqueKey();
            // 使用约定好的 EAN8 规格作为默认规格类型存储自定义数据源
            try {
                DNS1DFacade::setBarcode($key, 'EAN8');
                $code = $key = (DNS1DFacade::getBarcodeArray())['code'];
                $fileName = DNS1DFacade::getBarcodePNGPath($key, 'EAN8', 2, 100, array(0, 0, 0), boolval($hideCode));
            } catch (\Exception $exception) {
                Log::error($exception->getMessage());
                return ['code' => ['0x015040', 'system']];
            }
            $file = [
                'attachment_name' => $fileName,
                'temp_src_file' => public_path($fileName),
                'user_id' => $param['user_id'] ?? ''
            ];
            $attachment_id = $this->addAttachment($file);
            if (isset($attachment_id['code'])) {
                return $attachment_id;
            }
            $this->barcodeValueRepository->insertData([
                'key' => $code,
                'value' => $value,
                'attachment_id' => $attachment_id,
                'hide_code' => $hideCode
            ]);
            return [
                'attachment_id' => $attachment_id
            ];
        }
        return [
            'attachment_id' => $result->attachment_id,
        ];

    }

    /**
     * 标准规格条码生成
     * @param $param
     * @return mixed|\string[][]
     */
    private function generateBarcodeForStandardType($param)
    {
        $barcodeType = $param['barcode_type'];
        $value = $param['value'];
        $hideCode = (boolean)$param['hide_code'];
        try {
            $fileName = DNS1DFacade::getBarcodePNGPath($value, $barcodeType, 2, 100, array(0, 0, 0), $hideCode);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return ['code' => ['0x015040', 'system']];
        }
        $file = [
            'attachment_name' => $fileName,
            'temp_src_file' => public_path($fileName),
            'user_id' => $param['user_id'] ?? ''
        ];
        $attachment_id = $this->addAttachment($file);

        if (isset($attachment_id['code'])) {
            return $attachment_id;
        }

        return [
            'attachment_id' => $attachment_id,
        ];
    }

    /**
     * 获取自定义数据源条码的值
     * @param $param
     * @return |null
     */
    public function getBarcodeValue($param)
    {
        $key = $param['key'];
        $result = $this->barcodeValueRepository->getOneFieldInfo(['key' => [$key]]);
        if ($result) {
            return ['value' => $result->value];
        }
        return ['value' => null];
    }

    /**
     * 批量获取自定义数据条码的值
     * @param $param
     * @return array[]|null[]
     */
    public function batchGetBarcodeValue($param)
    {
        $key = $param['key'];
        $result = $this->barcodeValueRepository->getFieldInfo(null, ['value'], ['key' => [$key, 'in']]);
        if (empty($result)) {
            return ['value' => null];
        }
        $data = [];
        foreach ($result as $key => $value) {
            $data[] = $value['value'];
        }
        return ['value' => $data];
    }



    /**
     * 自定义数据源使用EAN8作为默认编码
     * @return int
     */
    private function getUniqueKey()
    {
        $key = mt_rand(1000000, 9999999);
        $result = $this->barcodeValueRepository->getOneFieldInfo(['key' => [$key]]);
        if ($result) {
            $this->getUniqueKey();
        }
        return $key;
    }

    /**
     * 生成二维码
     * @param $param
     * @return mixed
     */
    public function generateQrcode($param)
    {
        $url = $param['url'] ?? '';
        // 二维码
        $fileName = session_create_id() . '.png';
        if (!File::exists(public_path($fileName))) {
            try {
                \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size('200')->margin(1)->generate($url, public_path($fileName));
            } catch (\Exception $exception) {
                Log::error($exception->getMessage());
                return ['code' => ['0x015041', 'system']];
            }
        }
        $file = [
            'attachment_name' => $fileName,
            'temp_src_file' => public_path($fileName),
            'user_id' => $param['user_id'] ?? ''
        ];

        $attachment_id = $this->addAttachment($file);

        if (isset($attachment_id['code'])) {
            return $attachment_id;
        }

        return [
            'attachment_id' => $attachment_id,
        ];
    }

    /**
     * 检验是否能正确生成符合规范的条形码
     * @param $param
     * @return string[]|\string[][]
     */
    public function preGenerateBarcode($param)
    {
        $barcodeType = $param['barcode_type'];
        $value = $param['value'];
        try {
            $fileName = DNS1DFacade::getBarcodePNGPath($value, $barcodeType, 2, 100, array(0, 0, 0));
        } catch (\Exception $exception) {
            return ['code' => ['0x015040', 'system']];
        }
        @unlink($fileName);
        return ['msg' => 'success'];
    }

    /**
     * 以下附件相关的代码都是临时代码，等待通用的附件方法开发好之后，重构
     * @param $data
     * @return mixed
     */
    private function addAttachment($data)
    {
        if (empty($data['user_id'])) {
            return '';
        }
        $attachmentId = $this->attachmentService->makeAttachmentId($data['user_id']);
        $md5FileName = $this->getMd5FileName($data['attachment_name']);
        $customDir = $this->attachmentService->createCustomDir($attachmentId);
        if (isset($customDir['code'])) {
            return $customDir;
        }
        $newFullFileName =  $customDir. DIRECTORY_SEPARATOR . $md5FileName;
        rename($data['temp_src_file'], $newFullFileName); // 移动到新目录
        $fileType = pathinfo($newFullFileName, PATHINFO_EXTENSION);
        $attachmentPaths = $this->attachmentService->parseAttachmentPath($newFullFileName);
        $attachmentInfo = [
            "attachment_id" => $attachmentId,
            "attachment_name" => $data['attachment_name'],
            "affect_attachment_name" => $md5FileName,
            'new_full_file_name' => $newFullFileName,
            "thumb_attachment_name" => $this->generateImageThumb($fileType, null, $newFullFileName),
            "attachment_size" => filesize($newFullFileName),
            "attachment_type" => $fileType,
            "attachment_create_user" => $data['user_id'],
            "attachment_base_path" => $attachmentPaths[0],
            "attachment_path" => $attachmentPaths[1],
            "attachment_mark" => $this->getAttachmentMark($fileType),
            "relation_table" => 'document_content',
            "rel_table_code" => $this->getRelationTableCode('document_content'),
        ];
        $this->attachmentService->handleAttachmentDataTerminal($attachmentInfo);
        return $attachmentId;
    }

    private function getMd5FileName($gbkFileName)
    {
        $name = substr($gbkFileName, 0, strrpos($gbkFileName, "."));

        return md5(time() . $name) . strrchr($gbkFileName, '.');
    }

    private function generateImageThumb($fileType, $data, $sourcFile)
    {
        if (in_array($fileType, config('eoffice.uploadImages'))) {
            $thumbWidth = isset($data["thumbWidth"]) && $data["thumbWidth"] ? $data["thumbWidth"] : config('eoffice.thumbWidth', 100);
            $thumbHight = isset($data["thumbHight"]) && $data["thumbHight"] ? $data["thumbHight"] : config('eoffice.thumbHight', 40);
            $thumbPrefix = config('eoffice.thumbPrefix', "thumb_");
            return scaleImage($sourcFile, $thumbWidth, $thumbHight, $thumbPrefix);
        }

        return '';
    }

    private function getAttachmentMark($fileType)
    {
        $uploadFileStatus = config('eoffice.uploadFileStatus');

        foreach ($uploadFileStatus as $key => $status) {
            if (in_array(strtolower($fileType), $status)) {
                return $key;
            }
        }

        return 9;
    }

    private function getRelationTableCode($tableName)
    {
        return md5($tableName);
    }
}
