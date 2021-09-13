<?php


namespace App\EofficeApp\Salary\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Salary\Entities\SalaryFieldHistoryEntity;
use App\EofficeApp\Salary\Entities\SalaryFieldPersonalDefaultEntity;
use App\EofficeApp\Salary\Entities\SalaryFieldsEntity;
use App\EofficeApp\Salary\Services\SalaryReportSetService;
use App\EofficeApp\User\Entities\UserEntity;
use App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesEntity;
use Illuminate\Support\Collection;


class SalaryFieldPersonalDefaultRepository extends BaseRepository
{
    private $userEntity;

    private $salaryFieldsEntity;

    private $salaryFieldHistoryEntity;
    private $personnelFilesEntity;

    public function __construct(
        SalaryFieldPersonalDefaultEntity $entity,
        UserEntity $userEntity,
        SalaryFieldsEntity $salaryFieldsEntity,
        SalaryFieldHistoryEntity $salaryFieldHistoryEntity,
        PersonnelFilesEntity $personnelFilesEntity
    )
    {
        parent::__construct($entity);
        $this->userEntity = $userEntity;
        $this->salaryFieldsEntity = $salaryFieldsEntity;
        $this->salaryFieldHistoryEntity = $salaryFieldHistoryEntity;
        $this->personnelFilesEntity = $personnelFilesEntity;
    }

    /**
     * 20201124-没有地方调用此函数，看起来废弃了
     * @param $params
     * @return Collection
     */
    public function getInitPersonalDefaultList($params)
    {
        $params = array_merge($this->getDefaultParam(), array_filter($params));

        return $this->userEntity
            ->select(['user_id', 'user_name'])
            ->where('user_accounts', '!=', '')
            ->parsePage($params['page'], $params['limit'])
            ->orders($params['order_by'])
            ->get();
    }

    /**
     * @param $fieldId
     * @param $params
     * @return Collection
     */
    public function getPersonalDefaultList($fieldId, $params)
    {
        $payWithoutAccountConfig = 0;
        if(isset($params['payWithoutAccountConfig'])) {
            $payWithoutAccountConfig = $params['payWithoutAccountConfig'];
            unset($params['payWithoutAccountConfig']);
        }
        $params = array_merge($this->getDefaultParam(), array_filter($params));
        if($payWithoutAccountConfig == '1') {
            $query = $this->personnelFilesEntity
                ->select(['id', 'user_id', 'user_name'])
                ->with(['salaryPersonalDefault' => function ($query) use ($fieldId) {
                    $query->where('field_id', $fieldId)
                        ->select('user_id', 'default_value');
                }])
                ->when(isset($params['search']), function ($query) use ($params) {
                    $query->wheres($params['search']);
                })
                ->parsePage($params['page'], $params['limit'])
                ->orders($params['order_by'])
                ->get();
            return $query;
        } else {
            return $this->userEntity
                ->select(['user_id', 'user_name'])
                ->with(['salaryPersonalDefault' => function ($query) use ($fieldId) {
                    $query->where('field_id', $fieldId)
                        ->select('user_id', 'default_value');
                }])
                ->when(isset($params['search']), function ($query) use ($params) {
                    $query->wheres($params['search']);
                })
                ->where('user_accounts', '!=', '')
                ->parsePage($params['page'], $params['limit'])
                ->orders($params['order_by'])
                ->get();
        }
    }

    public function getPersonalDefaultTotal($params)
    {
        $payWithoutAccountConfig = 0;
        if(isset($params['payWithoutAccountConfig'])) {
            $payWithoutAccountConfig = $params['payWithoutAccountConfig'];
            unset($params['payWithoutAccountConfig']);
        }
        $params = array_merge($this->getDefaultParam(), array_filter($params));
        if($payWithoutAccountConfig == '1') {
            return $this->personnelFilesEntity
                ->when(isset($params['search']), function ($query) use ($params) {
                    $query->wheres($params['search']);
                })
                ->count();
        } else {
            return $this->userEntity
                ->when(isset($params['search']), function ($query) use ($params) {
                    $query->wheres($params['search']);
                })
                ->where('user_accounts', '!=', '')
                ->count();
        }
    }

    /**
     * @param $fieldId
     * @param $personalDefaultList
     */
    public function updatePersonalDefaultList($fieldId, $personalDefaultList)
    {
        $userIds = array_column($personalDefaultList, 'user_id');
        $this->entity->where('field_id', $fieldId)
            ->whereIn('user_id', $userIds)
            ->delete();

        return $this->insertPersonalDefaultList($fieldId, $personalDefaultList);
    }

    /**
     * @param $fieldId
     * @param $personalDefaultList
     */
    public function insertPersonalDefaultList($fieldId, $personalDefaultList)
    {
        $insertData = array_map(function ($value) use ($fieldId) {
            return [
                'field_id' => $fieldId,
                'user_id' => $value['user_id'],
                'default_value' => $value['default_value']
            ];
        }, $personalDefaultList);

        return $this->entity->insert($insertData);
    }

    /**
     * @param $fieldId
     * @param $userId
     * @return string
     */
    public function getUserPersonalDefault($fieldId, $userId)
    {
        $record = $this->entity
                    ->where('user_id', $userId)
                    ->where('field_id', $fieldId)
                    ->orderBy('id', 'desc')
                    ->first();
        if($record){
            return $record->default_value;
        }
        $field = $this->salaryFieldsEntity
                ->select('field_id', 'field_default')
                ->where('field_id', $fieldId)
                ->first();

        return $field->field_default;
    }

    /**
     * 默认配置
     * @return array
     */
    private function getDefaultParam()
    {
        return [
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['user_id' => 'asc'],
        ];
    }

    /**
     * 功能函数，salary联查人事档案表，用于将salary_field_personal_default表的user_id 翻译为 人事id
     * 注意返回值是 $query ，返回出去之后，用chunk处理。
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function getSalaryUserRelatePersonnel($param = [], $aboutUser = '')
    {
        $default = [
            'fields' => [
                'salary_field_personal_default.id',
                'personnel_files.id as user_id'
            ]
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        if($aboutUser && $aboutUser == 'user_system_info') {
            $query = $query->leftJoin('personnel_files', 'salary_field_personal_default.user_id', '=', 'personnel_files.id');
            $query = $query->leftJoin('user_system_info', 'personnel_files.user_id', '=', 'user_system_info.user_id');
        } else {
            $query = $query->leftJoin('personnel_files', 'salary_field_personal_default.user_id', '=', 'personnel_files.user_id');
        }
        if(isset($param['search'])){
            $query = $query->wheres($param['search']);
        }
        return $query;

    }




}
