<?php

namespace App\EofficeApp\Vacation\Repositories;

use App\EofficeApp\Vacation\Entities\VacationExpireRecordEntity;

class VacationExpireRecordRepository extends VacationBaseRepository
{
    public function __construct(VacationExpireRecordEntity $entity)
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
                    $update[] = [
                        'where' => ['id' => $vacation['id']],
                        'update' => ['days' => $days, 'updated_at' => date('Y-m-d H:i:s')]
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
                    $update[] = [
                        'where' => ['id' => $vacation['id']],
                        'update' => ['hours' => $hours, 'updated_at' => date('Y-m-d H:i:s')]
                    ];
                }
            }
        }
        return $update;
    }

}