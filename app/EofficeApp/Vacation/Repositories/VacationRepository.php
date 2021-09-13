<?php

namespace App\EofficeApp\Vacation\Repositories;

use App\EofficeApp\Vacation\Entities\VacationEntity;

class VacationRepository extends VacationBaseRepository
{
	public function __construct(VacationEntity $vacationEntity)
	{
		parent::__construct($vacationEntity);
	}

	/**
	 * [getVacationList 获取假期列表]
	 *
	 * @author 施奇
	 *
	 * @param  [array]          $params [查询条件]
	 *
	 * @return [array]                  [查询结果]
	 */
	public function getVacationList($params)
	{
		$defaultParams = [
			'fields' 	=> ['*'],
			'page' 		=> 0,
			'limit' 	=> config('eoffice.pagesize'),
			'order_by' 	=> ['sort' => 'desc','vacation_id'=>'asc'],
			'search' 	=> []
		];

		$params = array_merge($defaultParams, $params);
		
		return $this->entity->select($params['fields'])
					->wheres($params['search'])
					->orders($params['order_by'])
					->parsePage($params['page'], $params['limit'])
					->get();
	}

    public function getVacationSet()
    {
        $params = [
            'fields' 	=> ['is_transform','conversion_ratio', 'expire_remind_time'],
            'page' 		=> 0,
            'limit' 	=> config('eoffice.pagesize'),
            'order_by' 	=> ['vacation_id'=>'asc']
        ];
        $vacationList = $this->entity->select($params['fields'])
            ->orders($params['order_by'])
            ->parsePage($params['page'], $params['limit'])
            ->get()->toArray();
        return empty($vacationList) ? ['is_transform' => 0, 'conversion_ratio' => 480, 'expire_remind_time' => 10] : $vacationList[0];
    }

	/**
	 * [getVacationCount 获取假期数据条数]
	 *
	 * @author 施奇
	 *
	 * @param  [array]          $params [查询条件]
	 *
	 * @return [int]                    [查询结果]
	 */
	public function getVacationCount($params)
	{
		$search = isset($params['search']) ? $params['search'] : [];

		return $this->entity->wheres($search)->count();
	}

	public function getVacationId($vacationName){
		$vacationId = null;
		if(!empty($vacationName)){
			$vacation =  $this->entity->where(['vacation_name'=>$vacationName])->first();
			$vacation =  empty($vacation)?[]:$vacation->toArray();
			if(!empty($vacation['vacation_id'])) $vacationId= $vacation['vacation_id'];
		}
		return $vacationId;
	}

    public function vacationNameExists($id, $name)
    {
        return $this->entity->where('vacation_name',$name)->where('id','!=', $id)->exists();
    }

    public function getVacationById($id, $withTrashed = false)
    {
        if (empty($id)) {
            return false;
        }
        $query = $this->entity;
        if ($withTrashed) {
            $query = $query->withTrashed();
        }
        return $query->whereIn('vacation_id', $id)->get();
    }

    /**
     * 查询当前所有假期
     * @param bool $withTrashed
     * @return mixed
     */
    public function getAllVacation($withTrashed = false)
    {
        $query = $this->entity;
        if ($withTrashed) {
            $query = $query->withTrashed();
        }
        return $query->get();
    }

    public function getOneRecord($wheres=[]) {
        $query = $this->entity;
        if (!empty($wheres)) {
            $query = $query->wheres($wheres);
        }
        return $query->first();
    }

    public function getYearsHours($is_transform, $conversion_ratio, $vacations)
    {
        $update = [];
        if($is_transform == 0){
            //小时转换天
            if(!empty($vacations)){
                foreach ($vacations as $vacation){
                    if($vacation['days_rule_method'] == 1){
                        if(empty($vacation['hours_rule_detail']) || $vacation['hours_rule_detail']=='[]' || !is_numeric($vacation['days_rule_detail'])){
                            $days = 0;
                        }else{
                            $days = round($vacation['hours_rule_detail'] / $conversion_ratio,4);
                        }
                    }else{
                        $ruleDetail = json_decode($vacation['hours_rule_detail'], true);
                        if(empty($ruleDetail)){
                            $days = '';
                        }else{
                            $newRuleDetail = [];
                            foreach ($ruleDetail as $item){
                                if($item['hours'] == 0){
                                    $item['days'] = 0;
                                    unset($item['hours']);
                                }else{
                                    $item['days'] = round($item['hours'] / $conversion_ratio,4);
                                    unset($item['hours']);
                                }
                                array_push($newRuleDetail,$item);
                            }
                            $days = json_encode($newRuleDetail);
                        }
                    }
                    $update[] = [
                        'where' => ['vacation_id' => $vacation['vacation_id']],
                        'update' => ['days_rule_detail' => $days, 'updated_at' => date('Y-m-d H:i:s',time()+1)]
                    ];
                }
            }
        }else{
            //天转换小时
            if(!empty($vacations)){
                foreach ($vacations as $vacation){
                    if($vacation['days_rule_method'] == 1){
                        if(empty($vacation['days_rule_detail']) || $vacation['days_rule_detail']=='[]'|| !is_numeric($vacation['days_rule_detail'])){
                            $hours = 0;
                        }else{
                            $hours = round($vacation['days_rule_detail'] * $conversion_ratio,2);
                        }
                    }else{
                        $ruleDetail = json_decode($vacation['days_rule_detail'], true);
                        if(empty($ruleDetail)){
                            $hours = '';
                        }else{
                            $newRuleDetail = [];
                            foreach ($ruleDetail as $item){
                                if($item['days'] == 0){
                                    $item['hours'] = 0;
                                    unset($item['days']);
                                }else{
                                    $item['hours'] = round($item['days'] * $conversion_ratio,2);
                                    unset($item['days']);
                                }
                                array_push($newRuleDetail,$item);
                            }
                            $hours = json_encode($newRuleDetail);
                        }
                    }
                    $update[] = [
                        'where' => ['vacation_id' => $vacation['vacation_id']],
                        'update' => ['hours_rule_detail' => $hours, 'updated_at' => date('Y-m-d H:i:s',time()+1)]
                    ];
                }
            }
        }
        return $update;
    }

    public function getHours($is_transform, $data)
    {
        //获取分钟
        if($is_transform == 0){
            if($data['days_rule_method'] == 1){
                if(empty($data['days_rule_detail']) || $data['days_rule_detail']=='[]' || !is_numeric($data['days_rule_detail'])){
                    $hours = 0;
                }else{
                    $hours = round($data['days_rule_detail'] * $data['conversion_ratio'],2);
                }
            }else{
                $ruleDetail = json_decode($data['days_rule_detail'], true);
                if(empty($ruleDetail)){
                    $hours = '';
                }else{
                    $newRuleDetail = [];
                    foreach ($ruleDetail as $item){
                        if($item['days'] == 0){
                            $item['hours'] = 0;
                            unset($item['days']);
                        }else{
                            $item['hours'] = round($item['days'] * $data['conversion_ratio'],2);
                            unset($item['days']);
                        }
                        array_push($newRuleDetail,$item);
                    }
                    $hours = json_encode($newRuleDetail);
                }
            }
            return $hours;
        }else{
            //获取天数
            if($data['days_rule_method'] == 1){
                if(empty($data['days_rule_detail']) || $data['days_rule_detail']=='[]' || !is_numeric($data['days_rule_detail'])){
                    $days = 0;
                }else{
                    $days = round($data['days_rule_detail'] / ($data['conversion_ratio']/60),4);
                }
            }else{
                $ruleDetail = json_decode($data['days_rule_detail'], true);
                if(empty($ruleDetail)){
                    $days = '';
                }else{
                    $newRuleDetail = [];
                    foreach ($ruleDetail as $item){
                        if($item['days'] != 0){
                            $item['days'] = round($item['days'] / ($data['conversion_ratio']/60),4);
                        }
                        array_push($newRuleDetail,$item);
                    }
                    $days = json_encode($newRuleDetail);
                }
            }
            return $days;
        }
    }

    public function getDays($data)
    {
        if($data['days_rule_method'] == 1){
            if(empty($data['days_rule_detail']) || $data['days_rule_detail']=='[]' || !is_numeric($data['days_rule_detail'])){
                $hours = 0;
            }else{
                $hours = round($data['days_rule_detail'] * ($data['conversion_ratio']/60),2);
            }
        }else{
            $ruleDetail = json_decode($data['days_rule_detail'], true);
            if(empty($ruleDetail)){
                $hours = '';
            }else{
                $newRuleDetail = [];
                foreach ($ruleDetail as $item){
                    if($item['days'] != 0){
                        $item['days'] = round($item['days'] * ($data['conversion_ratio']/60),2);
                    }
                    array_push($newRuleDetail,$item);
                }
                $hours = json_encode($newRuleDetail);
            }
        }
        return $hours;
    }

}