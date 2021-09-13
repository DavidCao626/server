<?php

namespace App\EofficeApp\Salary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Salary\Entities\SalaryReportEntity;
use DB;

/**
 * 薪资上报Repository类:提供薪资相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2015-11-2 创建
 */
class SalaryReportRepository extends BaseRepository
{
    public function __construct(SalaryReportEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取薪酬上报列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getSalaryReportList(array $param = [])
    {
        $default = [
            'fields'    => ['*'],
            'search'    => [],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'order_by'  => ['report_id' => 'desc'],
        ];

        $param = array_merge($default, array_filter($param));

        return $this->entity
        ->select($param['fields'])
        ->wheres($param['search'])
        ->orders($param['order_by'])
        ->parsePage($param['page'], $param['limit'])
        ->get()
        ->toArray();
    }

    /**
     * 获取薪酬报表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     */
    public function getSalaryReport($date)
    {
        return DB::select("SELECT * FROM (SELECT salary.salary_id,salary.user_id,salary_report.* FROM salary_report LEFT JOIN salary ON salary_report.report_id = salary.report_id) a WHERE NOT EXISTS (SELECT * FROM (SELECT salary.salary_id, salary.user_id,salary_report.* FROM salary_report LEFT JOIN salary ON salary_report.report_id = salary.report_id) b WHERE b.user_id = a.user_id AND b.created_at > a.created_at) AND start_date >= '".$date['start']."' AND end_date <= '".$date['end']."'");
    }
}