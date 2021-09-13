<?php

namespace App\EofficeApp\System\Signature\Permissions;


use App\EofficeApp\System\Signature\Repositories\SignatureRepository;

class SignaturePermission
{
    private $repository;
    public $rules = [
        'getSignature'    => 'mustBeCurrentUserByUrl',
        'editSignature'   => 'mustBeCurrentUserByUrl',
        'deleteSignature' => 'mustBeCurrentUserByUrl',
    ];
    const MUST_BE_CURRENT_USER = ['code' => ['0x015031', 'system']];
    const NOT_EXIST = ['code' => ['0x015032', 'system']];

    public function __construct()
    {
        $this->repository = SignatureRepository::class;
    }

    /**
     * 20200225-dingpeng-去掉对此函数内判断[MUST_BE_CURRENT_USER]的调用，现在只要有菜单283的权限，就可以管理所有图片章
     * @param  [type] $own     [description]
     * @param  [type] $data    [description]
     * @param  [type] $urlData [description]
     * @return [type]          [description]
     */
    public function mustBeCurrentUserByUrl($own, $data, $urlData)
    {
        $personalSetFlag = isset($data['personal_set_flag']) ? $data['personal_set_flag'] : '';
        $id = $urlData['signatureId'];
        $signature = app($this->repository)->entity->find($id);
        if (!$signature) {
            return self::NOT_EXIST;
        }
        if ($personalSetFlag == "1" && $signature->signature_onwer != $own['user_id']) {
            return self::MUST_BE_CURRENT_USER;
        }
        return true;
    }

    // public function mustBeCurrentUserByUrlAndData($own, $data, $urlData)
    // {
    //     if ($own['user_id'] == 'admin') {
    //         return true;
    //     }
    //     $id = $urlData['id'];
    //     $signature = app($this->repository)->entity->find($id);
    //     if (!$signature) {
    //         return self::NOT_EXIST;
    //     }
    //     if ($signature->signature_onwer != $own['user_id']) {
    //         return false;
    //     }
    //     $user = $data['signature_onwer'] ?? 0;
    //     if ($user != $own['user_id']) {
    //         return self::MUST_BE_CURRENT_USER;
    //     }
    //     return true;
    // }
}
