<?php

namespace App\EofficeApp\Vacation\Repositories;

use App\EofficeApp\Vacation\Entities\VacationYearEntity;

class VacationYearRepository extends VacationBaseRepository
{
    public function __construct(VacationYearEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getYearsHours($is_transform, $conversion_ratio, $vacations)
    {
        $update = [];
        if($is_transform == 0){
            //小时转换天
            if(!empty($vacations)){
                foreach ($vacations as $vacation){
                    if($vacation['hours'] == 0){
                        $days = 0;
                    }else{
                        $days = round($vacation['hours'] / $conversion_ratio,4);
                    }
                    if($vacation['outsend_hours'] == 0){
                        $outsend_days = 0;
                    }else{
                        $outsend_days = round($vacation['outsend_hours'] / $conversion_ratio,4);
                    }
                    $update[] = [
                        'where' => ['id' => $vacation['id']],
                        'update' => ['days' => $days, 'outsend_days' => $outsend_days, 'updated_at' => date('Y-m-d H:i:s')]
                    ];
                }
            }
        }else{
            //天转换小时
            if(!empty($vacations)){
                foreach ($vacations as $vacation){
                    if($vacation['days'] == 0){
                        $hours = 0;
                    }else{
                        $hours = round($vacation['days'] * $conversion_ratio,2);
                    }
                    if($vacation['outsend_days'] == 0){
                        $outsend_hours = 0;
                    }else{
                        $outsend_hours = round($vacation['outsend_days'] * $conversion_ratio,2);
                    }
                    $update[] = [
                        'where' => ['id' => $vacation['id']],
                        'update' => ['hours' => $hours, 'outsend_hours' => $outsend_hours, 'updated_at' => date('Y-m-d H:i:s')]
                    ];
                }
            }
        }
        return $update;
    }

}