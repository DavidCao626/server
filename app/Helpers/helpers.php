<?php

use App\Utils\Utils;
use Illuminate\Support\Facades\Lang;
use App\Utils\Pinyin;
use Illuminate\Support\Arr;
use App\EofficeCache\ECache;
if (!function_exists('ecache')) {
    function ecache($cacheName)
    {
        return ECache::make($cacheName);
    }
}
if (!function_exists('quick_pinyin')) {
    function quick_pinyin($word, $first = false)
    {
        return Pinyin::get($word, $first);
    }
}
/**
 * 重启队列，区分linux和windows系统
 * linux系统需要增加supervisorctl进程监听，restart的queue进程会被回收
 * linux系统当前程序运行的用户需要sudo权限
 */
if (!function_exists('restart_queue')) {
    function restart_queue()
    {
        $os_name = PHP_OS;
        if (strpos($os_name, "Linux") !== false) {
            exec('sudo supervisorctl stop all && sudo supervisorctl start all');
        } else if (strpos($os_name, "WIN") !== false) {
            \Artisan::call('queue:restart');
        }
    }
}
if (!function_exists('check_date_format')) {
    function check_date_format($date)
    {
        //匹配日期格式
        if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date, $parts)) {
            //检测是否为日期
            if (checkdate($parts[2], $parts[3], $parts[1])) {
                return true;
            }
        }
        return false;
    }
}
if (!function_exists('get_combobox_table_name')) {
    function get_combobox_table_name($comboboxId, $prefix = 'system_combobox_field_')
    {
        return Utils::getComboboxTableName($comboboxId, $prefix);
    }
}
if (!function_exists('trans_dynamic')) {
    function trans_dynamic($keys = null, $replace = [], $local = null)
    {
        if ($local == null) {
            if (isset($GLOBALS['langType']) && $GLOBALS['langType']) {
                $local = $GLOBALS['langType'];
            }
        }
        return Utils::transDynamic($keys, $replace, $local);
    }
}
if (!function_exists('remove_dynamic_langs')) {
    function remove_dynamic_langs($langKeys, $local = null)
    {
        if ($local == null) {
            if (isset($GLOBALS['langType']) && $GLOBALS['langType']) {
                $local = $GLOBALS['langType'];
            }
        }
        return Utils::removeDynamicLangs($langKeys, $local);
    }
}
if (!function_exists('get_userid_group_by_locale')) {
    function get_userid_group_by_locale($userId = null)
    {
        return Utils::getUserIdGroupByLocale($userId);
    }
}
if (!function_exists('mulit_trans_dynamic')) {
    function mulit_trans_dynamic($keys = null, $replace = [], $local = null)
    {
        if ($local == null) {
            if (isset($GLOBALS['langType']) && $GLOBALS['langType']) {
                $local = $GLOBALS['langType'];
            }
        }
        return Utils::mulitTransDynamic($keys, $replace, $local);
    }
}
if (!function_exists('convert_to_utf8')) {
    function convert_to_utf8($content)
    {
        return Utils::convertToUtf8($content);
    }
}

if (!function_exists('convert_pinyin')) {

    function convert_pinyin($str)
    {
        return Utils::convertPy($str);
    }

}

if (!function_exists('get_system_param')) {

    function get_system_param($key = null, $default = '')
    {
        return Utils::getSystemParam($key, $default);
    }

}
if (!function_exists('set_system_param')) {

    function set_system_param($key = null, $default = '')
    {
        return Utils::setSystemParam($key, $default);
    }

}
if (!function_exists('get_user_simple_attr')) {

    function get_user_simple_attr($userId, $field = 'user_name')
    {
        return Utils::getUserSimpleAttr($userId, $field);
    }

}

if (!function_exists('success_response')) {

    function success_response($data = false)
    {
        return Utils::successResponse($data);
    }

}

if (!function_exists('warning_response')) {

    function warning_response($code, $langName = '', $dynamic = '')
    {
        return Utils::warningResponse($code, $langName, $dynamic);
    }

}

if (!function_exists('error_response')) {

    function error_response($code, $langName = '', $dynamic = '')
    {
        return Utils::errorResponse($code, $langName, $dynamic);
    }

}

if (!function_exists('convertValue')) {

    function convertValue($val)
    {
        return Utils::convertValue($val);
    }

}
/**
 * 字符转码
 */
if (!function_exists('transEncoding')) {
    function transEncoding($string, $target)
    {
        $encoding = mb_detect_encoding($string, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);
        return mb_convert_encoding($string, $target, $encoding);
    }
}

/**
 * curl抓取连接内容，postField = []
 */
if (!function_exists('readSmsByClient')) {

    function readSmsByClient($userId, $data = null)
    {
        return Utils::readSmsByClient($userId, $data);
    }

}

/**
 * curl抓取连接内容，postField = []
 */
if (!function_exists('getHttps')) {

    function getHttps($url, $data = null,$header = [],$options = [])
    {
        return Utils::getHttps($url, $data,$header,$options);
    }

}

if (!function_exists('own')) {

    function own($key = '')
    {
        return Utils::own($key);
    }
}
/**
 * curl抓取连接内容，postField = []
 */
if (!function_exists("getPlatformUser")) {
    function getPlatformUser($platform, $user_id)
    {
        return Utils::getPlatformUser($platform, $user_id);
    }
}

if (!function_exists('sql_error')) {

    /**
     * sql数据错误返回值
     *
     * @param  string  $errorCode    错误码
     * @param  string  $errorMessage 错误信息
     *
     * @return json|bool 返回值
     *
     * @author qishaobo
     *
     * @since  2015-11-11 创建
     */
    function sql_error($errorCode, $errorMessage)
    {
        if ($errorCode == '42S22') {
            $errorMessage = explode("'", $errorMessage)[1];
            $errorMessage = str_replace('{field}', $errorMessage, trans('common.0x000007'));
            header('Content-Type: application/json', false, 200);
            echo json_encode(error_response('0x000007', '', [$errorMessage]));
            exit;
        }

        return false;
    }

}

if (!function_exists('trans')) {

    function trans($name, $replace = [], $local = null)
    {
        return Lang::get($name, $replace, $local);
    }

}
// 加密
if (!function_exists('encrypt')) {

    function encrypt($val)
    {
        return Utils::encrypt($val);
    }

}
// 解密
if (!function_exists('decrypt')) {

    function decrypt($val)
    {
        return Utils::decrypt($val);
    }

}
if (!function_exists('config_path')) {

    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }

}

if (!function_exists('createCustomDir')) {

    function createCustomDir($path)
    {
        return Utils::createCustomDir($path);
    }

}
// 等比例缩放图片
if (!function_exists('scaleImage')) {

    function scaleImage($pic, $maxx, $maxy, $prefix)
    {
        return Utils::scaleImage($pic, $maxx, $maxy, $prefix);
    }

}

//64未base
if (!function_exists('imageToBase64')) {

    function imageToBase64($file)
    {
        return Utils::imageToBase64($file);
    }

}

if (!function_exists('getAttachmentDir')) {

    function getAttachmentDir()
    {
        return Utils::getAttachmentDir();
    }

}

/**
 * 添加系统日志
 *
 * @param  array $data 日志内容：log_user, log_type, log_time, log_ip, log_remark
 *
 * @return bool
 *
 * @author qishaobo
 *
 * @since  2016-07-01 创建
 */
if (!function_exists('add_system_log')) {

    function add_system_log($data)
    {
        return app('App\EofficeApp\System\Log\Services\LogService')->createLog($data);
    }

}

/**
 * 获取客户端ip
 *
 * @return string
 *
 * @author qishaobo
 *
 * @since  2016-12-12 创建
 */
if (!function_exists('getClientIp')) {

    function getClientIp()
    {
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            //nginx 代理模式下，获取客户端真实IP
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            //客户端的ip
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //浏览当前页面的用户计算机的网关
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);

            if (false !== $pos) {
                unset($arr[$pos]);
            }

            $ip = isset($arr[0]) ? trim($arr[0]) : '';
        } else {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }

        return $ip;
    }

}

/**
 * 转换路由->url
 */
if (!function_exists('convertRoute')) {

    function convertRoute($platfrom, $menu, $type, $params = null)
    {
        return Utils::convertRoute($platfrom, $menu, $type, $params);
    }

}

if (!function_exists('integratedErrorUrl')) {

    function integratedErrorUrl($message, $domin = null)
    {
        return Utils::integratedErrorUrl($message, $domin);
    }

}

if (!function_exists('getRemindsId')) {

    function getRemindsId($remind)
    {
        return Utils::getRemindsId($remind);
    }

}

if (!function_exists('getRolesByUserId')) {

    function getRolesByUserId($user_id)
    {
        return Utils::getRolesByUserId($user_id);
    }

}

if (!function_exists('getRoleCommunicateByTypeId')) {

    function getRoleCommunicateByTypeId($typeId)
    {
        return Utils::getRoleCommunicateByTypeId($typeId);
    }

}

if (!function_exists('getUsersByRoleId')) {

    function getUsersByRoleId()
    {
        return Utils::getUsersByRoleId();
    }

}

/**
 * 读取路由目录
 */
if (!function_exists('getDirRoutes')) {

    function getDirRoutes($moduleDir)
    {
        $modules = scandir($moduleDir);

        $systemDir = $moduleDir . '/System';

        if (is_dir($systemDir)) {
            $modules['system'] = scandir($systemDir);
        }

        return $modules;
    }

}

/**
 * 读取配置文件路由
 */
if (!function_exists('get_current_module')) {

    function get_current_module()
    {
        return Utils::getCurrentModule();
    }

}

/**
 * 加载并注册路由
 */
if (!function_exists('register_routes')) {

    function register_routes($app, $moduleDir, $module, $routeConfig = [])
    {
        return Utils::registerRoutes($app, $moduleDir, $module, $routeConfig);
    }
}
/**
 * 读取路由目录
 */
if (!function_exists('sendWebmail')) {

    function sendWebmail($data)
    {
        return app("App\EofficeApp\Webmail\Services\WebmailService")->sendWebmail($data);
    }

}

/**
 * 导入数据失败返回
 */
if (!function_exists("importDataFail")) {

    function importDataFail($info = "", $local = null)
    {
        return [
            'data'  => empty($info) ? trans("import.import_fail", [], $local) : $info,
            'style' => [
                'fontColor' => config("app.import_fail_color", [], $local)
            ],
        ];
    }

}

/**
 * 导入数据失败返回
 */
if (!function_exists("importDataSuccess")) {

    function importDataSuccess($info = "", $local = null)
    {
        return [
            'data'  => empty($info) ? trans("import.import_success", [], $local) : $info,
            'style' => [
                'fontColor' => config("app.import_success_color", [], $local)
            ],
        ];
    }

}

/**
 * 获取api
 */
if (!function_exists("getALLApi")) {

    function getALLApi()
    {

        $cacheId = 'eoffice10_apis';

        if (\Cache::has($cacheId)) {
            $data = \Cache::get($cacheId);
        } else {
            $data = \DB::table('api')->get()->toArray();
            \Cache::forever($cacheId, $data);
        }

        return $data;
    }
}

if (!function_exists("long2str")) {
    function long2str($v, $w)
    {
        $len = count($v);
        $s   = array();
        for ($i = 0; $i < $len; $i++) {
            $s[$i] = pack("V", $v[$i]);
        }
        if ($w) {
            return substr(join('', $s), 0, $v[$len - 1]);
        } else {
            return join('', $s);
        }
    }
}

if (!function_exists("str2long")) {
    function str2long($s, $w)
    {
        $v = unpack("V*", $s . str_repeat("\0", (4 - strlen($s) % 4) & 3));
        $v = array_values($v);
        if ($w) {
            $v[count($v)] = strlen($s);
        }
        return $v;
    }
}

if (!function_exists("int32")) {
    function int32($n)
    {
        while ($n >= 2147483648) {
            $n -= 4294967296;
        }

        while ($n <= -2147483649) {
            $n += 4294967296;
        }

        return (int) $n;
    }
}

if (!function_exists("xxtea_encrypt")) {
    function xxtea_encrypt($str, $key)
    {
        if ($str == "") {
            return "";
        }
        $v = str2long($str, true);
        $k = str2long($key, false);
        if (count($k) < 4) {
            for ($i = count($k); $i < 4; $i++) {
                $k[$i] = 0;
            }
        }
        $n = count($v) - 1;

        $z     = $v[$n];
        $y     = $v[0];
        $delta = 0x9E3779B9;
        $q     = floor(6 + 52 / ($n + 1));
        $sum   = 0;
        while (0 < $q--) {
            $sum = int32($sum + $delta);
            $e   = $sum >> 2 & 3;
            for ($p = 0; $p < $n; $p++) {
                $y  = $v[$p + 1];
                $mx = int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $z  = $v[$p]  = int32($v[$p] + $mx);
            }
            $y  = $v[0];
            $mx = int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $z  = $v[$n]  = int32($v[$n] + $mx);
        }
        return long2str($v, false);
    }
}

if (!function_exists("xxtea_decrypt")) {
    function xxtea_decrypt($str, $key)
    {
        if ($str == "") {
            return "";
        }
        $v = str2long($str, false);
        $k = str2long($key, false);
        if (count($k) < 4) {
            for ($i = count($k); $i < 4; $i++) {
                $k[$i] = 0;
            }
        }
        $n = count($v) - 1;

        $z     = $v[$n];
        $y     = $v[0];
        $delta = 0x9E3779B9;
        $q     = floor(6 + 52 / ($n + 1));
        $sum   = int32($q * $delta);
        while ($sum != 0) {
            $e = $sum >> 2 & 3;
            for ($p = $n; $p > 0; $p--) {
                $z  = $v[$p - 1];
                $mx = int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $y  = $v[$p]  = int32($v[$p] - $mx);
            }
            $z   = $v[$n];
            $mx  = int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $y   = $v[0]   = int32($v[0] - $mx);
            $sum = int32($sum - $delta);
        }
        return long2str($v, true);
    }
}

if (!function_exists("isPcRegistered")) {
    function isPcRegistered()
    {
        static $isRegister;

        if (isset($isRegister)) {
            return $isRegister;
        }

        $isRegister = app('App\Utils\Register')->isPcRegistered();

        return $isRegister;
    }
}

if (!function_exists("isMobileRegistered")) {
    function isMobileRegistered()
    {
        static $isRegister;

        if (isset($isRegister)) {
            return $isRegister;
        }

        $isRegister = app('App\Utils\Register')->isMobileRegistered();

        return $isRegister;
    }
}

/**
 * 加密、解密函数
 * $str = 'abcdef';
 * $key = 'eoffice9731';
 * echo authCode($str,'ENCODE',$key,0); //加密
 * $str = '56f4yER1DI2WTzWMqsfPpS9hwyoJnFP2MpC8SOhRrxO7BOk';
 * echo authCode($str,'DECODE',$key,0); //解密
 *
 * @param  [type]  $string    [需要加密、解密的字符串]
 * @param  string  $operation [DECODE-解密 ENCODE-解密]
 * @param  string  $key       [密钥]
 * @param  integer $expiry    [密文有效期]
 * @return [type]             [description]
 */
if (!function_exists("authCode")) {
    function authCode($string, $operation = 'DECODE', $key = 'eoffice9731', $expiry = 0)
    {

        // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
        $ckey_length = 4;

        // 密匙
        $key = md5($key);

        // 密匙a会参与加解密
        $keya = md5(substr($key, 0, 16));

        // 密匙b会用来做数据完整性验证
        $keyb = md5(substr($key, 16, 16));

        // 密匙c用于变化生成的密文
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

        // 参与运算的密匙
        $cryptkey   = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，
        //解密时会通过这个密匙验证数据完整性
        // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
        $string        = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);
        $result        = '';
        $box           = range(0, 255);
        $rndkey        = array();

        // 产生密匙簿
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
        for ($j = $i = 0; $i < 256; $i++) {
            $j       = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp     = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        // 核心加解密部分
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a       = ($a + 1) % 256;
            $j       = ($j + $box[$a]) % 256;
            $tmp     = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;

            // 从密匙簿得出密匙进行异或，再转成字符
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'DECODE') {

            // 验证数据有效性，请看未加密明文的格式
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {

            // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
            // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }
}

/**
 * 获取配置
 * @param key string 配置名
 * @param default mixed 配置为空时的默认值
 * @param enableMultipleCaseConfig bool 允许多案例配置，默认允许
 */
if (!function_exists("envOverload")) {
    function envOverload($key, $default = null, $enableMultipleCaseConfig = true)
    {
        static $defaultConfig = null;
        static $currentConfig = null;
        if($defaultConfig === null){
            $defaultConfig = getConfigIniData();
        }
        $config = $defaultConfig;
        // 获取动态配置
        if( $enableMultipleCaseConfig ) {
            if($currentConfig === null) {
                $currentConfig = getConfigIniData(getCurrentOaCaseId());
            }
            if (is_array($config) && is_array($currentConfig)) {
                $config = array_merge($config, $currentConfig);
            }
        }

        if (empty($config)) {
            return env($key, $default);
        }

        if (isset($config[$key])) {
            return $config[$key];
        }

        return $default;
    }
}

/**
 * 根据案例id获取配置文件数据
 * @param caseId string optional 案例id
 */
if (!function_exists("getConfigIniData")) {
    function getConfigIniData($caseId = ''){
        $documentRoot = dirname(getenv('DOCUMENT_ROOT'));
        if (empty($documentRoot)) {
            $documentRoot = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
        }
        $filePath = '';
        if($caseId != ''){
            $filePath = $documentRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'config_' . $caseId .'.ini';
        }else{
            $filePath = $documentRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'config.ini';
        }

        if ($filePath == '' || !file_exists($filePath)) {
            return [];
        }

        $data = readLinesFromFile($filePath);

        $envFile = base_path('.env');
        // 默认配置合并.env
        if ($caseId == 0 && is_file($envFile)) {
            $dataEnv = readLinesFromFile($envFile);
            $data    = array_merge($data, $dataEnv);
        }
        return $data;
    }
}

/**
 * 获取当前OA实例的CaseId
 * @param caseId string optional 案例id
 */
if (!function_exists("getCurrentOaCaseId")) {
    function getCurrentOaCaseId() {
        $caseId = '';
        if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] != '') {
            $serverName = explode('.', $_SERVER['SERVER_NAME']);
            $caseId = $serverName[0] ?? '';
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_SERVER']) && $_SERVER['HTTP_X_FORWARDED_SERVER'] != '') {
            $serverName = explode('.', $_SERVER['HTTP_X_FORWARDED_SERVER']);
            $caseId = $serverName[0] ?? '';
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST']) && $_SERVER['HTTP_X_FORWARDED_HOST'] != '') {
            $serverName = explode('.', $_SERVER['HTTP_X_FORWARDED_HOST']);
            $caseId = $serverName[0] ?? '';
        }
        // 获取CASE_ID信息, 优先获取header中CASE_ID信息
        if (isset($_REQUEST['CASE_ID']) && is_string($_REQUEST['CASE_ID']) && !empty($_REQUEST['CASE_ID']) && $_REQUEST['CASE_ID'] != 'null' ) {
            $caseId =  $_REQUEST['CASE_ID'];
        }
        if (isset($_SERVER['HTTP_CASE_ID']) && is_string($_SERVER['HTTP_CASE_ID']) && !empty($_SERVER['HTTP_CASE_ID']) && $_SERVER['HTTP_CASE_ID'] != 'null' ) {
            $caseId = $_SERVER['HTTP_CASE_ID'];
        }

        return $caseId;
    }
}

if (!function_exists("getDatexAxis")) {
    function getDatexAxis($dateType, $dateValue)
    {
        return Utils::getDatexAxis($dateType, $dateValue);
    }
}
if (!function_exists("getDateQuery")) {
    function getDateQuery($dateType, $dateValue, $dateFieldName)
    {
        return Utils::getDateQuery($dateType, $dateValue, $dateFieldName);
    }
}
if (!function_exists("parseDbRes")) {
    function parseDbRes(&$db_res, &$item)
    {
        return Utils::parseDbRes($db_res, $item);
    }
}

if (!function_exists("setEnv")) {
    function setEnv($name, $value = null)
    {
        if (function_exists('apache_setenv')) {
            apache_setenv($name, $value);
        }

        if (function_exists('putenv')) {
            putenv("$name=$value");
        }
    }
}

if (!function_exists("readLinesFromFile")) {
    function readLinesFromFile($filePath)
    {
        static $data;

        $key = md5($filePath);
        if (isset($data[$key])) {
            return $data[$key];
        }

        $autodetect = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', '1');
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        ini_set('auto_detect_line_endings', $autodetect);

        $data       = [];
        $data[$key] = [];
        foreach ($lines as $line) {
            if (strpos($line, '=') === false) {
                continue;
            }

            if (strpos(ltrim($line), '#') === 0) {
                continue;
            }

            if (strpos(ltrim($line), '//') === 0) {
                continue;
            }

            list($k, $v)          = explode('=', $line);
            $data[$key][trim($k)] = trim($v);
        }

        return $data[$key];
    }
}

if (!function_exists("createExportDir")) {
    function createExportDir()
    {
        $dirs = [
            base_path('public/export/')
        ];

        $dir = '';
        foreach ($dirs as $v) {
            $dir .= $v;

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        return $dir;
    }
}
//与date方法参数一致
if (!function_exists("getBeyondUnixDate")) {
    function getBeyondUnixDate($format, $timeStamp)
    {
        $obj = new DateTime("@$timeStamp");
        return $obj->format($format);
    }
}

if (!function_exists('digitUppercase')) {
    /**
     * 人民币小写转大写
     *
     * @param string $number 数值
     * @param string $int_unit 币种单位，默认"元"，有的需求可能为"圆"
     * @param bool $is_round 是否对小数进行四舍五入
     * @param bool $is_extra_zero 是否对整数部分以0结尾，小数存在的数字附加0,比如1960.30，
     *             有的系统要求输出"壹仟玖佰陆拾元零叁角"，实际上"壹仟玖佰陆拾元叁角"也是对的
     * @return string
     */
    function digitUppercase($number = 0, $int_unit = '元', $is_round = false, $is_extra_zero = false)
    {
        if ($number) {
            if (strpos($number, ',')) {
                $number = str_replace(',', '', $number);
            }
            $minusFlag = false;
            if (substr($number, 0, 1) == '-') {
                $number    = substr($number, 1);
                $minusFlag = true;
            }
        } else {
            if ($number === '') {
                return '';
            }
            return '零' . $int_unit . '整';
        }
        // 将数字切分成两段
        $parts = explode('.', $number, 2);
        $int   = isset($parts[0]) ? strval($parts[0]) : '0';
        $dec   = isset($parts[1]) ? strval($parts[1]) : '';

        // 如果小数点后多于2位，不四舍五入就直接截，否则就处理
        $dec_len = strlen($dec);
        if (isset($parts[1]) && $dec_len > 2) {
            $dec = $is_round
            ? substr(strrchr(strval(round(floatval("0." . $dec), 2)), '.'), 1)
            : substr($parts[1], 0, 2);
        }

        // 当number为0.001时，小数点后的金额为0元
        if (empty($int) && empty($dec)) {
            return '零' . $int_unit . '整';
        }

        // 定义
        $chs     = array('0', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
        $uni     = array('', '拾', '佰', '仟');
        $dec_uni = array('角', '分');
        $exp     = array('', '万');
        $res     = '';

        // 整数部分从右向左找
        for ($i = strlen($int) - 1, $k = 0; $i >= 0; $k++) {
            $str = '';
            // 按照中文读写习惯，每4个字为一段进行转化，i一直在减
            for ($j = 0; $j < 4 && $i >= 0; $j++, $i--) {
                $u   = $int[$i] > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位
                $str = $chs[$int[$i]] . $u . $str;
            }
            //echo $str."|".($k - 2)."<br>";
            $str = rtrim($str, '0'); // 去掉末尾的0
            $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0
            if (!isset($exp[$k])) {
                $exp[$k] = $exp[$k - 2] . '亿'; // 构建单位
            }
            $u2  = $str != '' ? $exp[$k] : '';
            $res = $str . $u2 . $res;
        }

        // 如果小数部分处理完之后是00，需要处理下
        $dec = rtrim($dec, '0');

        // 小数部分从左向右找
        if (!empty($dec)) {
            $res .= $int_unit;

            // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求
            if ($is_extra_zero) {
                if (substr($int, -1) === '0') {
                    $res .= '零';
                }
            }

            for ($i = 0, $cnt = strlen($dec); $i < $cnt; $i++) {
                $u = $dec[$i] > 0 ? $dec_uni[$i] : ''; // 非0的数字后面添加单位
                $res .= $chs[$dec[$i]] . $u;
            }
            $res = rtrim($res, '0'); // 去掉末尾的0
            $res = preg_replace("/0+/", "零", $res); // 替换多个连续的0
        } else {
            if (empty($res)) {
                $res .= '零' . $int_unit . '整';
            } else {
                $res .= $int_unit . '整';
            }
        }
        if ($minusFlag) {
            $res = '(负)' . $res;
        }
        return $res;
    }
}

/**
 * 二维数组根据数组的中某个元素排序
 * @param $arrays
 * @param $sort_key
 * @param $sort_order
 * @param $sort_type
 * @return array
 */
if (!function_exists("mult_array_sort")) {
    function mult_array_sort($arrays, $sort_key, $sort_order = SORT_DESC, $sort_type = SORT_NUMERIC)
    {
        if (!$arrays) {
            return $arrays;
        }
        if (is_array($arrays)) {
            foreach ($arrays as $array) {
                if (is_array($array)) {
                    $key_arrays[] = $array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_arrays, $sort_order, $sort_type, $arrays);
        return $arrays;
    }
}
// 功能函数，验证curl请求的地址，是否在系统设置的白名单中
if (!function_exists("check_white_list")) {
    function check_white_list($url, $params = [])
    {
        return Utils::checkWhiteList($url, $params);
    }
}

if (!function_exists("check_date_valid")) {
    function check_date_valid($date, $formats = array("Y-m-d", "Y/m/d"))
    {
        $unixTime =
            strtotime($date);
        if (!$unixTime) {
            return false;
        }
        //校验日期的有效性
        foreach ($formats as $format) {
            if (date($format, $unixTime) == $date) {
                return
                    true;
            }
        }

        return false;
    }
}

if (!function_exists("paginate")) {
    //分页
    function paginate(\Illuminate\Database\Eloquent\Builder $query
        , $type = null, $page = null , $limit = null)
    {
        // 不分页
        if ($page === 0) {
            $list = $query->get();
            return [
                'total' => $list->count(),
                'list' => $list,
            ];
        }
        $queryCopy = clone $query;
        $total = $queryCopy->count();

        if (is_null($limit)) {
            $limit = \Request::input('limit', 20);
        }
        if (is_null($page)) {
            $page = \Request::input('page', 1);
        }

        $page && $query->forPage($page, $limit);

        if (is_null($type)) {
            $list = $query->get();
        } else if ($type === 'array') {
            $list = $query->get()->toArray();
        } else if (is_callable($type)) {
            $list = $type($query);
        }

        return [
            'total' => $total,
            'list' => $list
        ];
    }
}

/**
 * 递归创建目录
 * @param $dir 目录
 * @param int $mode 权限
 * @return bool
 */
if (!function_exists("dir_make")) {
    function dir_make($dir, $mode = 0755)
    {
        if (file_exists($dir)) {
            return true;
        }
        if (is_dir($dir) || mkdir($dir, $mode, true)) {
            return true;
        }
        if (!dir_make(dirname($dir), $mode)) {
            return false;
        }
        return mkdir($dir, $mode);
    }
}

/**
 * 功能函数，linux环境下，判断目录的权限，默认判断'0777'--string
 */
if (!function_exists("verify_dir_permission")) {
    function verify_dir_permission($dir, $mode = '0777')
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            //win系统
            return true;
        } else {
            // 获取权限
            if($mode != substr(sprintf("%o",fileperms($dir)),-4)) {
                // '0x000026' => '没有 :dir 目录的写入权限，请给此目录赋权。',
                return ['code' => ['0x000026','common'], 'dynamic' => trans('common.0x000026', ['dir' => $dir])];
            }
        }
    }
}

/**
 * 功能函数，linux环境下，判断文件的权限，默认判断'0777'--string
 */
if (!function_exists("verify_file_permission")) {
    function verify_file_permission($file, $mode = '0777')
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            //win系统
            return true;
        } else {
            // 获取权限
            if($mode != substr(sprintf("%o",fileperms($file)),-4)) {
                // '0x000027' => '没有 :file 文件的写入权限，请给此文件赋权。',
                return ['code' => ['0x000027','common'], 'dynamic' => trans('common.0x000027', ['file' => $file])];
            }
        }
    }
}

/**
 * make方法类似于app，在调用其他模块的service时，多了权限菜单和数据权限验证以及拦截错误，一旦其他模块返回code码程序不继续执行了
 * @param $class
 */
if (!function_exists("make")) {
    function make($class, $validatePermission = true, $intercept = true)
    {
        if (!$validatePermission && !$intercept) {
            return app($class);
        }
        $instance = \App\Utils\XiaoE\Service::getInstance();
        $instance->class = $class;
        if (is_bool($validatePermission)) {
            $instance->validate = $validatePermission;
        } else if (is_string($validatePermission)) {
            $instance->permission = $validatePermission;
        }
        $instance->intercept = $intercept;
        return $instance;
    }
}

if (!function_exists("check_email")) {
    function check_email($email)
    {
        $reg = '/^[A-Za-z\d]+([-_.][A-Za-z\d]+)*@([A-Za-z\d]+[-.])+[A-Za-z\d]{2,4}$/';
        return (bool) preg_match($reg, $email);
    }
}

if (!function_exists("version")) {
    function version($version = null)
    {
       return Utils::version($version);
    }
}

// 判断传入的版本号是否大于当前系统的版本号，如果大于则返回true
if (!function_exists("check_version_larger_than_system_version")) {
    function check_version_larger_than_system_version($version = '')
    {
       return Utils::checkVersionLargerThanSystemVersion($version);
    }
}

/**
 * 保留指定小数位置
 * @param string $num 数值
 * @param int $places 位数
 * @param bool $round_flag 是否四舍五入
 * @return string
 */
if (!function_exists('decimal_places')) {
    function decimal_places($num, $places, $round_flag = true)
    {
        if ($round_flag) {
            return number_format((float)$num, $places, '.', '');
        }
        if ($places) {
            return substr(number_format((float)$num, ++$places, '.', ''), 0, -1);
        } else {
            return intval($num);
        }
    }
}


/**
 * 格式化字符串转为数值
 * @param string $str
 * @return string
 */
if (! function_exists('string_numeric'))
{
    function string_numeric($str)
    {
        // 字符串首位有负数符号，则先保留负号，如果确实是数字则最后再把负号加上
        $minus = (strpos($str, '-') === 0) ? '-' : '';

        $string = preg_replace("/[^0-9.]/", '', $str); // 保留点号和数值
        $number = preg_replace("/[^0-9]/", '', $str);  // 只保留数值
        $position = strpos($string, '.');
        if ($position) {
            $tmpNum = substr_replace($number, '.', $position, 0);
            return $tmpNum ? $minus . $tmpNum : '';
            // return substr_replace($number, '.', $position, 0);
        }
        return $string ? ($minus . $string) : '';
    }
}

/***
 * 判断整数或者字符串整数
 */
if (! function_exists('is_int_or_string_int'))
{
    function is_int_or_string_int($input)
    {
        return is_numeric($input) && !strpos($input, '.');
    }
}
/***
 * 判断整数或者字符串整数
 */
if (! function_exists('access_path'))
{
    function access_path($path = '')
    {
        $accessPath = envOverload('ACCESS_PATH', 'access');
        $dir = base_path('public/' . $accessPath . '/' . $path);
        if (is_dir($dir)) {
            return $dir;
        }
        dir_make($dir);
        return $dir;
    }
}
/***
 * 根据地址获取获取经纬度
 */
if (! function_exists('address_to_geocode'))
{
    function address_to_geocode($address)
    {
        return Utils::addressToGeocode($address);
    }
}

/***
 * 根据经纬度获取获取地址
 */
if (! function_exists('geocode_to_address'))
{
    function geocode_to_address($geoCode)
    {
        return Utils::geocodeToAddress($geoCode);
    }
}

/***
 * 获取周边地址
 */
if (! function_exists('get_nearby_place'))
{
    function get_nearby_place($data)
    {
        return Utils::getNearbyPlace($data);
    }
}

/**
 * 获取登录用户信息
 */
if (! function_exists('user'))
{
    function user()
    {
        $token = \Illuminate\Support\Facades\Request::bearerToken() ?: \Illuminate\Support\Facades\Request::getPassword();
        empty($token) && $token = \Illuminate\Support\Facades\Request::input('api_token');// 从url中获取token
        if ($authInfo = \Illuminate\Support\Facades\Cache::get($token)) {
            return [
                'user_id'                => $authInfo->user_id,
                'user_name'              => $authInfo->user_name,
                'user_accounts'          => $authInfo->user_accounts,
                'user_job_number'        => $authInfo->user_job_number ?? '',
                'dept_id'                => $authInfo->userHasOneSystemInfo->dept_id ?? ($authInfo->dept_id ?? null),
                'dept_name'              => $authInfo->userHasOneSystemInfo->userSystemInfoBelongsToDepartment->dept_name ?? ($authInfo->dept_name ?? null),
                'roles'                  => $authInfo->roles,
                'role_id'                => isset($authInfo->roles) ? array_column($authInfo->roles,'role_id') : parseRoles($authInfo['userHasManyRole'],'role_id'),
                'role_name'              => isset($authInfo->roles) ? array_column($authInfo->roles,'role_name') : parseRoles($authInfo['userHasManyRole'],'role_name'),
                'menus'                  =>  $authInfo->menus ?? [],
                'post_priv'              => $authInfo->userHasOneSystemInfo->post_priv ?? ($authInfo->post_priv ?? 0),
                'max_role_no'            => $authInfo->userHasOneSystemInfo->max_role_no ?? ($authInfo->max_role_no ?? 0),
                'user_position_name'     => $authInfo->user_position_name ?? '',
                'user_area_name'         => $authInfo->user_area_name ?? '',
                'user_city_name'         => $authInfo->user_city_name ?? '',
                'user_workplace_name'    => $authInfo->user_workplace_name ?? '',
                'user_job_category_name' => $authInfo->user_job_category_name ?? '',
                'post_dept'              => $authInfo->userHasOneSystemInfo->post_dept ?? ($authInfo->post_dept ?? ''),
                'phone_number'           => $authInfo->userHasOneInfo->phone_number ?? ($authInfo->phone_number ?? ''),
            ];
        }
        return [];
    }
}

/**
 * 解析用户角色
 */
if (! function_exists('parseRoles'))
{
    function parseRoles($roles, $type)
    {
        if(empty($roles)) {
            return [];
        }
        static $parseRole = array();

        if(!empty($parseRole)){
            return $parseRole[$type];
        }
        $roleId = $roleName = $roleArray = [];

        foreach ($roles as $role) {
            $roleId[]   = $role['role_id'];
            $roleName[] = $roleArray[$role['role_id']] = $role['hasOneRole']['role_name'];
        }

        $parseRole = ['role_id' => $roleId,'role_name' => $roleName,'role_array' => $roleArray];

        return $parseRole[$type];
    }
}

if (! function_exists('flow_out_extra_msg'))
{
    function flow_out_extra_msg($moduleName, array $errorInfo) {
        $keys = array_keys($errorInfo);
        $replace = [
            'moduleName' => trans($moduleName),
            'indexes' => implode(',', $keys)
        ];
        $content = '</br>' . trans('common.detailed_flow_out_error_info', $replace) . '</br>';
        foreach ($errorInfo as $key => $msg) {
            $content .= $key . '、' . $msg . '</br>';
        }
        return $content;
    }
}

// 解密参数
if (!function_exists('decrypt_params')) {
    function decrypt_params($params, $padding = false, $initValue = false, $base64Encrypt = false, $urlDecode = false) {
        if ($initValue) {
            $token = Request::input('api_token');
            if (empty($token)) {
                $token = Request::bearerToken();
            }

            if (empty($token)) {
                $token = Request::getPassword();
            }
        }
        $key = config('app.key');
        $iv = $initValue ? substr($token, 0, 16) : '1234567890123456';

        if ($urlDecode) {
            $params = urldecode($params);
        }

        if ($base64Encrypt) {
            $params = base64_decode($params);
        }
        if ($padding) {
            $decrypted = openssl_decrypt($params, 'aes-256-cbc', $key, OPENSSL_ZERO_PADDING , $iv);
            $decrypted = rtrim($decrypted);
        } else {
            $decrypted = openssl_decrypt($params, 'aes-256-cbc', $key, 0 , $iv);
        }
        return $decrypted;
    }
}

// 解密参数
if (!function_exists('encrypt_params')) {
    function encrypt_params($params, $padding = false, $initValue = false, $base64Encrypt = false) {
        if ($initValue) {
            $token = Request::input('api_token');
            if (empty($token)) {
                $token = Request::bearerToken();
            }

            if (empty($token)) {
                $token = Request::getPassword();
            }
        }
        $key = config('app.key');
        $iv = $initValue ? substr($token, 0, 16) : '1234567890123456';
        $params = urldecode($params);

        if ($padding) {
            $decrypted = openssl_encrypt($params, 'aes-256-cbc', $key, OPENSSL_ZERO_PADDING , $iv);
            $decrypted = rtrim($decrypted);
        } else {
            $decrypted = openssl_encrypt($params, 'aes-256-cbc', $key, 0 , $iv);
        }
        return $base64Encrypt ? base64_encode($decrypted) : $decrypted;
    }
}


/**
 * redis 加锁处理流程并发提交等场景
 */
if (! function_exists('lock')) {
    function lock($callback, $infoMessage = [])
    {
        $ttl = 4;
        $random = uniqid(mt_rand(), true);
        if (\Illuminate\Support\Facades\Redis::set('lock', $random, 'EX', $ttl, 'NX')) {
            $callbackValue = call_user_func($callback);
            if (\Illuminate\Support\Facades\Redis::get('lock') == $random) {
                \Illuminate\Support\Facades\Redis::del('lock');
            }
            return $callbackValue;
        } else {
            return $infoMessage;
        }
    }
}

if (!function_exists("emptyWithoutZero")) {
    function emptyWithoutZero($data, $needTrim = false)
    {
        $needTrim && $data = trim($data);
        if ($data === 0 || $data === '0' || $data === (float)0) {
            return false;
        }
        return empty($data);
    }
}

if (!function_exists("scalar_array_merge")) {
    function scalar_array_merge(array $data, ...$arrays)
    {
        $result = array_flip($data);
        foreach ($arrays as $array) {
            foreach ($array as $value) {
                $result[$value] = 0;
            }
        }
        return array_keys($result);
    }
}

/**
 * 百分数，对0与100修正，如：7个任务，完成6个，计算完成率
 * $value 分子
 * $total 分母
 */
if (!function_exists("percent")) {
    function percent($value, $total, $precision = 2)
    {
        $value *= 100;
        return percent_division($value, $total, $precision);
    }
}

/**
 * 百分比平分，对0与100修正，如：多个子任务进度，父任务进度为子任务进度平均值
 * $total 分子
 * $number 分母
 * $precision：精确小数位数
 */
if (!function_exists("percent_division")) {
    function percent_division($total, $number, $precision = 0)
    {
        if (!$total || !$number) {
            return 0;
        }
        $res = round( $total / $number, $precision);
        // 对数据进行修正，因为四舍五入存在误差，我们要保证0与100的绝对准确性
        $isRight = $res * $number == $total;
        if (!$isRight && ($res == 0 || $res == 100)) {
            $minNum = $precision == 0 ? 1 : (1 / pow(10, $precision)); // 最小精确度值
            $res = $res == 0 ? $res + $minNum : $res - $minNum;
        }
        return $res;
    }
}

/**
 * 可批量从数组中获取数据，也可以更改获取的值得key（$keys为数组则返回数组，为单个key则返回相应的值）
 *
 * @param $array
 * @param $keys
 * @param null $default
 * @return array|mixed|null
 */
if (!function_exists("array_extract")) {
    function array_extract($array, $keys, $default = null)
    {
        if (is_null($keys)) {
            return $default;
        }
        if (!is_array($keys)) {
            return Arr::get($array, $keys, $default);
        }

        $newArray = [];
        foreach ($keys as $newKey => $key) {
            $newArray[$key] = array_extract($array, $key, $default);
            if (is_string($newKey)) {
                $newArray[$newKey] = $newArray[$key];
                unset($newArray[$key]);
            }
        }
        return $newArray;
    }
}

/**
 * 获得随机字符串
 * @param $len             需要的长度
 * @param $special        是否需要特殊符号
 * @return string       返回随机字符串
 */
if ( !function_exists('getRandomStr')) {
    function getRandomStr($len,$special=false){
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9"
        );

        if($special){
            $chars = array_merge($chars, array(
                "!", "@", "#", "$", "?", "|", "{", "/", ":", ";",
                "%", "^", "&", "*", "(", ")", "-", "_", "[", "]",
                "}", "<", ">", "~", "+", "=", ",", "."
            ));
        }

        $charsLen = count($chars) - 1;
        shuffle($chars);                            //打乱数组顺序
        $str = '';
        for($i=0; $i<$len; $i++){
            $str .= $chars[mt_rand(0, $charsLen)];    //随机取出一位
        }
        return $str;
    }
}

/**
 * Get the path to the public folder.
 *
 * @param  string  $path
 * @return string
 */
if (! function_exists('public_path')) {
    function public_path($path = '')
    {
        return rtrim(app()->basePath('public/' . $path), '/');
    }
}


/**
 * 图片格式转换
 * @param string $image_path 文件路径或url
 * @param string $to_ext 待转格式，支持png,gif,jpeg,wbmp,webp,xbm
 * @param null|string $save_path 存储路径，null则返回二进制内容，string则返回true|false
 * @return boolean|string $save_path是null则返回二进制内容，是string则返回true|false
 * @throws Exception
 */
if (! function_exists('transform_image'))
{
    function transform_image($image_path, $to_ext = 'png', $save_path = null)
    {
        if (! in_array($to_ext, ['png', 'gif', 'jpeg', 'wbmp', 'webp', 'xbm'])) {
            throw new \Exception('unsupport transform image to ' . $to_ext);
        }
        switch (exif_imagetype($image_path)) {
            case IMAGETYPE_GIF :
                $img = imagecreatefromgif($image_path);
                break;
            case IMAGETYPE_JPEG :
            case IMAGETYPE_JPEG2000:
                $img = imagecreatefromjpeg($image_path);
                break;
            case IMAGETYPE_PNG:
                $img = imagecreatefrompng($image_path);
                break;
            case IMAGETYPE_BMP:
            case IMAGETYPE_WBMP:
                $img = imagecreatefromwbmp($image_path);
                break;
            case IMAGETYPE_XBM:
                $img = imagecreatefromxbm($image_path);
                break;
            case IMAGETYPE_WEBP: //(从 PHP 7.1.0 开始支持)
                $img = imagecreatefromwebp($image_path);
                break;
            default :
                throw new \Exception('Invalid image type');
        }
        $function = 'image'.$to_ext;
        if ($save_path) {
            return $function($img, $save_path);
        } else {
            $tmp = __DIR__.'/'.uniqid().'.'.$to_ext;
            if ($function($img, $tmp)) {
                $content = file_get_contents($tmp);
                unlink($tmp);
                return $content;
            } else {
                unlink($tmp);
                throw new \Exception('the file '.$tmp.' can not write');
            }
        }
    }
}
if (!function_exists("dateToDatetime")) {
    function dateToDatetime($date, $isStartOfDay = true)
    {
        $date = strtotime($date);
        if (!$date) {
            return null;
        }
        $carbon = \Carbon\Carbon::createFromTimestamp($date);
        $isStartOfDay ? $carbon->startOfDay() : $carbon->endOfDay();
        return $carbon->toDateTimeString();
    }
}

// 获取bin目录地址
if (!function_exists('get_bin_dir')) {
    function get_bin_dir() {
        $documentRoot = dirname(getenv('DOCUMENT_ROOT'));
        if (empty($documentRoot)) {
            $documentRoot = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
        }
        $filePath = $documentRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR;
        return $filePath;
    }
}

// 处理相对路径的url地址，拼接上ip域名和端口
if (!function_exists('parse_relative_path_url')) {
    function parse_relative_path_url($url) {
        if (!empty($url) && is_string($url) && strpos($url, 'http') === false) {
            if (!isset($_SERVER['REQUEST_SCHEME']) || !isset($_SERVER['SERVER_NAME']) || !isset($_SERVER["SERVER_PORT"])) {
                return $url;
            }
            $http = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'];
            if ($_SERVER["SERVER_PORT"] != '80') {
                $http .= ':' . $_SERVER["SERVER_PORT"];
            }
            $url = $http . '/' . ltrim(trim($url), '/');
        }
        return $url;
    }
}

if (!function_exists('log_write_to_file')){
    //记录数据日志
    function log_write_to_file($data,$dirAndFileName){
        $fileFolder = date('Ymd');
        $fileDir = date('Ym');
        $requestData=date('Y-m-d H:i:s').'***:'.json_encode($data,JSON_UNESCAPED_UNICODE)."\r\n";
        $dir = storage_path().'/logs/'.$dirAndFileName['dirName'].'/'.$fileDir.'/';
        //$dir = storage_path().'/logs/todo-push/'.$fileDir.'/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $fileName =  $dir.$fileFolder.$dirAndFileName['fileName'].'.log';
        //$fileName =  storage_path().'/logs/'.$fileName.'.log';
        file_put_contents($fileName,$requestData,FILE_APPEND);
    }
}

/**
 * 数组按指定 key 分组
 */
if (! function_exists('array_group_by'))
{
    function array_group_by($arr, $key)
    {
        $grouped = [];
        foreach ($arr as $value) {
            $grouped[$value[$key]][] = $value;
        }
        // Recursively build a nested grouping if more parameters are supplied
        // Each grouped array value is grouped according to the next sequential key
        if (func_num_args() > 2) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $params = array_merge([$value], array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array('array_group_by', $params);
            }
        }
        return $grouped;
    }
}
/**
 * sql参数过滤, 防止sql注入
 */
if (! function_exists('security_filter'))
{
    function security_filter($input)
    {
        return Utils::securityFilter($input);
    }

}

/**
 * 冗余函数，框架已废弃，但是二开存在用户需要使用，因此冗余，正常代码都不要在使用了
 */
if (! function_exists('array_get'))
{
    function array_get($array, $key, $default = null)
    {
        return Arr::get($array, $key, $default);
    }

}

/**
 * 同上，冗余函数
 */
if (! function_exists('array_pluck'))
{
    function array_pluck($array, $value, $key = null)
    {
        return Arr::pluck($array, $value, $key);
    }

}

// 判断文件夹是否为空
if (! function_exists('is_empty_dir'))
{
    function is_empty_dir($dir) {
        if (!is_dir($dir)) {
            return true;
        }
        $handle = @opendir($dir);
        while (false !== ($entry = @readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                return false;
            }
        }
        return true;
    }
}
