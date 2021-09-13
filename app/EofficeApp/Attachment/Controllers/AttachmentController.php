<?php

namespace App\EofficeApp\Attachment\Controllers;

use App\EofficeApp\Attachment\Requests\AttachmentRequest;
use App\EofficeApp\Attachment\Services\AttachmentContent\AttachmentContentManager;
use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Attachment\Services\FileConvert\WPSFileConvertService;
use App\EofficeApp\Attachment\Services\WPSNpApiService;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Document\Services\WPS\WPSAuthService;
use App\EofficeApp\System\Params\Services\SystemParamsService;
use Illuminate\Http\Request;

class AttachmentController extends Controller
{

    private $attachmentService;
    private $attachmentRequest;

    public function __construct(
        Request $request, AttachmentService $attachmentService, AttachmentRequest $attachmentRequest
    )
    {
        parent::__construct();
        $this->attachmentService = $attachmentService;
        $this->attachmentRequest = $request;
        $this->formFilter($request, $attachmentRequest);
    }

    /**
     * 附件上传
     *
     * @apiTitle 附件上传
     * @param {string} name 文件名称 传入此值则使用此值作为文件名称，否则根据上传的文件生成名称
     * @param {int} chunk 当前上传切片的索引，从0开始
     * @param {int} chunks 所有上传切片的个数
     * @param {string} attachment_table 关联表名称，可选，传入此值则将此附件与对应的表关联
     * @param {file} Filedata 文件上传name的值，固定为Filedata，上传的文件信息为$_FILES['Filedata']
     *
     * @paramExample {json} 参数示例
     * {
     *  "name": "图20160314105710.png",
     *  "chunk": "0",
     *  "chunks": "1",
     *  "attachment_table": "assets"
     * }
     *
     * @success {boolean} status(1) 上传成功
     * @success {array} data 返回附件id和名称
     *
     * @successExample {json} Success-Response:
     * {
     *  "status": 1,
     *  "data": {
     *      "attachment_id": "61bb77aa7c4eb8e3bb8b20c0ac72ab1e", //附件id
     *      "attachment_name": "图20160314105710.png"  //附件原名称
     *   }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function upload()
    {
        return $this->returnResult($this->attachmentService->upload($this->attachmentRequest->all(), $this->own, $_FILES));
    }

    /**
     * 文件复制
     * @return type
     */
    public function copy()
    {
        return $this->returnResult($this->attachmentService->attachmentCopy($this->attachmentRequest->all(), $this->own));
    }


    /**
     * 判断第三方平台的OCR接口有没有配置
     * @return array
     */
    public function getOcrConfig()
    {
        return $this->returnResult($this->attachmentService->getOcrConfig());
    }

    /**
     * 获取图片附件的 ocr 识别信息
     * @return \App\EofficeApp\Base\json
     */
    public function ocr()
    {
        return $this->returnResult($this->attachmentService->getOcrInfo($this->attachmentRequest->all()));
    }

    /**
     * 附件替换
     *
     * @return boolean
     */
    public function attachmentReplace()
    {
        return $this->returnResult($this->attachmentService->attachmentReplace($this->attachmentRequest->all()));
    }

    /**
     * 获取附件列表
     *
     * @apiTitle 获取附件列表
     * @param {array} attach_ids 附件ID集合
     *
     * @paramExample {json} 参数示例
     * {
     *  "attach_ids": ["61bb77aa7c4eb8e3bb8b20c0ac72ab1e", "61bb77aa7c4eb8e3bb8b20c0ac72ab1e", ...]
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @success {array} data 返回附件
     *
     * @successExample {json} Success-Response:
     * {
     *  "status": 1,
     *  "data": [
     * [
     * 'id' => 1600, //主键id
     * 'attachment_id' => '61bb77aa7c4eb8e3bb8b20c0ac72ab1e', //附件id
     * 'attachment_name' => '图20160314105710.png', //附件原名称
     * 'affect_attachment_name' => '9a28e37c0a9bae896ea2fa9bce07fe1c.png', //附件存储名称防止别人去直接拷贝
     * 'attachment_relative_path' => '2018/05/31/61bb77aa7c4eb8e3bb8b20c0ac72ab1e/', //附件相对目录
     * 'attachment_type' => 'png', //附件后缀
     * 'attachment_mark' => 1, //附件类型
     * 'category' => 1, //附件类型
     * 'relation_table' => "document_content", //附件关联表
     * 'thumb_attachment_name' => "data:image/png;base64,iVBORw0K.....==", //附件缩略图
     * 'attachment_size' => 2250, //附件大小
     * 'attachment_time' => "2018-05-31 16:40:30", //上传时间
     * 'attachment_path' => "attachment\index\61bb77aa7c4eb8e3bb8b20c0ac72ab1e", //附件下载路由
     * ],
     * ......
     * ]
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function getAttachments()
    {
        return $this->returnResult($this->attachmentService->getAttachments($this->attachmentRequest->all(),true));
    }

    /**
     * wps的国产化插件npapi初始化
     */
    public function wpsNpApiInit()
    {
        /**
         * 根据附件位置在www目录中生成缓存文档以便前端访问
         *  1. 获取附件信息
         *  2. 复制到www的cache目录中
         *  3. 返回可远程访问地址
         */
        $attachmentId = $this->attachmentRequest->request->get('attachmentId', '');
        if (!$attachmentId) {
            return $this->returnResult(['code' => ['0x011017', 'upload']]); //文件不存在
        }
        /** @var WPSNpApiService $wpsNpApiService */
        $wpsNpApiService = app('App\EofficeApp\Attachment\Services\WPSNpApiService');

        return $this->returnResult($wpsNpApiService->wpsNpApiInit($attachmentId));
    }

    /**
     * 清空wps国产化插件生成的对应缓存
     */
    public function emptyTargetCacheDir()
    {
        $attachmentId = $this->attachmentRequest->request->get('attachmentId', '');
        if ($attachmentId) {
            /** @var WPSNpApiService $wpsNpApiService */
            $wpsNpApiService = app('App\EofficeApp\Attachment\Services\WPSNpApiService');
            $wpsNpApiService->emptyTargetCacheDir($attachmentId);
        }

        return $this->returnResult([]);
    }

    /**
     * 获取wps国产化插件np相关初始化信息
     */
    public function wpsNpApiInitInfo()
    {
        /** @var WPSNpApiService $wpsNpApiService */
        $wpsNpApiService = app('App\EofficeApp\Attachment\Services\WPSNpApiService');
        $info = $wpsNpApiService->getWpsNpInitInfo();

        return $this->returnResult($info);
    }

    /**
     * 生成创建文档使用的附件id
     */
    public function generateAttachmentId()
    {
        $userId = $this->own['user_id'];
        $attachmentId = $this->attachmentService->makeAttachmentId($userId);
        $attachmentId .= '_wps_npapi';

        return $this->returnResult(['attachmentId' => $attachmentId]);
    }

    /**
     * 上传授权文件
     * @return type
     */
    public function attachmentAuthFile()
    {
        if (!$this->attachmentRequest->hasFile('Filedata')) {
            // 没有文件上传
            $result = ['code' => ['0x011018', 'upload']];
        } else {
            $result = $this->attachmentService->attachmentAuthFile($this->attachmentRequest->file('Filedata'), $this->attachmentRequest->all());
        }

        return $this->returnResult($result);
    }

    /**
     * 获取附件
     * @apiTitle 获取附件
     * @param {string} encrypt 固定值为：0
     * @param {string} operate 固定值为：download
     * @param {string} attachment_id 附件ID
     *
     * @paramExample {string} 参数示例
     * xxxxxx/api/attachment/index/ceb2b2b01e2405b7a7167829fab427d2?encrypt=0&operate=download&attachment_id=ceb2b2b01e2405b7a7167829fab427d2&api_token=6299293ad30e47f5a0c14b39ea11ff57496ea0ef617f4699f638e11183003b6e3058810836a16958af8b48690149abe9531f7f4c58a0f5059ca267da86c6b1e3
     * @success {boolean} status(1) 接入成功
     * @success {resource} attachment 返回附件
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x011017","message":"文件不存在"}] }
     */
    public function loadAttachment($attachmentId)
    {
        $result = $this->attachmentService->loadAttachment($attachmentId, $this->attachmentRequest->all(), $this->own);
        if (is_object($result)) {
            return $result;
        }
        if (isset($result['code'])) {
            return $this->locationError($result);
        }
        return $this->returnResult($result);
    }

    /**
     * 获取压缩图片
     * @param $attachmentId
     * @return array
     */
    public function getCompressImage($attachmentId, $size)
    {
        $result = $this->attachmentService->loadCompressImage($attachmentId, $size);
        if (is_object($result)) {
            return $result;
        }
        if (isset($result['code'])) {
            return $this->locationError($result);
        }
        return $this->returnResult($result);
    }
    /**
     * 获取附件缩略图
     * @param type $attachmentId
     * @return type
     */
    public function getThumbAttach($attachmentId)
    {
        $result = $this->attachmentService->getThumbAttach($attachmentId);
        return $this->returnResult($result);
    }

    /**
     * 下载附件压缩包
     *
     * @return type
     */
    public function downZip()
    {
        $result = $this->attachmentService->downZip($this->attachmentRequest->all(), $this->own['user_id']);
        if (isset($result['code'])) {
            return $this->locationError($result);
        }
        return $this->returnResult($result);
    }

    /**
     * 删除附件
     *
     * @return type
     */
    public function removeAttachment()
    {
        return $this->returnResult($this->attachmentService->removeAttachment($this->attachmentRequest->all()));
    }

    /**
     * 根据用户id获取附件id
     * @param type $userId
     * @return type
     */
    public function getAttachmentByUserId()
    {
        return $this->returnResult($this->attachmentService->getAttachmentByUserId($this->own['user_id'], $this->attachmentRequest->all()));
    }

    /**
     * 根据大朱要求添加
     * @return array
     */
    public function deleteAttachmentRel()
    {
        return $this->returnResult($this->attachmentService->deleteAttachmentRel($this->own['user_id'], $this->attachmentRequest->all()));
    }
    /**
     * base64转附件
     * @return type
     */
    public function base64Attachment()
    {
        return $this->returnResult($this->attachmentService->base64Attachment($this->attachmentRequest->all(), $this->own['user_id']));
    }

     /**
     * base64转附件
     * @return type
     */
    public function base64Attachments()
    {
        return $this->returnResult($this->attachmentService->base64Attachments($this->attachmentRequest->all(), $this->own['user_id']));
    }

    public function transToHtmlView()
    {
        $result = $this->attachmentService->transToHtmlView($this->attachmentRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取文档插件相关配置
     *
     * @return array
     */
    public function getOnlineReadOption()
    {
        /** @var SystemParamsService $service */
        $service = app('App\EofficeApp\System\Params\Services\SystemParamsService');
        $options = $service->getOnlineReadOption();

        return $this->returnResult($options);
    }

    public function saveOnlineReadOption()
    {
        /** @var SystemParamsService $service */
        $service = app('App\EofficeApp\System\Params\Services\SystemParamsService');
        $result = $service->saveOnlineReadOption($this->attachmentRequest->all());

        return $this->returnResult($result);
    }

    public function getPrintPower($attachmentId)
    {
        $result = $this->attachmentService->getPrintPower($attachmentId, $this->own);
        return $this->returnResult($result);
    }

    public function loadShareAttachment($shareToken)
    {
        return $this->returnResult(($this->attachmentService->loadShareAttachment($shareToken, $this->attachmentRequest->all())));
    }

    private function locationError($result)
    {
        $data = $this->attachmentRequest->all();
        if (!isset($data['location']) || !$data['location']) {
            return $this->returnResult($result);
        }
        $isMobile = app('App\EofficeApp\Auth\Services\AuthService')->isMobile();
        if($isMobile){
            return $this->returnResult($result);
        }
        $errorMsg = trans($result['code'][1] . '.' . $result['code'][0]);
        $domain = OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST;
        $url = $domain . '/eoffice10/client/web/error#error_msg=' . urlencode($errorMsg);
        header("Location:$url");
        exit;
    }

    /**
     *  获取wps渲染的页面地址
     */
    public function getWpsTransHtml()
    {
        $userId = $this->own['user_id'];
        $attachmentId = $this->attachmentRequest->get('attachment_id');
        $model = $this->attachmentRequest->get('operation', 'read');

        /** @var WPSAuthService $service */
        $service = app('App\EofficeApp\Document\Services\WPS\WPSAuthService');
        // 获取wps请求地址
        $url = $service->getUrl($attachmentId, $userId, ['_w_model' => $model]);
        $token = $service->getToken($userId);

        return $this->returnResult(['url' => $url, 'token' => $token]);
    }

    /**
     * 使用wps创建word/excel文档时先生成附件id
     */
    public function createWpsAttachmentId()
    {
        $userId = $this->own['user_id'];
        /** @var AttachmentService $service */
        $service = $this->attachmentService;
        $attachmentId = $service->makeAttachmentId($userId);
        $attachmentId .= '_wps';

        return $this->returnResult(['attachmentId' => $attachmentId]);
    }

    /**
     * 获取附件详情
     *
     * @return array
     */
    public function getDetail($attachmentId)
    {
        /** @var AttachmentService $attachmentService */
        $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
        /** @var array|null $attachmentInfo */
        $attachmentInfo = $attachmentService->getOneAttachmentById($attachmentId);

        return $this->returnResult($attachmentInfo);
    }
    /**
     * 迁移附件路径
     *
     * @return boolean
     */
    public function migrateAttachmentPath()
    {
        return $this->returnResult($this->attachmentService->migrateAttachmentPath($this->attachmentRequest->all()));
    }
    /**
     * 用于保存高拍仪图片转pdf
     *
     * @return void
     * @author yml
     */
    public function base64AttachmentPdf()
    {
        return $this->returnResult($this->attachmentService->base64AttachmentPdf($this->attachmentRequest->all(), $this->own['user_id']));
    }

    /**
     * 文档格式转换
     */
    public function convertFile()
    {
        /** @var WPSFileConvertService $service */
        $service = app('App\EofficeApp\Attachment\Services\FileConvert\WPSFileConvertService');
        $result = $service->convertFile($this->attachmentRequest);

        return $this->returnResult($result);
    }

    /**
     * 获取文档格式转换进度
     */
    public function getConvertProgress()
    {
        /** @var WPSFileConvertService $service */
        $service = app('App\EofficeApp\Attachment\Services\FileConvert\WPSFileConvertService');
        $process = $service->getConvertProgress($this->attachmentRequest);

        return $this->returnResult($process);
    }

    /**
     * 配置wps文档转换插件
     */
    public function setWpsFileConvertConfig()
    {
        /** @var WPSFileConvertService $service */
        $service = app('App\EofficeApp\Attachment\Services\FileConvert\WPSFileConvertService');
        $result = $service->setWpsFileConvertConfig($this->attachmentRequest);

        return $this->returnResult($result);
    }

    /**
     * 获取wps文档转换插件配置
     */
    public function getWpsFileConvertConfig()
    {
        /** @var WPSFileConvertService $service */
        $service = app('App\EofficeApp\Attachment\Services\FileConvert\WPSFileConvertService');
        $result = $service->getWpsFileConvertConfig();

        return $this->returnResult($result);
    }

    /**
     * wps文档转换功能回调
     */
    public function wpsFileConvertWebhook()
    {
        /** @var WPSFileConvertService $service */
        $service = app('App\EofficeApp\Attachment\Services\FileConvert\WPSFileConvertService');
        $service->wpsFileConvertWebhook($this->attachmentRequest);

        return $this->returnResult([]);
    }

    /**
     * 获取附件内容
     */
    public function getAttachmentContent($attachmentId)
    {
        /** @var AttachmentContentManager $manager */
        $manager = app('App\EofficeApp\Attachment\Services\AttachmentContent\AttachmentContentManager');

        $result = $manager->readContent($attachmentId);

        return $this->returnResult($result);
    }
}
