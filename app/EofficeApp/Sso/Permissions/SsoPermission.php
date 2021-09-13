<?php

namespace App\EofficeApp\Sso\Permissions;

use App\EofficeApp\Sso\Repositories\SsoLoginRepository;

class SsoPermission
{
    private $repository;

    public function __construct()
    {
        $this->repository = SsoLoginRepository::class;
    }

    public function editSsoLogin($own, $data, $urlData)
    {
        $id = $urlData['ssoLoginId'];
        $sso = app($this->repository)->entity->find($id);
        if (!$sso){
            return false;
        }
        if($sso->sso_login_user_id != $own['user_id']){
            return false;
        }
        return true;
    }
}
