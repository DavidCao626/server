<?php

namespace App\EofficeApp\System\BaseSetting\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\BaseSetting\Services\BaseSettingService;

class BaseSettingController extends Controller
{

    public function __construct(
            Request $request,
            BaseSettingService $baseSettingService
    ) {
            parent::__construct();
            $this->request = $request;
            $this->baseSettingService = $baseSettingService;
    }

    public function setDefaultAvatar() 
    {
        return $this->returnResult($this->baseSettingService->setDefaultAvatar($this->request->input('attachment_id')));
    }
    public function setUserType() 
    {
        return $this->returnResult($this->baseSettingService->setUserType($this->request->input('default_user_type')));
    }
    public function setDefaultAvatarType()
    {
        return $this->returnResult($this->baseSettingService->setDefaultAvatarType($this->request->input('default_avatar_type')));
    }
    public function getDefaultAvatarInfo()
    {
        return $this->returnResult($this->baseSettingService->getDefaultAvatarInfo());
    }
    public function getUserType()
    {
        return $this->returnResult($this->baseSettingService->getUserType());
    }
    public function getCommonMenu() 
    {
        return $this->returnResult($this->baseSettingService->getCommonMenu());
    }
    public function setCommonMenu() 
    {
        return $this->returnResult($this->baseSettingService->setCommonMenu($this->request->all()));
    }
    public function unityCommonMenu()
    {  
        return $this->returnResult($this->baseSettingService->unityCommonMenu());
    }
    public function getCommonModule() 
    {
        return $this->returnResult($this->baseSettingService->getCommonModule());
    }
    public function getUserCommonMenu() 
    {
        return $this->returnResult($this->baseSettingService->getUserCommonMenu($this->own['user_id']));
    }
    public function setCommonModule() 
    {
        return $this->returnResult($this->baseSettingService->setCommonModule($this->request->all()));
    }
}