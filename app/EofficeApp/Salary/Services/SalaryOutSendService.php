<?php


namespace App\EofficeApp\Salary\Services;


use App\EofficeApp\Salary\Repositories\SalaryFieldRepository;
use App\EofficeApp\User\Repositories\UserRepository;

class SalaryOutSendService extends SalaryBaseService
{
    private $salaryFieldRepository;

    private $userRepository;

    private $salaryAdjustService;

    public function __construct(
        SalaryFieldRepository $salaryFieldRepository,
        UserRepository $userRepository,
        SalaryAdjustService $salaryAdjustService
    )
    {
        $this->salaryFieldRepository = $salaryFieldRepository;
        $this->userRepository = $userRepository;
        $this->salaryAdjustService = $salaryAdjustService;
    }

    /**
     * 薪资调整外发
     * @param $data
     * @return array|bool|mixed
     */
    public function salaryAdjustOutSend($data)
    {
        // 被调整人
        if (!(isset($data["user_id"]) || isset($data["current_user_id"]))) {
            return ['code' => ['0x038011', 'salary']];
        }

        // 薪资项
        if (!isset($data["field_name"])) {
            return ['code' => ['0x038012', 'salary']];
        }
        // 调整金额
        if (!isset($data["adjust_value"])) {
            return ['code' => ['0x038013', 'salary']];
        }
        // 调整方式
        if (!isset($data["adjust_type"])) {
            return ['code' => ['0x038014', 'salary']];
        }
        // 调整人
        if (!isset($data["creator"])) {
            return ['code' => ['0x038015', 'salary']];
        }

        if (!empty($data["field_name"])) {
            // 外发数据
            $extra  = $this->getExtraDataFromFlow($data);

            foreach ($data["field_name"] as $key => $value) {
                if($value == ''){
                    continue;
                }
                $fieldInfo = $this->salaryFieldRepository->getSalaryItems(['search' => ['field_id' => [$value]]]);
                if (!isset($fieldInfo[0]['field_code'])) {
                    return ['code' => ['0x038012', 'salary']];
                }
                $adjustData = ['field_code' => $fieldInfo[0]['field_code'], 'field_id' => $value];
                $adjustData['user_id'] = is_array($data["user_id"]) && isset($data['user_id'][$key]) ? $data['user_id'][$key] : (is_array($data["user_id"]) && isset($data['user_id'][0]) ? $data['user_id'][0] : $data['user_id']);
                $userIdArray = explode(',', $adjustData["user_id"]);
                if(count(array_filter($userIdArray)) > 1){
                    return ['code' => ['0x038017', 'salary']];
                }

                if(isset($data['adjust_type'][$key])){
                    $adjustData['adjust_type'] = $data['adjust_type'][$key] == 0 ? 1 : 2;
                }else{
                    $adjustData['adjust_type'] = $data['adjust_type'][0] == 0 ? 1 : 2;
                }
                $adjustData['adjust_date'] = date("Y-m-d");
                if (!isset($data['adjust_value'][$key]) || empty($data['adjust_value'][$key])) {
                    return ['code' => ['0x038013', 'salary']];
                }
                $adjustData['adjust_value'] = $data['adjust_value'][$key];

                $symbol = $adjustData['adjust_type'] == 2 ? '-' : '+';
                $adjustData['adjust_value'] = $symbol.$adjustData['adjust_value'];

                $adjustData['adjust_reason'] = isset($data['adjust_reason'][$key]) ? $data['adjust_reason'][$key] : '';
                $adjustData['adjust_from'] = 2;
                $adjustData['extra_fields'] = $extra;
                $creator = is_array($data["creator"]) && isset($data["creator"][$key]) ? $data["creator"][$key] : (is_array($data["creator"]) && isset($data['creator'][0]) ? $data['creator'][0] : $data['creator']);
                $own            = $this->userRepository->getUserDeptIdAndRoleIdByUserId($creator);
                $own['role_id'] = explode(',', $own['role_id']);
                $own['user_id'] = $creator;
                $result = $this->salaryAdjustService->addSalaryAdjust($adjustData, $own);
                if(is_array($result) && isset($result['code'])){
                    return $result;
                }
            }
        }

        return true;
    }

    /**
     * 获取外发额外字段
     * @param array $data
     * @param array $extraFields
     * @return false|string
     */
    private function getExtraDataFromFlow($data, $extraFields = ['run_id', 'flow_id', 'run_name'])
    {
        $extra = [];

        foreach ($extraFields as $field) {
            if(isset($data[$field])){
                $extra[$field] = $data[$field];
            }
        }

        if(!empty($extra)){
            return json_encode($extra);
        }else{
            return '';
        }
    }
}
