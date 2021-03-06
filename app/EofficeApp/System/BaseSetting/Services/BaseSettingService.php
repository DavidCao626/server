<?php
namespace App\EofficeApp\System\BaseSetting\Services;

use App\EofficeApp\Base\BaseService;

class BaseSettingService extends BaseService
{
    private $userMenuRepository;
    private $attachmentService;
    private $userMenuService;
	private $accessImagePath;
    public function __construct()
    {
        parent::__construct();
        $this->accessImagePath = access_path('images/');
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->userMenuRepository = 'App\EofficeApp\Menu\Repositories\UserMenuRepository';
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
    }
    public function getCommonModule() 
    {
        $navbarType = get_system_param('navbar_type', 'click');
        $navigateMenus = [];
        if ($navbarType == "custom") {
            $navigateMenus = $this->getSystemParamArray('navigate_menus');
        }
        return [
            'navbar_type' => $navbarType,
            'navigate_menus' => $navigateMenus
        ];
    }
    public function setCommonModule($data) 
    {
        $navbarType = $data['navbar_type'] ?? 'click';
        set_system_param('navbar_type', $navbarType);
        if ($navbarType == "custom") {
            $navigateMenus = $data['navigate_menus'] ?? [];
            set_system_param('navigate_menus', json_encode($navigateMenus));
        }
        return true;
    }
    public function getUserCommonMenu($loginUserId) 
    {
        $menus = app($this->userMenuService)->getUserMenus($loginUserId);
        return  app($this->userMenuRepository)->getUserCommonMenu($menus['menu']);
    }
    public function getCommonMenu() 
    {
        return [
            'common_menu' => $this->getSystemParamArray('eoffice_common_menu'),
            'user_set_model' => get_system_param('user_set_model', 0)
        ];
    }
    public function setCommonMenu($data) 
    {
        $commonMenu = $data['common_menu'] ?? [];
        $userSetModel = $data['user_set_model'] ?? 0;
        $unify = $data['unify'] ?? 0;
        set_system_param('eoffice_common_menu', json_encode($commonMenu));
        set_system_param('user_set_model', $userSetModel);
        if ($unify) {
//            if (empty($commonMenu)) {
//                return ['code' => ['0x015034', 'system']];
//            }
            app($this->userMenuRepository)->unityCommonMenu($commonMenu);
        }
        return true;
    }
    public function unityCommonMenu()
    {
        $commonMenu = $this->getSystemParamArray('eoffice_common_menu');
        if(empty($commonMenu)) {
            return ['code' => ['0x015034', 'system']];
        }
        return  app($this->userMenuRepository)->unityCommonMenu($commonMenu);
    }
    /**
     * ?????????????????????????????????
     * @param type $key
     * @return type
     */
    private function getSystemParamArray($key) 
    {
        $value  = get_system_param($key, '');
        return $value ? json_decode($value) : [];
    }
    public function getDefaultAvatarInfo()
    {
        return [
            'default_avatar_type' => get_system_param('default_avatar_type', 1),
            'default_avatar' => get_system_param('default_avatar', 'default_avatar.png')
        ];
    }
    public function getUserType()
    {
        return [
            'default_user_type' => get_system_param('default_user_type', 1)
        ];
    }
    public function setDefaultAvatarType($type)
    {
        return set_system_param('default_avatar_type', $type);
    }
    public function setUserType($type)
    {
        return set_system_param('default_user_type', $type);
    }
    public function setDefaultAvatar($attachmentId) 
    {
        //base_path('public/avatar/');
        if ($attachmentId == 'eoffice') {
            set_system_param('default_avatar', 'default_avatar.png');

            return 'default_avatar.png';
        }
        $attachment = app($this->attachmentService)->getOneAttachmentById($attachmentId);
        if (!empty($attachment)) {
            $suffix = $attachment['attachment_type'];

            $source = $attachment['temp_src_file'];
            if (file_exists($source)) {
                if ($dir = $this->makeDir('avatar/default/')) {
                    $sourceDefaultAvatar = 'source_default_avatar.' . $suffix;
                    
                    copy($source, $dir . $sourceDefaultAvatar);
                    $defaultAvatar =  'default_avatar.' . $suffix;
                    $this->scaleImage($dir . $sourceDefaultAvatar, 80, 80, $dir . $defaultAvatar);
                    
                    set_system_param('default_avatar', $defaultAvatar);

                    return $defaultAvatar;
                }
            }
        }

        return ['code' => ['0x000003', 'common']];
    }
    //?????????????????????
    public static function scaleImage($pic, $nw = 60, $nh = 60, $newpicname)
    {
        //?????????????????????????????????????????????????????????????????????
        ini_set('memory_limit', '-1');
        // 20210312 ????????????dpi?????????????????? notice ?????????????????? DT202103110053
        $info = @getimageSize($pic); //???????????????????????????

        $w = $info[0]; //????????????
        $h = $info[1]; //????????????
        //??????????????????????????????????????????????????????
        switch ($info[2]) {
            case 1: //gif
                $im = imagecreatefromgif($pic);
                break;
            case 2: //jpg
                $im = imagecreatefromjpeg($pic);
                break;
            case 3: //png
                $im = imagecreatefrompng($pic);
                break;
            default:
                die("?????????????????????");
        }
        imagesavealpha($im,true);//???????????????
        //???????????????????????????(????????????)
        $des = imagecreatetruecolor($nw, $nh);
        imagealphablending($des,false);//???????????????,????????????????????????,?????????$img??????????????????,???????????????;  
        imagesavealpha($des,true);//???????????????,?????????????????????$thumb??????????????????;  
        //??????????????????
        imagecopyresampled($des, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);

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
        }
        //??????????????????
        imagedestroy($im);
        imagedestroy($des);
        //????????????
        return $newpicname;
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
}
