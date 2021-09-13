<?php


namespace App\EofficeApp\Salary\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Salary\Entities\SalaryFieldHistoryEntity;
use App\EofficeApp\Salary\Entities\SalaryFieldPersonalDefaultHistoryEntity;


class SalaryFieldPersonalDefaultHistoryRepository extends BaseRepository
{
    private $salaryFieldHistoryEntity;

    public function __construct(
        SalaryFieldPersonalDefaultHistoryEntity $entity,
        SalaryFieldHistoryEntity $salaryFieldHistoryEntity
    )
    {
        parent::__construct($entity);
        $this->salaryFieldHistoryEntity = $salaryFieldHistoryEntity;
    }

    /**
     * @param $historyField
     * @param $userId
     * @return mixed
     */
    public function getUserPersonalDefault($historyField, $userId)
    {
        $record = $this->entity
            ->where('user_id', $userId)
            ->where('field_history_id', $historyField['id'])
            ->orderBy('id', 'desc')
            ->first();
        if($record){
            return $record->default_value;
        }
        $field = $this->salaryFieldHistoryEntity
            ->select(['id', 'field_default'])
            ->where('id', $historyField['id'])
            ->first();

        return $field->field_default;
    }

    /**
     * 传入字段数组，查到列表，为了不在循环内查default数据
     * @param  [type] $historyFields [description]
     * @return [type]                [description]
     */
    public function getFieldHistoryDefaultList($historyFields, $usersInfo)
    {
        $defaultList = $this->entity
            ->whereIn('field_history_id', $historyFields)
            ->whereIn('user_id', $usersInfo)
            ->orderBy('id', 'desc')
            ->get();
        return $defaultList;
    }

    /**
     * 功能函数，salary联查人事档案表，用于将salary_field_personal_default_history表的user_id 翻译为 人事id
     * 注意返回值是 $query ，返回出去之后，用chunk处理。
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getSalaryUserRelatePersonnel($param = [], $aboutUser = '')
    {
        $default = [
            'fields' => [
                'salary_field_personal_default_history.id',
                'personnel_files.id as user_id'
            ]
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        if($aboutUser && $aboutUser == 'user_system_info') {
            $query = $query->leftJoin('personnel_files', 'salary_field_personal_default_history.user_id', '=', 'personnel_files.id');
            $query = $query->leftJoin('user_system_info', 'personnel_files.user_id', '=', 'user_system_info.user_id');
        } else {
            $query = $query->leftJoin('personnel_files', 'salary_field_personal_default_history.user_id', '=', 'personnel_files.user_id');
        }
        if(isset($param['search'])){
            $query = $query->wheres($param['search']);
        }
        return $query;

    }

}
