<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowChildFormControlDropEntity;
use App\EofficeApp\Base\BaseRepository;

class FlowChildFormControlDropRepository extends BaseRepository
{
    public function __construct(FlowChildFormControlDropEntity $entity) {
        parent::__construct($entity);
    }

    function getFlowFormControlStructure($param)
    {
        $default = array(
            'fields' => ['*'],
            'search' => [],
            //'order_by'  => ['sort' => 'asc']
        );
        $param = array_merge($default, $param);
        $query = $this->entity
                      ->select($param['fields'])
                      ->wheres($param['search']);
                      //->orders($param['order_by']);
        return $query->get()->toArray();
    }

    function getFlowFormTrashedControl($param){
    	$result = [];
		$data = $this->getFlowFormControlStructure($param);
		if(!empty($data)){
			foreach($data as $item){
				$result[$item['control_id']] = $item['control_id'];
			}
		}
		return $result;
    }

    function getFlowFormControlStructureWithTrashed($param)
    {
    	$default = array(
    			'fields' => ['*'],
    			'search' => [],
    			'order_by'  => ['sort' => 'asc']
    	);
    	$param = array_merge($default, $param);
    	$query = $this->entity
    	->select($param['fields'])
    	->wheres($param['search'])
    	->orders($param['order_by']);
    	return $query->withTrashed()->get()->toArray();
    }
}
