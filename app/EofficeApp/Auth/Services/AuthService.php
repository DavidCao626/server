<?php

namespace App\EofficeApp\Auth\Services;

use App\EofficeApp\Base\BaseService;
use Cache;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Illuminate\Support\Facades\Redis;
use Lang;
use QrCode;
use Request;
//use App\Utils\CacheCenter;
use App\Caches\CacheCenter;
use DB;
use Eoffice;
use Illuminate\Support\Str;
use App\EofficeApp\LogCenter\Facades\LogCenter;
use App\EofficeApp\Home\Facades\Home;

/**
 * @权限验证服务类，用于系统登录验证，注销。
 *
 * @author 李志军
 */
class AuthService extends BaseService
{
    /**
     * @var object  UserService 对象
     */

    /**
     * @var string 错误码
     */
    private $errors;
    private $accessImagePath;
    private $themePath;
    private $logoPath;
    private $qrcodePath;
    private $captchaPath;
    private $openApiService;
    private $firstLogin;
    private $mainAuthCheckMethods = [
        1 => 'checkAccount',// OA认证方式
        2 => 'checkUserFromAd',// LDAP认证方式
        3 => 'checkUserFromCas'// CAS认证方式
    ];
    private $userTokenContrastKey = 'eoffice_user_token_contrast';
    const USER_ACCOUNT_CACHE_KEY = 'user:user_accounts';
    public function __construct()
    {
        parent::__construct();

        $this->accessImagePath = access_path('images/');
        $this->themePath = access_path('images/login/theme/');
        $this->logoPath = access_path('images/login/logo/');
        $this->qrcodePath = access_path('images/login/qrcode/');
        $this->captchaPath = access_path('images/login/captcha/');

        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->shortMessageService = 'App\EofficeApp\System\ShortMessage\Services\ShortMessageService';
        $this->authRepository = 'App\EofficeApp\Auth\Repositories\AuthRepository';
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->portalService = 'App\EofficeApp\Portal\Services\PortalService';
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->empowerService = 'App\EofficeApp\Empower\Services\EmpowerService';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->loginThemeRepository = 'App\EofficeApp\Auth\Repositories\LoginThemeRepository';
        $this->companyService = 'App\EofficeApp\System\Company\Services\CompanyService';
        $this->weixinService = 'App\EofficeApp\Weixin\Services\WeixinService';
        $this->mobileService = 'App\EofficeApp\Mobile\Services\MobileService';
        $this->userInfoRepository = 'App\EofficeApp\User\Repositories\UserInfoRepository';
        $this->openApiService = 'App\EofficeApp\OpenApi\Services\OpenApiService';
        $this->attendanceSettingService = 'App\EofficeApp\Attendance\Services\AttendanceSettingService';
        $this->personalSetService = 'App\EofficeApp\PersonalSet\Services\PersonalSetService';
    }
    /**
     * 系统登录
     *
     * @param type $data
     *
     * @return userinfo
     */
    public function login($data)
    {
        /**
        | -------------------------------------------------------------------
        | 设置语言环境
        | -------------------------------------------------------------------
        */
        $local = $this->defaultValue('local', $data, 'zh-CN');
        if (!app($this->langService)->langExists($local)) {
            return ['code' => ['0x003049', 'auth']];
        }
        Lang::setLocale($local);
        /**
        | -------------------------------------------------------------------
        | 获取系统参数
        | -------------------------------------------------------------------
        */
        $this->systemParams = $this->getSystemParam();
        $data['user_account'] = trim($data['user_account'], ' ');
        /**
        | -------------------------------------------------------------------
        | 验证账号和密码，是oa系统的主要验证函数。
        | -------------------------------------------------------------------
        | 目前支持普通的账号密码登录，CAS验证登录， LDAP认证登录。
        | 如果以后有其他验证方式可以在全局变量$mainAuthCheckMethods里定义一个函数
        | 名，然后定义一个独立的验证函数即可，不需改动以下代码。
        */
        $this->authRepositoryObj = app($this->authRepository);
        $password                = $this->defaultValue('password', $data, '');
        $terminal                = $this->defaultValue('terminal', $data, '');//获取登录平台
        $decode                  = $this->defaultValue('decode', $data, '');
        $data['CASE_ID'] = (isset($data['CASE_ID']) && $data['CASE_ID']) ? $data['CASE_ID']: ($_SERVER['HTTP_CASE_ID'] ?? '');
        $loginSet = $this->defaultValue('login_set', $data, '');
        if (!isset($this->systemParams['login_auth_type'])
                || !$this->systemParams['login_auth_type']
                || !isset($this->mainAuthCheckMethods[$this->systemParams['login_auth_type']])
                || $data['user_account'] == 'admin') {
            $mainAuthCheckMethod = 'checkAccount';
        } else {
            $mainAuthCheckMethod = $this->mainAuthCheckMethods[$this->systemParams['login_auth_type']];
        }

        if ($decode != 'none' && ($terminal == 'pc' || $terminal == 'mobile' || isset($data['encrypt'] ))) {
            if(isset($data['platform']) && $data['platform'] == 'ios'){
                $password = $this->decryptByECB($password);
            }else{
                $password = $this->decryptPassword($password);
            }
            if($password === false){
                $this->errors = $this->wrongAccountErrorCode($data['user_account']);
            }
        }

        $user = $this->validAccount($data['user_account']);
        if ($user && ! $this->errors) {
            $data['user_account'] = $user->user_accounts ?? '';
        }
        // 检测用户账号是否存在
        if (Redis::exists(self::USER_ACCOUNT_CACHE_KEY)) {
            if (!Redis::hExists(self::USER_ACCOUNT_CACHE_KEY, trim($data['user_account'], ' '))) {
                $this->addLoginLog($data['user_account'], 'erroruname', trans('auth.wrong_name') . '：' . $data['user_account']);
                return ['code' => ['0x003004', 'auth']];
            }
        } else {
            $lock = Cache::lock(self::USER_ACCOUNT_CACHE_KEY, 10);
            try {
                $lock->block(5);
                // 调用用户生成redis缓存
                app($this->userService)->generateUserCache();
                if (Redis::exists(self::USER_ACCOUNT_CACHE_KEY)) {
                    $lock->release();
                }
            } catch (LockTimeoutException $e) {
                optional($lock)->release();
            } finally {
                optional($lock)->release();
            }
        }
        if ($user && !$this->errors) {
            $user = app($this->userService)->getLoginUserInfo($user->user_id);
            if ($user->user_id == 'admin' && isset($user->last_login_time) && $user->last_login_time == '0000-00-00 00:00:00') {
                $this->firstLogin = 1;
            }
        }
        // 登录设置不验证项
        if (empty($loginSet)) {
            /**
            | -------------------------------------------------------------------
            | 动态令牌验证
            | -------------------------------------------------------------------
            */
            if (strtolower($terminal) == 'pc' && !$this->errors) {
                $this->dynamicCodeCheck($user->user_id, $data);
            }
            /**
            | -------------------------------------------------------------------
            | 手机短信验证
            | -------------------------------------------------------------------
            */
            if (strtolower($terminal) == 'pc' && !$this->errors) {
                $this->smsCodeCheck($user->user_id, $data);
            }
            /**
            | -------------------------------------------------------------------
            | 图形验证码验证
            | -------------------------------------------------------------------
            */
            if (strtolower($terminal) == 'pc' && !$this->errors){
                $captcha = strtolower($this->defaultValue('captcha', $data, ''));
                $this->captchaCheck($captcha, $data);
            }
        }
        /**
        | -------------------------------------------------------------------
        | 账号绑定手机验证
        | -------------------------------------------------------------------
        */
        if (!$this->errors) {
            $this->mobileBindAccountCheck($user, $data);
        }
        /**
        | -------------------------------------------------------------------
        | 更多验证机制
        | -------------------------------------------------------------------
        */
        if (!$this->errors) {
            $this->moreVerification($user, $terminal);
        }
        // 最后验证账号密码，防止暴力破解，2019-05-08
        if (!$this->errors) {
            $user = $this->{$mainAuthCheckMethod}(trim($password), $user);
        }

        if (is_array($user) && isset($user['code'])) {
            $this->errors = $user;
        }
        /**
        | -------------------------------------------------------------------
        | 返回数据中不需要密码
        | -------------------------------------------------------------------
        */
        if (isset($user->password)) {
            unset($user->password);
        }

        return $this->errors ? $this->errors : $this->generateInitInfo($user, $local, $terminal == '' ? 'PC' : $terminal,true, $data);
    }
    private function decryptPassword($password) {
        $key       = config('app.key');
        $iv        = "1234567890123456";
        $decrypted = openssl_decrypt($password, 'aes-256-cbc', $key, OPENSSL_ZERO_PADDING , $iv);

        return $decrypted;
    }


    /**
     * ios解密特殊处理
     *
     * @param string $password 要解密的数据
     *
     * @return string
     *
     */
    public function decryptByECB($password)
    {
        $key = substr(config('app.key'), 0, 16);
        return openssl_decrypt(base64_decode($password), 'AES-128-ECB',$key, OPENSSL_RAW_DATA);
    }

    /**
     * 快捷登录验证
     *
     * @param type $data
     *
     * @return array
     */
    public function quickLogin($data)
    {
        $this->authRepositoryObj = app($this->authRepository);
        $this->systemParams = $this->getSystemParam();
        $data['user_account'] = trim($data['user_account'], ' ');
        $password = $this->decryptPassword($this->defaultValue('password', $data, ''));
        $valid = $this->validAccount($data['user_account']);
        if (isset($valid['code'])) {
            $this->errors = $valid;
            return $valid;
        }
        if (!isset($this->systemParams['login_auth_type'])
                || !$this->systemParams['login_auth_type']
                || !isset($this->mainAuthCheckMethods[$this->systemParams['login_auth_type']])
                || $data['user_account'] == 'admin') {
            $mainAuthCheckMethod = 'checkAccount';
        } else {
            $mainAuthCheckMethod = $this->mainAuthCheckMethods[$this->systemParams['login_auth_type']];
        }
        $user = $this->{$mainAuthCheckMethod}($password, $valid); //验证账号密码，并返回用户信息
        $terminal = $this->defaultValue('terminal', $data, '');
        $local = $this->defaultValue('local', $data, 'zh-CN');
        Lang::setLocale($local);
        /**
         * 更多验证机制
         */
        if (!$this->errors) {
            $this->moreVerification($user);
        }
        return $this->errors ? $this->errors : $this->generateInitInfo($user, $local, $terminal == '' ? 'PC' : $terminal, true, $data);
    }
    /**
     * 单点登录验证
     *
     * @param type $data
     */
    public function singleSignOn($data)
    {
        $data = $this->checkDecrypt($data);
        $this->systemParams = $this->getSystemParam();
        $this->authRepositoryObj = app($this->authRepository);
        if (!isset($data['u']) || empty($data['u'])) {
            $this->errors = ['code' => ['0x003002', 'auth']];
        }
        $valid = $this->validAccount($data['u']);
        $password = $this->defaultValue('p', $data, '');
        // 20200604-旧版验证方法，“登录认证方式”是非OA验证，就会出bug
        // $user = $this->checkAccount($password, $valid); //验证账号密码，并返回用户信息
        // 20200604-新版验证方法，调用登录的那套验证
        // 1、获取系统-登录认证方式
        $loginAuthType = $this->getLoginAuthType();
        if($loginAuthType) {
            $mainAuthCheckMethod = $this->mainAuthCheckMethods[$loginAuthType];
        }
        if(!$loginAuthType || !$mainAuthCheckMethod) {
            // 未获取到登录认证方式，单点登录失败
            $this->errors = ['code' => ['0x003073', 'auth']];
        }
        // 2、验证账号密码，防止暴力破解
        if (!$this->errors) {
            $user = $this->{$mainAuthCheckMethod}(trim($password), $valid);
        }
        $local = $this->defaultValue('local', $data, 'zh-CN');
        $domain = OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST;
        $isMobile = $this->isMobile();
        if (!$this->errors) {
            $registerInfo = $this->generateInitInfo($user, $local, 'PC', false);
            ecache('Auth:Sso')->set($registerInfo['token'], $registerInfo);
            // 判断是哪个平台登录
            // 手机端登录
            if ($isMobile) {
                $verify = $this->moreVerification($user, 'mobile');
                if (isset($verify['code'])) {
                    $this->errors = $verify;
                }

                if(!$this->errors){
                    // 手机端
                    setcookie("token", $registerInfo['token'], time() + 7200, "/");
                    $domain = OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST;
                    // 【EofficeApp是手机app，先不做判断，app不兼容】
                    if (strpos($_SERVER['HTTP_USER_AGENT'], 'EofficeApp') !== false) {
                        header('Location:'.$domain.'/eoffice10/client/mobile');
                        exit;
                    }
                    header('Location:'.$domain.'/eoffice10/client/mobile/#sso=true&local=' . $local.'&token='.$registerInfo['token']);
                }
            } else {
                $verify = $this->moreVerification($user);
                if (isset($verify['code'])) {
                    $this->errors = $verify;
                }
                if (!$this->errors) {
                    // 电脑登录
                    header('Location:' . $domain . "/eoffice10/client/web/#sso=true&token=" . $registerInfo['token'] . '&local='. $local);
                }
            }
        }

        if ($this->errors) {
            $platform = $isMobile ? 'mobile' : 'web';
            $errorMessage = trans($this->errors['code'][1] . '.' . $this->errors['code'][0]);
            header('Location:' . $domain . "/eoffice10/client/".$platform."/error/#sso=false&local=" . $local . "&error_msg=" . rawurlencode($errorMessage));
        }
        exit;
    }
    // 调用解密文件
    private function checkDecrypt($data) {
        if(isset($data['decrypt_method']) && !empty($data['decrypt_method'])){
            $domain = OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST;
            $local = $this->defaultValue('local', $data, 'zh-CN');
            if(!isset($data['u'])){
                $this->errors = ['code' => ['0x003004', 'auth']];
                $errorMessage = trans($this->errors['code'][1] . '.' . $this->errors['code'][0]);
                header('Location:' . $domain . "/eoffice10/client/web/error/#sso=false&local=" . $local . "&error_msg=" . $errorMessage);
            }
            if(!isset($data['p'])){
                $this->errors = ['code' => ['0x003005', 'auth']];
                $errorMessage = trans($this->errors['code'][1] . '.' . $this->errors['code'][0]);
                header('Location:' . $domain . "/eoffice10/client/web/error/#sso=false&local=" . $local . "&error_msg=" . $errorMessage);
            }

            $decryptClass = base_path().DS.'ext'.DS.'sso_decrypt'.DS.'Decrypt.php';
            require_once $decryptClass;
            $decrypt = new \SsoDecrypt\Decrypt;
            if(!method_exists($decrypt, $data['decrypt_method'])){
                $this->errors = ['code' => ['0x003065', 'auth']];
                $errorMessage = trans($this->errors['code'][1] . '.' . $this->errors['code'][0]);
                header('Location:' . $domain . "/eoffice10/client/web/error/#sso=false&local=" . $local . "&error_msg=" . $errorMessage);
            }

            $data = $decrypt->{$data['decrypt_method']}($data);

            if(!$data['p']){
                $this->errors = ['code' => ['0x003005', 'auth']];
                $errorMessage = trans($this->errors['code'][1] . '.' . $this->errors['code'][0]);
                header('Location:' . $domain . "/eoffice10/client/web/error/#sso=false&local=" . $local . "&error_msg=" . $errorMessage);
            }
        }
        return $data;
    }



    /**
     * 判断是否是手机端登录
     * @return boolean
     *
     */
    public function isMobile()
    {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset($_SERVER['HTTP_VIA'])) {
            // 找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高。其中'MicroMessenger'是电脑微信,DingTalk钉钉客户端

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile', 'DingTalk','EofficeApp', 'Eoffice','okhttp');
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        // 协议法，因为有可能不准确，放到最后判断
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取单点登录后的用户信息
     *
     * @param type $token
     *
     * @return userinfo
     */
    public function ssoRegisterInfo($token)
    {
        return ecache('Auth:Sso')->get($token);
    }

    /**
     * 合法性验证
     *
     * @return boolean
     */
    public function check()
    {
        if (!$token = $this->getTokenForRequest()) {
            return ['code' => ['0x003001', 'auth']];
        }

        if (!Cache::has($token)) {
            return ['code' => ['0x003003', 'auth']];
        }

        $user = Cache::get($token);
        if (!isset($user->user_id)) {
            return ['code' => ['0x003003', 'auth']];
        }
         //从缓存中心获取离职状态
        $userStatus = CacheCenter::make('UserStatus', $user->user_id)->getCache();

        if ($userStatus == 2 || $userStatus == 0) {
            Cache::forget($token);
            return ['code' => ['0x003068', 'auth']];
        }
        if($this->tokenExpired($token)){
            return ['code' => ['0x003008', 'auth']];
        }else{
//            $openCheck = app($this->openApiService)->openCheck($user->user_id,$token);
//            if(isset( $openCheck['code'])){
//                return $openCheck;
//            }
            return true;
        }
        //return $this->tokenExpired($token) ? ['code' => ['0x003008', 'auth']] : true;
    }
    private function tokenExpired($token)
    {
        $tokenGenerateTime = ecache('Auth:AccessTokenGenerateTime')->get($token);
        if($this->isMobile()) {
            $tokenTtl = config('auth.mobile_token_ttl') * 60;
        } else {
            $tokenTtl = config('auth.web_token_ttl') * 60;
        }
        if ((time() - $tokenGenerateTime) > $tokenTtl) {
            return true;
        }
        return false;
    }
    public function checkToken() {
        try {
            $check = $this->check();
        } catch (\ExceptFion $e) {
            return ['code' => ['0x000018', 'common']];
        }
        if (isset($check['code'][0])) {
            return in_array($check['code'][0], ['0x003001', '0x003003', '0x003008', '0x003068']) ? 0 : $check;
        } else {
            return $check ? 1 : 0;
        }
    }
    public function checkTokenExist($data) {
        $token = $data['token'] ?? '';
        if (!$token) {
            return false;
        }
        if (Cache::has($token)) {
            return true;
        }
        return false;
    }
    /**
     * 刷新新token
     *
     * @param type $refreshToken
     * @param type $token
     * @param type $checkTokenExpired
     *
     * @return type
     */
    public function refresh($refreshToken, $token, $checkTokenExpired = 0)
    {
        if(!$refreshToken || !$token) {
            return ['code' => ['0x003070', 'auth']];
        }
        $lock = Cache::lock($refreshToken, 10);
        try {
            $lock->block(5);
            $newToken = ecache('Auth:NewToken')->get($token);
            if(!isset($newToken) || empty($newToken)) {
                if(!ecache('Auth:RefreshToken')->get($token)) {
                    return ['code' => ['0x003068', 'auth']];
                }

                $user = Cache::get($token);

                if(!$user || !$user->user_id) {
                    return ['code' => ['0x003068', 'auth']];
                }
                // 如果该token没过期，直接返回
                if($checkTokenExpired == 1 && !$this->tokenExpired($token)) {
                    return $token;
                }
                $refreshTokenGenerateTime = ecache('Auth:RefreshTokenGenerateTime')->get($refreshToken);
                if($this->isMobile()) {
                    $tokenTtl = config('auth.mobile_refresh_token_ttl') * 60;
                    $terminal = 'mobile';
                } else {
                    $tokenTtl = config('auth.web_refresh_token_ttl') * 60;
                    $terminal = 'pc';
                }
                if ((time() - $refreshTokenGenerateTime) > $tokenTtl) {
                    return ['code' => ['0x003068', 'auth']];
                }
                $newToken = $this->generateToken($user->user_id, $tokenTtl);
                //检查是否是记录在案的openAPI $refreshToken 来刷新token
                app($this->openApiService)->openRefreshToken($user->user_id,$token,$newToken,$refreshToken);

                Cache::put($newToken, $user, $tokenTtl);

                // 修改刷新token没有更新多语言问题。
                $local = ecache('Lang:Local')->get($token);
                app($this->langService)->bindUserLocale($local, $user->user_id, $newToken);
                $tokenGracePeriod = config('auth.token_grace_period', 60);
                Cache::put($token, $user, $tokenGracePeriod);
                ecache('Auth:AccessTokenGenerateTime')->clear($token);
                ecache('Auth:RefreshTokenGenerateTime')->set($refreshToken, time());
                ecache('Auth:NewToken')->set($token, $newToken);
                ecache('Auth:RefreshToken')->set($newToken, $refreshToken);
                ecache('Auth:RefreshToken')->clear($token);
                $this->userTokenContrast($user->user_id, $terminal, $newToken);
            }
            $lock->release();
        } catch (LockTimeoutException $e) {
            optional($lock)->release();

            return ['code' => ['0x003069', 'auth']];
        } finally {
            optional($lock)->release();
        }

        return $newToken;
    }
    /**
     * @系统注销
     * @param type $token
     * @return boolean
     */
    public function logout()
    {
        if (!$token = $this->getTokenForRequest()) {
            return false;
            // return ['code' => ['0x003001', 'auth']];
        }
        $user = Cache::get($token);
        Cache::forget($token);// 清空access_token
        ecache('Auth:AccessTokenGenerateTime')->clear($token);// 清空access_token 生成时间
        $refreshToken = ecache('Auth:RefreshToken')->get($token);
        ecache('Auth:RefreshTokenGenerateTime')->clear($refreshToken);// 清空refresh_token 生成时间
        ecache('Auth:RefreshToken')->clear($token);// 清空refresh_token

        $identifier  = "system.login.logout";
        $logParams = $this->handleLogParams($user->user_id ?? '', trans('auth.logout'));
        logCenter::info($identifier , $logParams);
        return true;
    }
    public function getSmsVerifyCode($phoneNumber, $params)
    {
        $smsVerfiyType = get_system_param('sms_verify_type', 0);
        if($smsVerfiyType != 1){
            //验证手机号是否为空
            if (!$phoneNumber) {
                return ['code' => ['0x003042', 'auth']];
            }
        }
        // 验证绑定的手机号
        $account = $this->defaultValue('user_account', $params, '');
        $user = app($this->authRepository)->getUserByAccountOrMobile($account);
        if(empty($user)){
            return $this->wrongAccountErrorCode($account);
        }
        if($smsVerfiyType == 1 && isset($user->phone_number)){
            if($user->phone_number == ''){
                return ['code' => ['0x003059', 'auth']];
            }
            $phoneNumber = $user->phone_number;
        }
        if (ecache('Auth:PhoneNumber')->get($phoneNumber)) {
            return true;
        }
        //生成随机的动态验证码
        $verifyCode = '';
        for ($i = 0; $i < 6; $i ++) {
            $verifyCode .= random_int(1, 9);
        }
        //拼接手机短信内容
        $message = ' ' . $verifyCode . ' ' . trans('auth.0x003048');
        //发送手机短信
        $result = app($this->shortMessageService)->sendSMS(['mobile_to' => [$phoneNumber], 'message' => $message]);
        ecache('Auth:PhoneNumber')->set($phoneNumber, $verifyCode);
        ecache('Auth:UserAccount')->set($user->user_accounts ?? '', $verifyCode);
        //处理发送后的结果
        if ($result === true) {
            $smsToken = md5(time() . $phoneNumber . $verifyCode);
            ecache('Auth:SmsToken')->set($smsToken, $verifyCode);
            ecache('Auth:SmsTokenGenerateTime')->set($smsToken, time());
            return ['sms_token' => $smsToken];
        }

        return $result;
    }
    /**
     * 修改密码
     *
     * @param type $data
     *
     * @return boolean
     */
    public function modifyPassword($data)
    {
        $userAccount = $data['user_accounts'];

        $oldPassword = $this->defaultValue('old_password', $data, '');

        $password = $this->defaultValue('password', $data, '');
        $this->authRepositoryObj = app($this->authRepository);
        $allowMobile = get_system_param('allow_mobile_login', 0);
        $passwordLength = app($this->personalSetService)->getPasswordLength();
        $passwordSecurity = get_system_param('login_password_security_switch', 0);
        //验证账号
        $user = $allowMobile == 1 ? $this->authRepositoryObj->getUserByAccountOrMobile($userAccount) : $this->authRepositoryObj->getUserByAccount($userAccount);
        if (!$user) {
            return ['code' => ['0x003004', 'auth']];
        }

        if ($user->password != crypt($oldPassword, $user->password)) {
            return ['code' => ['0x003023', 'auth']];
        }
        if (strlen($data['password']) < $passwordLength) {
            return ['code' => ['0x039016', 'personalset'], 'dynamic' => [trans('personalset.new_password_length_invalid').$passwordLength.trans('personalset.digits')]];
        }
        if ($this->authRepositoryObj->updateData(['password' => crypt($password, null), 'change_pwd' => 0], ['user_id' => [$user->user_id]])) {
            if (app($this->userSystemInfoRepository)->updateData(['last_pass_time' => date('Y-m-d H:i:s')], ['user_id' => [$user->user_id]])) {
                return true;
            }
        }

        return ['code' => ['0x000003', 'common']];
    }

    public function generalLoginQRCode($param)
    {
        $color = isset($param['color']) && !empty($param['color']) ? json_decode($param['color']): [0, 0, 0];
        $background = isset($param['background']) && !empty($param['background']) ? json_decode($param['background']): [255, 255, 255];
        $nonceStr = Str::random(); //生成登录随机串;

        $tokenSecret = config('auth.token_secret'); //获取token加密key

        $loginKey = md5($tokenSecret . $nonceStr . time());

        $qrcodeInfo = [
            'mode' => 'function',
            'body' => [
                'function_name' => '_qrcodeLogin',
                'params' => [
                    'nonce_str' => $nonceStr,
                    'login_key' => $loginKey,
                ]
            ],
            'timestamp' => time(),
            'ttl' => 10
        ];
        $qrcodePath = $this->qrcodePath;
        @chmod($qrcodePath, 0777);
        $tempQRCode =  $qrcodePath .'/'. $loginKey . '.png';
        QrCode::format('png')->size(220)->margin(0)->backgroundColor(intval($background[0]),intval($background[1]),intval($background[2]))->color(intval($color[0]),intval($color[1]),intval($color[2]))->generate(json_encode($qrcodeInfo), $tempQRCode);

        if (!file_exists($tempQRCode)) {
            return ['code' => ['0x003021', 'auth']];
        }
        $data = [
            'nonce_str' => $nonceStr,
            'login_key' => $loginKey,
            'qrcode' =>imageToBase64($tempQRCode),
            'qrcodeInfo' => $qrcodeInfo
        ];

        unlink($tempQRCode);

        return $data;
    }

    public function qrcodeSignOn($data)
    {
        $this->systemParams = $this->getSystemParam();
        $local = $this->defaultValue('local', $data, 'zh-CN');
        // 验证loginKey
        $loginKeySecret = config('auth.login_key_secret');
        $loginKey = md5($loginKeySecret . $data['login_key']);
        if (Cache::has('signing_' . $loginKey)) {
            return ['code' => ['0x003076', 'auth']];
        }
        if (!Cache::has($loginKey)) {
            return ['code' => ['0x003019', 'auth']];
        }
        // 验证$nonceStr
        $tokenSecret = config('auth.token_secret'); //获取token加密key
        $nonceStr = md5($tokenSecret . $data['nonce_str']);
        $qrcodeInfo = Cache::get($loginKey);
        if ($qrcodeInfo['nonce_str'] != $nonceStr) {
            return ['code' => ['0x003020', 'auth']];
        }
        Cache::put('signing_' . $loginKey, true, 60);
        $user = app($this->userService)->getLoginUserInfo($qrcodeInfo['user_id']);
        $this->authRepositoryObj = app($this->authRepository);
        $this->moreVerification($user, 'PC'); //更多验证机制

        if ($this->errors) {
            return $this->errors;
        }
        ecache('Auth:LoginKey')->clear($loginKey);
        return $this->generateInitInfo($user, $local, 'PC');
    }
    public function setLoginThemeAttribute($data)
    {
        if(app($this->loginThemeRepository)->getDetail(1)){
            return app($this->loginThemeRepository)->updateData($data, ['id' => 1]);
        } else {
            $data['id'] = 1;
            return app($this->loginThemeRepository)->insertData($data);
        }
    }
    public function getLoginThemeAttribute($param)
    {
        return app($this->loginThemeRepository)->getDetail(1);
    }
    /**
     * 获取登录模板信息
     *
     * @return string
     */
    public function getLoginInitInfo()
    {
        $this->systemParams = $this->getSystemParam();
        // 如果只有二维码登录，初始化时检查是否授权过期
        $loginByNormal = $this->defaultValue('login_by_normal', $this->systemParams, 0);
        if ($loginByNormal == 0) {
            $pcEmpower = app($this->empowerService)->checkPcEmpower();
            if (is_array($pcEmpower) && isset($pcEmpower['code'])) {
                return $pcEmpower;
            }
            $mobileEmpower = app($this->empowerService)->checkMobileEmpower();
            if (is_array($mobileEmpower) && isset($mobileEmpower['code'])) {
                return $mobileEmpower;
            }
        }
        $passwordLength = app($this->personalSetService)->getPasswordLength();

        return [
            'password_length'         => $passwordLength,
            'allow_mobile_login'      => $this->defaultValue('allow_mobile_login', $this->systemParams, 0),
            'login_by_qrcode'         => $this->defaultValue('login_by_qrcode', $this->systemParams, 0),
            'login_by_normal'         => $loginByNormal,
            'sms_verify'              => $this->defaultValue('sms_verify', $this->systemParams, 0),
            'has_lang_module'         => app($this->empowerService)->checkModuleWhetherExpired(104), //判断多语言模块是否授权
            "security_image_code"     => $this->defaultValue('security_image_code', $this->systemParams, ''),
            'sms_verify_type'         => $this->defaultValue('sms_verify_type', $this->systemParams, 0),
            'login_password_security' => $this->defaultValue('login_password_security', $this->systemParams, 0),
            'login_password_security_switch' => $this->defaultValue('login_password_security_switch', $this->systemParams, 0),
            'crypt_key'               => config('app.key'),
            'crypt_iv'                => '1234567890123456',
            'access_path'             => envOverload('ACCESS_PATH', 'access')
        ];
    }

    public function uploadThemeImage($data)
    {
        $attachment = app($this->attachmentService)->getOneAttachmentById($data['attachment_id']);

        if (!empty($attachment)) {
            $attachmentId = $attachment['attachment_id'];

            $suffix = $attachment['attachment_type'];

            $source = $attachment['temp_src_file'];

            if (file_exists($source)) {
                if ($dir = $this->makeDir('login/theme/')) {
                    $theme = $dir . $attachmentId . '.' . $suffix;

                    copy($source, $theme);

                    scaleImage($theme, 176, 96, 'thumb_');

                    return 'thumb_' . $attachmentId . '.' . $suffix;
                }
            }
        }

        return ['code' => ['0x003011', 'auth']];
    }
    public function deleteThemeImage($data)
    {
        $thumb = $data['thumb'];

        list($thumbPrefix, $theme) = explode('_', $thumb);

        if (unlink($this->themePath . $theme)) {
            if (unlink($this->themePath . $thumb)) {
                return 1;
            }
        }

        return 0;
    }

    public function setLogo($data)
    {
        if ($data['attachment_id'] == 'eoffice') {
            //set_system_param('login_logo', '');
            $this->setLoginThemeAttribute(['login_logo' => '']);
            return '';
        }
        $attachment = app($this->attachmentService)->getOneAttachmentById($data['attachment_id']);
        if (!empty($attachment)) {
            $suffix = $attachment['attachment_type'];
            $types = ['png', 'jpg', 'jpeg', 'gif', 'PNG', 'JPG', 'JPEG', 'GIF'];
            if(!in_array($suffix, $types)){
                return ['code' => ['0x011011', 'upload']];
            }
            $source = $attachment['temp_src_file'];
            if (file_exists($source)) {
                if ($dir = $this->makeDir('login/logo/')) {
                    $logo = 'logo.' . $suffix;

                    copy($source, $dir . $logo);

                    //set_system_param('login_logo', $logo);
                    $this->setLoginThemeAttribute(['login_logo' => $logo]);
                    return $logo;
                }
            }
        }

        return ['code' => ['0x003011', 'auth']];
    }
    public function setFormLeftBg($data)
    {
        if ($data['attachment_id'] == 'eoffice') {
            $this->setLoginThemeAttribute(['form_left_background' => '']);
            return '';
        }
        $attachment = app($this->attachmentService)->getOneAttachmentById($data['attachment_id']);
        if (!empty($attachment)) {
            $suffix = $attachment['attachment_type'];

            $source = $attachment['temp_src_file'];
            if (file_exists($source)) {
                if ($dir = $this->makeDir('login/left/')) {
                    $leftBg = 'left.' . $suffix;

                    copy($source, $dir . $leftBg);

                    $this->setLoginThemeAttribute(['form_left_background' => $leftBg]);
                    return $leftBg;
                }
            }
        }

        return ['code' => ['0x003011', 'auth']];
    }
    public function setElementImage($data)
    {
        if ($data['attachment_id'] == 'eoffice') {
            return '';
        }
        $attachment = app($this->attachmentService)->getOneAttachmentById($data['attachment_id']);
        if (!empty($attachment)) {
            $suffix = $attachment['attachment_type'];

            $source = $attachment['temp_src_file'];
            if (file_exists($source)) {
                if ($dir = $this->makeDir('login/elements/')) {
                    $elementImg = $data['attachment_id'] . '.' . $suffix;

                    copy($source, $dir . $elementImg);

                    return $elementImg;
                }
            }
        }

        return ['code' => ['0x003011', 'auth']];
    }
    /**
     * 获取登录页主题图片
     *
     * @success array 登录页主题图片数组
     * @successExample 成功返回示例:
     * ["123.jpg","234.jpg"]
     *
     */
    public function getLoginThemeImages()
    {
        if (!file_exists($this->themePath)) {
            return [];
        }

        $thumbs = [];

        $handler = opendir($this->themePath);

        while (($fileName = readdir($handler)) !== false) {
            if ($fileName != "." && $fileName != "..") {
                list($file, $suffix) = explode(".", $fileName);

                if (in_array(strtolower($suffix), ['png', 'jpg', 'jpeg', 'gif'])) {
                    if (strpos($file, 'thumb_') !== false) {
                        $thumbs[] = $fileName;
                    }
                }
            }
        }

        closedir($handler);

        return $thumbs;
    }

    /**
     * 注册系统初始信息
     * yww = weixin 注入时 private 不能访问 改成public
     */
    public function generateInitInfo($user, $local, $terminal = '', $isNormalLogin = true, $data = [])
    {
        $isApp = false;
        if ((isset($_SERVER['HTTP_USER_AGENT'])
                && (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false
                        || strpos($_SERVER['HTTP_USER_AGENT'], 'Eoffice') !== false
                        || strpos($_SERVER['HTTP_USER_AGENT'], 'okhttp') !== false))
                || $terminal == 'mobile') {
            // 微信、钉钉、APP、手机版登录页面
            $terminal = 'mobile';
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
                if(strpos($_SERVER['HTTP_USER_AGENT'], 'Light MicroMessenger') == false){
                    $terminal = 'PC';
                }
                $logContent = trans('auth.weixin_login');
            } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Eoffice') !== false) {
                $logContent = trans('auth.mobile_login');
                $isApp = true;
            } else {
                if((strpos($_SERVER['HTTP_USER_AGENT'], 'okhttp') !== false)) {
                    $isApp = true;
                }
                $logContent = trans('auth.mobile_login');

            }
        } else if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'DingTalk') !== false) {
            if($terminal == 'dingTalk'){
                $terminal = 'mobile';
            }else{
                $terminal = 'PC';
            }
            $logContent = trans('auth.dingding_login');
        }else if ($terminal == 'cas') {
            $terminal = 'PC';
            $logContent = trans('auth.0x003054'); // CAS登录
        } else if ($terminal == 'client') {
            $terminal = 'PC';
            $logContent = trans('auth.client_login'); //可以设置为客户端登录，但是真实平台号无法记录
        } else {
            $logContent = trans('auth.web_login');
        }
        if($this->isMobile()) {
            $refreshTokenTtl = config('auth.mobile_refresh_token_ttl') * 60;
        } else {
            $refreshTokenTtl = config('auth.web_refresh_token_ttl') * 60;
        }
        $token = $this->generateToken($user->user_id, $refreshTokenTtl);
        if($isNormalLogin) {
            $refreshToken = $this->generateToken($user->user_id, $refreshTokenTtl, 'refresh');
            ecache('Auth:RefreshToken')->set($token, $refreshToken);
        }
        $response = $this->combineUser($user, $local, function($user) use($token, $local, $terminal, $logContent, $refreshTokenTtl) {
            app($this->langService)->bindUserLocale($local, $user->user_id, $token);

            $user->menus = app($this->userMenuService)->getUserMenus($user->user_id, $terminal);
            //获取有权限的模块菜单
            $empowerMenuId = ecache('Empower:EmpowerModuleInfo')->get();
            $user->empower_module = $empowerMenuId;

            Cache::put($token, $user, $refreshTokenTtl);
            // 保存用户token关系对应
            $this->userTokenContrast($user->user_id, $terminal, $token);

            $this->addLoginLog($user->user_id, $terminal, $logContent); //添加登录日志
            return $user;
        }, $isApp);
        $response['token'] = $token;
        // 微信首次登录问题处理
        if (isset($data['platform']) && $data['platform'] == 'officialAccount') {
            $params = [
                'sign' => $this->defaultValue('sign', $data, ''),
                'state' => $this->defaultValue('state', $data, ''),
                'token' => $token,
                'locale' => $local
            ];
            $response['redirect_url'] = app($this->weixinService)->weixinLogin($params, ['user_id' => $user->user_id]);
        }

        if($isNormalLogin) {
            $response['refresh_token'] = $refreshToken;
        }
        if ($this->firstLogin == 1) {
            $response['first_login'] = 1;
        }
        if(isset($data['CASE_ID']) && $data['CASE_ID']) {
            $response['case_id'] = $data['CASE_ID'];
        }

        /**
         * 其他登录相关处理
         *  1. 清除导航栏搜索分类缓存
         */
        $userId = $user->user_id;
        ecache('Portal:GlobalSearchItem')->clear($userId);
        ecache('Auth:UserAccount')->clear($user->user_accounts);
        if (!ecache('Home:BootPageStatus')->has() || ecache('Home:BootPageStatus')->get() == 0) {
            Home::setBootPageStatus();
        }
        return $response;
    }
    // 保存用户token关系对应
    public function userTokenContrast($userId, $terminal, $token) {
        if (!isset($this->systemParams)) {
            $this->systemParams = $this->getSystemParam();
        }
        if (empty($terminal)) {
            $terminal = 'pc';
        } else {
            $terminal = strtolower($terminal);
        }
        $userLoginToken = [$terminal => [$token]];
        if (Redis::exists($this->userTokenContrastKey)) {
            if (Redis::hExists($this->userTokenContrastKey, $userId)) {
                $userLoginToken = unserialize(Redis::hGet($this->userTokenContrastKey, $userId));

                if (isset($this->systemParams['account_login_control']) && $this->systemParams['account_login_control'] == 1) {
                    // 后登录挤掉前面的
                    foreach ($userLoginToken as $k => $v) {
                        if ($k == $terminal) {
                            // 表示用户已在该平台登录，删除之前登录的token
                            if (!in_array($token, $v)) {
                                foreach ($v as $oldToken) {
                                    Cache::forget($oldToken);// 清空access_token
                                    ecache('Auth:AccessTokenGenerateTime')->clear($oldToken);// 清空access_token 生成时间
                                    $refreshToken = ecache('Auth:RefreshToken')->get($oldToken);
                                    ecache('Auth:RefreshTokenGenerateTime')->clear($refreshToken);// 清空refresh_token 生成时间
                                    ecache('Auth:RefreshToken')->clear($oldToken);// 清空refresh_token
                                }
                                $userLoginToken[$k] = [];
                                $userLoginToken[$k][] = $token;
                            }
                        } else {
                            if (!isset($userLoginToken[$terminal])) {
                                $userLoginToken[$terminal] = [];
                            }
                            $userLoginToken[$terminal][] = $token;
                        }
                    }
                } else {
                    // 无
                    $userLoginToken[$terminal][] = $token;
                }
            }
        }
        // 保存当前平台登录token
        Redis::hset($this->userTokenContrastKey, $userId, serialize($userLoginToken));
    }
    // 删除token
    public function deleteToken($data) {
        if (isset($data['token'])) {
            Cache::forget($data['token']);
        }
        if (isset($data['refresh_token'])) {
            Cache::forget($data['refresh_token']);
        }
        return true;
    }
    /**
     * 刷新用户初始化信息
     *
     * @return boolean | array
     */
    public function refreshLoginInfo($refreshNewToken = false)
    {
        if (!$token = $this->getTokenForRequest()) {
            return ['code' => ['0x003001', 'auth']];
        }

        $user = Cache::get($token);

        if($user){
            $local = app($this->langService)->getUserLocale($user->user_id);
            if($this->isMobile()) {
                $refreshTokenTtl = config('auth.mobile_refresh_token_ttl') * 60;
                $user = app($this->userService)->getLoginUserInfo($user->user_id);
                $terminal = 'mobile';
            } else {
                $refreshTokenTtl = config('auth.web_refresh_token_ttl') * 60;
                $terminal = 'pc';
            }

            if($refreshNewToken == 'true') {
                $token = $this->generateToken($user->user_id, $refreshTokenTtl);
                $refreshToken = $this->generateToken($user->user_id, $refreshTokenTtl, 'refresh');
                $refreshToken = ecache('Auth:RefreshToken')->set($token, $refreshToken);
            } else {
                $refreshToken = ecache('Auth:RefreshToken')->get($token);
            }
            $response = $this->combineUser($user, $local, function($user) use($token, $local, $refreshTokenTtl, $terminal){
                if($this->isMobile()) {
                     $user->menus = app($this->userMenuService)->getUserMenus($user->user_id,'mobile');//此处缺少第二个平台参数，导致默认都是pc端菜单
                    //获取有权限的模块菜单
                    $empowerMenuId = ecache('Empower:EmpowerModuleInfo')->get();
                    $user->empower_module = $empowerMenuId;
                }
                app($this->langService)->bindUserLocale($local, $user->user_id, $token);

                Cache::put($token, $user, $refreshTokenTtl);
                // 保存用户token关系对应
                $this->userTokenContrast($user->user_id, $terminal, $token);
                return $user;
            });
            $response['token'] = $token;
            $response['refresh_token'] = $refreshToken;
            return $response;
        }

        return false;
    }
    /**
     * 组装用户初始化信息
     *
     * @param string $token
     * @param object $user
     * @param Closure $terminal
     * @param string $local
     *
     * @return array
     */
    private function combineUser($user, $local, $terminal = null, $isApp = false)
    {
        if(is_callable($terminal)) {
            $user = $terminal($user);
        }

        $company = app($this->companyService)->getCompanyDetail();

        $lastLoginTime = date('Y-m-d H:i:s');

        app($this->userSystemInfoRepository)->updateData(['last_login_time' => $lastLoginTime], ['user_id' => [$user->user_id]]); //更新用户最后登录时间

        $recombine = $this->separateUser($user);

        $version = version(); //获取系统版本号
        if($isApp) {
            return [
                'locale' => $local,
                'version' => $version,
                'access_path' => envOverload('ACCESS_PATH', 'access'),
                'can_use_eassistant' => app('App\EofficeApp\XiaoE\Services\SystemService')->canUse(),
                'mobile_url' => '/eoffice10/client/mobile/'
            ];
        }
        $baseData = [
            'mobile_token_ttl' => intval(config('auth.web_token_ttl')),
            'current_user' => $recombine['user'],
            'company_name' => $company['company_name'],
            'portal_menus' => $this->getPortalMenus($user),
            'last_login_time' => $lastLoginTime,
            'locale' => $local,
            'im_config' => $this->combineIMConfig(),
            'system_info' => $this->combineInitSystemParam($version),
            'version' => $version,
            'request_time' => $this->getMsectime(),
            'client_url' => '/eoffice10/client/web#',
            'mobile_url' => '/eoffice10/client/mobile/',
            'can_use_eassistant' => app('App\EofficeApp\XiaoE\Services\SystemService')->canUse(),
            'access_path' => envOverload('ACCESS_PATH', 'access'),
        ];

        return array_merge($baseData, $recombine['data']);
    }
    private function combineIMConfig()
    {
        return  [
                'IM_PORT' => envOverload('IM_PORT'),
                'IM_HOST' => envOverload('IM_HOST'),
                'IM_ENCRYPT_KEY' => envOverload('IM_ENCRYPT_KEY'),
                'IM_PORT_HTTPS' => envOverload('IM_PORT_HTTPS'),
                'PROTOCOL' => envOverload('PROTOCOL'),
                'IM_PORT_INTERNAL' => envOverload('IM_PORT_INTERNAL'),
                'CASE_PLATFORM' => envOverload('CASE_PLATFORM', false),
                'SOCKET_CONFIG' => envOverload('SOCKET_CONFIG', false),
                'IM_ENABLE' => envOverload('IM_ENABLE', '1')
            ];
    }
    private function getPortalMenus($user)
    {
        $userId = $user->user_id;
        $deptId = $user->dept_id;
        $roleId = isset($user->roles) ? array_column($user->roles, 'role_id') : array_column($user['userHasManyRole']->toArray(), 'role_id');
        return app('App\EofficeApp\Portal\Services\PortalService')->getPortalMenus($userId, $deptId, $roleId);
    }
    private function combineInitSystemParam($version)
    {
        $this->systemParams = !isset($this->systemParams) ? $this->getSystemParam() : $this->systemParams;
        $initSystemParams = $this->handleSystemParams($this->systemParams, ['web_home_module_type','home_module','navbar_type', 'navigate_menus', 'sys_logo', 'sys_logo_type', 'default_avatar_type', 'default_avatar', 'system_title', 'user_set_model', 'web_home', 'home_text', 'default_user_type', 'default_theme', 'message_read']);
        $initSystemParams['navigate_menus'] = json_decode($initSystemParams['navigate_menus']);
        $initSystemParams['version'] = $version;
        $initSystemParams['default_theme'] = $initSystemParams['default_theme'] ? json_decode($initSystemParams['default_theme'], true) : '';
        $initSystemParams['home_text'] = $initSystemParams['home_text'] ? mulit_trans_dynamic("system_params.param_value.home_text") : '';
        $initSystemParams['web_home_module_type'] = $initSystemParams['web_home_module_type'] ? $initSystemParams['web_home_module_type'] : 'portal';
        $initSystemParams['home_module'] = $initSystemParams['home_module'] ?? null;
        $initSystemParams['attend_mobile_type_location'] = app($this->attendanceSettingService)->getSystemParam('attend_mobile_type_location');
        $initSystemParams['attend_mobile_type_wifi'] = app($this->attendanceSettingService)->getSystemParam('attend_mobile_type_wifi');
        $initSystemParams['attendance_points_adjust_useed'] = app($this->attendanceSettingService)->getSystemParam('attendance_points_adjust_useed');
        $initSystemParams['attendance_mobile_useed'] = app($this->attendanceSettingService)->getSystemParam('attendance_mobile_useed');
        $initSystemParams['message_read'] =$initSystemParams['message_read'] ?? 1;
        return $initSystemParams;
    }
    public function getMsectime() {
        list($msec, $sec) = explode(' ', microtime());
        return (string)sprintf('%.0f', ($msec + $sec) * 1000);
    }

    private function separateUser($user) {
        $data = [
            'menus' => [],
            'empower_module' => [],
        ];
        if (isset($user->menus)) {
            $data['menus'] = $user->menus;
            unset($user->menus);
        }
        if (isset($user->empower_module)) {
            $data['empower_module'] = $user->empower_module;
            unset($user->empower_module);
        }
        // 当前用户是否加密组织架构
        if (is_object($user)) {
            $user->encrypt_organization = $this->getEncryptOrNot($user);
            // 加密并且已选默认是组织时,将默认组织更换为其他菜单
            if ($user->encrypt_organization == 1 && isset($user->show_page_after_login) && $user->show_page_after_login == 'organization') {
                $navbarArray = ['', 'message', 'app', 'create', 'organization', 'myself'];
                $navbar = app($this->mobileService)->getNavbarChildren([], 0, ['user_id' => $user->user_id]);
                if (!empty($navbar) && count($navbar) > 0 && isset($navbar[0]) && isset($navbar[0]['navbar_id'])) {
                    $navbarId = $navbar[0]['navbar_id'];
                    if (isset($navbarArray[$navbarId]) && !empty($navbarArray[$navbarId])) {
                        $user->show_page_after_login = $navbarArray[$navbarId];
                        app($this->userInfoRepository)->updateData(['show_page_after_login' => $navbarArray[$navbarId]], ['user_id' => $user->user_id]);
                    } else {
                        $user->show_page_after_login = 'myself';
                        app($this->userInfoRepository)->updateData(['show_page_after_login' => 'myself'], ['user_id' => $user->user_id]);
                    }
                } else {
                    $user->show_page_after_login = 'myself';
                    app($this->userInfoRepository)->updateData(['show_page_after_login' => 'myself'], ['user_id' => $user->user_id]);
                }
            }
        }
        return ['data' => $data, 'user' => $user];
    }
    /**
     * 当前用户是否加密组织架构
     *
     * @return boolean | array
     */
    public function getEncryptOrNot($currentUser) {
        if (!isset($this->systemParams)) {
            $this->systemParams = $this->getSystemParam();
        }
        $encryptOrganization = $this->defaultValue('encrypt_organization', $this->systemParams, 0);
        if (!isset($currentUser->user_id) || empty($currentUser->user_id)) {
            return 0;
        }
        if ($encryptOrganization) {
            $dept = $this->defaultValue('exclude_encrypt_dept', $this->systemParams, []);
            if (isset($currentUser->dept_id) && !empty($currentUser->dept_id)) {
                if (!empty($dept) && in_array($currentUser->dept_id, explode(',', $dept))) {
                    ecache('Auth:EncryptOrganization')->set($currentUser->user_id, 0);
                    return 0;
                }
            }
            $role = $this->defaultValue('exclude_encrypt_role', $this->systemParams, []);
            $userRole = isset($currentUser->roles) && !empty($currentUser->roles) ? array_column($currentUser->roles, 'role_id') : [];
            if (!empty($role) && !empty(array_intersect($userRole, explode(',', $role)))) {
                ecache('Auth:EncryptOrganization')->set($currentUser->user_id, 0);
                return 0;
            }
            $user = $this->defaultValue('exclude_encrypt_user', $this->systemParams, []);
            if (!empty($user) && in_array($currentUser->user_id, explode(',', $user))) {
                ecache('Auth:EncryptOrganization')->set($currentUser->user_id, 0);
                return 0;
            }
            ecache('Auth:EncryptOrganization')->set($currentUser->user_id, 1);
            return 1;
        } else {
            ecache('Auth:EncryptOrganization')->set($currentUser->user_id, 0);
            return 0;
        }
    }
    /**
     * 移动端返回数据
     */
    public function appLoginInfo()
    {
        if (!$token = $this->getTokenForRequest()) {
            return ['code' => ['0x003001', 'auth']];
        }

        if (!$user = Cache::get($token)) {
            return false;
        }

        $locale = app($this->langService)->getUserLocale($user->user_id);

        return $this->combineUser($user, $locale);
    }
    private function handleSystemParams($systemParam, array $keys)
    {
        $handleParam = [];
        if(!empty($keys) && !empty($systemParam)){
            foreach ($keys as $key){
                $handleParam[$key] = $systemParam[$key] ?? '';
            }
        }
        return $handleParam;
    }
    private function makeDir($path)
    {
        if (!$path || $path == '/' || $path == '\\') {
            return $this->accessImagePath;
        }

        $dir = $this->accessImagePath;

        $dirNames = explode('/', trim(str_replace('\\', '/', $path), '/'));

        foreach ($dirNames as $dirName) {
            $dir .= $dirName . '/';

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);

                chmod($dir, 0777);
            }
        }

        return $dir;
    }

    /**
     * 获取token
     * @return type
     * openApi 记录日志要时也要获取到一致性的token,将private改成public
     */
    public function getTokenForRequest()
    {
        $token = Request::input('api_token');
        if (empty($token)) {
            $token = Request::bearerToken();
        }

        if (empty($token)) {
            $token = Request::getPassword();
        }
        return $token;
    }

    /**
     * 生成token
     * @param type $userId
     * @return type
     */
    private function generateToken($userId, $refreshTokenTtl, $type = 'access')
    {
        $tokenSecret = config('auth.token_secret');

        $tokenAlgo = config('auth.token_algo');

        $tokenTime = time();

        $token = hash($tokenAlgo, $type . $userId . $tokenTime . $tokenSecret, false);
        if($type == 'access'){
            ecache('Auth:AccessTokenGenerateTime')->set($token, time());
        }else if($type == 'refresh'){
            ecache('Auth:RefreshTokenGenerateTime')->set($token, time());
        }
        return $token;
    }

    /**
     * 获取系统参数
     * @return type
     */
    public function getSystemParam()
    {
        $params = [];
        foreach (get_system_param() as $key => $value) {
            $params[$value->param_key] = $value->param_value;
        }
        return $params;
    }

    /**
     * @验证账号，密码
     * @param type $userAccount
     * @param type $password
     * @return array 用户信息
     */
    private function checkAccount($password, $user)
    {
        if ($this->errors) {
            return $this->errors;
        }
        // 启用密码强度限制，当密码为空时，强制修改密码
        $passwordSecurity = $this->defaultValue('login_password_security_switch', $this->systemParams, 0);
        $emptyPassword = '$1$RH0lEs3P$TTSrhDpNXywnU1Y9e0i5t0';
        if ($passwordSecurity == 1 && $emptyPassword == crypt($password, $emptyPassword)) {
            $this->errors = ['code' => ['0x003062', 'auth']];
        }

        //验证密码
        if ($user->password != crypt($password, $user->password)) {
            if (strlen($password) > 5) {
                $pattern     = '/(\w{2})(\w{0,})(\w{2})/i';
                $replacement = '$1***$3';
                $resStr      = preg_replace($pattern, $replacement, $password);
            } else {
                $resStr = $password;
            }
            $this->errors = [
                'code' => ['0x003005', 'auth']
            ];
            // 密码错误次数,第5次输错锁定
            $wrongPasswordLock = $this->defaultValue('wrong_password_lock', $this->systemParams, 0);
            if($wrongPasswordLock == 1){
                if($times = ecache('Auth:WrongPwdTimes')->get($user->user_id)){
                        $times--;
                    if ($times == 0) {
                        $sendData = [
                            'toUser'       => 'admin',
                            'remindMark'   => 'user-lock',
                            'contentParam' => ['userName' => $user->user_name],
                        ];
                        Eoffice::sendMessage($sendData);
                        $this->errors = $this->unlockTimeError($user->user_id);
                    } else {
                        $this->errors['dynamic'] = [trans("auth.0x003005").','.trans("auth.can_try").' '.$times.' '.trans("auth.times")];
                    }
                }else{
                    $times = 4;
                    $this->errors['dynamic'] = [trans("auth.0x003005").','.trans("auth.can_try").' '.$times.' '.trans("auth.times")];
                }
                ecache('Auth:WrongPwdTimes')->set($user->user_id, $times);
            } else {
                $this->errors = [
                    'code'    => ['0x003005', 'auth']
                ];
            }

            $this->addLoginLog($user->user_id, 'pwderror', trans('auth.wrong_password') . '：' . $resStr);

            return $this->errors;
        }

        // 如果密码输入正确，重置密码错误次数
        ecache('Auth:WrongPwdTimes')->clear($user->user_id);
        return app($this->userService)->getLoginUserInfo($user->user_id, []);
    }

    private function handleUserData($user) {
        $newUser = new \stdClass();
        $newUser->user_id = $user->user_id;
        $newUser->user_name = $user->user_name;
        $newUser->user_accounts = $user->user_accounts;
        $newUser->dept_id = 0;
        $newUser->dept_name = '';
        $newUser->roles = [];
        $newUser->user_status = 0;
        $newUser->user_status_name = '0';
        $newUser->superior = [];
        $newUser->subordinate = [];
        $newUser->last_pass_time = $user->userHasOneSystemInfo->last_pass_time ?? null;
        $newUser->change_pwd = $user->change_pwd ?? 0;


        if (isset($user->userHasOneSystemInfo->userSystemInfoBelongsToDepartment)) {
            $department = $user->userHasOneSystemInfo->userSystemInfoBelongsToDepartment;
            $newUser->dept_id = $department->dept_id ?? 0;
            $newUser->dept_name = $department->dept_name ?? 0;
        }

        if (isset($user->userHasManyRole) && !empty($user->userHasManyRole)) {
            $roles = [];
            foreach($user->userHasManyRole as $value){
                if (isset($value->hasOneRole) && !empty($value->hasOneRole)) {
                    $roles[] = [
                        'role_id' => $value->hasOneRole->role_id,
                        'role_name' => $value->hasOneRole->role_name,
                    ];
                }
            }
            $newUser->roles = $roles;
        }

        if (isset($user->userHasOneSystemInfo->userSystemInfoBelongsToUserStatus->status_id)) {
            $newUser->user_status = $user->userHasOneSystemInfo->userSystemInfoBelongsToUserStatus->status_id;
        }

        if (isset($user->userHasManySuperior) && !empty($user->userHasManySuperior)) {
            $superior = [];
            foreach($user->userHasManySuperior as $value){
                if (isset($value->superiorHasOneUser) && !empty($value->superiorHasOneUser)) {
                    $superior[] = [
                        'user_id' => $value->superiorHasOneUser->user_id,
                        'user_name' => $value->superiorHasOneUser->user_name,
                    ];
                }
            }
            $newUser->superior = $superior;
        }
        if (isset($user->userHasManySubordinate) && !empty($user->userHasManySubordinate)) {
            $subordinate = [];
            foreach($user->userHasManySubordinate as $value){
                if (isset($value->subordinateHasOneUser) && !empty($value->subordinateHasOneUser)) {
                    $subordinate[] = [
                        'user_id' => $value->subordinateHasOneUser->user_id,
                        'user_name' => $value->subordinateHasOneUser->user_name,
                    ];
                }
            }
            $newUser->subordinate = $subordinate;
        }
        return $newUser;
    }

    private function validAccount($userAccount) {

        $allowMobile = $this->defaultValue('allow_mobile_login', $this->systemParams, 0);
        $wrongPasswordLock = $this->defaultValue('wrong_password_lock', $this->systemParams, 0);
        //验证账号
        $user = $allowMobile == 1 ? $this->authRepositoryObj->getUserByAccountOrMobile($userAccount) : $this->authRepositoryObj->getUserByAccount($userAccount);
        if (!$user) {
            $this->addLoginLog($userAccount, 'erroruname', trans('auth.wrong_name') . '：' . $userAccount);
            return $this->errors = $this->wrongAccountErrorCode($userAccount);
        }

        if (!$user->user_accounts || $user->user_accounts == '') {
            return $this->errors = ['code' => ['0x003014', 'auth']];
        }
        // 判断当前账号是否已锁定
        if ((ecache('Auth:WrongPwdTimes')->get($user->user_id) === '0')) {
            return $this->errors = $this->unlockTimeError($user->user_id);
        }
        return $user;
    }

    /**
     * 图形码验证
     *
     * @param type $captcha
     *
     * @return boolean
     */
    private function captchaCheck($captcha, $data)
    {
        $captchaCheck = $this->systemParams['security_image_code'] ?? 0;
        if ($captchaCheck == 0) {
            return true;
        }
        if ($captcha != '') {
            $captchaKey = $data['captcha_key'] ?? '';
            if (empty($captchaKey)) {
                return $this->errors = ['code' => ['0x003060', 'auth']];
            }
            $phrase = ecache('Auth:CaptchaKey')->get($captchaKey);
            // 防止验证码复用
            $captchaResult = $this->getCaptcha('');
            if(is_array($captchaResult) && isset($captchaResult['code'])) {
                return $this->errors = $captchaResult;
            }
            if (!$phrase) {
                return $this->errors = ['code' => ['0x003066', 'auth']];
            }
            if ($phrase != $captcha) {
                return $this->errors = ['code' => ['0x003038', 'auth']];
            }
        } else{
            return $this->errors = ['code' => ['0x003060', 'auth']];
        }
    }
    /**
     * 手机版绑定账号验证
     *
     * @param type $user
     * @param type $data
     *
     * @return type
     */
    private function mobileBindAccountCheck($user, $data)
    {
        $mobileService = app('App\EofficeApp\Mobile\Services\MobileService');
        $checkAuthInfo = $mobileService->getBindMobileSet();
        /**
        | -------------------------------------------------------------------
        | 判断是否为手机版app登录
        | -------------------------------------------------------------------
         */
        if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Eoffice') !== false ||
                strpos($_SERVER['HTTP_USER_AGENT'], 'okhttp') !== false)) {
            if ($mobileService->getMobileCodeCheck() == 1) {
                $macAddress   = $this->defaultValue('mac_addr', $data, '');
                if(strpos($_SERVER['HTTP_USER_AGENT'], 'Eoffice') !== false ){
                    $platform = 'iphone';
                } else {
                    $platform = 'android';
                }

                $check = $mobileService->bindUserMobileCheck($macAddress, $user);
                if ($check == 3) {
                    return $this->errors = ['code' => ['0x003040', 'auth']];
                } else if ($check == 4) {
                    return $this->errors = ['code' => ['0x003041', 'auth']];
                } else if ($check == 2) {
                    $mobileService->bindUserMobile($macAddress, $user->user_id, $platform, true);
                } else if ($check == 0) {
                    $mobileService->bindUserMobile($macAddress, $user->user_id, $platform);
                }
            }
        }else{
            $data['terminal'] = $data['terminal'] ?? 'pc';
            if(isset($checkAuthInfo['mobile_code_check']) && ($checkAuthInfo['mobile_code_check'] == 1) &&
                isset($checkAuthInfo['browser_login_control']) && ($checkAuthInfo['browser_login_control'] == 1) &&
                ($data['terminal'] == 'mobile')){
                return $this->errors = ['code' => ['0x003075', 'auth']];
            }
        }
        return true;
    }
    /**
     * @其他验证方式
     * @return boolean|string 其他验证失败，成功信息
     */
    private function moreVerification($user, $terminal = '')
    {
        //ip控制
        if (!$this->ipControl($user)) {
            $this->addLoginLog($user->user_id, 'ilip', trans('auth.illegal_ip'));
            return $this->errors = ['code' => ['0x000009', 'common']];
        }
        if (!$this->systemParams) {
            $this->systemParams = $this->getSystemParam();
        }
        $loginByNormal = $this->systemParams['login_by_normal'] ?? 1;
        if ($loginByNormal) {
            // 更换密码强度强制用户更换密码
            if(isset($user->change_pwd) && $user->change_pwd == 1){
                if($terminal == 'mobile'){
                    return $this->errors = ['code' => ['0x003065', 'auth']];
                }else{
                    return $this->errors = ['code' => ['0x003062', 'auth']];
                }
            }
            //密码过期判断
            if (!$this->passwordExpireCheck($user)) {
                return $this->errors = ['code' => ['0x003010', 'auth']];
            }
        }

        $empowerService = app($this->empowerService);
        //授权
        if ((isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'DingTalk') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Eoffice') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'okhttp') !== false)) || $terminal == 'mobile') {
            // 微信、钉钉、APP、手机版登录页面
            $userInfo = $this->authRepositoryObj->getUserByAccountOrMobile($user->user_accounts);
            $res      = $empowerService->checkMobileEmpowerAndWapAllow($userInfo->user_id);
        } else {
            // PC端
            $res = $empowerService->checkPcEmpower();
        }

        if ((isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Eoffice') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'okhttp') !== false)) || $terminal == 'mobile') {
            // 手机app登录的或者手机浏览器访问的移动端页面的判断app是否授权
            if (!(app($this->empowerService)->checkModuleWhetherExpired(192))) {
                // APP模块未授权
                $res = ['code' => ['0x003039', 'auth']];
            }
        }

        if (isset($res['code'])) {
            if ($this->registerTryUsedVersion()) {
                return true;
            }

            return $this->errors = $res;
        }
        return true;
    }
    public function checkMobileEmpowerAndWapAllow($userId)
    {
        return app($this->empowerService)->checkMobileEmpowerAndWapAllow($userId);
    }
    public function smsCodeCheck($userId, $data)
    {
        $smsVerify = $this->systemParams['sms_verify'] ?? 0;
        if ($smsVerify == 0) {
            return true;
        }
        $smsToken = (isset($data['sms_token']) && !empty($data['sms_token'])) ? trim($data['sms_token']) : '';
        $smsVerifyCode = (isset($data['sms_verify_code']) && !empty($data['sms_verify_code'])) ? trim($data['sms_verify_code']) : '';
        // 判断绑定手机号的验证码
        $account = $data['user_account'] ?? '';
        $userAccountCode = ecache('Auth:UserAccount')->get($account);
        
        if(empty($smsToken)){
            return $this->errors = ['code' => ['0x003044', 'auth']];
        }
        if(empty($smsVerifyCode)){
            return $this->errors =  ['code' => ['0x003045', 'auth']];
        }
        $serverSmsVerifyCode = ecache('Auth:SmsToken')->get($smsToken);
        if (!$serverSmsVerifyCode) {
            return $this->errors =  ['code' => ['0x003046', 'auth']];
        }
        if($smsVerifyCode != $serverSmsVerifyCode){
            return $this->errors =  ['code' => ['0x003046', 'auth']];
        }
        $generateTime = ecache('Auth:SmsTokenGenerateTime')->get($smsToken);
        if((time() - $generateTime) > 60){
            return $this->errors =  ['code' => ['0x003047', 'auth']];
        }
        if($smsVerifyCode != $userAccountCode){
            return $this->errors =  ['code' => ['0x003077', 'auth']];
        }

        return true;
    }
    /**
     * 验证动态令牌，动态密码
     */
    public function dynamicCodeCheck($userId, $data)
    {
        $dynamicPasswordIsUseed = $this->systemParams['dynamic_password_is_useed'] ?? 0;
        if ($dynamicPasswordIsUseed == 0) {
            return true;
        }

        if ($dynamicInfo = app($this->userService)->getUserDynamicCodeInfoByUserId($userId)) {
            if ($dynamicInfo->is_dynamic_code != 1) {
                return true;
            }

            $snNumber = $dynamicInfo->sn_number ? trim($dynamicInfo->sn_number) : '';
            if ($snNumber == "") {
                return $this->errors = ['code' => ['0x003025', 'auth']];
            }

            $dynamicAddr = isset($this->systemParams['dynami_addr']) ? $this->systemParams['dynami_addr'] : '';
            if ($dynamicAddr == '') {
                return $this->errors = ['code' => ['0x003026', 'auth']];
            }

            $dynamicCode = isset($data['dynamic_code']) ? trim($data['dynamic_code']) : '';
            if ($dynamicCode == '') {
                return $this->errors = ['code' => ['0x003027', 'auth']];
            }

            $result = @file_get_contents($dynamicAddr . "/tokenDL/index.jsp?TYPE=checkKey&SN_NUM=" . $snNumber . "&TOKENCODE=" . $dynamicCode);
            $result = trim($result);
            if ($result == "") {
                return $this->errors = ['code' => ['0x003028', 'auth']];
            }
            $errorCodes = [
                "7"   => "0x003031",
                "14"  => "0x003029",
                "15"  => "0x003030",
                "100" => "0x003031",
                "208" => "0x003032",
            ];
            if (isset($errorCodes[$result])) {
                return $this->errors = ['code' => [$errorCodes[$result], 'auth']];
            }
            if ($result == "0" || $result == "1") {
                return true;
            } else {
                return $this->errors = ['code' => ['0x003033', 'auth']];
            }
        }

        return true;
    }
    /**
     * 获取用户动态令牌验证状态，（是否开启）
     */
    public function getUserDynamicCodeAuthStatus($params)
    {
        $userAccount = $params['user_account'];
        $user = app($this->authRepository)->getUserByAccountOrMobile($userAccount);
        if(empty($user)){
            return ['code' => ['0x003024', 'auth']];
        }

        if (get_system_param('dynamic_password_is_useed', 0) == 1) {
            $allowMobile = get_system_param('allow_mobile_login', 0);
            $userService = app($this->userService);
            if ($allowMobile == 1) {
                $userInfo = app($this->authRepository)->getUserByAccountOrMobile(urldecode($userAccount));
                if ($userSecext = $userService->getUserDynamicCodeInfoByUserId($userInfo->user_id)) {
                    if ($userSecext->is_dynamic_code == 1) {
                        return true;
                    }
                }
            } else {
                if ($userService->getUserDynamicCodeAuthStatus($userAccount)) {
                    return true;
                }
            }
        }

        return ['code' => ['0x003024', 'auth']];
    }
    /**
     * 动态令牌同步
     */
    public function dynamicCodeSync($data)
    {
        $dynamicAddr  = get_system_param('dynami_addr');
        $tokenKey     = isset($data["token_key"]) ? trim($data["token_key"]) : '';
        $tokenCodeOne = isset($data["token_code_one"]) ? trim($data["token_code_one"]) : '';
        $tokenCodeTwo = isset($data["token_code_two"]) ? trim($data["token_code_two"]) : '';
        if ($tokenKey == '') {
            return ['code' => ['0x003035', 'auth']];
        }
        if ($tokenCodeOne == '') {
            return ['code' => ['0x003036', 'auth']];
        }
        if ($tokenCodeTwo == '') {
            return ['code' => ['0x003037', 'auth']];
        }
        $result = @file_get_contents($dynamicAddr . "/tokenDL/index.jsp?TYPE=syncKey&TOKENKEY=" . $tokenKey . "&TOKENCODE1=" . $tokenCodeOne . "&TOKENCODE2=" . $tokenCodeTwo);

        if (strpos($result, "0")) {
            return true;
        }

        return ['code' => ['0x003034', 'auth']];
    }

    /**
     * 检测性能安全设置中是否开启了动态密码验证
     */
    public function getDynamicCodeSystemParamStatus()
    {
        if (get_system_param('dynamic_password_is_useed', 0) == 1) {
            return 1;
        } else {
            return 0;
        }
    }

    private function registerTryUsedVersion()
    {
        $firstLogin = $this->defaultValue('first_login', $this->systemParams, 0);

        if ($firstLogin == 0) {
            $demoPath = base_path('public/iWebOffice/demo.txt');

            if (file_exists($demoPath)) {
                unlink($demoPath);
            }

            $month = intval(date('m')) * 125 % 12;

            $month = $month == 0 ? 12 : ($month < 10 ? '0' . $month : $month);

            $year = date('Y');

            $day = date('d');

            file_put_contents($demoPath, $year . '-' . $month . '-' . $day);

            set_system_param('first_login', 1);

            // 删除登录页面生成的模块授权缓存
            ecache('Empower:EmpowerModuleInfo')->clear();
            // 首次登录后更新授权信息文件内容
            app($this->empowerService)->addOrUpdateEmpowerInfoFile();

            return true;
        }

        return false;
    }
    /**
     * 判断密码是否超出设置的过期时间
     *
     * @param object $user
     *
     * @return boolean
     */
    private function passwordExpireCheck($user)
    {
        $lastPassTime = $user->last_pass_time ?? ($user->userHasOneSystemInfo->last_pass_time ?? '');
        $isCheck = $this->defaultValue('security_password_overdue', $this->systemParams, 0);
        if ($lastPassTime != null && $lastPassTime != '0000-00-00 00:00:00') {
            $overDays = $this->defaultValue('security_password_effective_time', $this->systemParams, 0);

            if (($isCheck == 1) && ((time() - strtotime($lastPassTime)) > ($overDays * 86400))) {
                return false;
            }
        } else {
            if($isCheck == 1){
                return false;
            }
        }

        return true;
    }

    public function ipControl($user)
    {
        $userArray = [
            'user_id' => $user->user_id,
            'dept_id' => $user->dept_id,
            'role_id' => isset($user->roles) ? array_column($user->roles, 'role_id') : array_column($user['userHasManyRole']->toArray(), 'role_id'),
        ];

        return app('App\EofficeApp\IpRules\Services\IpRulesService')->accessControl($userArray, 0);
    }

    private function defaultValue($key, $data, $default)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }

    private function addLoginLog($userId, $terminal, $log_content = '')
    {
        if ($log_content == '') {
            $log_content = trans('auth.login');
        }
        $identifier  = "system.login.login";
        $logParams = $this->handleLogParams($userId, $log_content, '','','',$terminal);
        logCenter::info($identifier , $logParams);
        //return $this->addLog($data);
    }

//    private function addLog($data)
//    {
//        $data['log_time'] = $this->defaultValue('log_time', $data, date('Y-m-d H:i:s'));
//
//        $data['log_ip'] = $this->defaultValue('log_ip', $data, getClientIp());
//
//        return add_system_log($data);
//    }

    public function getCaptcha($temp)
    {
        $phrase = new PhraseBuilder;
        // 设置验证码长度
        $code    = $phrase->build(4);
        $builder = new CaptchaBuilder($code, $phrase);
        // 设置背景颜色
        $builder->setBackgroundColor(231, 250, 255);
        // 设置长宽字体
        $builder->build($width = 80, $height = 30, $font = null);
        // 忽略大小写并保存
        $phrase  = strtolower($builder->getPhrase());
        if (empty($temp)) {
            $temp = (string)strtotime(date('Y-m-d h:i:s'));
            Cache::put($temp, $phrase, 60);
        }else{
            ecache('Auth:CaptchaKey')->set($temp, $phrase);
        }
        $path = $this->captchaPath;
        if (!is_dir($path)) {
            mkdir($path, 0777);
        } else {
            // @chmod($path, 0777);
            // 替换为会报错的dir验证
            $dirPermission = verify_dir_permission($path);
            if(is_array($dirPermission) && isset($dirPermission['code'])) {
                return $dirPermission;
            }
        }
        $captchaImage =  $path .'/'. $temp . '.png';

        $builder->save($captchaImage);

        $base64Captcha = imageToBase64($captchaImage);

        unlink($captchaImage);

        return $base64Captcha;
    }

    public function checkUserFromAd($password, $user)
    {
        $userAccount = $user->user_accounts;
        $empower = app($this->empowerService)->checkModuleWhetherExpired(390);
        if($empower == 0){
            return $this->errors = ['code' => ['0x003056', 'auth']];
        }
        // 获取域信息
        $data = app('App\EofficeApp\System\Domain\Services\DomainService')->getDomainInfo();
        $data = $data->toArray();
        if (count($data) == 0) {
            return $this->errors = ["code" => ['0x003055', 'auth']];
        }
        $domainName = $data['domain_name'];
        // 服务器地址
        $ldapHost = $data['domain_host'];
        // 判断服务器地址格式
        $hostFilter = strpos($data['domain_host'], "ldap://");
        if ($hostFilter !== 0) {
            $data['domain_host'] = "ldap://" . $data['domain_host'];
        }
        // 同步域
        $dn = $data['domain_dn'];


        try{
            // 测试连接服务器
            $ldap = ldap_connect($ldapHost);

            if ($ldap == false) {
                return $this->errors = ['code' => ['0x072001', 'domain']];
            }
        }catch (\Exception $e){
            return $this->errors = ['code' => ['0x072001', 'domain']];
        }
        // 密码为空，不允许登录
        if($password == null){
            return $this->errors = ['code' => ['0x072002', 'domain']];
        }

        try{
            // 绑定用户登录AD域服务器
            ldap_bind($ldap, $userAccount . "@" . $domainName, trim($password));
        }catch (\Exception $e){
            return $this->errors = ['code' => ['0x072002', 'domain']];
        }

        // 检测用户是否存在
        $user = app($this->userRepository)->getUserByAccount($userAccount);

        if(empty($user)){
            return $this->errors = ['code' => ['0x003050', 'auth']];
        }

        return app($this->userService)->getLoginUserInfo($user->user_id);
    }

    // 获取登录验证方式
    public function getLoginAuthType()
    {
        $this->systemParams = $this->getSystemParam();
        return isset($this->systemParams['login_auth_type']) ? $this->systemParams['login_auth_type'] : 1;
    }
    /**
     * 获取CAS单点登录后的用户信息
     *
     * @param string user_id
     *
     * @return userinfo
     */
    public function casRegisterInfo($userId)
    {
        $this->systemParams = $this->getSystemParam();
        $user = app($this->userService)->getLoginUserInfo($userId);
        // 更多验证机制
        // $this->moreVerification($user, 'PC');
        return $this->generateInitInfo($user, 'zh-CN', 'cas');
    }

    /**
     * 登录页面通过cas认证方式获取用户信息
     *
     * @param string $userAccount, string $password, array $casParams
     *
     * @return userinfo
     */
    public function checkUserFromCas($userPassword, $user)
    {
        $userAccount = $user->user_accounts;
        if (!(app($this->empowerService)->checkModuleWhetherExpired(350))) {
            // CAS认证模块未授权
            return $this->errors = ['code' => ['0x003057', 'auth']];
        }
        $casParams = app('App\EofficeApp\System\Cas\Services\CasService')->getCasParams();
        $excludedUserAccounts = [];
        if (isset($casParams['excluded_user']) && !empty($casParams['excluded_user'])) {
            if (!empty($casParams['excluded_user'])) {
                $userAccountsArray = DB::table('user')->select(['user_accounts', 'user_id'])->whereIn('user_id', $casParams['excluded_user'])->get()->toArray();
                if (!empty($userAccountsArray)) {
                    foreach ($userAccountsArray as $key => $value) {
                        $excludedUserAccounts[$value->user_id] = $value->user_accounts;
                    }
                }
            }
        }
        $valid = $this->validAccount($userAccount);
        if (!empty($excludedUserAccounts) && in_array($userAccount, $excludedUserAccounts)) {
            $user = $this->checkAccount($userPassword, $valid);
        } else {
            if (isset($casParams['cas_st_api']) && isset($casParams['cas_user_api']) && isset($casParams['oa_url'])) {
                if (!empty($casParams['cas_st_api']) && !empty($casParams['cas_user_api']) && !empty($casParams['oa_url'])) {
                    $user = $this->checkUserFromCasApi($userAccount, $userPassword, $casParams);
                } else if (empty($casParams['cas_st_api']) && empty($casParams['cas_user_api']) && empty($casParams['oa_url'])) {
                    $user = $this->checkAccount($userPassword, $valid);
                } else {
                    // CAS认证API参数设置不全
                    return $this->errors = ['code' => ['0x003053', 'auth']];
                }
            } else {
                $user = $this->checkAccount($userPassword, $valid);
            }
        }
        return $user;
    }

    /**
     * 通过cas的接口认证用户信息
     *
     * @param string $userAccount, string $password, array $casParams
     *
     * @return userinfo
     */
    public function checkUserFromCasApi($userAccount, $password, $casParams)
    {
        $currentUser = '';
        $userInfo = [];
        $oaUrl    = $casParams['oa_url'];
        $casStApi = $casParams['cas_st_api'];
        $curlPost = array(
            'username' => $userAccount,
            'password' => $password
        );

        // 获取TGT
        $tgtData = $this->casCurlPost($casStApi, $curlPost);
        if (!empty($tgtData)) {
            $tgtUrl = '';
            if (is_string($tgtData) && substr($tgtData, 0, 4) != 'http') {
                $regex = '/action=\"(.*?)\"/';
                preg_match_all($regex, $tgtData, $matches);
                if (!empty($matches) && isset($matches[1][0])) {
                    $tgtUrl = $matches[1][0];
                }
            } else {
                $tgtUrl = $tgtData;
            }

            if (!empty($tgtUrl)) {
                // 获取ST
                $curlPost = array(
                    'service' => $oaUrl
                );
                $stData = $this->casCurlPost($tgtUrl, $curlPost);

                if (!empty($stData)) {
                    $casUserApi = $casParams['cas_user_api'];
                    $curlPost = array(
                        'ticket'  => $stData,
                        'service' => $oaUrl
                    );
                    // 获取登录用户信息
                    $casUserData = $this->casCurlPost($casUserApi, $curlPost);
                    if (!empty($casUserData)) {
                        $regex = '#<cas:user>(.*?)</cas:user>#';
                        preg_match_all($regex, $casUserData, $matches);
                        if (!empty($matches) && isset($matches[1][0])) {
                            $currentUser = $matches[1][0];
                        }
                    }
                }
            } else {
                // 获取TGT地址失败
                return $this->errors = ['code' => ['0x003058', 'auth']];
            }
        }

        if (!empty($currentUser)) {
            if (!empty($casParams) && isset($casParams['sync_basis_field']) && !empty($casParams['sync_basis_field'])) {
                $basicUserInfo = DB::table('user')->where($casParams['sync_basis_field'], $currentUser)->first();
                if (!empty($basicUserInfo)) {
                    // 获取用户信息
                    $userInfo = app($this->userService)->getLoginUserInfo($basicUserInfo->user_id);
                }
            } else {
                // CAS认证参数未设置或同步依据字段未设置，请重试或联系管理员
                return $this->errors = ['code' => ['0x003063', 'auth']];
            }
        } else {
            // 获取CAS登录用户信息失败
            return $this->errors = ['code' => ['0x003052', 'auth']];
        }
        if (empty($userInfo)) {
            // 获取OA用户信息失败
            return $this->errors = ['code' => ['0x003051', 'auth']];
        }

        return $userInfo;
    }

    public function casCurlPost($url, $param)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    // CAS认证登出
    public function casLoginOut($loginUserId = '')
    {
        $casParams = app('App\EofficeApp\System\Cas\Services\CasService')->getCasParams();

        $excludedUserAccounts = [];
        if (isset($casParams['excluded_user']) && !empty($casParams['excluded_user'])) {
            if (!empty($casParams['excluded_user'])) {
                $userAccountsArray = DB::table('user')->select(['user_id'])->whereIn('user_id', $casParams['excluded_user'])->get()->toArray();
                if (!empty($userAccountsArray)) {
                    foreach ($userAccountsArray as $key => $value) {
                        $excludedUserAccounts[] = $value->user_id;
                    }
                }
            }
        }
        if (!empty($excludedUserAccounts) && in_array($loginUserId, $excludedUserAccounts)) {
            // 如果是排除更新的用户
            return 1;
        }

        $casLoginOutUrl = isset($casParams['cas_login_out_url']) && !empty($casParams['cas_login_out_url']) ? '/'.trim($casParams['cas_login_out_url'], '/') : '/eoffice10/server/public/cas/loginout.php';

        $osType = strtolower(PHP_OS);
        switch ($osType) {
            case "darwin":
            case "linux":
                $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === false ? 'http' : 'https';
                break;
            default:
                $protocol = isset($_SERVER["REQUEST_SCHEME"]) ? $_SERVER["REQUEST_SCHEME"] : 'http';
                break;
        }
        $url = $protocol . "://" . OA_SERVICE_HOST . $casLoginOutUrl;
        return $url;
    }

    public function refreshToken() {
        if (!$token = $this->getTokenForRequest()) {
            return ['code' => ['0x003001', 'auth']];
        }

        if (!Cache::has($token)) {
            return ['code' => ['0x003003', 'auth']];
        }
        ecache('Auth:LastVisitTime')->set($token, time());
        return true;
    }
    public function setSystemDefaultTheme($data) {
        $default = $this->defaultValue('default_theme', $data, '');
        if (!empty($default)) {
            return set_system_param('default_theme', json_encode($default));
        }
        return true;
    }
    // 非系统账号防暴力破解提示
    private function wrongAccountErrorCode($account) {
        $systemParams = $this->getSystemParam();
        $isWrongLock = $systemParams['wrong_password_lock'] ?? 0;
        if ($isWrongLock == 0) {
            return ['code' => ['0x003004', 'auth']];
        } else {
            if($times = ecache('Auth:WrongPwdTimes')->get($account)){
                if ($times == 1) {
                    return $this->errors = $this->unlockTimeError($account);
                }
                $times--;
            } else {
                $times = 4;
            }
            ecache('Auth:WrongPwdTimes')->set($account, $times);
            return [
                'code' => ['0x003004', 'auth'],
                'dynamic' => [trans("auth.0x003004").','.trans("auth.can_try").' '.$times.' '.trans("auth.times")]
            ];
        }
    }
    // 返回自动解锁剩余时长的错误信息
    private function unlockTimeError($userId) {
        if (!$this->systemParams) {
            $this->systemParams = $this->getSystemParam();
        }
        $isAutoUnlock = $this->systemParams['auto_unlock_account'] ?? 0;
        if ($isAutoUnlock) {
            $ttl = ecache('Auth:WrongPwdTimes')->ttl($userId);
            $translateTtl = $this->translateSecond($ttl);
            return [
                'code' => ['0x003061', 'auth'],
                'dynamic' => [trans('auth.0x003074', ['ttl' => $translateTtl])]
            ];
        } else {
            return ['code' => ['0x003061', 'auth']];
        }
    }
    // 转换秒为天时分秒
    private function translateSecond($second) {
        if ($second >= 86400) {
            $day = floor($second/86400);
            $hour = floor(($second%86400)/3600);
            return $day . trans('auth.day') . ($hour > 0 ? $hour.trans('auth.hour') : '');
        } else {
            if ($second >= 3600) {
                $hour = floor($second/3600);
                $minute = floor(($second%3600)/60);
                return $hour . trans('auth.hour') . ($minute > 0 ? $minute.trans('auth.minute') : '');
            } else {
                if ($second >= 60) {
                    return floor($second/60) . trans('auth.minute') . $second%60 . trans('auth.second');
                } else {
                    return $second . trans('auth.second');
                }
            }
        }
    }

    public function handleLogParams($user , $content , $relation_id = '' ,$relation_table = '', $relation_title = '', $terminal = '')
    {
        if($terminal == 'mobile'){
            $platform = 2;
        }else if ($terminal == 'client'){
            $platform = 3;
        }else{
            $platform = 1;
        }
        $data = [
            'creator' => $user,
            'content' => $content,
            'relation_table' => $relation_table,
            'relation_id' => $relation_id,
            'relation_title' => $relation_title,
            'platform' => $platform
        ];
        return $data;
    }
}
