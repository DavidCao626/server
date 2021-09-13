<?php

namespace App\Utils;

use App\Utils\Blogger;
use Cache;
use DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
class Utils
{
    /**
     * 获取当前路由访问的模块
     *
     * @return string | array
     */
    public static function getCurrentModule()
    {
        $firstSegment = \Illuminate\Http\Request::createFromGlobals()->segment(2);

        if($firstSegment === 'system') {
            $secondSegment = \Illuminate\Http\Request::createFromGlobals()->segment(3);

            return ['System', static::toCamelCase($secondSegment)];
        }

        return static::toCamelCase($firstSegment);
    }
    /**
     * 注册路由
     *
     * @param type $app
     * @param type $moduleDir
     * @param type $module
     * @param type $routeConfig
     *
     * @return boolean
     */
    public static function registerRoutes($app, $moduleDir, $module, $routeConfig = [])
    {
        if (empty($module)) {
            return false;
        }

        $prefix = '';
        if (is_array($module)) {
            $prefix = 'system/';
            $file = $moduleDir . '/' . $module[0] . '/' . $module[1] . '/routes.php';
            $controller = $module[0] . '\\' . $module[1] . '\Controllers\\' . $module[1] . 'Controller';
        } else {
            $file = $moduleDir . '/' . $module . '/routes.php';
            $controller = $module . '\Controllers\\' . $module . 'Controller';
        }

        if(empty($routeConfig)){
            if (is_file($file)) {
                require $file;
            }
        }

        if(!empty($routeConfig)) {
            foreach ($routeConfig as $routeInfo) {
                $method = isset($routeInfo[2]) && is_string($routeInfo[2]) ? $routeInfo[2] : 'get';
                $app->$method($prefix . $routeInfo[0], $controller . '@' . $routeInfo[1]);
            }
        }
    }
    /**
     * 转驼峰
     *
     * @param string $str
     * @param string $delimter
     *
     * @return string
     */
    private static function toCamelCase($str, $delimter = '-')
    {
        $array = explode($delimter, $str);

        $name = array_reduce($array, function($carry, $item) {
            return $carry . ucfirst($item);
        });

        return $name;
    }

    public static function getComboboxTableName($comboboxId, $prefix)
    {
        static $map;

        if(!$comboboxId){
            return false;
        }

        if(!isset($map[$comboboxId])){
            $comboboxTypes = DB::table("system_combobox")->get()->toArray();
            $map = collect($comboboxTypes)->mapWithKeys(function ($item) {
                    return [$item->combobox_id => strtolower($item->combobox_identify)];
                });
        }

        $comboboxIdentify = $map[$comboboxId] ?? '';

        return $prefix . $comboboxIdentify;
    }
    public static function getUserIdGroupByLocale($userIds)
    {
        if (!$userIds) {
            return [];
        }

        if (is_string($userIds)) {
            $userIds = explode(',', rtrim($userIds, ','));
        }

        $group = [];
        if (!empty($userIds)) {
            $langService = app('App\EofficeApp\Lang\Services\LangService');

            foreach ($userIds as $userId) {
                $locale = $langService->getUserLocale($userId);

                $group[$locale][] = $userId;
            }
        }

        return $group;
    }
    public static function transDynamic($langKeys, array $replace, $local)
    {
        return static::handleTrans($langKeys, $replace, $local, function($langTable, $keys, $langKeys, $local) {
                    $localeKey = 'lang_' . $local;
                    $joinLangKey = implode('.', $keys);
                    if (Redis::hExists($localeKey, $joinLangKey)) {
                        return Redis::hGet($localeKey, $joinLangKey);
                    } else {
                        $lang = DB::table($langTable)->select(['lang_value'])
                                        ->where('table', $keys[0])
                                        ->where('column', $keys[1])
                                        ->where('option', $keys[2])
                                        ->where('lang_key', $keys[3])->first();

                        $transLang = $lang ? $lang->lang_value : '';
                        if ($transLang) {
                            Redis::hSet($localeKey, $joinLangKey, $transLang);
                        }
                        return $transLang;
                    }
                });
    }
    public static function mulitTransDynamic($langKeys, array $replace, $local)
    {
        return static::handleTrans($langKeys, $replace, $local, function($langTable, $keys, $langKeys, $local) {
                    return static::getLangFromStaticArray($local, $keys, $langKeys, $langTable);
                });
    }
    public static function removeDynamicLangs($langKeys, $local = null)
    {
        if (!$langKeys) {
            return false;
        }

        if (!$local) {
            $packages = DB::table('lang_package')->select(['lang_code'])->get()->toArray();

            $local = array_column($packages, 'lang_code');
        }

        if (is_array($langKeys)) {
            $handleRemoveArray = [];

            foreach ($langKeys as $langKeyString) {
                list($table, $column, $option, $langKey) =  static::getLangKeysArray($langKeyString);

                $TCO = $table . '.' . $column . '.' . $option;

                if (isset($handleRemoveArray[$TCO])) {
                    $handleRemoveArray[$TCO][] = $langKey;
                } else {
                    $handleRemoveArray[$TCO] = [$langKey];
                }
            }

            foreach ($handleRemoveArray as $TCO => $langKeyItems) {
                list($table, $column, $option) = explode('.', $TCO);

                return static::removeDynamicLangsTernimal($table, $column, $option, $langKeyItems, $local);
            }
        } else {
            list($table, $column, $option, $langKey) = static::getLangKeysArray($langKeys);

            return static::removeDynamicLangsTernimal($table, $column, $option, [$langKey], $local);
        }
    }
    private static function removeDynamicLangsTernimal($table, $column, $option, $langKey, $local)
    {
        if(is_array($local)){
            return array_map(function($localItem) use($table, $column, $option, $langKey){
                return static::removeDynamicLangsThen($table, $column, $option, $langKey, $localItem);
            }, $local);
        } else {
            return static::removeDynamicLangsThen($table, $column, $option, $langKey, $local);
        }
    }
    private static function removeDynamicLangsThen($table, $column, $option, $langKeys, $local)
    {
        $langTable = 'lang_' . str_replace('-', '_', strtolower($local));

        if(Schema::hasTable($langTable)){
            return DB::table($langTable)->where('table', $table)->where('column', $column)->where('option', $option)->whereIn('lang_key', $langKeys)->delete();
        }

        return true;
    }
    private static function handleTrans($langKeys, array $replace, $local, $then)
    {
        $keys = static::getLangKeysArray($langKeys);

        if (is_string($keys)) {
            return $langKeys;
        }

        $local = $local ? : Lang::getLocale();

        $langTable = 'lang_' . str_replace('-', '_', strtolower($local));

        $langValue = $then($langTable, $keys, $langKeys, $local);

        return static::makeReplacements($langValue, $replace);
    }

    private static function getLangFromStaticArray($local, $keys, $langKeys, $langTable)
    {
        $localKey = 'mulitlang_' . $local;
        $joinKeys = $keys[0] . '.' . $keys[1];
        $fullJoinKeys = $keys[0] . '.' . $keys[1] . '.' . $keys[2] . '.' . $keys[3];
        if(Redis::hExists($localKey, $fullJoinKeys)){
            return Redis::hGet($localKey, $fullJoinKeys);
        } else {
            $langs = DB::table($langTable)->select(['option', 'lang_key', 'lang_value'])
                ->where('table', $keys[0])->where('column', $keys[1])->get();

            if (count($langs) > 0) {
                $map = [];
                foreach ($langs as $lang) {
                    $map[$joinKeys . '.' . $lang->option . '.' . $lang->lang_key] = $lang->lang_value;
                }
                Redis::hmset($localKey, $map);
                return $map[$fullJoinKeys] ?? '';
            } else {
                Redis::hSet($localKey, $fullJoinKeys, '');
                return '';
            }
        }
    }

    private static function getLangKeysArray($langKeys)
    {
        if (empty($langKeys)) {
            return '';
        }

        $keys = explode('.', $langKeys);

        $kSize = sizeof($keys);

        if ($kSize < 3 || $kSize > 4) {
            return $langKeys;
        }

        if ($kSize == 3) {
            array_splice($keys, 2, 0, $keys[1]);
        }

        return $keys;
    }
    public static function makeReplacements($lang, array $replace)
    {
        if (empty($replace)) {
            return $lang;
        }

        $replace = static::sortReplacements($replace);

        foreach ($replace as $key => $value) {
            $lang = str_replace(
                [':'.$key, ':'.Str::upper($key), ':'.Str::ucfirst($key)],
                [$value, Str::upper($value), Str::ucfirst($value)],
                $lang
            );
        }

        return $lang;
    }
    public static function sortReplacements(array $replace)
    {
        return (new Collection($replace))->sortBy(function ($value, $key) {
            return mb_strlen($key) * -1;
        })->all();
    }
    public static function own($key)
    {
        $token = Request::input('api_token');
        if (empty($token)) {
            $token = Request::bearerToken();
        }

        if (empty($token)) {
            $token = Request::getPassword();
        }

        $authInfo = $token ? Cache::get($token) : [];
        if(!$authInfo) {
            return false;
        }
        $own = [
            'user_id'                => $authInfo->user_id,
            'user_name'              => $authInfo->user_name,
            'user_accounts'          => $authInfo->user_accounts,
            'dept_id'                => $authInfo->dept_id,
            'dept_name'              => $authInfo->dept_name ?? '',
            'role_id'                => isset($authInfo->roles) ? array_column($authInfo->roles,'role_id') : self::parseRoles($authInfo['userHasManyRole'],'role_id'),
            'role_name'              => isset($authInfo->roles) ? array_column($authInfo->roles,'role_name') : self::parseRoles($authInfo['userHasManyRole'],'role_name'),
            'menus'                  => isset($authInfo->menus) ? $authInfo->menus : [],
            'post_priv'              => $authInfo->post_priv,
            'post_dept'              => $authInfo->post_dept,
            // 'duty_type'              => $authInfo['userHasOneSystemInfo']['duty_type'],
            // 'last_visit_time'        => $authInfo['userHasOneSystemInfo']['last_visit_time'],
            // 'last_pass_time'         => $authInfo['userHasOneSystemInfo']['last_pass_time'],
            // 'shortcut'               => $authInfo['userHasOneSystemInfo']['shortcut'],
            // 'sms_login'              => $authInfo['userHasOneSystemInfo']['sms_login'],
            // 'wap_allow'              => $authInfo['userHasOneSystemInfo']['wap_allow'],
            // 'login_usbkey'           => $authInfo['userHasOneSystemInfo']['login_usbkey'],
            // 'usbkey_pin'             => $authInfo['userHasOneSystemInfo']['usbkey_pin'],
            // 'user_status'            => $authInfo['userHasOneSystemInfo']['user_status'],
            // 'dept_name'              => $authInfo['userHasOneSystemInfo']['userSystemInfoBelongsToDepartment']['dept_name'],
            // 'sex'                    => $authInfo['userHasOneInfo']['sex'],
            // 'birthday'               => $authInfo['userHasOneInfo']['birthday'],
            // 'dept_phone_number'      => $authInfo['userHasOneInfo']['dept_phone_number'],
            // 'home_address'           => $authInfo['userHasOneInfo']['home_address'],
            // 'home_phone_number'      => $authInfo['userHasOneInfo']['home_phone_number'],
            // 'phone_number'           => $authInfo['userHasOneInfo']['phone_number'],
            // 'weixin'                 => $authInfo['userHasOneInfo']['weixin'],
            // 'email'                  => $authInfo['userHasOneInfo']['email'],
            // 'oicq_no'                => $authInfo['userHasOneInfo']['oicq_no'],
            // 'msn'                    => $authInfo['userHasOneInfo']['msn'],
            // 'notes'                  => $authInfo['userHasOneInfo']['notes'],
            // 'theme'                  => $authInfo['userHasOneInfo']['theme'],
            // 'avatar_type'            => $authInfo['userHasOneInfo']['avatar_type'],
            // 'signature_picture_type' => $authInfo['userHasOneInfo']['signature_picture_type'],
            // 'menu_hide'              => $authInfo['userHasOneInfo']['menu_hide'],
            // 'role_id'                => array_column($authInfo['roles'],'role_id'),
            // 'role_name'              => array_column($authInfo['roles'],'role_name'),
            // 'role_array'             => array_combine(array_column($authInfo['roles'],'role_id'), array_column($authInfo['roles'],'role_name')),
            // 'userHasManySuperior'    => $authInfo['userHasManySuperior'],
            // 'userHasManySubordinate' => $authInfo['userHasManySubordinate'],
        ];

        return $key ? (isset($own[$key]) ? $own[$key] : '') : $own;
    }
    private static function parseRoles($roles,$type)
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
    /**
     * @进行拼音的转换
     * @param string $str 需要进行拼音转换的字段
     */
    public static function convertPy($str)
    {
        $splitStr  = static::strSplitUtf8($str);
        $pinYinStr = "";
        $ZiMuStr   = "";

        for ($j = 0; $j < count($splitStr); $j++) {
            if (preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $splitStr[$j])) {
                //对新增加的字段的拼音进行处理的地方
                $pinyin = DB::table('pinyin')->where('hz', $splitStr[$j])->get();
                if ($pinyin) {
                    if (isset($pinyin[0])) {
                        $pinYinObj = $pinyin[0];
                        $pinYinStr .= $pinYinObj->py;
                        $ZiMuStr .= $pinYinObj->zm;
                    }
                }
            } else {
                $pinYinStr .= $splitStr[$j];
                $ZiMuStr .= $splitStr[$j];
            }
        }
        $pinYinarr = array($pinYinStr, $ZiMuStr);
        return $pinYinarr;
    }

    /**
     * @拆分字符串(处理字母、汉字、数字混合字符)
     * @param string $str 需拆分的字符串
     */
    public static function strSplitUtf8($str)
    {
        $split = 1;
        $array = array();

        for ($i = 0; $i < strlen($str);) {
            $value = ord($str[$i]);
            if ($value > 127) {
                if ($value >= 192 && $value <= 223) {
                    $split = 2;
                } elseif ($value >= 224 && $value <= 239) {
                    $split = 3;
                } elseif ($value >= 240 && $value <= 247) {
                    $split = 4;
                }

            } else {
                $split = 1;
            }

            $key = null;
            for ($j = 0; $j < $split; $j++, $i++) {
                $key .= $str[$i];
            }
            array_push($array, $key);
        }
        return $array;
    }

    /**
     * @获取系统参数
     * @param type $key
     * @param type $default
     * @return array|string 系统参数
     */
    public static function getSystemParam($key = null, $default = '')
    {
        if (is_null($key)) {
            return DB::table('system_params')->get();
        }
        $paramValue = '';
        $param      = DB::table('system_params')->where('param_key', $key)->get();
        if (count($param)) {
            $_param     = $param[0];
            $paramValue = $_param->param_value;
        }
        if ($paramValue == '') {
            return $default;
        }
        if (is_numeric($default)) {
            $paramValue = intval($paramValue);
        }
        return $paramValue;
    }

    public static function setSystemParam($key = null, $value = '')
    {
        if (is_null($key)) {
            return false;
        }

        if (DB::table('system_params')->where('param_key', $key)->count() == 0) {
            DB::table('system_params')->insert(['param_key' => $key, 'param_value' => $value]);
        } else {
            DB::table('system_params')->where('param_key', $key)->update(['param_value' => $value]);
        }

        return true;
    }

    /**
     * 获取用户属性
     * @param type $userId 可以单个用户Id或用“，”隔开的用户id字符串或用户id数组
     * @param type $field 用户属性，默认user_name
     * @return 用户属性字符串，多条用“，”分隔.
     */
    public static function getUserSimpleAttr($userId, $field = 'user_name')
    {
        if (!$userId) {
            return '';
        }

        if (is_string($userId)) {
            $userId = explode(',', trim($userId, ','));
        }

        $users = DB::table('user')->select([$field])->whereIn('user_id', $userId)->get();

		if(count($users) > 0) {
			if (count($users) > 1) {
				$userAttr = '';

				foreach ($users as $user) {
					$userAttr .= $user->$field . ',';
				}

				return rtrim($userAttr, ',');
			}

			return $users ? $users[0]->$field : '';
		}

		return '';
    }

    /**
     * @返回成功响应信息
     * @param type $data 响应数据,data不传默认只返回状态
     * @return array 成功响应信息
     */
    public static function successResponse($data = false)
    {
        return static::handleResponse($data, 1, 'data');
    }

    /**
     * @返回警告响应信息
     * @param type $code 错误编码，为字符串或数组
     * @param type $langName 对应的语言包文件名
     * @param type $dynamic 动态错误信息，优先语言包使用 ；为字符串或数组(与错误编码数组元素对应写入)
     * @return array 警告响应信息
     */
    public static function warningResponse($code, $langName = '', $dynamic = '')
    {
        return static::handleResponse(static::handleErrorCode($code, $langName, $dynamic), 1);
    }

    /**
     * @返回错误响应信息
     * @param type $code 错误编码，为字符串或数组
     * @param type $langName 对应的语言包文件名
     * @param type $dynamic 动态错误信息，优先语言包使用 ；为字符串或数组(与错误编码数组元素对应写入)
     * @return array 错误响应信息
     */
    public static function errorResponse($code, $langName = '', $dynamic = '')
    {
        return static::handleResponse(static::handleErrorCode($code, $langName, $dynamic));
    }

    /**
     * @处理错误信息
     * @param type $code
     * @param type $langName
     * @param type $dynamic
     * @return array 错误信息
     */
    public static function handleErrorCode($code, $langName, $dynamic)
    {
        if (is_string($dynamic)) {
            $dynamic = [$dynamic];
        }
        if (is_string($code)) {
            return [[
                'code'    => $code,
                'message' => (isset($dynamic[0]) && $dynamic[0]) ? $dynamic[0] : trans($langName . '.' . $code),
            ]];
        }
        $errors = [];
        foreach ($code as $k => $v) {
            if (is_array($langName)) {
                $langCode = $langName[$k] . '.' . $v;
            } else {
                $langCode = $langName . '.' . $v;
            }
            $errors[] = [
                'code'    => $v,
                'message' => (isset($dynamic[$k]) && $dynamic[$k]) ? $dynamic[$k] : trans($langCode),
            ];
        }
        return $errors;
    }

    /**
     * @处理响应信息
     * @param type $messages
     * @param type $status
     * @param type $messageStatus
     * @return array 响应信息
     */
    public static function handleResponse($messages, $status = 0, $messageStatus = 'errors')
    {
        $result = ['status' => $status];
        if ($messages !== false) {
            $result[$messageStatus] = $messages;
        }
        if (defined('LUMEN_START')) {
            // $result['runtime'] = round(microtime(true) - LUMEN_START, 3);
            $result['runtime'] = mb_substr(strval(microtime(true) - LUMEN_START), 0, 5);
        }
        try {
            $user_id = isset($_COOKIE["loginUserId"]) ? $_COOKIE["loginUserId"] : "";

            $tempRegisterUserInfo = 'registerUserInfo_' . $user_id;
            if (Cache::has($tempRegisterUserInfo)) {
                $result['registerUserInfo'] = Cache::get($tempRegisterUserInfo);
            }
        } catch (\Exception $e) {
            //已有报错，不做处理
        }
        if(isset($result['runtime'])){
            Blogger::longApiLog(Request::path(), $result['runtime']); //记录api执行时间
        }
        return $result;
    }

    /**
     * 将一些特殊字符实体化
     *
     * @param string $val
     *
     * @return string
     */
    public static function convertValue($val)
    {
        if ("" == $val) {
            return "";
        }

        $val = str_replace("&#032;", " ", $val);

        $val = str_replace("&", "&amp;", $val);
        $val = str_replace("<!--", "&#60;&#33;--", $val);
        $val = str_replace("-->", "--&#62;", $val);
        $val = preg_replace("/<script/i", "&#60;script", $val);
        $val = str_replace(">", "&gt;", $val);
        $val = str_replace("<", "&lt;", $val);
        $val = str_replace("\"", "&quot;", $val);
        $val = preg_replace("/\n/", "<br />", $val); // Convert literal newlines
        $val = preg_replace("/\\\$/", "&#036;", $val);
        $val = preg_replace("/\r/", "", $val); // Remove literal carriage returns
        $val = str_replace("!", "&#33;", $val);
        $val = str_replace("'", "&#39;", $val); // IMPORTANT: It helps to increase sql query safety.

        if (get_magic_quotes_gpc()) {
            $val = stripslashes($val);
        }

        return $val;
    }

    /**
     * CURL获取信息 //--cookie.txt 需要测试
     *
     * @param string $url
     *
     * @param array $data
     *
     * @param bool $options 额外的配置参数 例：[[CURLOPT_COOKIEJAR,'/CloudSession']]
     *
     * @return type
     *
     */
    public static function getHttps($url, $data = null , $header = [], $options = [])
    {

        try {
            if (function_exists('curl_init')) {

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_SSLVERSION, 1);
                if ($proxy = envOverload('CURL_PROXY')) {
                    $proxyArray = explode(':', $proxy);
                    curl_setopt($curl, CURLOPT_PROXY, $proxyArray[0]);
                    curl_setopt($curl, CURLOPT_PROXYPORT, $proxyArray[1]);
                }
                if ($data) {
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    if(!empty($header)) {
                        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
                    } else {
                        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json; encoding=utf-8'));
                    }
                }

                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                if(is_array($options) && count($options) > 0){
                    foreach($options as $option){
                        if(is_array($option) && count($option) == 2 && isset($option[0]) && isset($option[1])){
                            // dd($option[1]);
                            curl_setopt($curl, $option[0], $option[1]);
                        }
                    }
                }
                $output = curl_exec($curl);

                if ($output === false) {
                    $res = array(
                        "errcode" => "0x000111", //获取错误异常
                        "errmsg"  => curl_error($curl),
                    );
                    $output = json_encode($res);
                }

                curl_close($curl);
            } else {
                $res = array(
                    "errcode" => "0x000112", //没有开启curi_init
                    "errmsg"  => "CURL扩展没有开启!",
                );
                $output = json_encode($res);
            }
        } catch (Exception $exc) {

            $res = array(
                "errcode" => "0x000113", //其他异常
                "errmsg"  => $exc->getTraceAsString(),
            );

            $output = json_encode($res);
        }

        return $output;
    }

    /**
     * 转换路由到url
     */
    public static function convertRoute($platfrom, $menu, $type, $params)
    {
        $tempParams           = [];
        $tempParams["module"] = $menu;
        $tempParams["action"] = $type;
        $tempParams["params"] = [];
        if ($params) {
            $paramArray           = json_decode($params, true);
            $tempParams["params"] = $paramArray;
        }
        if ($platfrom == "qyweixin") {
            $temp = DB::table('qyweixin_token')->first();
            if ($temp) {
                $domin   = $temp->domain;
            } else {
                return "";
            }
            //存储cookie
            //注册cookile
            return $domin . "/eoffice10/server/public/api/qywechat-access?type=0&reminds=" . json_encode($tempParams);
        } else if ($platfrom == "weixin") {
            $temp = DB::table('weixin_token')->first();
            if ($temp) {
                $domin   = $temp->domain;
            } else {
                return "";
            }
            //存储cookie
            //注册cookile
            return $domin . "/eoffice10/server/public/api/weixin-access?type=0&reminds=" . json_encode($tempParams);
        } else if ($platfrom == "workwechat") {
            $temp = DB::table('work_wechat')->first();
            if ($temp) {
                $domin   = $temp->domain;
                $agentid = $temp->sms_agent;
            } else {
                return "";
            }
            //存储cookie
            //注册cookile
            return $domin . "/eoffice10/server/public/api/workwechat-access?type=0&agentid=$agentid&reminds=" . json_encode($tempParams);
        } else if ($platfrom == "dingtalk" || $platfrom == "pc_dingtalk") {
            $temp = DB::table('dingtalk_token')->first();
            if ($temp) {
                $domin   = $temp->domain;
            } else {
                return "";
            }
            //存储cookie
            //注册cookile
            if ($platfrom == "pc_dingtalk") {
                return $domin . "/eoffice10/server/public/api/pc-dingtalk-access?type=0&reminds=" . json_encode($tempParams);
            } else if ($platfrom == "dingtalk") {
                return $domin . "/eoffice10/server/public/api/dingtalk-access?type=0&reminds=" . base64_encode(json_encode($tempParams));
            }
        } else if ($platfrom == "dgwork" || $platfrom == "pc_dgwork") {
            $temp = DB::table('dgwork_config')->first();
            if ($temp) {
                $domin   = $temp->domain;
            } else {
                return "";
            }
            //存储cookie
            //注册cookile
            if ($platfrom == "pc_dgwork") {
                return $domin . "/eoffice10/server/public/api/pc-dgwork-access?type=0&reminds=" . json_encode($tempParams);
            } else if ($platfrom == "dgwork") {
                return $domin . "/eoffice10/server/public/api/dgwork-access?type=0&reminds=" . base64_encode(json_encode($tempParams));
            }
        } else if ($platfrom == 'app') {

        }
    }

    /*
     * 加密
     * @param string $txt 需要加密的字符串
     * @param string $key 加密key
     */

    public static function encrypt($txt, $key = "eoffice")
    {
        srand((double) microtime() * 1000000);
        $encrypt_key = md5(rand(0, 32000));
        $ctr         = 0;
        $tmp         = '';
        for ($i = 0; $i < strlen($txt); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $encrypt_key[$ctr] . ($txt[$i] ^ $encrypt_key[$ctr++]);
        }
        return base64_encode(static::passport_key($tmp, $key));
    }

    /*
     * 解密
     * @param string $txt 需要解密的字符串
     * @param string $key 解密key(需要于加密key一致，否则无法解密)
     */

    public static function decrypt($txt, $key = "eoffice")
    {
        $txt = static::passport_key(base64_decode($txt), $key);
        $tmp = '';
        for ($i = 0; $i < strlen($txt); $i++) {
            $md5 = $txt[$i];
            // $tmp .= $txt[++$i] ^ $md5;
            // 20191224 系统邮箱密码数据解密报错处理
            $tmp .= ($txt[++$i] ?? '') ^ $md5;
        }
        return $tmp;
    }

    /*
     * 加密、解密key解析
     * @param string $txt 需加、解密的字符串
     * @param string $encrypt_key 加、解密key
     */

    public static function passport_key($txt, $encrypt_key)
    {
        $encrypt_key = md5($encrypt_key);
        $ctr         = 0;
        $tmp         = '';
        for ($i = 0; $i < strlen($txt); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $txt[$i] ^ $encrypt_key[$ctr++];
        }
        return $tmp;
    }

    //检查自定义路径的权限 并指定777
    public static function createCustomDir($path)
    {

        $uploadDir = getAttachmentDir();
        $path   = str_replace('\\', '/', $path);
        $pathes = explode('/', $path);
        $dir    = $uploadDir;
        foreach ($pathes as $k => $v) {
            if ($v) {
                $dir .= $v . '/';
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                    chmod($dir, 0777);
                }
            }
        }

        return $dir;
    }

    //等比例缩放图片
    public static function scaleImage($pic, $maxx = 80, $maxy = 60, $prefix = 'thumb_')
    {
        //这里不限制内存避免图片分辨率过高时会有致命错误
        ini_set('memory_limit', '-1');
        // 20210312 客户图片dpi较高导致报出 notice 先屏蔽此错误 DT202103110053
        $info = @getimageSize($pic); //获取图片的基本信息

        $w = $info[0]; //获取宽度
        $h = $info[1]; //获取高度
        //获取图片的类型并为此创建对应图片资源
        switch ($info[2]) {
            case 1: //gif
                $im = imagecreatefromgif($pic);
                break;
            case 2: //jpg
                $im = @imagecreatefromjpeg($pic);
                break;
            case 3: //png
                $im = imagecreatefrompng($pic);
                break;
            case 6://bmp
                $im = static::imagecreatefrombmp($pic);
                break;
            default:
                die("图片类型错误！");
        }

        //计算缩放比例
        if (($maxx / $w) > ($maxy / $h)) {
            $b = $maxy / $h;
        } else {
            $b = $maxx / $w;
        }

        //计算出缩放后的尺寸
        $nw = ceil($w * $b);
        $nh = ceil($h * $b);

        //创建一个新的图像源(目标图像)
        $des = imagecreatetruecolor($nw, $nh);

        //执行等比缩放
        imagecopyresampled($des, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);

        //输出图像（根据源图像的类型，输出为对应的类型）
        $picinfo    = pathinfo($pic); //解析源图像的名字和路径信息
        $newpicname = $picinfo["dirname"] . DIRECTORY_SEPARATOR . $prefix . $picinfo["basename"];
        $newpic     = $prefix . $picinfo["basename"];
        switch ($info[2]) {
            case 1:
                imagegif($des, $newpicname);
                break;
            case 2:
                imagejpeg($des, $newpicname);
                break;
            case 3:
                imagepng($des, $newpicname);
                break;
            case 6:
                static::imagebmp($des, $newpicname);
                break;
        }
        //释放图片资源
        imagedestroy($im);
        imagedestroy($des);
        //返回结果
        return $newpic;
    }

    //64位base
    public static function imageToBase64($file)
    {
        $encoding = mb_detect_encoding($file, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
        if(!is_file($file)){
            $file = iconv($encoding, 'GBK', $file);
            if (!is_file($file)) {
                return '';
            }
        }

        $type = getimagesize($file); //取得图片的大小，类型等
        $fp           = fopen($file, "r") or die("Can't open file");
        $file_content = chunk_split(base64_encode(fread($fp, filesize($file)))); //base64编码
        switch ($type[2]) {
            //判读图片类型
            case 1:$img_type = "gif";
                break;
            case 2:$img_type = "jpg";
                break;
            case 3:$img_type = "png";
                break;
            case 6:$img_type = "bmp";
                break;
            default:
                die("图片类型错误！");
        }
        $img = 'data:image/' . $img_type . ';base64,' . $file_content; //合成图片的base64编码
        fclose($fp);
        return $img;
    }
    //日期类型分组字段x坐标
    public static function getDatexAxis($dateType,$dateValue){
    	$reportData = [];
    	switch ($dateType) {
    		//按年
    		case 'year' :
    			$split = explode("-", $dateValue);
    			if (count($split) == 2) {
    				for ($i = $split[0]; $i <= $split[1]; $i++) {
    					$reportData[$i] = array(
    							"name" => (string)$i,
    							"y" => 0
    					);
    				}
    			}
    			break;
    			//按季度
    		case 'quarter' :
    			$reportData[1] = array(
    				"name" => trans("report.report_firstQuarter"),
    				"y" => 0
    			);
    			$reportData[2] = array(
    					"name" => trans("report.report_secondQuarter"),
    					"y" => 0
    			);
    			$reportData[3] = array(
    					"name" => trans("report.report_thirdQuarter"),
    					"y" => 0
    			);
    			$reportData[4] = array(
    					"name" => trans("report.report_fourthQuarter"),
    					"y" => 0
    			);
    			break;
    			//按月
    		case 'month' :
    			for ($i = 1; $i <= 12; $i++) {
    				$reportData[$i] = array(
    						"name" => $i,
    						"y" => 0
    				);
    			}
    			break;
    			//按天
    		case 'day' :
    			//获取指定月的最后一天
    			$lastDay = date('t', strtotime($dateValue));
    			for ($i = 1; $i <= $lastDay; $i++) {
    				$reportData[$i] = array(
    						"name" => $i,
    						"y" => 0
    				);
    			}
    			break;
    		default :
    			break;
    	}
    	return $reportData;
    }
    //日期类型分组字段获取数据语句
    public static function getDateQuery($dateType,$dateValue,$dateFieldName) {
    	$selectStr = "";
    	$whereStr = "";
    	switch ($dateType) {
		//按年
		case 'year' :
			$selectStr = " DATE_FORMAT(" . $dateFieldName . ", '%Y')  ";
			$whereStr = "";
			break;
		//按季度
		case 'quarter' :
			$selectStr = " quarter(" . $dateFieldName . ") ";
			$whereStr = "  DATE_FORMAT(" . $dateFieldName . ", '%Y') = '" . $dateValue . "'  ";
			break;
		//按月
		case 'month' :
			$selectStr = " DATE_FORMAT(" . $dateFieldName . ",'%c') ";
			$whereStr = "  DATE_FORMAT(" . $dateFieldName . ", '%Y') = '" . $dateValue . "'  ";
			break;
		//按天
		case 'day' :
			$selectStr = " DATE_FORMAT(" . $dateFieldName . ",'%e') ";
			$whereStr = "  DATE_FORMAT(" . $dateFieldName . ", '%Y-%c') = '" . $dateValue . "'  ";
			break;
		default :
			break;
		}
		return array($selectStr,$whereStr);
    }
    //分析数据返回结果
    public static function parseDbRes(&$db_res,&$array){
    	if(is_array($db_res)){
            // 0 和 null处理
            $new_arr = $result_arr = [];
            foreach ($db_res as $key => $item) {
                $item = (array) $item;
                $item['group_by'] = isset($item['group_by']) && !empty($item['group_by']) ? $item['group_by'] : 0;
                if (isset($new_arr[$item['group_by']])) {
                    $temp_item = array_keys($item);
                    $temp_key = isset($temp_item[0]) ? $temp_item[0] : '';
                    if ($temp_key) {
                        $new_arr[$item['group_by']][$temp_key] += intval($item[$temp_key]);
                    }
                } else {
                    $new_arr[$item['group_by']] = $item;
                }
            }
            $db_res = $new_arr;
    		foreach($db_res as &$v){
    			if(is_object($v)) $v = get_object_vars($v);
    			//多个数据分析字段
    			foreach($array as $k => &$item){
    				if(isset($v[$k])){
    					if(isset($v['group_by'])&&isset($item[$v['group_by']])){
    						$item[$v['group_by']]['y'] = (float) $v[$k];
    					}else{
    						if(isset($item['else'])) $item['else']['y'] = (float) $v[$k];
    					}
    				}
    			}
    		}
    	}
    }
    //获取指定的目录 最后带斜杠的 F:/xxxx/attachment/
    public static function getAttachmentDir($attachBase= '')
    {

        if (!$attachBase) {
            $attachBase = config('eoffice.attachmentDir');
            if (!$attachBase) {
                $attachBase = "attachment";
            }
        }
        if (isset($_SERVER["DOCUMENT_ROOT"]) && !empty($_SERVER["DOCUMENT_ROOT"])) {
            $docPath = str_replace('\\', '/', $_SERVER["DOCUMENT_ROOT"]);
        } else {
            $docPath = str_replace('\\', '/', dirname(dirname(dirname(dirname(__DIR__)))));
        }
        $docPathTemp  = rtrim($docPath, "/");
        $docNum       = strripos($docPathTemp, "/");
        $docFinalPath = substr($docPathTemp, 0, $docNum);

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            //winos系统
            if (strripos($attachBase, ":/") || strripos($attachBase, ":\\")) {
                $uploadDir = rtrim(str_replace('\\', '/', $attachBase), "/") . DIRECTORY_SEPARATOR;
            } else {
                $attachBase = str_replace('\\', '/', $attachBase);
                $uploadDir  = rtrim($docFinalPath, "/") . "/" . ltrim($attachBase, "/");
            }
        } else {
            if (substr($attachBase, 0, 1) == DIRECTORY_SEPARATOR) {
                $uploadDir = rtrim(str_replace('\\', '/', $attachBase), "/") . DIRECTORY_SEPARATOR;
            } else {
                $attachBase = str_replace('\\', '/', $attachBase);
                $uploadDir  = rtrim($docFinalPath, "/") . "/" . ltrim($attachBase, "/");
            }
        }

        $uploadDir = str_replace('\\', '/', $uploadDir);
        return rtrim($uploadDir, "/") . "/";
    }

    //获取在职用户ID （配合选择器选取全部人员时 返回所有用户ID）
    public static function getUserIds()
    {

        $users = DB::table('user')->select(["user_id"])->where('user_accounts', "!=", "")->get();

        $userStr = "";
        foreach ($users as $user) {
            $userStr .= $user->user_id . ',';
        }
        return trim($userStr, ",");
    }

    //获取所有的角色ID（配合选择器选取全部时 返回所有的角色ID）
    public static function getRoleIds()
    {
        $roles = DB::table('role')->select(["role_id"])->get();

        $roleStr = "";
        foreach ($roles as $role) {
            $roleStr .= $role->role_id . ',';
        }
        return trim($roleStr, ",");
    }

    public static function integratedErrorUrl($message, $domin = null)
    {
        if (!$domin) {
            $domin = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["SERVER_NAME"];
        }
        // return $domin . "/eoffice10/client/mobile/#error?message= " . $message;
        return $domin . "/eoffice10/client/mobile/error#error_msg= " . $message;
    }

    public static function getRemindsId($remind)
    {

        if (!$remind) {
            return '';
        }
        $id      = "";
        $reminds = DB::table('reminds')->select(["id"])->where('reminds', "=", $remind)->get();
        foreach ($reminds as $remind) {
            $id = $remind->id;
        }
        return $id;
    }

    public function getRoleCommunicateByTypeId($type)
    {
        if (!$type) {
            return [];
        }

        $result = DB::table('communicate_type')->select(["role_from", "role_to"])->whereRaw('find_in_set(\'' . $type . '\',communicate_type)')->get()->toArray();
        return $result;
    }

    public static function getRolesByUserId($user_id)
    {
        if (!$user_id) {
            return [];
        }
        $roles     = [];
        $userRoles = DB::table('user_role')->select(["role_id"])->where('user_id', "=", $user_id)->get();
        foreach ($userRoles as $role) {
            $roles[] = $role->role_id;
        }
        return $roles;
    }

    public static function getUsersByRoleId($role_id)
    {
        $users   = DB::table('user_role')->select(["user_id"])->where('role_id', "=", $role_id)->get();
        $userObj = [];
        foreach ($users as $user) {
            $userObj[] = $user->user_id;
        }

        return $userObj;
    }

    public static function readSmsByClient($userId, $data = null)
    {
        //定位消息

        $params   = isset($data["params"]) ? json_encode($data["params"]) : "";
        $sms_menu = isset($data["module"]) ? $data["module"] : "";
        $sms_type = isset($data["action"]) ? $data["action"] : "";
        $smss     = DB::table('system_sms')->select(["sms_id"])
            ->where('sms_menu', "=", $sms_menu)
            ->where('sms_type', "=", $sms_type)
            ->where('params', "=", $params)->get();

        $smsIds = [];
        foreach ($smss as $sms) {
            $smsIds[] = $sms->sms_id;
        }

        return DB::table('system_sms_receive')
            ->where('recipients', $userId)
            ->whereIn('sms_id', $smsIds)
            ->update(['remind_flag' => 1]);
    }

    public static function getPlatformUser($platform, $user_id)
    {
        if ($platform == "ding_talk" || $platform == "ding_talk_pc") {
            $users = DB::table('dingtalk_user')
                ->where('oa_id', $user_id)->get();
        } else if ($platform == "enterprise_account" || $platform == "enterprise_wechat") {
            $users = DB::table('qyweixin_conversation')
                ->where('oa_id', $user_id)->get();
        }

        $userId = "";
        foreach ($users as $user) {
            $userId = $user->userid;
        }

        if (!$userId) {
            $userId = $user_id;
        }

        return $userId;
    }

    /**
     * 内容转换为utf-8
     * @param  string $content 需要转换编码的内容
     * @return string          转化后的内容
     */
    public static function convertToUtf8($content)
    {

        //检测内容的编码
        $encode = mb_detect_encoding($content, array("ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5'));

        return mb_convert_encoding($content, 'UTF-8', $encode);
    }

    /**
     * 功能函数，验证curl请求的地址，是否在系统设置的白名单中
     * @param  [type] $url    [string 待验证的url]
     * @param  array  $params [array]
     * @return [type]         [description]
     */
    public static function checkWhiteList($url, $params=[])
    {
        $status = true;
        // 判断逻辑
        if (Cache::has('system_security_white_address_config')) {
            $whiteAddressSet = Cache::get('system_security_white_address_config');
            $whiteAddressSet = json_decode($whiteAddressSet);
        } else {
            $whiteAddressSet = DB::table('system_params')->where('param_key','system_security_white_address')->get();
            // 把 $whiteAddressSet 缓存 1 分钟
            Cache::put('system_security_white_address_config', json_encode($whiteAddressSet), 1);
        }
        if(count($whiteAddressSet) == 1 && isset($whiteAddressSet[0]->param_value)){
            $setId = $whiteAddressSet[0]->param_value;
            //开启了设置
            if($setId == 1 || $setId == '1'){
                $url = trim($url);
                $systemWhiteAddress = config('eoffice.systemSecurityWhiteAddress');
                foreach ($systemWhiteAddress as $value) {
                    if (strpos($url, $value) === 0) {
                        return true;
                    }
                }
                if(!in_array($url,$systemWhiteAddress)){
                    $count = DB::table('system_security_white_address')->where('white_address_url',$url)->count();
                    if($count == 0)
                        $status = false;
                }
            }
        }
        // 记录日志
        if($status === false) {
            if(isset($params['user_id']) && !empty($params['user_id'])) {
                $userId = $params['user_id'];
            }else{
                $userId = own("user_id");
            }
            if(isset($params['ip']) && !empty($params['ip'])) {
                $ip = $params['ip'];
            }else{
                $ip = getClientIp();
            }
            $data = [
                // 请求地址{url}不在白名单中，请在系统管理-性能安全设置中添加。
                'log_content'         => trans("systemlog.white_list_check_failed", ['url'=>$url]),
                'log_type'            => "whiteListCheck",
                'log_creator'         => $userId,
                'log_time'            => date('Y-m-d H:i:s'),
                'log_ip'              => $ip,
                'log_relation_table'  => "", // 关联表
                'log_relation_id'     => "", // 关联表主键
                'log_relation_field'  => "", // 关联表字段
            ];
            add_system_log($data);
        }
        // 返回状态
        return $status;
    }
    public static function version($version = null)
    {
        if($version) {
            Cache::forever('eoffice_system_version', $version);
            return DB::table('version')->update(['ver' => $version]);
        }
        if(Cache::has('eoffice_system_version')) {
            return Cache::get('eoffice_system_version');
        }
        $result = DB::table('version')->first();

        $version = $result->ver;

        Cache::forever('eoffice_system_version', $version);

        return $version;
    }

    // 判断传入的版本号是否大于当前系统的版本号，如果大于则返回true
    public static function checkVersionLargerThanSystemVersion($contentVersion = '')
    {
        if (empty($contentVersion)) {
            // 传入的版本号为空的直接通过
            return false;
        }
        // 传入的版本
        $contentVersionArray = explode('_', $contentVersion);
        $contentBigVersion   = $contentVersionArray[0] ?? 0;
        $contentSmallVersion = $contentVersionArray[1] ?? 0;
        // 系统版本
        $systemVersion       = version();
        $systemVersionArray  = explode('_', $systemVersion);
        $systemBigVersion    = $systemVersionArray[0] ?? 0;
        $systemSmallVersion  = $systemVersionArray[1] ?? 0;
        // 如果传入的大版本号大于系统版本的大版本号，或者传入的小版本号大于系统的小版本号
        if ($contentBigVersion * 100 > $systemBigVersion * 100 || intval($contentSmallVersion) > intval($systemSmallVersion)) {
            return true;
        }
        return false;
    }

    /**
     * 官方imagecreatefrombmp方法需php>=7.2先自己实现下
     * @param $filename
     * @return bool|resource
     */
    public static function imagecreatefrombmp($filename)
    {
        if (!$f1 = fopen($filename, "rb"))
            return FALSE;

        $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1, 14));
        if ($FILE['file_type'] != 19778)
            return FALSE;

        $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' . '/Vcompression/Vsize_bitmap/Vhoriz_resolution' . '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));
        $BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
        if ($BMP['size_bitmap'] == 0)
            $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
        $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
        $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
        $BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] -= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] = 4 - (4 * $BMP['decal']);
        if ($BMP['decal'] == 4)
            $BMP['decal'] = 0;

        $PALETTE = array();
        if ($BMP['colors'] < 16777216) {
            $PALETTE = unpack('V' . $BMP['colors'], fread($f1, $BMP['colors'] * 4));
        }

        $IMG = fread($f1, $BMP['size_bitmap']);
        $VIDE = chr(0);

        $res = imagecreatetruecolor($BMP['width'], $BMP['height']);
        $P = 0;
        $Y = $BMP['height'] - 1;
        while ($Y >= 0) {
            $X = 0;
            while ($X < $BMP['width']) {
                if ($BMP['bits_per_pixel'] == 32) {
                    // 20201119-丁鹏修改，删掉这一行，给个空的 $COLOR ，这里报错，且后面只用到$COLOR[1]
                    // $COLOR = unpack("V", substr($IMG, $P, 3));
                    $COLOR = [];
                    $B = ord(substr($IMG, $P, 1));
                    $G = ord(substr($IMG, $P + 1, 1));
                    $R = ord(substr($IMG, $P + 2, 1));
                    $color = imagecolorexact($res, $R, $G, $B);
                    if ($color == -1)
                        $color = imagecolorallocate($res, $R, $G, $B);
                    $COLOR[0] = $R * 256 * 256 + $G * 256 + $B;
                    $COLOR[1] = $color;
                } elseif ($BMP['bits_per_pixel'] == 24)
                    $COLOR = unpack("V", substr($IMG, $P, 3) . $VIDE);
                elseif ($BMP['bits_per_pixel'] == 16) {
                    $COLOR = unpack("n", substr($IMG, $P, 2));
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 8) {
                    $COLOR = unpack("n", $VIDE . substr($IMG, $P, 1));
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 4) {
                    $COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
                    if (($P * 2) % 2 == 0)
                        $COLOR[1] = ($COLOR[1] >> 4);
                    else
                        $COLOR[1] = ($COLOR[1] & 0x0F);
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 1) {
                    $COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
                    if (($P * 8) % 8 == 0)
                        $COLOR[1] = $COLOR[1] >> 7;
                    elseif (($P * 8) % 8 == 1)
                        $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
                    elseif (($P * 8) % 8 == 2)
                        $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
                    elseif (($P * 8) % 8 == 3)
                        $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
                    elseif (($P * 8) % 8 == 4)
                        $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
                    elseif (($P * 8) % 8 == 5)
                        $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
                    elseif (($P * 8) % 8 == 6)
                        $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
                    elseif (($P * 8) % 8 == 7)
                        $COLOR[1] = ($COLOR[1] & 0x1);
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } else
                    return FALSE;
                imagesetpixel($res, $X, $Y, $COLOR[1]);
                $X++;
                $P += $BMP['bytes_per_pixel'];
            }
            $Y--;
            $P += $BMP['decal'];
        }
        fclose($f1);

        return $res;
    }

    /**
     * 官方imagebmp方法需php>=7.2先自己实现下
     * @param $im
     * @param string $filename
     * @param int $bit
     * @param int $compression
     * @return int
     */
    public static function imagebmp(&$im, $filename = '', $bit = 8, $compression = 0)
    {
        if (!in_array($bit, array(1, 4, 8, 16, 24, 32))) {
            $bit = 8;
        } else if ($bit == 32) // todo:32 bit
        {
            $bit = 24;
        }
        $bits = pow(2, $bit);
        // 调整调色板
        imagetruecolortopalette($im, true, $bits);
        $width = imagesx($im);
        $height = imagesy($im);
        $colors_num = imagecolorstotal($im);
        if ($bit <= 8) {
            // 颜色索引
            $rgb_quad = '';
            for ($i = 0; $i < $colors_num; $i++) {
                $colors = imagecolorsforindex($im, $i);
                $rgb_quad .= chr($colors['blue']) . chr($colors['green']) . chr($colors['red']) . "\0";
            }
            // 位图数据
            $bmp_data = '';
            // 非压缩
            if ($compression == 0 || $bit < 8) {
                if (!in_array($bit, array(1, 4, 8))) {
                    $bit = 8;
                }
                $compression = 0;
                // 每行字节数必须为4的倍数，补齐。
                $extra = '';
                $padding = 4 - ceil($width / (8 / $bit)) % 4;
                if ($padding % 4 != 0) {
                    $extra = str_repeat("\0", $padding);
                }
                for ($j = $height - 1; $j >= 0; $j--) {
                    $i = 0;
                    while ($i < $width) {
                        $bin = 0;
                        $limit = $width - $i < 8 / $bit ? (8 / $bit - $width + $i) * $bit : 0;
                        for ($k = 8 - $bit; $k >= $limit; $k -= $bit) {
                            $index = imagecolorat($im, $i, $j);
                            $bin |= $index << $k;
                            $i++;
                        }
                        $bmp_data .= chr($bin);
                    }
                    $bmp_data .= $extra;
                }
            } // RLE8 压缩
            else if ($compression == 1 && $bit == 8) {
                for ($j = $height - 1; $j >= 0; $j--) {
                    $last_index = "\0";
                    $same_num = 0;
                    for ($i = 0; $i <= $width; $i++) {
                        $index = imagecolorat($im, $i, $j);
                        if ($index !== $last_index || $same_num > 255) {
                            if ($same_num != 0) {
                                $bmp_data .= chr($same_num) . chr($last_index);
                            }
                            $last_index = $index;
                            $same_num = 1;
                        } else {
                            $same_num++;
                        }
                    }
                    $bmp_data .= "\0\0";
                }
                $bmp_data .= "\0\1";
            }
            $size_quad = strlen($rgb_quad);
            $size_data = strlen($bmp_data);
        } else {
            // 每行字节数必须为4的倍数，补齐。
            $extra = '';
            $padding = 4 - ($width * ($bit / 8)) % 4;
            if ($padding % 4 != 0) {
                $extra = str_repeat("\0", $padding);
            }
            // 位图数据
            $bmp_data = '';
            for ($j = $height - 1; $j >= 0; $j--) {
                for ($i = 0; $i < $width; $i++) {
                    $index = imagecolorat($im, $i, $j);
                    $colors = imagecolorsforindex($im, $index);
                    if ($bit == 16) {
                        $bin = 0 << $bit;
                        $bin |= ($colors['red'] >> 3) << 10;
                        $bin |= ($colors['green'] >> 3) << 5;
                        $bin |= $colors['blue'] >> 3;
                        $bmp_data .= pack("v", $bin);
                    } else {
                        $bmp_data .= pack("c*", $colors['blue'], $colors['green'], $colors['red']);
                    }
                    // todo: 32bit;
                }
                $bmp_data .= $extra;
            }
            $size_quad = 0;
            $size_data = strlen($bmp_data);
            $colors_num = 0;
        }
        // 位图文件头
        $file_header = "BM" . pack("V3", 54 + $size_quad + $size_data, 0, 54 + $size_quad);
        // 位图信息头
        $info_header = pack("V3v2V*", 0x28, $width, $height, 1, $bit, $compression, $size_data, 0, 0, $colors_num, 0);
        // 写入文件
        if ($filename != '') {
            $fp = fopen($filename, "wb");
            fwrite($fp, $file_header);
            fwrite($fp, $info_header);
            fwrite($fp, $rgb_quad);
            fwrite($fp, $bmp_data);
            fclose($fp);
            return 1;
        }
    }
    /***
     * 根据地址获取经纬度
     */
    public static function addressToGeocode($address)
    {
        $mapType = self::getSystemParam('map_type', 'amap');

        if ($mapType == 'amap') {
            $url = self::getMapUrl('amap', 'geo');
            if (!$url) return ['code' => ['wrong_map_key', 'integrationCenter']];
            $url .= "&address=$address&output=JSON";

            $infoTemp = getHttps($url);
            if ($infoTemp) {
                $decodeAddressInfo = json_decode($infoTemp, true);
                if (isset($decodeAddressInfo['geocodes'][0]['location'])) {
                    $location = $decodeAddressInfo['geocodes'][0]['location'];
                    $explodeLocation = explode(',', $location);

                    return ['long' => $explodeLocation[0], 'lat' => $explodeLocation[1]];
                }
            }
        } elseif ($mapType == 'google') {
            $url = self::getMapUrl('google', 'geo');
            if (!$url) return false;

            $url .= "&address=$address";
            $infoTemp = getHttps($url);
            $infoList = json_decode($infoTemp, true);
            if (isset($infoList["status"]) && $infoList["status"] == 'OK') {
                if (isset($infoList['results']) && !empty($infoList['results']) && isset($infoList['results'][0]['geometry']['location'])) {
                    $location = $infoList['results'][0]['geometry']['location'];
                    return ['long' => $location['lng'], 'lat' => $location['lat']];
                }
            }
        }

        return false;
    }
    /***
     * 根据经纬度获取地址
     */
    public static function geocodeToAddress($geoCode)
    {
        if (!isset($geoCode['longitude']) || !isset($geoCode['latitude'])) {
            return ['code' => ['positioning_failed', 'integrationCenter']];
        }
        $mapType = self::getSystemParam('map_type', 'amap');

        if ($mapType == 'amap') {
            $url = self::getMapUrl('amap', 'regeo');
            if (!$url) return ['code' => ['wrong_map_key', 'integrationCenter']];
            $coordinate = $geoCode["longitude"] . "," . $geoCode["latitude"];
            $url .= "&location=$coordinate";

            $infoTemp = getHttps($url);
            $infoList = json_decode($infoTemp, true);
            if (isset($infoList["status"])) {
                if ($infoList["status"] == 0) {
                    return ['code' => ['wrong_map_key', 'integrationCenter']];
                }
                if ($infoList["status"] == 1 && isset($infoList["regeocode"]["formatted_address"])) {
                    return $infoList["regeocode"]["formatted_address"];
                }
            }
        } elseif ($mapType == 'google') {
            $url = self::getMapUrl('google', 'geo');
            if (!$url) return ['code' => ['wrong_map_key', 'integrationCenter']];

            // 坐标
            $latlng = $geoCode["latitude"] . "," . $geoCode["longitude"];
            $local = Lang::getLocale();
            $url .= "&latlng=$latlng&language=$local";

            $infoTemp = getHttps($url);
            $infoList = json_decode($infoTemp, true);
            if (isset($infoList["status"]) && $infoList["status"] == 'OK') {
                if (isset($infoList['results']) && !empty($infoList['results']) && isset($infoList['results'][0]['formatted_address'])) {
                    return $infoList['results'][0]['formatted_address'];
                }
            }
        }
        return false;
    }
    public static function getMapUrl($mapType, $apiType) {
        $urlConfig = config('eoffice.map_url');
        // 秘钥
        if ($mapType == 'google') {
            $key = self::getSystemParam('google_map_key');
        } else {
            $key = self::getSystemParam('amap_key');
        }

        if (empty($key)) return false;

        $url = $urlConfig[$mapType][$apiType]."?key=$key";

        return $url;
    }
    // 获取周边地址
    public static function getNearbyPlace($data) {
        if (!isset($data['longitude']) || !isset($data['latitude'])) {
            return false;
        }
        $mapType = self::getSystemParam('map_type', 'amap');

        if ($mapType == 'amap') {
            $url = self::getMapUrl('amap', 'around');
            if (!$url) return false;
            $coordinate = $data["longitude"] . "," . $data["latitude"];
            $radius = $data["radius"];
            $page = $data['page'] ??  1;
            $offset = $data['offset'] ?? 25;
            $url .= "&offset=$offset&page=$page&types=170000|050000|070000|120000|130000&location=$coordinate&radius=$radius";

            $tempData = getHttps($url);
            $resData  = json_decode($tempData, true);

            if ($resData["status"] == 0) {
                return false;
            }

            return $resData["pois"];
        } elseif ($mapType == 'google') {
            $url = self::getMapUrl('google', 'nearby');
            if (!$url) return false;

            // 坐标
            $location = $data["latitude"] . "," . $data["longitude"];
            $radius = $data['radius'];
            $local = Lang::getLocale();
            $url .= "&location=$location&radius=$radius&language=$local";

            $infoTemp = getHttps($url);
            $infoList = json_decode($infoTemp, true);
            if (isset($infoList['status']) && $infoList['status'] == 'OK') {
                if (isset($infoList['results']) && !empty($infoList['results'])) {
                    $position = [];
                    foreach ($infoList['results'] as $key => $value) {
                        if (isset($value['name']) && isset($value['vicinity']) && isset($value['geometry']['location']['lng']) && isset($value['geometry']['location']['lat'])) {
                            $position[] = [
                                'name' => $value['name'],
                                'pname' => $value['vicinity'],
                                'adname' => '',
                                'address' => $value['name'],
                                'location' => $value['geometry']['location']['lng'].','.$value['geometry']['location']['lat']
                            ];
                        }
                    }
                    return $position;
                }
            }
        }
        return false;
    }

    public static function handleLogParams($user , $content , $relation_id = '' ,$relation_table = '', $relation_title='')
    {
        $data = [
            'creator' => $user,
            'content' => $content,
            'relation_table' => $relation_table,
            'relation_id' => $relation_id,
            'relation_title' => $relation_title,
        ];
        return $data;
    }
    /**
     * 半角转全角
     * @param string $str
     * @return string
     * */
    public static function sbc2dbc($str) {
        $DBC = Array(
            '０', '１', '２', '３', '４',
            '５', '６', '７', '８', '９',
            'Ａ', 'Ｂ', 'Ｃ', 'Ｄ', 'Ｅ',
            'Ｆ', 'Ｇ', 'Ｈ', 'Ｉ', 'Ｊ',
            'Ｋ', 'Ｌ', 'Ｍ', 'Ｎ', 'Ｏ',
            'Ｐ', 'Ｑ', 'Ｒ', 'Ｓ', 'Ｔ',
            'Ｕ', 'Ｖ', 'Ｗ', 'Ｘ', 'Ｙ',
            'Ｚ', 'ａ', 'ｂ', 'ｃ', 'ｄ',
            'ｅ', 'ｆ', 'ｇ', 'ｈ', 'ｉ',
            'ｊ', 'ｋ', 'ｌ', 'ｍ', 'ｎ',
            'ｏ', 'ｐ', 'ｑ', 'ｒ', 'ｓ',
            'ｔ', 'ｕ', 'ｖ', 'ｗ', 'ｘ',
            'ｙ', 'ｚ', '－', '　', '：',
            '．', '，', '／', '％', '＃',
            '！', '＠', '＆', '（', '）',
            '＜', '＞', '＂', '＇', '？',
            '［', '］', '｛', '｝', '＼',
            '｜', '＋', '＝', '＿', '＾',
            '￥', '￣', '｀'
        );
        $SBC = Array(// 半角
            '0', '1', '2', '3', '4',
            '5', '6', '7', '8', '9',
            'A', 'B', 'C', 'D', 'E',
            'F', 'G', 'H', 'I', 'J',
            'K', 'L', 'M', 'N', 'O',
            'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y',
            'Z', 'a', 'b', 'c', 'd',
            'e', 'f', 'g', 'h', 'i',
            'j', 'k', 'l', 'm', 'n',
            'o', 'p', 'q', 'r', 's',
            't', 'u', 'v', 'w', 'x',
            'y', 'z', '-', ' ', ':',
            '.', ',', '/', '%', '#',
            '!', '@', '&', '(', ')',
            '<', '>', '"', '\'', '?',
            '[', ']', '{', '}', '\\',
            '|', '+', '=', '_', '^',
            '$', '~', '`'
        );
        return str_replace($SBC, $DBC, $str);
    }
    /**
     * 获取sql关键字
     * 
     * @param type $value
     * @return type
     */
    public static function getSqlKeywords($value = null) {
        $str = '/(?<=([^a-z0-9_]))(in|or|xor|and|like|between|exists|union|select|META|HTTP-EQUIV|COUNT_BIG|waitfor|DBMS_LOCK|DBMS_PIPE|xp_cmdshell|xp_dirtree|UTL_HTTP|USER_LOCK|EXTRACTVALUE|UPDATEXML|—|CHR|SLEEP|truncate|delete|update|exec|drop)(?=([^_a-z0-9"\-]))/i';

        preg_match_all($str, $value, $matchs);
        return $matchs;
    }
    /**
     * 输入字符串安全过滤
     * @param type $input
     * @return type
     */
    public static function securityFilter($input)
    {
        if (empty($input)) {
            return $input;
        }
        if (!is_string($input)) {
            return $input;
        }
        $keywords = self::getSqlKeywords($input);
        if (!empty($keywords[0])) {
            $replace = array();
            $old = array();
            foreach ($keywords[0] as $value) {
                $old[] = $value;
                $replace[] = self::sbc2dbc($value);
            }
            $input = str_replace($old, $replace, $input);
        }
        return htmlspecialchars($input);
    }
}
