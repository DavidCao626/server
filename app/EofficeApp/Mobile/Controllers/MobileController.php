<?php
namespace App\EofficeApp\Mobile\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Mobile\Services\MobileService;
use App\EofficeApp\Base\Controller;
use QrCode;
class MobileController extends Controller
{
    private $mobileService;
    
    public function __construct(MobileService $mobileService) 
    {
        parent::__construct();
        
        $this->mobileService = $mobileService;
    }
    public function saveMobileSign(Request $request)
    {
        return $this->returnResult($this->mobileService->saveMobileSign($request->all()));
    }
    public function getMobileSign()
    {
        return $this->returnResult($this->mobileService->getMobileSign());
    }
    public function setMobileCodeCheckFlag(Request $request)
    {
        return $this->returnResult($this->mobileService->setMobileCodeCheckFlag($request->all()));
    }
    public function getMobileCodeCheckFlag()
    {
        return $this->returnResult($this->mobileService->getMobileCodeCheckFlag());
    }
    public function bindUserMobile(Request $request)
    {
        return $this->returnResult($this->mobileService->bindUserMobile($request->input('mac_address'), $this->own['user_id']));
    }
    public function unbindUserMobile(Request $request)
    {
        return $this->returnResult($this->mobileService->unbindUserMobile($request->input('user_id'), $request->input('mobile_code')));
    }
    public function bindUserMobileCheck(Request $request)
    {
        return $this->returnResult($this->mobileService->bindUserMobileCheck($request->input('mac_address'), $this->own['user_id']));
    }
    public function initInfo()
    {
        return $this->returnResult($this->mobileService->initInfo());
    }
    public function setAppLoginLogo(Request $request)
    {
        return $this->returnResult($this->mobileService->setAppLoginLogo($request->all()));
    }
    public function getAppLoginLogoAttachmentId()
    {
        return $this->returnResult($this->mobileService->getAppLoginLogoAttachmentId());
    }
    public function setQRCodeLoginInfo(Request $request)
    {
        return $this->returnResult($this->mobileService->setQRCodeLoginInfo($request->all(),$this->own['user_id']));
    }
    public function bindMobile(Request $request)
    {
        return $this->returnResult($this->mobileService->bindMobile($request->all()));
    }
    public function unbindMobile(Request $request)
    {
        return $this->returnResult($this->mobileService->unbindMobile($request->all()));
    }
    public function upload(Request $request)
    {
        $result = $this->returnResult($this->mobileService->upload($request->all(), $this->own['user_id']));
        if (isset($result['status'])) {
            if ($result['status'] == 1){
                return $result;
            } else {
                if (isset($result['errors'][0]['message'])) {
                    $result['status'] = $result['errors'][0]['message'];
                }
            }
        }
        return $result;
    }
    
    public function getAttachmentThumbs(Request $request)
    {
        return $this->returnResult($this->mobileService->getAttachmentThumbs($request->all()));
    }
    
    public function handWrite(Request $request)
    {
        return $this->returnResult($this->mobileService->handWrite($request->all(), $this->own['user_id']));
    }
    public function getRedirectUrl(Request $request)
    {
        return $this->returnResult($this->mobileService->getRedirectUrl($request->input('app_msg')));
    }
    public function generalUrlQRCode()
    {
        if(!is_dir(base_path('public/mobile/qrcode'))) {
            mkdir(base_path('public/mobile/qrcode'), 777, true);
        }
        $filePath = base_path('public/mobile/qrcode/qrcode.png');
   
        if(!file_exists($filePath)){
            QrCode::format('png')->size(140)->margin(0)->generate('http://v10.e-office.cn/download/mobile/index.php#mp.weixin.qq.com',$filePath);
        }
        
        if(file_exists($filePath)) {
            return $this->returnResult(imageToBase64($filePath));
        }
        
        return false;
    }
    public function getUserBindMobileList(Request $request)
    {
        return $this->returnResult($this->mobileService->getUserBindMobileList($request->all()));
    }
    
    public function getNavbarChildren(Request $request, $parentId) 
    {
        return $this->returnResult($this->mobileService->getNavbarChildren($request->all(), $parentId, $this->own));
    }
    public function addNavbar(Request $request) 
    {
        return $this->returnResult($this->mobileService->addNavbar($request->all()));
    }
    public function sortNavbar(Request $request, $parentId) 
    {
        return $this->returnResult($this->mobileService->sortNavbar($request->all(), $parentId));
    }
    public function editNavbar(Request $request, $navbarId) 
    {
        return $this->returnResult($this->mobileService->editNavbar($navbarId, $request->all()));
    }
    public function deleteNavbar($navbarId) 
    {
        return $this->returnResult($this->mobileService->deleteNavbar($navbarId));
    }
    public function getNavbarDetail($navbarId) 
    {
        return $this->returnResult($this->mobileService->getNavbarDetail($navbarId));
    }
    public function setFontSize(Request $request, $userId)
    {
        return $this->returnResult($this->mobileService->setFontSize($userId, $request->input('font_size', 0)));
    }
    public function getFontSize($userId)
    {
        return $this->returnResult($this->mobileService->getFontSize($userId));
    }
    public function isAgreePrivacyProtocal() 
    {
        return $this->returnResult($this->mobileService->isAgreePrivacyProtocal($this->own['user_id']));
    }
    public function agreePrivacyProtocal()
    {
        return $this->returnResult($this->mobileService->agreePrivacyProtocal($this->own['user_id']));
    }
    public function getMobileMac()
    {
        return $this->returnResult($this->mobileService->getMobileMac($this->own['user_id']));
    }
    public function checkAppVersion(Request $request)
    {
        return $this->returnResult($this->mobileService->checkAppVersion($request->all()));
    }
    public function getUserType()
    {
        return $this->returnResult($this->mobileService->getUserType());
    }
    public function setUserType(Request $request) 
    {
        return $this->returnResult($this->mobileService->setUserType($request->input('default_user_type')));
    }
}
