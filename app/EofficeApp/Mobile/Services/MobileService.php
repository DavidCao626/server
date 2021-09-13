<?php
namespace App\EofficeApp\Mobile\Services;

use App\EofficeApp\System\Params\Entities\SystemParamsEntity;
use App\Utils\AppPush\AppPush;
use DB;
use Cache;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Mobile\Repositories\MobileNavbarRepository;
use Lang;
use App\Caches\CacheCenter;
/**
 * 手机端对接oa服务。
 * 
 * @author 李志军
 * 
 * @since 2017-4-25 
 */
class MobileService extends BaseService
{  
    private $attachmentService;
    private $langService;
    private $empowerService;
    private $appLogoPath = '';
    private $serverPath = '/eoffice10/server/';
    private $userMenuService;
    private $mobileNavbarRepository;
    private $accessPath;
    private $accessFolder;
    private $mobileFontSizeRepository;
    public function __construct(MobileNavbarRepository $mobileNavbarRepository) 
    {
        $this->accessPath = access_path();
        $this->accessFolder = envOverload('ACCESS_PATH', 'access');
        $this->appLogoPath = '/public/' . $this->accessFolder . '/images/mobile/logo/';
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
        $this->empowerService = 'App\EofficeApp\Empower\Services\EmpowerService';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->mobileNavbarRepository = $mobileNavbarRepository;
        $this->mobileFontSizeRepository = 'App\EofficeApp\Mobile\Repositories\MobileFontSizeRepository';
    }
    /**
     * 获取app初始信息
     * 
     * @return array
     */
    public function initInfo() 
    {
        $langModuleEffect = $this->isLangModuleEffect();//判断多语言模块是否生效

        $langPackages = $langModuleEffect ? $this->getLangPackages() : ['zh-CN' => '中文'];//获取所有生效的语言包
        // wps是否开启
        $isWpsOpen = get_system_param(SystemParamsEntity::ONLINE_READ_TYPE) == SystemParamsEntity::ONLINE_READ_TYPE_WPS ? true : false;
        $isWpsCloud = get_system_param(SystemParamsEntity::ONLINE_READ_PARSE_TYPE) == 1 ? true : false;
        return [
            'logo' => $this->getAppLoginLogo(),//获取app登录界面logo
            'mobile_code_check' => get_system_param('mobile_code_check', 0),
            'lang_packages' => $langPackages,
            'lang_module_effect' => $langModuleEffect,
            'page_footer' => get_system_param('app_login_page_footer', 'Copyright © 2001-2019 Weaver Sofeware All Rights Reserved'),
            'open_mobile_sign' => get_system_param('open_mobile_sign', 0),
            'open_wps' =>$isWpsOpen && $isWpsCloud,
            'mobile_sign_mfrs' => get_system_param('mobile_sign_mfrs', 'yongzhong'),
            'langs' => $this->getAppLangs($langPackages),//获取所有生效的多语言数组
            'access_path' => $this->accessFolder,
            'ios_app_scheme' => ['itms-apps', 'iosamap','baidumap'],
            'encrypt' => 1
        ];
    }
    public function saveMobileSign($data)
    {
        $openMobileSign = $data['open_mobile_sign'] ?? 0;
        $mobileSignMfrs = $data['mobile_sign_mfrs'] ?? 'yongzhong';
        set_system_param('open_mobile_sign', $openMobileSign);
        set_system_param('mobile_sign_mfrs', $mobileSignMfrs);
        return true;
    }
    public function getMobileSign()
    {
        return [
            'open_mobile_sign' => get_system_param('open_mobile_sign', 0),
            'mobile_sign_mfrs' => get_system_param('mobile_sign_mfrs', 'yongzhong')
        ];
    }
    public function setMobileCodeCheckFlag($data)
    {
        $mobileCodeCheck = $data['mobile_code_check'] ?? 0;
        $mobileCodeCount = $data['mobile_code_count'] ?? 1;
        $browserLoginControl = $data['browser_login_control'] ?? 0;
        $userId = $data['user_id'] ?? [];
        $deptId = $data['dept_id'] ?? [];
        $roleId = $data['role_id'] ?? [];
        $bindData = [
            'id' => 1,
            'all_user' => $data['all_user'] ?? 0,
            'user_id' => implode(',', $userId),
            'dept_id' => implode(',', $deptId),
            'role_id' => implode(',', $roleId),
        ];
        set_system_param('mobile_code_check', $mobileCodeCheck);
        set_system_param('mobile_code_count', $mobileCodeCount);
        set_system_param('browser_login_control', $browserLoginControl);
        if(DB::table('user_bind_mobile_check')->where('id',1)->first()){
            DB::table('user_bind_mobile_check')->where('id',1)->update($bindData);
        } else {
            DB::table('user_bind_mobile_check')->insert($bindData);
        }
        Cache::forever('user_bind_mobile_check',$data);
        return true;
    }
    public function getMobileCodeCheckFlag()
    {
        $mobileCodeCheck = get_system_param('mobile_code_check', 0);
        $mobileCodeCount = get_system_param('mobile_code_count', 1);
        $browserLoginControl = get_system_param('browser_login_control', 0);
        $bindData = DB::table('user_bind_mobile_check')->where('id',1)->first();
        if($bindData){
            $allUser = $bindData->all_user;
            $userId = $bindData->user_id ? explode(',', $bindData->user_id) : [];
            $deptId = $bindData->dept_id ? explode(',', $bindData->dept_id) : [];
            $roleId = $bindData->role_id ? explode(',', $bindData->role_id) : [];
        } else {
            $allUser = 0;
            $userId = [];
            $deptId = [];
            $roleId = [];
        }
        return [
            'mobile_code_check' => $mobileCodeCheck,
            'mobile_code_count' => $mobileCodeCount,
            'browser_login_control' => $browserLoginControl,
            'all_user' => $allUser,
            'user_id' => $userId,
            'dept_id' => $deptId,
            'role_id' => $roleId
        ];
    }
    public function getUserBindMobileList($param)
    {
        $query = DB::table('user_mobile_code');
        $param = $this->parseParams($param);
        if(isset($param['user_name']) && $param['user_name']){
            $userIds = $this->getUserIdByUserName($param['user_name']);
            $query = $query->whereIn('user_mobile_code.user_id', $userIds);
        }
        
        $total = $query->count();
        $orderBy    = (isset($param['order_by']) && !empty($param['order_by'])) ? $param['order_by'] : ['last_bind_time' => 'desc'];
        $limit      = (isset($param['limit']) && $param['limit']) ? $param['limit'] : 20;
        $page       = (isset($param['page']) && $param['page']) ? $param['page'] : 1;
        $listQuery = $query->select(['user_mobile_code.*','user.user_name'])->leftJoin('user','user.user_id','=','user_mobile_code.user_id');
        $listQuery = $this->orders($listQuery, $orderBy);
        $listQuery = $this->parsePage($query, $page, $limit);
        $list = $query->get();
        if($total > 0) {
            foreach ($list as $key => $item){
                $mobile_code = $item->mobile_code;
                $list[$key]->mobile_code = $item->mobile_code ? substr($item->mobile_code, 0, 8) . '******' : '';
                $list[$key]->mobile_code_key = $mobile_code ?? '';
            }
        }
        return ['total' => $total,'list' => $list];
    }
     private function orders($query, $orders)
    {
        if (!empty($orders)) {
            foreach ($orders as $field => $order) {
                $query = $query->orderBy($field, $order);
            }
        }

        return $query;
    }
    private function parsePage($query, $start, $limit, $isPage = true)
    {
        $start = (int) $start;

        if ($isPage && $start == 0) {
            return $query;
        }

        if ($isPage) {
            $start = ($start - 1) * $limit;
        }

        $query->offset($start)->limit($limit);

        return $query;
    }
    private function getUserIdByUserName($userName)
    {
        $condition = '%' . $userName . '%';
        
        $users = DB::table('user')->select(['user_id'])->where('user_name','like', $condition)->orWhere('user_name_py', 'like', $condition)->orWhere('user_name_zm', 'like', $condition)->get()->toArray();
        
        return array_column($users, 'user_id');
    }
    public function bindUserMobile($macAddress, $userId, $platform, $isUpdate = false)
    {
        if($isUpdate){
            $bind = DB::table('user_mobile_code')->where('user_id',$userId)->where('mobile_code',$macAddress)->first();
            if($bind){
                return DB::table('user_mobile_code')->where('user_id',$userId)->where('mobile_code',$macAddress)->update(['login_count' => $bind->login_count + 1, 'mobile_code' => $macAddress, 'platform' => $platform]);
            }else{
//                $bind2 = DB::table('user_mobile_code')->where('user_id',$userId)->first();
                $bind2 = DB::table('user_mobile_code')->where('user_id',$userId)->orderby('last_bind_time', 'asc')->get()->toArray();
                return DB::table('user_mobile_code')->where('user_id',$userId)->where('mobile_code',$bind2[0]->mobile_code)->update(['login_count' => $bind2[0]->login_count + 1, 'mobile_code' => $macAddress, 'platform' => $platform, 'last_bind_time' => date('Y-m-d H:i:s')]);
            }
        } else {
            return DB::table('user_mobile_code')->insert(['login_count' => 1, 'mobile_code' => $macAddress, 'user_id' => $userId, 'platform' => $platform, 'last_bind_time' => date('Y-m-d H:i:s')]);
        }
    }
    public function unbindUserMobile($userId, $mobileCode = '')
    {
        if(DB::table('user_mobile_code')->where('user_id',$userId)->where('mobile_code',$mobileCode)->first()){
            return DB::table('user_mobile_code')->where('user_id',$userId)->where('mobile_code',$mobileCode)->delete();
        }
        return true;
    }
    public function getMobileCodeCheck()
    {
        $checkAuthInfo = $this->getBindCheckInfo();
     
        return $checkAuthInfo['mobile_code_check'] ?? 0;
    }

    public function getBindMobileSet()
    {
        $checkAuthInfo = $this->getBindCheckInfo();

        return $checkAuthInfo ?? [];
    }

    private function parseRoles($roles)
    {
        if(count($roles) == 0) {
            return [];
        }
        $roleId = [];

        foreach ($roles as $role) {
            $roleId[]   = $role->role_id;
        }
        return $roleId;
    }
    private function getBindCheckInfo()
    {
        if(!Cache::has('user_bind_mobile_check')){
            return $this->getMobileCodeCheckFlag();
        }
        
        return Cache::get('user_bind_mobile_check');
    }
    private function mustBindCheck($user)
    {
        $checkAuthInfo = $this->getBindCheckInfo();
        
        $allUser = $checkAuthInfo['all_user'] ?? 0;
        if($allUser == 1){
            return true;
        }
        
        $checkUserIds = $checkAuthInfo['user_id'] ?? [];    
        if(in_array($user->user_id, $checkUserIds)){
            return true;
        }
        
        $checkDeptIds = $checkAuthInfo['dept_id'] ?? [];
        //$deptId = isset($user->userHasOneSystemInfo) ? $user->userHasOneSystemInfo->dept_id : '';
        $deptId = isset($user->dept_id) ? $user->dept_id : 0;
        if(in_array($deptId, $checkDeptIds)){
            return true;
        }
        
        $checkRoleIds = $checkAuthInfo['role_id'] ?? [];
       // $roleIds = isset($user->userHasManyRole) ? $user->userHasManyRole->pluck('role_id')->toArray() : [];
        $roleIds = isset($user->roles) ? array_column($user->roles,'role_id') : [];
        $intersetRoleIds = array_intersect($roleIds, $checkRoleIds);

        if(!empty($intersetRoleIds)){
            return true;
        }
        
        return false;
    }
    public function bindUserMobileCheck($macAddress, $user)
    {
        //传入的标志码为空
        if(!$macAddress){
            return 3;
        }
//        $bind = DB::table('user_mobile_code')->where('user_id',$user->user_id)->first();
        $bind = DB::table('user_mobile_code')->where('user_id',$user->user_id)->get()->toArray();
        $bindUserMobile = get_system_param('mobile_code_count', 1);//绑定手机数量
        if(!$this->mustBindCheck($user)){
            if(empty($bind)){
                return 0;//没有绑定信息，可以新增
            }else{
                if(count($bind) < $bindUserMobile){
                    if((isset($bind[0]) && $bind[0]->mobile_code)){
                        if((isset($bind[0]) && $bind[0]->mobile_code == $macAddress)){
                            return 1;//该用户标志码匹配
                        } else {
                            return 0;//该用户标志码不匹配,而且没有满，可以新增
                        }
                    } else {
                        return 0;//该用户标志码没有满，可以新增
                    }
                }else{
                    return 2;//绑定信息满了，可以更新
                }
            }
        }else{
            if(empty($bind)){
                return 0;//没有绑定信息，可以新增
            }else{
                if(count($bind) < $bindUserMobile){
                    if((isset($bind[0]) && $bind[0]->mobile_code)){
                        if((isset($bind[0]) && $bind[0]->mobile_code == $macAddress)){
                            return 1;//该用户标志码匹配
                        } else {
                            return 0;//该用户标志码不匹配,而且没有满，可以新增
                        }
                    } else {
                        return 0;//该用户标志码没有满，可以新增
                    }
                }else{
                    if((isset($bind[0]) && $bind[0]->mobile_code) || (isset($bind[1]) && $bind[1]->mobile_code)){
                        if((isset($bind[0]) && $bind[0]->mobile_code == $macAddress) || (isset($bind[1]) && $bind[1]->mobile_code == $macAddress)){
                            return 1;//该用户标志码匹配
                        } else {
                            return 4;//该用户标志码不匹配
                        }
                    } else {
                        return 0;//该用户标志码没有满，可以新增
                    }
                }
            }
        }
    }

    /**
     * 获取所有生效的语言包
     * 
     * @return array
     */
    private function getLangPackages()
    {
        $packages = app($this->langService)->getLangPackages(['page' => 0, 'search' => ['effect' => [1]]]);

        if ($packages['total'] > 0) {
            $list = $packages['list']->toArray();
            
            return array_combine(array_column($list, 'lang_code'), array_column($list, 'lang_name'));
        }
        
        return [];
    }
    /**
     * 判断多语言模块是否生效
     * 
     * @return int
     */
    private function isLangModuleEffect()
    {
        if (Cache::has('no_permission_menus')) {
            $modules = Cache::get('no_permission_menus');
        } else {
            $modules = app($this->userMenuService)->getPermissionModules();
        }
        if (!empty($modules) && is_array($modules) && in_array('104', $modules)) {
           return 0;
        }
        return 1;
    }
    /**
     * 获取app登录界面logo
     * 
     * @return string
     */
    private function getAppLoginLogo() 
    {
        $logo = get_system_param('app_login_logo', 'logo.png');
        if (file_exists(base_path($this->appLogoPath) . $logo)) {
            return  $this->serverPath . $this->appLogoPath . $logo;
        }
        
        return  $this->serverPath . '/public/access/images/mobile/logo/logo.png';
    }
    public function getAppLoginLogoAttachmentId()
    {
        return [
                'logo' => [get_system_param('app_login_logo_src', '')],
                'page_footer' => get_system_param('app_login_page_footer', '')
            ];
    }
    public function setAppLoginLogo($data)
    {
        set_system_param('app_login_page_footer', $data['page_footer'] ?? '');
        if (empty($data['attachment_id']) || $data['attachment_id'][0] == "") {
            set_system_param('app_login_logo', '');

            set_system_param('app_login_logo_src', '');

            return true;
        }
        $attachments = app($this->attachmentService)->getMoreAttachmentById($data['attachment_id']);

        if (!empty($attachments)) {
            $source = $attachments[0]['temp_src_file'];

            $suffix = $attachments[0]['attachment_type'];

            if (file_exists($source)) {
                if ($dir = $this->makeDir('images/mobile/logo/')) {
                    $logo = 'logo.' . $suffix;
                    $fullLogoPath = $dir . $logo;
                    
                    if (file_exists($fullLogoPath)) {
                        unlink($fullLogoPath);
                    }
                    
                    copy($source, $fullLogoPath);

                    set_system_param('app_login_logo', $logo);

                    set_system_param('app_login_logo_src', $data['attachment_id'][0]);

                    return $logo;
                }
            }
        }

        return false;
    }
    private function makeDir($path)
    {
        if (!$path || $path == '/' || $path == '\\') {
            return $this->accessPath;
        }

        $dir = $this->accessPath;

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
     * 获取所有生效的多语言数组
     * 
     * @param array $langPackages
     * 
     * @return array
     */
    private function getAppLangs(array $langPackages) 
    {
        $langs = [];
        
        foreach ($langPackages as $langCode => $langName) {
            $langs[$langCode] = trans('mobile', [], $langCode);
        }
        
        return $langs;
    }

    /**
     * 获取消息跳转链接
     * 
     * @param type $_data
     * 
     * @return type
     */
    public function getRedirectUrl($_data)
    {
        $data = json_decode($_data,true);
        
        if($data['redirect_url']){
            return ['redirect_url' => $data['redirect_url']];
        } 

        $remindInfo = DB::table('system_reminds')->select(['remind_state'])->where('remind_menu',$data['menu'])->where('remind_type',$data['type'])->first();

        return ['redirect_url' => $remindInfo->remind_state,'stateParam' => $data['stateParam']];
    }
    public function setQRCodeLoginInfo($data, $userId)
    {
        $tokenSecret = config('auth.token_secret'); //获取token加密key
        $qrCodeInfo = [
            'nonce_str' => md5($tokenSecret . $data['nonce_str']),
            'login_key' => $data['login_key'],
            'user_id' => $userId
        ];
        $loginKeySecret = config('auth.login_key_secret');
        $loginKey = md5($loginKeySecret . $data['login_key']);
        Cache::forever($loginKey, $qrCodeInfo);
        
        return $qrCodeInfo;
    }
    /**
     * 手机客户端绑定OA用户,用于消息推送
     * 
     * @param array $data
     * 
     * @return boolean
     */
    public function bindMobile($data)
    {
        return AppPush::bindMobile($data);
    }
    /**
     * 手机客户端解除绑定OA用户,用于消息推送
     * 
     * @param type $data
     * 
     * @return type
     */
    public function unbindMobile($data)
    {
        return AppPush::unbindMobile($data);
    }
    /**
     * 手写签批
     * 
     * @param array $data
     * @param string $userId
     * 
     * @return array
     */
    public function handWrite($data, $userId)
    {
        return ;
    }
    /**
     * 手机app附件上传
     * 
     * @param array $data
     * @param string $userId
     * 
     * @return array
     */
    public function upload($data, $userId)
    {
        $fileData = isset($data['file_data']) ? $data['file_data'] : '';
        
        $fileData = json_decode($fileData, true);

        if (empty($fileData)) {
            return ['code' => ['0x011018', 'upload']];
        }

        $basePath = getAttachmentDir();
        
        return $this->moveFile($fileData, $basePath, $userId);
    }
    /**
     * 处理上传的文件
     * 
     * @param array $files
     * @param string $basePath
     * @param string $userId
     * 
     * @return array
     */
    private function moveFile($files, $basePath, $userId) 
    {
        $result = [];
        
        $thumbWidth = config('eoffice.thumbWidth', 100);
        $thumbHight = config('eoffice.thumbHight', 40);
        $thumbPrefix = config('eoffice.thumbPrefix', "thumb_");
        $imageSuffixs = config('eoffice.uploadImages');
        
        foreach ($files as $file) {
            $name = $file[0];
            if (empty($name)) {
                return ['code' => ['0x011011', 'upload']];
            }
            
            $suffix = $file[1];
            if (empty($suffix)) {
                return ['code' => ['0x011011', 'upload']];
            }
            
            $content =  $file[2];
            
            if(!$content) {
                $result[] = [];
                continue;
            }

            $content = str_replace(' ', '+', $content);

            $attachmentId = app($this->attachmentService)->makeAttachmentId($userId);
            
            $attachmentPath = app($this->attachmentService)->createCustomDir($attachmentId);

            if (isset($attachmentPath['code'])) {
                return $attachmentPath;
            }

            $attachmentName = $this->handleAttachmentName($name, $suffix);

            $md5FileName = $this->getMd5FileName($attachmentName);

            $fullAttachmentName = $attachmentPath . $md5FileName;

            $attachment = base64_decode($content);
            
            $attachmentSize = strlen($attachment);
            
            $handle = @fopen($fullAttachmentName, "a");
            
            fwrite($handle, $attachment);
            
            fclose($handle);
            $thumbAttachmentName = in_array($suffix, $imageSuffixs) ? scaleImage($fullAttachmentName, $thumbWidth, $thumbHight, $thumbPrefix) : '';
            app($this->attachmentService)->generateImageCompress($suffix, $fullAttachmentName);
            $attachmentPaths = app($this->attachmentService)->parseAttachmentPath($fullAttachmentName);
            $attachmentInfo = [
                "attachment_id" => $attachmentId,
                "attachment_name" => $attachmentName,
                "affect_attachment_name" => $md5FileName,
                'new_full_file_name' => $fullAttachmentName,
                "thumb_attachment_name" => $thumbAttachmentName,
                "attachment_size" => $attachmentSize,
                "attachment_type" => $suffix,
                "attachment_create_user" => $userId,
                "attachment_base_path" => $attachmentPaths[0],
                "attachment_path" => $attachmentPaths[1],
                "attachment_mark" => $this->getAttachmentMark($suffix),
                "relation_table" => '',
                "rel_table_code" => ''
            ];
            app($this->attachmentService)->handleAttachmentDataTerminal($attachmentInfo); //组装数据 存入附件表
            $result[] = $attachmentId;
        }
        return $result;
    }

    /**
     * 获取附件标记
     *
     * @param type $fileType
     * @return int
     */
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

    /**
     * 处理重复后缀问题
     * @param $name
     * @param $suffix
     */
    private function handleAttachmentName($name, $suffix)
    {
        if (collect(explode('.', $name))->last() == $suffix) {
            return $name;
        } else {
            return $name . "." . $suffix;
        }
    }
    public function getAttachmentThumbs($data)
    {
        $attachmentIds = (isset($data['attachment_id']) && $data['attachment_id']) ? explode(',', rtrim($data['attachment_id'], ',')) : [];
        if(empty($attachmentIds)){
            return [];
        }
        $attachments = app($this->attachmentService)->getMoreAttachmentById($attachmentIds, false);
        $result = [];
        // 发票拍照识别需要压缩图 不是缩略图 增加type
        $type = isset($data['type']) ? 'thumb_original_' : '';
        if(count($attachments) > 0) {
            foreach ($attachments as $temp) {
                $tempResult = [];
                $tempResult['attachmentThumb'] = '';
                if (!empty($temp['thumb_attachment_name'])) {
                    if ($type) {
                        $path = $temp['attachment_base_path'] . $temp['attachment_relative_path'] . $type . $temp['affect_attachment_name'];
                    } else {
                        $path = $temp['attachment_base_path'] . $temp['attachment_relative_path'] . DIRECTORY_SEPARATOR . $temp['thumb_attachment_name'];
                    }

                    $tempResult['attachmentThumb'] = imageToBase64($path);
                }
                $tempResult['attachmentId'] = $temp['attachment_id'];
                $tempResult['attachmentName'] = $temp['attachment_name'];
                $tempResult['attachmentMark'] = $temp['attachment_mark'];
                $tempResult['attachmentSize'] = $temp['attachment_size'];
                $tempResult['attachmentType'] = $temp['attachment_type'];
                array_push($result, $tempResult);
            }
        }
        return $result;
    }
    public function getNavbarChildren($data, $parentId = 0, $own = []) 
    {   
        $all = $data['all'] ?? false;
        $list =  $this->mobileNavbarRepository->getNavbarChildren($parentId, $all);
        $encrypt = isset($own['user_id']) && !empty($own['user_id']) ? (ecache('Auth:EncryptOrganization')->get($own['user_id']) ?? 0) : 0;
        $newList = [];
        if(count($list) > 0) {
            foreach($list as $item) {
                if ($item->navbar_id == 4 && $encrypt== 1) {
                    continue;
                }
                $item->navbar_name = mulit_trans_dynamic('mobile_navbar.navbar_name.navbar_name_' . $item->navbar_id);
                $newList[] = $item;
            }
        }
        
        return $newList;
    }
    public function addNavbar($data) 
    {
        if(!isset($data['navbar_name']) || empty($data['navbar_name'])) {
            return ['code' => ['0x060002', 'mobile']];
        }
        if($data['navbar_category'] == 1){
            if(!isset($data['navbar_url1']) || empty($data['navbar_url1'])) {
                return ['code' => ['0x060003', 'mobile']];
            }
            $data['navbar_url'] = $data['navbar_url1'];
        }else{
            if(!isset($data['navbar_url2']) || empty($data['navbar_url2'])) {
                return ['code' => ['0x060003', 'mobile']];
            }
            $data['navbar_url'] = $data['navbar_url2'];
        }
        $data['params'] = isset($data['params']) && $data['params']  ? json_encode($data['params']) : json_encode([]);
        if($result = $this->mobileNavbarRepository->insertData($data)) {
            $langKey = 'navbar_name_' . $result->navbar_id;
            $this->mobileNavbarRepository->updateData(['navbar_name' => $langKey], ['navbar_id' => [$result->navbar_id]]);
            $this->addLang($data, $langKey);
        }
        return $result;
    }
    public function sortNavbar($data, $parentId = 0) 
    {
        foreach ($data as $key => $id) {
            $this->mobileNavbarRepository->updateData(['sort' => $key + 1], ['navbar_id' => [$id]]);
        }
        return true;
    }
    public function editNavbar($navbarId, $data) 
    {
        if(isset($data['is_simple']) && $data['is_simple']){
            return $this->mobileNavbarRepository->updateData($data, ['navbar_id' => [$navbarId]]);
        }
        if(!isset($data['navbar_name']) || empty($data['navbar_name'])) {
            return ['code' => ['0x060002', 'mobile']];
        }
        if($data['navbar_category'] == 1){
            if(!isset($data['navbar_url1']) || empty($data['navbar_url1'])) {
                return ['code' => ['0x060003', 'mobile']];
            }
            $data['navbar_url'] = $data['navbar_url1'];
        }else{
            if(!isset($data['navbar_url2']) || empty($data['navbar_url2'])) {
                return ['code' => ['0x060003', 'mobile']];
            }
            $data['navbar_url'] = $data['navbar_url2'];
        }
        $data['parent_id'] = $data['parent_id'] ?? 0;
        $data['params'] = isset($data['params']) && $data['params']  ? json_encode($data['params']) : json_encode([]);
        $this->addLang($data, 'navbar_name_' . $navbarId);
        $data['navbar_name'] = 'navbar_name_' . $navbarId;
        return $this->mobileNavbarRepository->updateData($data, ['navbar_id' => [$navbarId]]);
    }
    private function addLang($data, $langKey) {
        $langService = app($this->langService);
        if(isset($data['navbar_name_lang']) && !empty($data['navbar_name_lang'])){
            $currentLocale = Lang::getLocale();
            foreach ($data['navbar_name_lang'] as $locale => $langValue) {
                if($currentLocale == $locale) {
                    $langValue = $data['navbar_name'];
                }
                $langService->addDynamicLang(['table' => 'mobile_navbar', 'column' => 'navbar_name', 'lang_key' => $langKey, 'lang_value' => $langValue], $locale); 
            }
        } else {
            $langService->addDynamicLang(['table' => 'mobile_navbar', 'column' => 'navbar_name', 'lang_key' => $langKey, 'lang_value' => $data['navbar_name']]); 
        } 
    }
    public function deleteNavbar($navbarId) 
    {
        return $this->mobileNavbarRepository->deleteById([$navbarId]);
    }
    public function getNavbarDetail($navbarId) 
    {
        $detail =  $this->mobileNavbarRepository->getDetail($navbarId);
        $detail->navbar_name = trans_dynamic('mobile_navbar.navbar_name.navbar_name_' . $navbarId);
        $detail->params = $detail->params ? json_decode($detail->params) : [];
        $detail->navbar_name_lang = app($this->langService)->transEffectLangs('mobile_navbar.navbar_name.navbar_name_' . $navbarId, true);
        if($detail->navbar_category == 1){
            $detail->navbar_url1 = $detail->navbar_url;
            $detail->navbar_url2 = '';
        }else{
            $detail->navbar_url1 = '';
            $detail->navbar_url2 = $detail->navbar_url;
        }
        return $detail;
    }
    public function setFontSize($userId, $fontSize) 
    {
        $params = [
            'search' => ['user_id' => [$userId]]
        ];
        if(app($this->mobileFontSizeRepository)->getTotal($params)) {
            $result = app($this->mobileFontSizeRepository)->updateData(['font_size' => $fontSize], ['user_id' => [$userId]]);
        } else {
            $result = app($this->mobileFontSizeRepository)->insertData(['user_id' => $userId, 'font_size' => $fontSize]);
        }
        if($result) {
            CacheCenter::make('MobileFontSize', $userId)->setCache($fontSize);
        }
        return $result;
    }
    public function getFontSize($userId)
    {
        return CacheCenter::make('MobileFontSize', $userId)->getCache();
    }
    private function getMd5FileName($gbkFileName)
    {
        $name = substr($gbkFileName, 0, strrpos($gbkFileName, "."));

        return md5(time() . $name) . strrchr($gbkFileName, '.');
    }
    public function getUserType()
    {
        return [
            'default_user_type' => get_system_param('default_user_type', 1)
        ];
    }
    public function setUserType($type)
    {
        return set_system_param('default_user_type', $type);
        
    }
    public function getMobileMac($userId)
    {
        $info = DB::table('user_mobile_code')->where('user_id',$userId)->first();
        if($info) {

            return $info->mobile_code;
        }
        return null;
    }
     public function isAgreePrivacyProtocal($userId)
    {
        return CacheCenter::make('UserPrivacyProtocal', $userId)->getCache();
    }
    public function agreePrivacyProtocal($userId)
    {
        $currentTime = date('Y-m-d H:i:s');
        $data = [
            'agree_protocal' => 1,
            'ip' => getClientIp(),
            'updated_at' => $currentTime
        ];
        if(DB::table('user_privacy_protocal')->where('user_id', $userId)->count()) {
            $result = DB::table('user_privacy_protocal')->where('user_id', $userId)->update($data);
        } else {
            $data['user_id'] = $userId;
            $data['created_at'] = $currentTime;
            $result = DB::table('user_privacy_protocal')->insert($data);
        }
        if($result) {
            CacheCenter::make('UserPrivacyProtocal', $userId)->setCache(1);
            return true;
        }
        return false;
    }
    /**
     * 检查app版本
     * @param type $data
     * @return type
     */
    public function checkAppVersion($data) 
    {
        $appCheckUrl = 'http://v10.e-office.cn/mobile/check.php';
        $secret = 'f96e4c38007cb1a2bb33a5921bef6064';
        
        $platform = $this->defaultValue($data, 'platform');
        $appVersion = $this->defaultValue($data, 'app_version');
        if (!($platform && $appVersion )) {
            return ['update_version' => ''];
        }
        $systemVertion = version();
        $httpData = [
            'platform' => $platform,
            'app_version' => $appVersion,
            'nonce_str' => md5($platform . $appVersion . $systemVertion . $secret),
            'system_version' => $systemVertion
        ];
        $options = [
            'http' => [
                'method' => "GET",
                'timeout' => 2,
            ]
        ];
        $url = $appCheckUrl . '?' . http_build_query($httpData, '', '&');
        $result = @file_get_contents($url, false, stream_context_create($options));
        if ($result) {
            return json_decode($result, true);
        }
        return ['update_version' => ''];
    }
    public function defaultValue($data, $key, $default = '')
    {
        return (isset($data[$key]) && $data[$key]) ? $data[$key] : $default;
    }
}
