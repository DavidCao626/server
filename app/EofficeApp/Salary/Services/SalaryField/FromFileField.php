<?php


namespace App\EofficeApp\Salary\Services\SalaryField;


use App\EofficeApp\Api\Services\ApiService;
use Illuminate\Support\Facades\DB;
use Cache;

class FromFileField extends SalaryField
{
    private $filePath;

    private $dependence;

    /**
     * [getValue description]
     * @param  [type] $userInfo [传入的$userInfo是个数组：user_id 是代码运行的原始id，是传入的；transform_user_id是转换后的用户id，用于取绩效、考勤]
     * @param  [type] $reportId [description]
     * @return [type]           [description]
     */
    public function getValue($userInfo, $reportId)
    {
        // 此类中，使用传入的原始user_id
        $userId = $userInfo['user_id'] ?? '';
        if (Cache::has('salary_report_info_'.$reportId)) {
            $report = Cache::get('salary_report_info_'.$reportId);
        } else {
            $report = DB::table('salary_report')->where('report_id', $reportId)->first();
            // 把 $report 缓存 1 分钟
            Cache::put('salary_report_info_'.$reportId, $report, 1*60);
        }
        if(!$report) return 0;
        $params = [
            'url' => $this->filePath,
            'month' => $report->month,
            'year' => $report->year,
            'user_id' => $userId
        ];
        $paramsCache = $params;
        if(!empty($this->dependence)){
            $dependenceData = [];
            foreach($this->fieldList as $field){
                if(in_array($field['field_id'], $this->dependence)){
                    $dependenceData[$field['field_code']] = [
                        'field_id' => $field['field_id'],
                        'value' => $field['result'],
                        'name' => $field['field_name']
                    ];
                }
            }
            $params['dependence'] = $dependenceData;
            $paramsCache['dependence'] = json_encode($dependenceData);
        }

        if (Cache::has('salary_url_data_info_'.md5(implode('*',$paramsCache)))) {
            $response = Cache::get('salary_url_data_info_'.md5(implode('*',$paramsCache)));
        } else {
            $response = app(ApiService::class)->getUrlData($params);
            // 把 $response 缓存 1 分钟
            Cache::put('salary_url_data_info_'.md5(implode('*',$paramsCache)), $response, 1*60);
        }

        return $response['content'] ?? 0;
    }

    public function getDependenceIds()
    {
        return implode(',', $this->dependence);
    }

    protected function parseConfig()
    {
        $extra = json_decode($this->config['field_source']);
        $this->filePath = $extra->file_path;
        $this->dependence = $extra->dependence;
    }


}
