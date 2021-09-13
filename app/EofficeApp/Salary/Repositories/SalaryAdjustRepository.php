<?php

namespace App\EofficeApp\Salary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Salary\Entities\SalaryAdjustEntity;
use Illuminate\Database\Eloquent\Collection;

/**
 * 薪资调整Repository类:提供薪资相关的数据库操作方法。
 */
class SalaryAdjustRepository extends BaseRepository
{
    public function __construct(SalaryAdjustEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取薪酬调整列表
     *
     * @param  array $param 查询条件
     *
     * @return Collection 查询列表
     *
     */
    public function getSalaryAdjust($param)
    {
        $query = $this->entity->leftJoin('salary_fields','salary_adjust.field_id','=','salary_fields.field_id');

        if(isset($param['search'])){
            if(isset($param['search']['user_name'])){
                unset($param['search']['user_name']);
            }
            $query->wheres($param['search']);
        }

        $query = $query->with(["hasPersonnel" => function ($query) {
            $query->select("id","user_id", "user_name");
        }]);
        return $query->orderBy('adjust_date', 'desc')->orderBy('adjust_id', 'desc')->get();
    }

    public function getLastSalaryAdjust($where)
    {
        return $this->entity->wheres($where)->orderBy('adjust_id', 'desc')->first();
    }

    public function getSalaryAdjustGroup() {
        return $this->entity->select(['user_id', 'field_code', 'field_default_new', 'has_children'])
                    ->leftJoin('salary_fields','salary_adjust.field_id','=','salary_fields.field_id')
                    ->groupBy(['user_id', 'salary_adjust.field_id'])
                    ->get();
    }

    /**
     * 功能函数，salary联查人事档案表，用于将salary_adjust表的user_id 翻译为 人事id
     * 注意返回值是 $query ，返回出去之后，用chunk处理。
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getSalaryUserRelatePersonnel($param = [], $aboutUser = '')
    {
        $default = [
            'fields' => [
                'salary_adjust.adjust_id',
                'personnel_files.id as user_id'
            ]
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        if($aboutUser && $aboutUser == 'user_system_info') {
            $query = $query->leftJoin('personnel_files', 'salary_adjust.user_id', '=', 'personnel_files.id');
            $query = $query->leftJoin('user_system_info', 'personnel_files.user_id', '=', 'user_system_info.user_id');
        } else {
            $query = $query->leftJoin('personnel_files', 'salary_adjust.user_id', '=', 'personnel_files.user_id');
        }
        if(isset($param['search'])){
            $query = $query->wheres($param['search']);
        }
        return $query;

    }
}
