<?php
namespace App\EofficeApp\Auth\Permissions;

class AuthPermission
{
    public $rules = [
        'setLoginTheme' => 'loginSetVaildate',
        'setLoginTemplate' => 'loginSetVaildate',
        'setWelcomeWord' => 'loginSetVaildate',
        'setLoginBtnColor' => 'loginSetVaildate',
    ];
    public function __construct()
    {
    }
    public function loginSetVaildate($own, $data, $urlData)
    {
        return $own['user_id'] == 'admin';
    }
}
