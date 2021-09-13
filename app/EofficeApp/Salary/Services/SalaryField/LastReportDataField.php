<?php


namespace App\EofficeApp\Salary\Services\SalaryField;


use Illuminate\Support\Facades\DB;
use Cache;

class LastReportDataField extends SalaryField
{
    private $dataFieldId;

    private $crossClear;

    /**
     * [getValue description]
     * @param  [type] $userInfo [传入的$userInfo是个数组：user_id 是代码运行的原始id，是传入的；transform_user_id是转换后的用户id，用于取绩效、考勤]
     * @param  [type] $reportId [description]
     * @return [type]           [description]
     */
    public function getValue($userInfo, $reportId)
    {
        // 薪酬表记录的是人事id了，此处，使用传入的user_id，才能正确的累计上次上报数据
        $userId = $userInfo['user_id'] ?? '';
        if (Cache::has('salary_last_report_data_'.$reportId.'_'.$userId)) {
            $lastReport = Cache::get('salary_last_report_data_'.$reportId.'_'.$userId);
            $lastReport = json_decode($lastReport);
        } else {
            $lastReport = DB::table('salary')
                        ->select(['salary_report.report_id', 'salary_report.year'])
                        ->leftJoin('salary_report','salary_report.report_id','=','salary.report_id')
                        ->where('salary.report_id', '<', $reportId)
                        ->where('salary.user_id', $userId)
                        ->orderBy('salary.report_id', 'desc')
                        ->first();
            // 把 $lastReport 缓存 1 分钟
            Cache::put('salary_last_report_data_'.$reportId.'_'.$userId, json_encode($lastReport), 1*60);
        }
        if (!$lastReport) {
            return 0;
        }
        // 如果设置跨年清0
        if($this->crossClear && $this->isCrossYear($reportId, $lastReport->year)){
            return 0;
        }
        $lastReportId = $lastReport->report_id;
        if (Cache::has('salary_last_pay_detail_'.$lastReportId.'_'.$userId.'_'.$this->dataFieldId)) {
            $record = Cache::get('salary_last_pay_detail_'.$lastReportId.'_'.$userId.'_'.$this->dataFieldId);
        } else {
            $record = DB::table('salary')
                ->leftJoin('salary_pay_detail', 'salary.salary_id', '=', 'salary_pay_detail.salary_id')
                ->where('report_id', $lastReportId)
                ->where('user_id', $userId)
                ->where('field_id', $this->dataFieldId)
                ->value('value');
            // 把 $record 缓存 1 分钟
            Cache::put('salary_last_pay_detail_'.$lastReportId.'_'.$userId.'_'.$this->dataFieldId, $record, 1*60);
        }
        return $record ? $record : 0;
    }

    public function formatValue($value)
    {
        if(! is_numeric($value))
            return $value;

        if($this->config['over_zero']){
            $value =  $value < 0 ? 0 : $value;
        }

        return $value;
    }

    public function getDependenceIds()
    {
        return '';
    }


    protected function parseConfig()
    {
        $extra = json_decode($this->config['field_source']);
        $this->dataFieldId = $extra->field_id;
        $this->crossClear = $extra->cross_clear;
    }

    private function isCrossYear($reportId, $lastReportYear)
    {
        $reportYear = DB::table('salary_report')
            ->where('report_id', $reportId)
            ->value('year');

        return $reportYear != $lastReportYear;
    }
}
