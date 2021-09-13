<?php

namespace App\EofficeApp\Vacation\Repositories;

use App\EofficeApp\Vacation\Entities\VacationLogEntity;

class VacationLogRepository extends VacationBaseRepository
{
    public function __construct(VacationLogEntity $vacationLogEntity)
    {
        parent::__construct($vacationLogEntity);
    }

    public function getLogList($params)
    {
        $defaultParams = [
            'fields' 	=> ['*'],
            'page' 		=> 0,
            'limit' 	=> config('eoffice.pagesize'),
            'order_by' 	=> ['created_at' => 'desc'],
            'search' 	=> []
        ];

        $params = array_merge($defaultParams, $params);
        return $this->entity->select($params['fields'])
            ->wheres($params['search'])
            ->orders($params['order_by'])
            ->parsePage($params['page'], $params['limit'])
            ->get();
    }

    public function getYearsHours($is_transform, $conversion_ratio, $vacations)
    {
//        echo "<pre>".print_r($is_transform);die();
        $update = [];
        if($is_transform == 0){
            //小时转换天
            if(!empty($vacations)){
                foreach ($vacations as $vacation){
                    if($vacation['before'] == 0 && $vacation['after'] == 0){
                        if($vacation['before_hours'] == 0){
                            $before = 0;
                        }else{
                            $before = round($vacation['before_hours'] / $conversion_ratio,4);
                        }
                        if($vacation['after_hours'] == 0){
                            $after = 0;
                        }else{
                            $after = round($vacation['after_hours'] * $conversion_ratio,4);
                        }
                        if($vacation['change_hours'] == 0){
                            $change = 0;
                        }else{
                            $change = round($vacation['change_hours'] * $conversion_ratio,4);
                        }
                        $update[] = [
                            'where' => ['id' => $vacation['id']],
                            'update' => ['before' => $before, 'after' => $after, 'change' => $change, 'updated_at' => date('Y-m-d H:i:s')]
                        ];
                    }
                }
            }
        }else{
            //天转换小时
            if(!empty($vacations)){
                foreach ($vacations as $vacation){
                    if($vacation['before_hours'] == 0 && $vacation['after_hours'] == 0){
                        if($vacation['before'] == 0){
                            $before_hours = 0;
                        }else{
                            $before_hours = round($vacation['before'] * $conversion_ratio,2);
                        }
                        if($vacation['after'] == 0){
                            $after_hours = 0;
                        }else{
                            $after_hours = round($vacation['after'] * $conversion_ratio,2);
                        }
                        if($vacation['change'] == 0){
                            $change_hours = 0;
                        }else{
                            $change_hours = round($vacation['change'] * $conversion_ratio,2);
                        }
                        $update[] = [
                            'where' => ['id' => $vacation['id']],
                            'update' => ['before_hours' => $before_hours, 'after_hours' => $after_hours, 'change_hours' => $change_hours, 'updated_at' => date('Y-m-d H:i:s')]
                        ];
                    }
                }
            }
        }
        return $update;
    }

}