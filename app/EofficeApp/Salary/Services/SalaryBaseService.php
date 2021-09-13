<?php
namespace App\EofficeApp\Salary\Services;

use App\EofficeApp\Base\BaseService;
use Batch;
use DB;

class SalaryBaseService extends BaseService
{
    // 公共属性，“[无账号发薪]”配置，开启1、关闭0
    public $payWithoutAccountConfig;
    public function __construct(
    ) {
        parent::__construct();
        $this->salaryBaseSetRepository = 'App\EofficeApp\Salary\Repositories\SalaryBaseSetRepository';
        $this->personnelFilesRepository = "App\EofficeApp\PersonnelFiles\Repositories\PersonnelFilesRepository";
        $this->personnelFilesService = "App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService";
        $this->salaryRepository = 'App\EofficeApp\Salary\Repositories\SalaryRepository';
        $this->salaryAdjustRepository = 'App\EofficeApp\Salary\Repositories\SalaryAdjustRepository';
        $this->salaryFieldPersonalDefaultRepository = 'App\EofficeApp\Salary\Repositories\SalaryFieldPersonalDefaultRepository';
        $this->salaryFieldPersonalDefaultHistoryRepository = 'App\EofficeApp\Salary\Repositories\SalaryFieldPersonalDefaultHistoryRepository';
        $this->payWithoutAccountConfig = $this->getPayWithoutAccountConfig();
        $this->salaryEntity = 'App\EofficeApp\Salary\Entities\SalaryEntity';
        $this->salaryAdjustEntity = 'App\EofficeApp\Salary\Entities\SalaryAdjustEntity';
        $this->salaryFieldPersonalDefaultEntity = 'App\EofficeApp\Salary\Entities\SalaryFieldPersonalDefaultEntity';
        $this->salaryFieldPersonalDefaultHistoryEntity = 'App\EofficeApp\Salary\Entities\SalaryFieldPersonalDefaultHistoryEntity';
    }

    // 薪酬，基础设置，获取
    // 用法1，支持前端路由，一次返回所有配置项
    // 用法2，传入param_key参数(非必填)，获取指定配置项
    public function getSalaryBaseSet($configKey = '')
    {
        $info = app($this->salaryBaseSetRepository)->getSalaryBaseSetInfo();
        $result = [];
        if($info && !empty($info)) {
            // [param_key] => pay_without_account
            // [param_value] => 0
            foreach ($info as $key => $value) {
                $paramKey = $value->param_key;
                $paramValue = $value->param_value;
                $result[$paramKey] = $paramValue;
            }
        }
        if(isset($configKey) && isset($result[$configKey])) {
            return $result[$configKey];
        } else {
            return $result;
        }
    }

    // 薪酬，基础设置，保存
    public function saveSalaryBaseSet($param)
    {
        if(!empty($param)) {
            foreach ($param as $configKey => $value) {
                $saveResult = app($this->salaryBaseSetRepository)->updateData(['param_value' => $value], ['param_key' => $configKey]);
            }
            if(isset($param['pay_without_account'])) {
                // 根据模式，转换4个数据库表内存储的人事id/用户id
                $this->migrateDatabaseSalaryUser($param['pay_without_account']);
            }
        }
        return 1;
    }

    // 根据模式，转换4个数据库表内存储的人事id/用户id
    public function migrateDatabaseSalaryUser($payWithoutAccount) {
        // 人事档案发薪
        if($payWithoutAccount == '1') {
            // 查人事档案数据，把数据库已有的用户id翻译成人事id
            // 人事表数据要求(user_id != '' AND status != '2')
            $param = ['search' => ['personnel_files.user_id' => ['', '!='], 'personnel_files.status' => ['2', '!=']]];
            // `salary`
            app($this->salaryRepository)->getSalaryUserRelatePersonnel($param)->chunkById(500, function($lists) {
                Batch::update(app($this->salaryEntity),$lists->toArray(),'salary_id');
            }, 'salary.salary_id', 'salary_id');
            // `salary_adjust`;
            app($this->salaryAdjustRepository)->getSalaryUserRelatePersonnel($param)->chunkById(500, function($lists) {
                Batch::update(app($this->salaryAdjustEntity),$lists->toArray(),'adjust_id');
            }, 'salary_adjust.adjust_id', 'adjust_id');
            // `salary_field_personal_default`;
            app($this->salaryFieldPersonalDefaultRepository)->getSalaryUserRelatePersonnel($param)->chunkById(500, function($lists) {
                Batch::update(app($this->salaryFieldPersonalDefaultEntity),$lists->toArray(),'id');
            }, 'salary_field_personal_default.id', 'id');
            // `salary_field_personal_default_history`;
            app($this->salaryFieldPersonalDefaultHistoryRepository)->getSalaryUserRelatePersonnel($param)->chunkById(500, function($lists) {
                Batch::update(app($this->salaryFieldPersonalDefaultHistoryEntity),$lists->toArray(),'id');
            }, 'salary_field_personal_default_history.id', 'id');
        } else if($payWithoutAccount == '0') {
            // 用户管理发薪
            // 查人事档案数据，把数据库已有的人事id翻译成用户id
            // 人事表数据要求(user_id != '' AND status != '2') 且 关联user_system_info 数据要求(user_status NOT IN (0,2))
            $param = ['search' => ['personnel_files.user_id' => ['', '!='], 'personnel_files.status' => ['2', '!='], 'user_system_info.user_status' => [['0', '2'], 'not_in']]];
            // `salary`
            $param['fields'] = ['salary.salary_id', 'personnel_files.user_id as user_id'];
            app($this->salaryRepository)->getSalaryUserRelatePersonnel($param, 'user_system_info')->chunkById(500, function($lists) {
                Batch::update(app($this->salaryEntity),$lists->toArray(),'salary_id');
            }, 'salary.salary_id', 'salary_id');
            // `salary_adjust`;
            $param['fields'] = ['salary_adjust.adjust_id', 'personnel_files.user_id as user_id'];
            app($this->salaryAdjustRepository)->getSalaryUserRelatePersonnel($param, 'user_system_info')->chunkById(500, function($lists) {
                Batch::update(app($this->salaryAdjustEntity),$lists->toArray(),'adjust_id');
            }, 'salary_adjust.adjust_id', 'adjust_id');
            // `salary_field_personal_default`;
            $param['fields'] = ['salary_field_personal_default.id', 'personnel_files.user_id as user_id'];
            app($this->salaryFieldPersonalDefaultRepository)->getSalaryUserRelatePersonnel($param, 'user_system_info')->chunkById(500, function($lists) {
                Batch::update(app($this->salaryFieldPersonalDefaultEntity),$lists->toArray(),'id');
            }, 'salary_field_personal_default.id', 'id');
            // `salary_field_personal_default_history`;
            $param['fields'] = ['salary_field_personal_default_history.id', 'personnel_files.user_id as user_id'];
            app($this->salaryFieldPersonalDefaultHistoryRepository)->getSalaryUserRelatePersonnel($param, 'user_system_info')->chunkById(500, function($lists) {
                Batch::update(app($this->salaryFieldPersonalDefaultHistoryEntity),$lists->toArray(),'id');
            }, 'salary_field_personal_default_history.id', 'id');
        }
    }

    // 后台，获取无账号算薪配置
    // 可以加缓存增加查询速度，在编辑保存的时候，更新缓存
    public function getPayWithoutAccountConfig() {
        $payWithoutAccount = $this->getSalaryBaseSet('pay_without_account');
        return $payWithoutAccount;
    }

    // 功能函数，用户id转换为档案的id，多应用场景，支持传入数组
    public function getUserPersonnelId($userId) {
        $result = [];
        $userSearch = [];
        if(is_string($userId)) {
            $userSearch = explode(',', $userId);
        } else if(is_array($userId)) {
            $userSearch = $userId;
        }
        $params = ['fields' => ['id', 'user_id', 'user_name'],'search' => ['user_id' => [$userSearch, 'in']]];
        $personnelInfo = app($this->personnelFilesRepository)->getPersonnelFilesList($params);
        $personnelId = collect($personnelInfo)->pluck('id')->toArray();
        if(is_string($userId)) {
            $result = implode(",", $personnelId);
        } else if(is_array($userId)) {
            $result = $personnelId;
        }
        return $result;
    }

    // 功能函数，调人事档案service，将人事档案id串里的用户id翻译出来
    // 传入人事档案id的数组，传出一个混合id数组
    // 20201124-暂不启用，没有地方调用此函数
    // 20201223-CalculateField - getValue 用到了这个函数，要用用户id获取考勤信息
    public function transPersonnelFileIds($fileIds, $type='id', $returnType='') {
        if($this->payWithoutAccountConfig == '1') {
            return app($this->personnelFilesService)->checkPersonnelFiles($fileIds, $type, $returnType);
        } else {
            return $fileIds;
        }
    }

    // 功能函数，调人事档案service，将人事档案id混合用户id的串，翻译成 人事档案id串
    // 传入混合id数组，传出一个人事档案id的数组
    public function transUserIds($fileIds) {
        if($this->payWithoutAccountConfig == '1') {
            return app($this->personnelFilesService)->transUserPersonnelFileIds($fileIds);
        } else {
            return $fileIds;
        }
    }

}