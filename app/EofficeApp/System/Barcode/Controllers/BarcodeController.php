<?php

namespace App\EofficeApp\System\Barcode\Controllers;

use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Barcode\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Milon\Barcode\Facades\DNS1DFacade;
use Ramsey\Uuid\Uuid;

class BarcodeController extends Controller
{

    /**
     * @var string
     */
    private $barcodeService;
    /**
     * @var \Laravel\Lumen\Application|mixed
     */
    private $attachmentService;
    /**
     * @var BarcodeRequest
     */
    private $barcodeRequest;
    /**
     * @var Request
     */
    private $request;

    public function __construct(
        BarcodeService $barcodeService,
        AttachmentService $attachmentService
    )
    {
        parent::__construct();
        $this->attachmentService = $attachmentService;
        $this->barcodeService = $barcodeService;
        $userInfo = $this->own;
        $this->userId = isset($userInfo['user_id']) ? $userInfo['user_id'] : "";
    }


    /**
     * 预请求判断条形码能否生成，是否符合规范
     * @param Request $request
     * @return array
     */
    public function preGenerateBarcode(Request $request)
    {
        return $this->barcodeService->preGenerateBarcode($request->all());
    }


    /**
     * 生成指定规格的条形码
     * @param Request $request
     * @return array
     */
    public function generateBarcode(Request $request)
    {
        $params = $request->all();
        $params['user_id'] = $this->userId;
        $result = $this->barcodeService->generateBarcode($params);
        return $this->returnResult($result);
    }

    /**
     * 根据条码数字获取对应的存储内容
     * @param Request $request
     * @return array
     */
    public function getBarcodeValue(Request $request)
    {
        return $this->returnResult($this->barcodeService->getBarcodeValue($request->all()));
    }

    /**
     * 批量获取条码对应存储内容
     * @param Request $request
     * @return array
     */
    public function batchGetBarcodeValue(Request $request)
    {
        return $this->returnResult($this->barcodeService->batchGetBarcodeValue($request->all()));
    }



    /**
     * 生成二维码
     * @param Request $request
     * @return array
     */
    public function generateQrCode(Request $request)
    {
        $params = $request->all();
        $params['user_id'] = $this->userId;
        return $this->returnResult($this->barcodeService->generateQrcode($params));
    }



}
