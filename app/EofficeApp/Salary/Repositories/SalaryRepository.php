<?php

namespace App\EofficeApp\Salary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Salary\Entities\SalaryEntity;
use App\EofficeApp\Salary\Entities\SalaryPayDetailEntity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\EofficeApp\Base\ModelTrait;

/**
 * 薪资Repository类:提供薪资相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2015-11-2 创建
 */
class SalaryRepository extends BaseRepository
{
    use ModelTrait;

    private $salaryPayDetailEntity;

    public function __construct(
        SalaryEntity $entity,
        SalaryPayDetailEntity $salaryPayDetailEntity
    )
    {
        parent::__construct($entity);
        $this->salaryPayDetailEntity = $salaryPayDetailEntity;
    }

    /**
     * 查询薪资详情
     *
     * @param array $where 查询条件
     *
     * @return Model|null 薪资详情
     *
     * @author qishaobo
     * @since  2015-10-21
     */
    public function getDetailByWhere(array $where)
    {
        return $this->entity->wheres($where)->first();
    }

    /**
     * 薪资是否上报
     *
     * @param  array $where  查询条件
     *
     * @return array 薪资详情
     *
     * @author qishaobo
     *
     * @since  2015-12-18
     */
    public function isSalaryReport(array $where)
    {
        return $this->entity->wheres($where)->first();
    }

    public function getSalaryTotal($param = [])
    {
        return $this->entity
            ->join('salary_report', 'salary.report_id', '=', 'salary_report.report_id')
            ->whereHas('salaryToSalaryReport')
            ->wheres($param['search'])
            ->count();
    }

    /**
     * @param $where
     * @return Collection
     */
    public function getSalaryListByWhere($where)
    {
        return $this->entity->wheres($where)->get();
    }

    public function getSalaryByReportIdAndUserId($reportId, $userId)
    {
        return $this->entity->where('report_id', $reportId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * @param $reportId
     * @param array $userIds
     */
    public function getReportSalaryListWithPayDetailsAndUserInfo($reportId, $userIds)
    {
        $query = $this->entity->where('report_id', $reportId)
            ->select(['salary_id', 'report_id', 'user_id'])
            ->whereIn('user_id', $userIds)
            ->orderBy('salary_id', 'desc')
            ->with(['user' => function ($query){
                    $query->select(['user_id', 'user_name'])
                    ->with('userRole:role_name')
                    ->with('userToDept:dept_name');
            }])
            ->with(['hasPersonnel' => function ($query){
                    $query->select(['id','user_id','user_name','dept_id'])
                    ->with(['department' => function ($query){
                            $query->select(['dept_id','dept_name']);
                    }]);
            }])
            ->with('payDetails');
        return $query->get();
    }

    /**
     * @param $userIds
     * @param array $param
     * @return mixed
     */
    public function getAllSalaryListWithPayDetailsAndUserInfo($userIds, $param = [])
    {
        $params = [
            'page' => 0,
            'limit' => 10
        ];
        $param = array_merge($params, $param);
        return $this->entity
            ->select(['salary_id', 'salary.report_id', 'user_id'])
            ->join('salary_report', 'salary.report_id', '=', 'salary_report.report_id')
            ->when(isset($param['search']), function ($query) use ($param){
                $query->wheres($param['search']);
            })
            ->when(isset($param['search']['updated_at']), function ($query) {
                $query->whereNotNull('updated_at');
            })
            ->whereIn('user_id', $userIds)
            ->orderBy('salary_id', 'desc')
            ->whereHas('salaryToSalaryReport')
            ->with('salaryToSalaryReport')
            ->with('user:user_id,user_name')
            ->with('payDetails')
            // ->parsePage($param['page'], $param['limit'])
            ->get();
    }

    /**
     * @param $userIds
     * @param array $param
     * @return array
     */
    public function getAllSalaryReportPayDetails($userIds, $param = [])
    {
        $query = DB::table('salary')
            ->select([
                'salary.salary_id',
                'salary.report_id',
                'salary.user_id as salary_user_id',
                'user.user_id',
                'user.user_name',
                'salary_report.year',
                'salary_report.month',
                'salary_report.title',
                'salary_pay_detail.field_id',
                'salary_pay_detail.value',
                'personnel_files.id as personnel_id',
                'personnel_files.user_id as personnel_user_id',
                'personnel_files.user_name as personnel_user_name',
                'personnel_files.dept_id as personnel_dept_id'
            ])
            ->join('salary_report', 'salary.report_id', '=', 'salary_report.report_id')
            ->join('salary_pay_detail', 'salary.salary_id', '=', 'salary_pay_detail.salary_id')
            ->leftJoin('user', 'salary.user_id', '=', 'user.user_id')
            ->leftJoin('personnel_files', 'salary.user_id', '=', 'personnel_files.id')
            ->whereIn('salary.user_id', $userIds);
            if(isset($param['search'])){
                $query = $this->wheres($query, $param['search']);
            }
            if(isset($param['search']['updated_at'])){
                $query = $query->whereNotNull('updated_at');
            }

            return $query->orderByDesc('salary_id')
                    ->get()->toArray();
    }

    /**
     * @param array $param
     */
    public function getListWithReport($param = [])
    {
        $params = [
            'page' => 0,
            'limit' => 15
        ];
        $param = array_merge($params, $param);
        return $this->entity
            ->when(isset($param['search']), function ($query) use ($param){
                $query->wheres($param['search']);
            })
            ->orderBy('salary_id', 'desc')
            ->whereHas('salaryToSalaryReport')
            ->with('salaryToSalaryReport')
            ->parsePage($param['page'], $param['limit'])
            ->get();
    }

    /**
     * 功能函数，salary联查人事档案表，用于将salary表的user_id 翻译为 人事id
     * 注意返回值是 $query ，返回出去之后，用chunk处理。
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getSalaryUserRelatePersonnel($param = [], $aboutUser = '')
    {
        $default = [
            'fields' => [
                'salary.salary_id',
                'personnel_files.id as user_id'
            ]
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        if($aboutUser && $aboutUser == 'user_system_info') {
            $query = $query->leftJoin('personnel_files', 'salary.user_id', '=', 'personnel_files.id');
            $query = $query->leftJoin('user_system_info', 'personnel_files.user_id', '=', 'user_system_info.user_id');
        } else {
            $query = $query->leftJoin('personnel_files', 'salary.user_id', '=', 'personnel_files.user_id');
        }
        if(isset($param['search'])){
            $query = $query->wheres($param['search']);
        }
        return $query;
    }

}
