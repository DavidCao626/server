<?php
namespace App\EofficeApp\OfficeSupplies\Requests;

use App\EofficeApp\Base\Request;
use Illuminate\Contracts\Validation\Validator;

/**
 * 办公用品请求验证
 *
 * @author  朱从玺
 *
 * @since  2015-11-03 创建
 */
class OfficeSuppliesRequest extends Request
{
    public function rules($request)
    {
        $typeId = isset($request->route()[2]['typeId']) ? $request->route()[2]['typeId'] : '';
        $officeSuppliesId = isset($request->route()[2]['officeSuppliesId']) ? $request->route()[2]['officeSuppliesId'] : '';

        $rules = array(
            'createOfficeSuppliesType' => array(
                'type_no' => 'required|max:50|unique:office_supplies_type,type_no',
                'type_name' => 'required|max:200|unique:office_supplies_type,type_name',
                'type_sort' => 'numeric',
            ),
            'modifyOfficeSuppliesType' => array(
                'type_no' => 'required|max:50|unique:office_supplies_type,type_no,'.$typeId,
                'type_name' => 'required|max:200|unique:office_supplies_type,type_name,'.$typeId,
                'type_sort' => 'numeric',
            ),
            'createOfficeSupplies' => array(
                'type_id' => 'required|integer|exists:office_supplies_type,id',
                'office_supplies_no' => 'string|max:50|unique:office_supplies,office_supplies_no,NULL,type_id,deleted_at,NULL',
                'office_supplies_name' => 'required|string|max:200',
                'stock_remind' => 'boolean',
                'usage' => 'boolean',
                'remind_max' => 'integer',
                'remind_min' => 'integer'
            ),
            'modifyOfficeSupplies' => array(
                'type_id' => 'required|integer|exists:office_supplies_type,id,deleted_at,NULL',
                'office_supplies_no' => 'string|max:50|unique:office_supplies,office_supplies_no,'.$officeSuppliesId.',id,deleted_at,NULL',
                'office_supplies_name' => 'required|string|max:200',
                'stock_remind' => 'boolean',
                'usage' => 'boolean',
                'remind_max' => 'integer',
                'remind_min' => 'integer'
            ),
            // 'createStorageRecord' => array(
            //     'storage_bill' => 'required|max:50|unique:office_supplies_storage,storage_bill,NULL,id,deleted_at,NULL',
            //     'storage_date' => 'date',
            //     'office_supplies_id' => 'required|integer|exists:office_supplies,id,deleted_at,NULL',
            //     'price' => 'required|numeric',
            //     'storage_amount' => 'required|numeric',
            //     'arithmetic' => 'required|boolean',
            //     'operator' => 'required|exists:user,user_id,deleted_at,NULL'
            // ),
//            'createApplyRecord' => array(
//                'apply_bill' => 'required|max:20|unique:office_supplies_apply,apply_bill,NULL,id,deleted_at,NULL',
//                'office_supplies_id' => 'required|integer|exists:office_supplies,id,deleted_at,NULL',
//                'apply_number' => 'required|numeric',
//                'apply_type' => 'required|integer',
//                'receive_date' => 'required|date',
//                'return_date' => 'date',
//                'receive_way' => 'required|integer',
////                'apply_user' => 'required|exists:user,user_id,deleted_at,NULL'
//            ),
            'modifyApplyRecord' => array(
                'apply_status' => 'integer',
                'return_status' => 'boolean',
            ),
            'getCreateNo' => array(
                'no_type' => 'required',
                'office_supplies_id' => 'integer'
            ),
        );

        //给编辑数组添加不能重复忽略ID
        // $this->rules['modifyOfficeSuppliesType']['type_no'] = $this->rules['modifyOfficeSuppliesType']['type_no'].$this->route('type_id');
        // $this->rules['modifyOfficeSuppliesType']['type_name'] = $this->rules['modifyOfficeSuppliesType']['type_name'].$this->route('type_id');
        // $this->rules['modifyOfficeSupplies']['office_supplies_no'] = $this->rules['modifyOfficeSupplies']['office_supplies_no'].$this->route('office_supplies_id');

        $function = explode("@", $request->route()[1]['uses'])[1];

        return $this->getRouteValidateRule($rules, $function);
    }
}
