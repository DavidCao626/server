<?php


namespace App\EofficeApp\Document\Services\WPS;

use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\System\Params\Entities\SystemParamsEntity;
use App\EofficeApp\System\Params\Repositories\SystemParamsRepository;
use function GuzzleHttp\Psr7\str;
use Symfony\Component\HttpFoundation\HeaderBag;
use Illuminate\Support\Facades\Redis;

class WPSAuthService extends BaseService
{
    const WPS_APP_ID = 'DOCUMENT:WPS:APP:ID';   // wpsId在redis中的key
    const WPS_APP_KEY = 'DOCUMENT:WPS:APP:KEY'; // wpsKey在redis中的key

    const S_TYPE = 's'; // Excel类型
    const W_TYPE = 'w'; // Word类型
    const P_TYPE = 'p'; // PPT类型
    const F_TYPE = 'f'; // PDF类型

    const WORD_SUFFIX = '.docx';    // Word后缀
    const EXCEL_SUFFIX = '.xlsx';   // Excel后缀

    const WPS_BASE_URL = "https://wwo.wps.cn/office/";

    /**
     * 签名验证
     *
     * @param string $sign
     * @param array  $data
     *
     * @return bool
     */
    public function authSignature($sign, $data): bool
    {
        return $sign === $this->getSignature($data);
    }

    /**
     * 根据参数获取签名
     *
     * @param array  $data
     *
     * @return string
     */
    public function getSignature($data = []): string
    {
        // 构造签名的源字符串
        $parameterStr = $this->sortByKey($data);
        // 生成签名值
        $sign = $this->generateSignature($parameterStr);

        return $sign;
    }

    /**
     * 获取排序后的参数字符串
     *
     * @param array $data
     *
     * @return string
     */
    private function sortByKey($data = []): string
    {
        $parameterStr = '';

        $appId = $this->getAppId();
        $data['_w_appid'] = $appId;

        // 将以”_w_”作为前缀所有参数按key进行字典升序排列，将排序后的key,value字符串以%s=%s格式拼接起来
        ksort($data);

        foreach ($data as $key => $value) {
            $position = strpos($key,'_w_');
            if ($position !== 0) {
                continue;
            }
            $str = $key.'='.$value;
            $parameterStr .= $str;
        }

        // 将_w_appsecret加到最后，得到待加密的字符串(_w_appsecret只在签名时加入)
        $secret = $this->getAppSecret();
        $parameterStr .= '_w_secretkey='.$secret;
        return $parameterStr;
    }

    /**
     * 生成签名
     *
     * @param string $content
     *
     * @return string
     */
    private function generateSignature($content): string
    {
        // 进行hmac sha1 签名
        $appHash = hash_hmac('sha1', $content, $this->getAppSecret(), true);
        // 字符串经过Base64编码
        $base64 = base64_encode($appHash);
        // 字符串经过Url编码
        $signature = urlencode($base64);

        return $signature;
    }

    /**
     * 获取appId
     *
     * @return string
     */
    private function getAppId(): string
    {
        // TODO 后续统计redis的key, 统一存储在常量文件中

        return $this->getValueFromRedis(self::WPS_APP_ID);
    }

    /**
     * 获取appSecret
     *
     * @return string
     */
    private function getAppSecret(): string
    {
        // TODO 对值为空的异常验证

        return $this->getValueFromRedis(self::WPS_APP_KEY);
    }

    /**
     * 从缓存中获取指定值
     *
     * @param string $key
     * @param bool $refresh
     *
     * @return string
     */
    private function getValueFromRedis($key, $refresh = true): string
    {
        $value = Redis::get($key);

        if (!$value && $refresh) {
            $paramKey = '';
            /** @var SystemParamsRepository $systemRepository */
            $systemRepository = app('App\EofficeApp\System\Params\Repositories\SystemParamsRepository');
            if ($key === self::WPS_APP_ID) {
                $paramKey = SystemParamsEntity::ONLINE_READ_TYPE_WPS_APP_ID;
            } elseif ($key === self::WPS_APP_KEY) {
                $paramKey = SystemParamsEntity::ONLINE_READ_TYPE_WPS_APP_KEY;
            }

            $paramValue = $systemRepository->getParamByKey($paramKey);
            if ($paramValue) {
                Redis::set($key, $paramValue);
                $value = $paramValue;
            }
        }

        return (string)$value;
    }

    /**
     * 获取wps访问地址
     *
     * @param string  $attachmentId
     * @param string  $userId
     * @param array   $options  其他参数
     *
     * @return string
     */
    public function getUrl($attachmentId, $userId, $options = []): string
    {
        /**
         * 1. 获取wps的baseUrl
         * 2. 获取文档类型
         * 2.1 获取额外参数
         * 3. 获取签名(需要拼接userId参数)
         * 4. 获取appId
         * 5. 根据所有参数生成字符串
         */
        //  参考 'https://wwo.wps.cn/office/s/:file_id?_w_appid=xxxxxxxxxxx&_w_param1=xxxx&_w_param2=xxxxxx&_w_signature=xxx';
        $baseUrl = $this->getBaseUrl();
        $type = $this->getDocumentType($attachmentId); // TODO 类型验证

        $params = array_merge(['_w_userid' => $userId, '_w_tokentype' => 1], $options);
        $extraParamsStr = $this->getExtraParamStr($params);

        $sign = $this->getSignature($params);
        $appId = $this->getAppId();
        // 拼接参数
        $url = $baseUrl.$type.'/'.$attachmentId.'?'.'_w_appid='.$appId.$extraParamsStr.'&_w_signature='.$sign;

        return $url;
    }

    /**
     * 获取wps模板列表
     *  @see https://open.wps.cn/docs/wwo/access/api-list#des9 新建文档->获取文字(w)/表格(s)模板列表
     *  https://wwo.wps.cn/office/w/new/0?_w_appid=xxxxxxxxxxxxxxxxxxxxxxxxxxxappid&_w_signature=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx&…(对接模块需要的自定义参数)
     *
     * @param string  $userId
     * @param string   $type  文档类型
     *
     * @return string
     */
    public function getTemplateListUrl($userId, $type, $options)
    {
        $baseUrl = $this->getBaseUrl();

        $params = array_merge(['_w_userid' => $userId], $options);
        $extraParamsStr = $this->getExtraParamStr($params);

        $sign = $this->getSignature($params);
        $appId = $this->getAppId();

        $url = $baseUrl.$type.'/new/0?_w_appid='.$appId.'&_w_signature='.$sign.'&'.$extraParamsStr;

        return $url;
    }

    /**
     * 判断是否为有效的模板类型
     *
     * @param string $type
     *
     * @return bool
     */
    public function isValidateTemplateType($type)
    {
        return in_array($type, [self::W_TYPE, self::S_TYPE]);
    }

    /**
     * 生成额外参数字符串
     *
     * @param array $extraParams
     * @return string
     */
    private function getExtraParamStr($extraParams): string
    {
        $paramStr = '';

        ksort($extraParams);

        foreach ($extraParams as $key =>  $extraParam) {
            $paramStr .= '&'.$key.'='.$extraParam;
        }

        return $paramStr;
    }
    /**
     * 获取wps文件地址
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        $default = self::WPS_BASE_URL;
        // 允许后续wps开发平台后续改动, 但可能性不大
        $baseUrl = envOverload('wps_base_url', $default);

        return $baseUrl;
    }

    /**
     * 获取文档类型
     *
     * @param string $attachmentId
     *
     * @return string
     */
    public function getDocumentType($attachmentId): string
    {
        // TODO 如何与WPSFileService::getAttachmentFile()统一

        /** @var AttachmentService $attachmentService */
        $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
        /** @var array|null $attachmentInfo */
        $attachmentInfo = $attachmentService->getOneAttachmentById($attachmentId);

        $attachmentType = isset($attachmentInfo['attachment_type']) ? $attachmentInfo['attachment_type'] : '';
        $attachmentType = strtolower($attachmentType);
        //  表格文件url:s
        //  文字文件url:w
        //  演示文件url:p
        //  PDF文件url:f
        // TODO 移入常量文件
        $sType = ['xls', 'xlt', 'et', 'xlsx', 'xltx', 'csv', 'xlsm', 'xltm'];
        $wType = ['doc', 'dot', 'wps', 'wpt', 'docx', 'dotx', 'docm', 'dotm'];
        $pType = ['ppt', 'pptx', 'pptm', 'ppsx', 'ppsm', 'pps', 'potx', 'potm', 'dpt', 'dps'];
        $fType = ['pdf'];

        if (in_array($attachmentType, $sType)) {
            return self::S_TYPE;
        } elseif (in_array($attachmentType, $wType)) {
            return self::W_TYPE;
        } elseif (in_array($attachmentType, $pType)) {
            return self::P_TYPE;
        } elseif (in_array($attachmentType, $fType)) {
            return self::F_TYPE;
        }

        return self::W_TYPE;
    }

    /**
     * 获取用户token
     *
     * @param string $userId
     *
     * @return string
     */
    public function getToken($userId): string
    {
        $str = $userId.'e_office_105';
        $secret = $this->getAppSecret();

        $token = hash_hmac('md5', $str, $secret, false);

        return $token;
    }

    /**
     * 头信息验证
     *
     * @param HeaderBag $headers
     *
     * @throws \Exception
     */
    public function authWPSUserAgent(HeaderBag $headers, $userId): void
    {

        // 验证 User-Agent
//        $ua = $headers->get('User-Agent');
//        if ($ua !== 'wps-weboffice-openplatform') {
//            throw new \Exception('Illegal request', 403);
//        }

        // 验证 x-wps-weboffice-token
        $token = $headers->get('x-wps-weboffice-token');

        if ($token !== $this->getToken($userId)) {
            throw new \Exception('Illegal request', 403);
        }
    }
}