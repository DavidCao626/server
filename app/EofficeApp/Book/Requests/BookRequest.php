<?php
namespace App\EofficeApp\Book\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;

/**
 * 图书管理模块请求验证
 *
 * @author  朱从玺
 *
 * @since  2015-10-30 创建
 */
class BookRequest extends Request
{
    public function rules($request)
    {
        $rules = array(
            'createBook' => array(
                'type_id' => 'required|integer|exists:book_type,id',
                'dept_id' => 'required|integer',
                'book_name' => 'required|string|max:80',
                'publish_date' => 'date',
                'number' => 'integer',
                'borrow_number' => 'integer',
                'borrow_range' => 'boolean'
            ),
            'modifyBookInfo' => 'createBook',
            'createBookType' => array(
                'type_name' => 'required|unique:book_type,type_name',
            ),
            'modifyBookType' => 'createBookType',
            'createBookManage' => array(
                'book_id' => 'required|integer|exists:book_info,id,deleted_at,NULL',
                'borrow_person' => 'required|exists:user,user_id',
                'borrow_number' => 'required|integer|min:1'
            ),
            'modifyBookManage' => 'createBookManage'
        );

        $function = explode("@", $request->route()[1]['uses'])[1];
        $rules = $this->verifyAddId($request, $rules, $function);

        return $this->getRouteValidateRule($rules, $function);
    }

    /**
     * [verifyAddId 如果是编辑图书类型,为其增加需要过滤的当前ID]
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
        if($function == 'modifyBookType') {
            $fileId = $request->route()[2]['bookTypeId'];

            if(!is_array($rules['modifyBookType'])) {
                $rules['modifyBookType'] = $rules[$rules['modifyBookType']];
            }

            foreach ($rules['modifyBookType'] as $field => $verify) {
                if(strstr($verify, 'unique')) {
                    $verifyArray = explode('|', $verify);

                    foreach ($verifyArray as $key => $value) {
                        if(strstr($value, 'unique')) {
                            $verifyArray[$key] = $value.','.$fileId;
                        }
                    }

                    $rules['modifyBookType'][$field] = implode('|',$verifyArray);
                }
            }
        }

        return $rules;
    }
}
