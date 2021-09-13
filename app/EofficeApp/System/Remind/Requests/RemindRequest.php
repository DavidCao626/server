<?php
namespace App\EofficeApp\System\Remind\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;
use Exception;

/**
 * 系统提醒设置请求验证
 *
 * @author  朱从玺
 *
 * @since  2015-10-28 创建
 */
class RemindRequest extends Request
{
    public function rules($request)
    {
        $rules = array(
            'setMultipleFunctionRemind' => array(
                'select_function' => 'required|string',
            ),
            'sendMessage' => array(
                'sendMethod' => 'required',
                'remindMark' => 'required',
                'contentParam' => 'required',
                'fromUser' => 'required',
                'toUser' => 'toUser'
            )
        );

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }
}
