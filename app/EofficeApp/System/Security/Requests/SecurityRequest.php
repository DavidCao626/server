<?php
namespace App\EofficeApp\System\Security\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;

/**
 * 系统安全设置请求验证
 *
 * @author  朱从玺
 *
 * @since  2015-10-28 创建
 */
class SecurityRequest extends Request
{
    public function rules($request)
    {
        $rules = array(
            'modifyModuleUpload' => array(
                'upload_max_num' => 'required|integer',
                'upload_single_max_size' => 'required|integer',
                'suffix' => 'required|string',
                'suffix_status' => 'required|boolean',
            ),
            'modifySecurityOption' => array(
                'security_password_overdue' => 'boolean',
                'security_password_effective_time' => 'integer',
                'security_image_code' => 'boolean',
                'limit_window' => 'boolean',
                'dynamic_password_type' => 'integer',
                'dynamic_password_is_useed' => 'boolean',
                'usbkey_code_type' => 'integer',
                'sms_refresh_frequency' => 'integer',
                'form_refresh_frequency' => 'integer',
                'commerce_contract_period' => 'integer',
                'labour_contract_period' => 'integer',
                'show_day' => 'integer',
                'show_hour' => 'integer',
            ),
            'modifySystemTitleSetting' => array(
                'system_title' => 'required'
            ),
        );

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
