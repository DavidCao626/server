<?php


namespace App\EofficeApp\Attachment\Services\FileConvert;


use app\EofficeApp\Attachment\Entities\AttachmentRelEntity;
use App\EofficeApp\Attachment\Entities\WPS\WPSFileConvertEntity;
use App\EofficeApp\Attachment\Repositories\AttachmentRelRepository;
use App\EofficeApp\Attachment\Repositories\AttachmentRepository;
use App\EofficeApp\Attachment\Repositories\WPS\WPSFileConvertRepository;
use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Attachment\Services\FileConvert\WPSFileConvertObject\ConvertRequestBodyObject;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Document\Services\WPS\WPSFileService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WPS文档格式转换功能
 *  目前使用博文二开
 * Class WPSFileConvertService
 * @package App\EofficeApp\Attachment\Services\FileConvert
 */
class WPSFileConvertService extends BaseService
{
    const SYSTEM_PARAMS_FILE_CONVERT_APP_ID = 'wps_file_convert_app_id'; // system_prams表中wps文档转换id
    const SYSTEM_PARAMS_FILE_CONVERT_APP_KEY = 'wps_file_convert_app_key';  // system_prams表中wps文档转换key

    const WPS_FILE_CONVERT = 'https://dhs.open.wps.cn/pre/v1/convert';
    const WPS_FILE_CONVERT_QUERY = 'https://dhs.open.wps.cn/pre/v1/query';

    /**
     * 获取请求签名
     *
     * @param string $secretKey appKey
     * @param string $date GMT日期格式
     * @param string $md5 请求参数md5加密
     * @param string $uri 请求路由
     * @param string $method 请求方式 [GET/POST]
     * @return string
     */
    public function getHeaderSingedStr($secretKey, $date, $md5, $uri, $method)
    {
        $str = $method . "\n" . $md5 . "\n" . 'application/json' . "\n" . $date . "\n" . $uri;
        $sign = hash_hmac('sha1', $str, $secretKey, true);
        $sign = base64_encode($sign);

        return $sign;
    }

    /**
     * 向wps文档转换功能发起请求
     *
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param array $json
     *
     * @return array
     */
    public function requestWpsConvertService($method, $uri, $headers = [], $json = [])
    {
        $http = new Client();
        $options = [];

        if ($headers) {
            $options['headers'] = $headers;
        }

        if ($json) {
            $options['json'] = $json;
        }

        $guzzleResponse = $http->request($method, $uri, $options);

        return json_decode($guzzleResponse->getBody()->getContents(), true);
    }

    /**
     * 文档转换
     *
     * @param Request $request
     *
     * @return array
     */
    public function convertFile(Request $request)
    {
        $attachmentId = $request->get('attachmentId');
        $convertedType = $request->get('convertedType', 'pdf');
        /** @var AttachmentService $attachmentService */
        $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
        $attachmentInfo = $attachmentService->getOneAttachmentById($attachmentId);

        if (!$attachmentInfo) {
            return ['code' => ['0x011017', 'upload']]; //文件不存在
        }
        $taskId = $this->generateTaskId($attachmentInfo['id']);
        $fileName = $attachmentInfo['attachment_name'];

        //TODO  验证类型是否支持

        try {
            // 配置请求body
            $body = new ConvertRequestBodyObject();
            /** @var WPSFileService $fileService */
            $fileService = app('App\EofficeApp\Document\Services\WPS\WPSFileService');
            $url = $fileService->getDownloadId($attachmentId);
            $callback = $this->getCallBack();
            $body->setSrcUri($url);
            $body->setFileName($fileName);
            $body->setExportType($convertedType);
            $body->setCallBack($callback);
            $body->setTaskId($taskId);

            // 获取appId和appKey
            $appConfig = $this->getWpsFileConvertConfig();
            $appId = $appConfig['appId'];
            $appKey = $appConfig['appKey'];

            // 获取相关签名
            $date = gmdate("l, d F Y H:i:s") . ' GMT';
            $md5 = md5(json_encode($body->convertToArray()));
            $uri = '/pre/v1/convert';
            $method = 'POST';
            $signature = $this->getHeaderSingedStr($appKey, $date, $md5, $uri, $method);

            // 配置请求头
            $authorizationStr = 'WPS ' . $appId . ':' . $signature;
            $headers = [
                'Content-Type' => 'application/json',
                'Content-Md5' => $md5,
                'Date' => $date,
                'Authorization' => $authorizationStr,
            ];
            $url = self::WPS_FILE_CONVERT;

            $result = $this->requestWpsConvertService($method, $url, $headers, $body->convertToArray());

            if (isset($result['Code']) && ($result['Code'] === 'OK')) {
                /** @var WPSFileConvertRepository $convertRepository */
                $convertRepository = app('App\EofficeApp\Attachment\Repositories\WPS\WPSFileConvertRepository');
                $params = [
                    'task_id' => $taskId,
                    'attachment_id' => $attachmentId,
                    'origin_type' => $attachmentInfo['attachment_type'],
                    'converted_type' => $convertedType,
                ];
                $convertRepository->addConvertTask($params);
            }
            // TODO 等待转换完成后返回前端

        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            // 转换任务已存在
            if (preg_match('/AlreadyExists/', $message)) {
                return ['code' => ['0x000002', 'attachment']];
            }
            Log::error($message);

            return ['code' => ['0x000001', 'attachment']]; //文件不存在
        }

        return $result;
    }

    /**
     * 获取文档转换进度
     *
     * @param Request $request
     *
     */
    public function getConvertProgress(Request $request)
    {
        $attachmentId = $request->get('attachmentId');
        $isReplaced = $request->get('isReplaced', false);

        /** @var WPSFileConvertRepository $convertRepository */
        $convertRepository = app('App\EofficeApp\Attachment\Repositories\WPS\WPSFileConvertRepository');
        /** @var WPSFileConvertEntity $task */
        $task = $convertRepository->getConvertTaskByAttachmentId($attachmentId);
        $taskId = $task->getTaskIdAttribute();

        try {
            // 获取appId和appKey
            $appConfig = $this->getWpsFileConvertConfig();
            $appId = $appConfig['appId'];
            $appKey = $appConfig['appKey'];

            // 获取请求body
            $body = [
                'AppId' => $appId,
                'TaskId' => $taskId,
            ];

            // 获取签名
            $md5 = md5(json_encode($body));
            $date = gmdate("l, d F Y H:i:s") . ' GMT';
            $uri = '/pre/v1/query?AppId=' . $appId . '&TaskId=' . $taskId;
            $method = 'GET';
            $signature = $this->getHeaderSingedStr($appKey, $date, $md5, $uri, $method);

            // 获取请求header
            $authorizationStr = 'WPS ' . $appId . ':' . $signature;
            $url = self::WPS_FILE_CONVERT_QUERY . '?AppId=' . $appId . '&TaskId=' . $taskId;
            $headers = [
                'Content-Type' => 'application/json',
                'Content-Md5' => $md5,
                'Date' => $date,
                'Authorization' => $authorizationStr,
            ];

            $response = $this->requestWpsConvertService($method, $url, $headers, $body);

            $result = [
                'converted' => false,
                'detail' => [],
            ];

            if ($response['Code'] === 'OK') {
                /** @var WPSFileConvertEntity|null $convert */
                $convert = $convertRepository->getConvertTaskByTaskId($taskId);
                if ($convert) {
                    // 如果存在转换记录且转换已过期则需重新转换
                    $hasExpires = $convert->getExpiresAttribute();
                    if ($hasExpires && ($hasExpires <= time())) {
                        throw new \Exception('转换已过期，请重新转换');
                    }
                    $url = $response['Urls'][0];
                    // 更新文档转换记录
                    $expires = '';
                    $queryParams = $this->getUrlQueryParams($url);
                    if (isset($queryParams['Expires'])) {
                        $expires = $queryParams['Expires'];
                    }
                    $convertRepository->completeConvert($convert, $expires);
                    $result['converted'] = true;
                    $result['detail'] = $response;
                    if ($isReplaced) {
                        $this->replaceTargetFile($attachmentId, $url, $convert->getConvertedTypeAttribute());
                    }
                    return $result;
                }
            }
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            Log::error($message);
        }

        return $result;
    }

    /**
     * 替换指定附件
     *
     * @param string $attachmentId
     * @param string $url
     * @param string $convertedType
     *
     * @return void
     */
    public function replaceTargetFile($attachmentId, $url, $convertedType)
    {
        /**
         * 转换完成后后文档替换
         *  1. 下载到对应目录(保留原文件名, 后缀不同)
         *  2. 更新附件表相关信息
         *  3. 撤销时(需重新生成, 如二开流程退回)清除已转换的PDF
         */
        /** @var AttachmentService $attachmentService */
        $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
        $attachmentInfo = $attachmentService->getOneAttachmentById($attachmentId);
        $tmpFilePath = $attachmentInfo['attachment_base_path'] . $attachmentInfo['attachment_relative_path'];

        $filename = $attachmentInfo['attachment_name'];
        $withoutExtension = substr($filename, 0, strrpos($filename, '.'));
        $newFilename = $withoutExtension . '.'.$convertedType;
        $attachmentFilename = $attachmentInfo['affect_attachment_name'];
        $affectWithoutExtension = substr($attachmentFilename, 0, strrpos($attachmentFilename, '.'));
        $newAffectFilename = $affectWithoutExtension . '.'.$convertedType;
        $newTmpFile = $tmpFilePath . $newAffectFilename;

        // 若文档已存在则直接返回/删除?
        $fileExists = file_exists($newTmpFile);
        if ($fileExists) {
            return;
        }
        $out = @fopen($newTmpFile, "wb");
        $in = @fopen($url, "rb");

        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        @fclose($out);
        @fclose($in);

        // 更新文档相关信息
        /** @var AttachmentRelRepository $relRepository */
        $relRepository = app('App\EofficeApp\Attachment\Repositories\AttachmentRelRepository');
        $attachmentRel = $relRepository->getOneAttachmentRel(['attachment_id' => [$attachmentInfo['attachment_id']]]);
        $tableName = $attachmentService->getAttachmentTableName($attachmentRel->year, $attachmentRel->month);

        /** @var AttachmentRepository $attachmentRepository */
        $attachmentRepository = app('App\EofficeApp\Attachment\Repositories\AttachmentRepository');
        $newFileSize = filesize($newTmpFile);
        $attachmentMark = $attachmentService->getAttachmentMark($convertedType);
        $updateData = [
            'attachment_name' => $newFilename,
            'affect_attachment_name' => $newAffectFilename,
            'attachment_size' => $newFileSize,
            'attachment_type' => $convertedType,
            'attachment_mark' => $attachmentMark,
        ];

        return $attachmentRepository->updateAttachmentData($tableName, $updateData, ['rel_id' => [$attachmentRel->rel_id]]);
    }

    /**
     * 配置wps文档转换插件
     *
     * @param Request $request
     *
     * @return bool
     */
    public function setWpsFileConvertConfig(Request $request): bool
    {
        $appId = $request->get('appId');
        $appKey = $request->get('appKey');

        if ($appId && $appKey) {
            // 数据比较少 可直接使用助手函数
            set_system_param(self::SYSTEM_PARAMS_FILE_CONVERT_APP_ID, $appId);
            set_system_param(self::SYSTEM_PARAMS_FILE_CONVERT_APP_KEY, $appKey);

            return true;
        }

        return false;
    }

    /**
     * 获取wps文档转换插件配置
     *  TODO 后续使用redis优化 每次转换需读取mysql2次
     * @return array
     */
    public function getWpsFileConvertConfig(): array
    {
        $appId = get_system_param(self::SYSTEM_PARAMS_FILE_CONVERT_APP_ID, '');
        $appKey = get_system_param(self::SYSTEM_PARAMS_FILE_CONVERT_APP_KEY, '');

        return [
            'appId' => $appId,
            'appKey' => $appKey,
        ];
    }

    /**
     * 文档转换结果回调
     *
     * @param Request $request
     */
    public function wpsFileConvertWebhook(Request $request)
    {
        $status = $request->get('Code');
        $taskId = $request->get('TaskId');
        $detail = $request->get('Detail');

        $attachmentId = $this->getAttachmentIdFromTaskId($taskId);

        if (($status === 'OK') && $attachmentId) {
            $url = $detail['Urls'][0];

            /** @var WPSFileConvertRepository $convertRepository */
            $convertRepository = app('App\EofficeApp\Attachment\Repositories\WPS\WPSFileConvertRepository');
            /** @var WPSFileConvertEntity $convert */
            $convert = $convertRepository->getConvertTaskByTaskId($taskId);
            if ($convert) {
                // 更新文档转换记录
                $expires = '';
                $queryParams = $this->getUrlQueryParams($url);
                if (isset($queryParams['Expires'])) {
                    $expires = $queryParams['Expires'];
                }
                $convertRepository->completeConvert($convert, $expires);
                $this->replaceTargetFile($attachmentId, $url, $convert->getConvertedTypeAttribute());
            }
        }

        // TODO 结果存入redis 错误则终止转换
    }

    /**
     * 获取taskId
     *
     * @param string $relId
     * @return string
     */
    public function generateTaskId($relId)
    {
        return base64_encode($relId . '|' . time());
    }

    /**
     * 从任务id中获取rel_id
     *
     * @param string $taskId
     * @return string
     */
    public function getRelIdFromTaskId($taskId)
    {
        $params = explode('|', base64_decode($taskId));

        return $params[0] ?? '';
    }

    /**
     * 从任务id中获取attachmentId
     *
     * @param string $taskId
     * @return string
     */
    public function getAttachmentIdFromTaskId($taskId)
    {
        $relId = $this->getRelIdFromTaskId($taskId);

        if (!$relId) {
            return '';
        }

        /** @var AttachmentRelRepository $relRepository */
        $relRepository = app('App\EofficeApp\Attachment\Repositories\AttachmentRelRepository');
        /** @var AttachmentRelEntity|null $rel */
        $rel = $relRepository->getOneAttachmentRel(['rel_id'=> [$relId]]);

        if ($rel) {
            return $rel->attachment_id;
        }

        return '';
    }

    /**
     * 返回url中的query参数
     *
     * @param string $url
     *
     * @return array
     */
    public function getUrlQueryParams($url)
    {
        $urlParams = parse_url($url);
        $queryParams = [];
        if (isset($urlParams['query'])) {
            parse_str($urlParams['query'], $queryParams);
        }

        return $queryParams;
    }

    /**
     * 撤回文档转换
     *
     * @param string $attachmentId
     *
     * @return bool
     */
    public function revertFileConvert($attachmentId)
    {
        try {
            /** @var AttachmentService $attachmentService */
            $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
            /** @var AttachmentRelRepository $relRepository */
            $relRepository = app('App\EofficeApp\Attachment\Repositories\AttachmentRelRepository');
            /** @var AttachmentRepository $attachmentRepository */
            $attachmentRepository = app('App\EofficeApp\Attachment\Repositories\AttachmentRepository');
            $attachmentRel = $relRepository->getOneAttachmentRel(['attachment_id' => [$attachmentId]]);

            if (!$attachmentRel) {
                return false;
            }

            $tableName = $attachmentService->getAttachmentTableName($attachmentRel->year, $attachmentRel->month);
            $attachment = $attachmentRepository->getOneAttachment($tableName, ['rel_id' => [$attachmentRel->rel_id]]);

            if (!$attachment) {
                return false;
            }

            $fileDir = $attachment->attachment_path;
            $attachmentDir = getAttachmentDir();
            $files = [];
            if ($scanDir = scandir($attachmentDir.$fileDir)) {
                $files = array_slice($scanDir, 2);
            }

            /**
             * 如果附件目录下存在2个文件, 则一个为转换后的附件, 一个为转换前附件
             *  1. 根据转换前附件更新表
             *  2. 删除转换后附件
             */
            if (count($files) == 2) {
                $originFileArr = array_diff($files,[$attachment->affect_attachment_name]);
                $originAffectFilename = implode('', $originFileArr);
                $originFile = $attachmentDir.$fileDir.$originAffectFilename;// 格式转换前的文件
                $originFileSize = filesize($originFile);
                $convertedFileName = $attachment->attachment_name;

                // 分别获取初始文件扩展名和转换文件名
                $extensionName = substr(strrchr($originAffectFilename, '.'), 1);
                $filename = str_replace(strrchr($convertedFileName, '.'),'',$convertedFileName);

                $attachmentMark = $attachmentService->getAttachmentMark($extensionName);
                $updateData = [
                    'attachment_name' => $filename.'.'.$extensionName,
                    'affect_attachment_name' => $originAffectFilename,
                    'attachment_size' => $originFileSize,
                    'attachment_type' => $extensionName,
                    'attachment_mark' => $attachmentMark
                ];
                $attachmentRepository->updateAttachmentData($tableName, $updateData, ['rel_id' => [$attachmentRel->rel_id]]);

                // 删除转换文件
                unlink($attachmentDir.$fileDir.($attachment->affect_attachment_name));

                return true;
            }
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            Log::error($message);
        }

        return false;
    }

    /**
     * 获取回调地址
     *
     * @return string
     */
    public function getCallBack()
    {
        // 'http://xxxx.xxx.cn/eoffice10_dev/server/public/api/wps/pre/v1/convert/webhook'
        $host = OA_SERVICE_HOST;
        $scheme = OA_SERVICE_PROTOCOL;
        $scriptName = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        // $scriptName 为 /eoffice10_dev/server/public/index.php 匹配第一对//包裹的路径
        $strPattern = '/\/.*?\//';
        $arrMatches = [];
        preg_match($strPattern, $scriptName, $arrMatches);
        $dir = isset($arrMatches[0]) ? $arrMatches[0] : '/eoffice10/';
        $callbackUrl = $scheme.'://'.$host.$dir.'server/public/api/wps/pre/v1/convert/webhook';

        return $callbackUrl;
    }
}