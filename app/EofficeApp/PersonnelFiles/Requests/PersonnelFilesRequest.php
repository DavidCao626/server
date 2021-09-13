<?php

namespace App\EofficeApp\PersonnelFiles\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;
use App\EofficeApp\FormModeling\Services\FormModelingService;

/**
 * 人事档案请求验证
 *
 * @author  朱从玺
 *
 * @since  2015-10-28 创建
 */
class PersonnelFilesRequest extends Request
{
    public $errorCode = '0x024002';

    /**
     * [$formModelingService 自定义字段service]
     *
     * @var [object]
     */
    protected $formModelingService;

    /**
     * [$rules 验证规则数组]
     *
     * @var [array]
     */
    protected $rules;

    /**
     * [__construct 获取所有字段的验证规则并写入规则数组]
     *
     * @author 朱从玺
     *
     * @param  formModelingService $formModelingService [自定义字段service]
     *
     * @since  2015-10-28 创建
     */
    public function __construct(FormModelingService $formModelingService)
    {
        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
//        $verifyFields = $this->formModelingService->getAllVerifyFields('personnel_files');
//        if(!empty($verifyFields)) {
//            foreach ($verifyFields as $field) {
//                $this->rules['createPersonnelFile'][$field->field_code] = $field->verify;
//            }
//        }else{
            $this->rules['createPersonnelFile'] = [];
//        }

        $this->rules['modifyPersonnelFile'] = $this->rules['createPersonnelFile'];
    }

    /**
     * [rules 调用函数对应的验证规则]
     *
     * @method 朱从玺
     *
     * @param  [object] $request [请求对象]
     *
     * @since  2015-10-28 创建
     *
     * @return [array]           [验证规则数组]
     */
    public function rules($request)
    {
        $function = explode("@", $request->route()[1]['uses'])[1];
        $rules = $this->verifyAddId($request, $this->rules, $function);

        return $this->getRouteValidateRule($rules, $function);
    }

    /**
     * [verifyAddId 如果是编辑人事文档,在验证规则有unique的情况下,为其增加需要过滤的当前ID]
     *
     * @method 朱从玺
     *
     * @param  [object]      $request  [请求对象]
     * @param  [array]       $rules    [验证数组]
     * @param  [string]      $function [请求的函数名]
     *
     * @since  2015-10-28 创建
     *
     * @return [array]                 [整理后的验证数组]
     */
    public function verifyAddId($request, $rules, $function)
    {
        if($function == 'modifyPersonnelFile') {
            $fileId = $request->route()[2]['personnelFileId'];

            foreach ($rules['modifyPersonnelFile'] as $field => $verify) {
                if(strstr($verify, 'unique')) {
                    $verifyArray = explode('|', $verify);

                    foreach ($verifyArray as $key => $value) {
                        if(strstr($value, 'unique')) {
                            $verifyArray[$key] = $value.','.$fileId;
                        }
                    }

                    $rules['modifyPersonnelFile'][$field] = implode($verifyArray, '|');
                }
            }
        }

        return $rules;
    }
}
