<?php
namespace App\EofficeApp\Role\Requests;

use App\EofficeApp\Base\Request;

class RoleRequest extends Request
{
    public $errorCode = '0x006002';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request) {
        $roleId = isset($request->route()[2]['roleId']) ? $request->route()[2]['roleId'] : 0;
        $rules = [
            'createRoles' => [
                'role_name' => 'required|max:200|unique:role',
                'role_no'   => 'required|integer',
            ],
            'editRoles' => [
                'role_name' => 'required|max:200|unique:role,role_name,'.$roleId.',role_id',
                'role_no'   => 'required|integer',
            ],
            'createRolePermission' => [
                'role_id' => 'required',
                'func_id' => 'required',
            ],
            'createUserRole' => [
                'user_id'   => 'required',
                'role_id'   => "required"
            ],
            'createRoleCommunicate' => [
                'role_from'         => 'required',
                'role_to'           => 'required',
                'communicate_type'  => 'required',
            ],
            'editRoleCommunicate' => 'createRoleCommunicate',
            'createUserSuperior' => [
                'user_id'         => 'required'
            ],
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
