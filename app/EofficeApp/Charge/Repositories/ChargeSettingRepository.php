<?php

namespace App\EofficeApp\Charge\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Charge\Entities\ChargeSettingEntity;
use DB;

/**
 * 费用设置资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ChargeSettingRepository extends BaseRepository
{
    private $default;
    public function __construct(ChargeSettingEntity $entity)
    {
        parent::__construct($entity);
        $this->default = [
            'fields'   => ['*'],
            'search'   => [],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['charge_setting.updated_at' => 'desc', 'charge_setting_id' => 'desc'],
        ];
    }

    /**
     * 获取费用详细
     *
     * @param  int $id
     *
     * @return array
     *
     * @author 喻威
     *
     * @since
     */
    public function getDataByWhere($where)
    {
        return $this->entity->wheres($where)->get()->toArray();
    }

    public function getChargeSetList($param)
    {
        $param = array_merge($this->default, $param);

        $query = $this->handleJoin($param);

        if(isset($param['hasProject']) && (!$param['hasProject'] || $param['hasProject'] == 'false')){
            $param['search']['project_id'] = [null];
        }

        $query = $query->select($param['fields']);

        if (isset($param['search']['charge_setting.user_id']) && is_array($param['search']['charge_setting.user_id'])) {
            $query = $this->handleProjectQuery($query, $param['search']['charge_setting.user_id'], 'charge_setting.user_id');
            unset($param['search']['charge_setting.user_id']);
        }

        if(isset($param['project_id']) && is_array($param['project_id'])){
            if(!isset($param['search']['set_type'])){
                if(isset($param['hasProject']) && $param['hasProject']){
                    $query = $query->orWhereNull('project_id');
                }else{
                    $query = $query->WhereNull('project_id');
                }
            }
            if (isset($param['search']['set_type']) && $param['search']['set_type'][0] != 5) {
                $query = $query->orWhere(function($query) use($param) {
                    $query->whereNull('project_id')->where('set_type', $param['search']['set_type'][0]);
                    switch ($param['search']['set_type'][0]) {
                        case 1:
                            break;
                        case 2:
                            if(isset($param['search']['charge_setting.dept_id'][0])){
                                $query->where('charge_setting.dept_id', $param['search']['charge_setting.dept_id'][0]);
                            }
                            break;
                        case 3:
                            if(isset($param['search']['charge_setting.user_id'][0]) && count($param['search']['charge_setting.user_id'][0]) == 1){
                                $query->where('charge_setting.user_id', $param['search']['charge_setting.user_id'][0]);
                            }
                            break;
                        default:
                            break;
                    }
                });
            }
            $query = $this->handleProjectQuery($query, $param['project_id'], 'project_id','or');
        }
        $query = $query->wheres($param['search']);

        $query = $query->orders($param['order_by']);
        $query = $query->parsePage($param['page'], $param['limit']);
        return $query->get();
    }

    public function getChargeSetTotal($param, $field = ['*'])
    {
        $param = array_merge($this->default, $param);

        $query = $this->handleJoin($param);

        if (isset($param['search']['charge_setting.user_id']) && is_array($param['search']['charge_setting.user_id'])) {
            $query = $this->handleProjectQuery($query, $param['search']['charge_setting.user_id'], 'charge_setting.user_id');
            unset($param['search']['charge_setting.user_id']);
        }

        if(isset($param['project_id']) && is_array($param['project_id'])){
            if(!isset($param['search']['set_type'])){
                if(isset($param['hasProject']) && $param['hasProject']){
                    $query = $query->orWhereNull('project_id');
                }else{
                    $query = $query->WhereNull('project_id');
                }
            }
            if (isset($param['search']['set_type']) && $param['search']['set_type'][0] != 5) {
                $query = $query->orWhere(function($query) use($param) {
                    $query->whereNull('project_id')->where('set_type', $param['search']['set_type'][0]);
                    switch ($param['search']['set_type'][0]) {
                        case 1:

                            break;
                        case 2:
                            if(isset($param['search']['charge_setting.dept_id'][0])){
                                $query->where('charge_setting.dept_id', $param['search']['charge_setting.dept_id'][0]);
                            }
                            break;
                        case 3:
                            if(isset($param['search']['charge_setting.user_id'][0]) && count($param['search']['charge_setting.user_id'][0]) == 1){
                                $query->where('charge_setting.user_id', $param['search']['charge_setting.user_id'][0]);
                            }
                            break;
                        default:
                            # code...
                            break;
                    }
                });
            }
            $query = $this->handleProjectQuery($query, $param['project_id'], 'project_id','or');
        }

        $query = $query->wheres($param['search']);

        $query = $query->orders($param['order_by']);

        return $query->count();
    }

    private function handleProjectQuery($query, $data, $field,$relation='and') {
        if (empty($data)) {
            return $query;
        }
        if(count($data) > 1000){
            $tableName = 'charge_'.rand() . uniqid();
            $res = DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` char(36) NOT NULL,PRIMARY KEY (`data_id`))");
            $tempIds = array_chunk($data, 1000);
            foreach ($tempIds as $key => $item) {
                $ids = implode('"),("', $item);
                $sql = "insert into {$tableName} (data_id) values (\"{$ids}\");";
                DB::insert($sql);
            }
            $query = $query->whereExists(function($query)use($tableName, $field){
                $query->select(DB::raw(1))
                    ->from($tableName)
                    ->whereRaw($field.' = '.$tableName.'.data_id');
            });
        } else {
            if($relation == 'or'){
                $query = $query->orWhereIn($field, $data);
            }else{
                $query = $query->whereIn($field, $data);
            }
        }

        return $query;
    }

    private function handleJoin($param) {
        $setType = $param['search']['set_type'][0] ?? '';
        if ($setType == 1) {
            $query = $this->entity;
        } elseif ($setType == 2) {
            $query = $this->entity->leftJoin("department", "department.dept_id", '=', 'charge_setting.dept_id');
        } elseif ($setType == 3) {
            $query = $this->entity->leftJoin("user", "charge_setting.user_id", "=", "user.user_id")
                ->leftJoin('user_system_info', "charge_setting.user_id", "=", "user_system_info.user_id");
        } elseif($setType == 5) {
            $query = $this->entity->leftJoin("project_manager", "charge_setting.project_id", "=", "project_manager.manager_id");
        } else {
            $query = $this->entity->leftJoin("user", "charge_setting.user_id", "=", "user.user_id")
                ->leftJoin('user_system_info', "charge_setting.user_id", "=", "user_system_info.user_id")
                ->leftJoin("department", "department.dept_id", '=', 'charge_setting.dept_id')
                ->leftJoin("project_manager", "charge_setting.project_id", "=", "project_manager.manager_id");
        }
        return $query;
    }

    public function getCompanyChargeSetList($param) {
        $param = array_merge($this->default, $param);
        return $this->entity->select($param['fields'])
                    ->wheres($param['search'])
                    ->orders($param['order_by'])
                    ->parsePage($param['page'], $param['limit'])
                    ->get();
    }

    public function getCompanyChargeSetTotal($param) {
        $param = array_merge($this->default, $param);
        return $this->entity->select($param['fields'])
                    ->wheres($param['search'])
                    ->count();
    }

    public function getChargeSetSelectorList($param) {
        $default = [
            'search'   => [],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['created_at' => 'desc'],
        ];

        $param = array_merge($default, $param);

        $query = $this->entity->leftJoin("user", "charge_setting.user_id", "=", "user.user_id")
                            ->leftJoin('user_system_info', "charge_setting.user_id", "=", "user_system_info.user_id")
                            ->leftJoin("department", "department.dept_id", '=', 'charge_setting.dept_id')
                            ->leftJoin("project_manager", "charge_setting.project_id", "=", "project_manager.manager_id");

        if (isset($param['name'])) {
            $name = $param['name'];
            $query = $query->where(function($query)use($name){
                $query->where('user_name', 'like', '%'.$name.'%')->orWhere('dept_name', 'like', '%'.$name.'%')->orWhere('manager_name', 'like', '%'.$name.'%');
            });
        }

        if(isset($param['hasProject']) && (!$param['hasProject'] || $param['hasProject'] == 'false')){
            $param['search']['project_id'] = [null];
        }

        $query = $query->where(function($query)use($param){
            $query->wheres($param['search']);
            if (isset($param['hasProject']) && $param['hasProject'] != 'false' && !isset($param['search']['charge_setting_id'])) {
                $query = $query->orWhereNull('project_id');
            }
        });
        $query = $query->orders($param['order_by']);

        $query = $query->parsePage($param['page'], $param['limit']);

        return $query->get();
    }

    public function getChargeSetSelectorTotal($param) {
        $default = [
            'search'   => [],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['created_at' => 'desc'],
        ];

        $param = array_merge($default, $param);

        $query = $this->entity->leftJoin("user", "charge_setting.user_id", "=", "user.user_id")
                            ->leftJoin("department", "department.dept_id", '=', 'charge_setting.dept_id')
                            ->leftJoin("project_manager", "charge_setting.project_id", "=", "project_manager.manager_id");

        if (isset($param['name'])) {
            $name = $param['name'];
            $query = $query->where(function($query)use($name){
                $query->where('user_name', 'like', '%'.$name.'%')->orWhere('dept_name', 'like', '%'.$name.'%')->orWhere('manager_name', 'like', '%'.$name.'%');
            });
        }

        if(isset($param['hasProject']) && (!$param['hasProject'] || $param['hasProject'] == 'false')){
            $param['search']['project_id'] = [null];
        }

        $query = $query->where(function($query)use($param){
            $query->wheres($param['search']);
            if (isset($param['hasProject']) && $param['hasProject'] != 'false' && !isset($param['search']['charge_setting_id'])) {
                $query = $query->orWhereNull('project_id');
            }
        });

        $query = $query->orders($param['order_by']);

        return $query->count();
    }

    public function getSubjectCheck($where)
    {
        return $this->entity->select("subject_check")->wheres($where)->first();
    }

    public function getSettingByWhere($where, $flag, $start='', $end='', $same=false) {
        if ($flag) {
            if ($same) {
                $result = $this->entity->where('alert_data_start', $start)
                                        ->where('alert_data_end', $end)
                                        ->get();
                if (!$result->isEmpty()) {
                    return true;
                }
            }
            return $this->entity->where(function($query) use ($start, $end, $where){
                                    $query->where('alert_data_start', '<=', $start)
                                        ->where('alert_data_end', '>=', $start)
                                        ->wheres($where);
                                })
                                ->orWhere(function($query) use ($start, $end, $where){
                                    $query->where('alert_data_start', '<=', $start)
                                        ->where('alert_data_end', '>=', $end)
                                        ->wheres($where);
                                })
                                ->orWhere(function($query) use ($start, $end, $where){
                                    $query->where('alert_data_start', '<=', $end)
                                        ->where('alert_data_end', '>=', $end)
                                        ->wheres($where);
                                })
                                ->orWhere(function($query) use ($start, $end, $where){
                                    $query->where('alert_data_start', '>=', $start)
                                        ->where('alert_data_end', '<=', $end)
                                        ->wheres($where);
                                })
                                ->get();
        } else {
            return $this->entity->wheres($where)->first();
        }
    }

    public function insertGetId($data = []) {
        return $this->entity->insertGetId($data);
    }
}
