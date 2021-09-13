<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowChildFormControlStructureEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程表单控件结构表
 *
 */
class FlowChildFormControlStructureRepository extends BaseRepository
{
    public function __construct(FlowChildFormControlStructureEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取基本信息
     *
     * @method FlowFormControlStructureRepository
     *
     * @param  [type]                             $param [description]
     *
     * @return [type]                                    [description]
     */
    function getFlowFormControlStructure($param)
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
        return $query->get()->toArray();
    }

    function getFlowFormTrashedControl($param){
    	$result = [];
		$data = $this->getFlowFormControlStructureWithTrashed($param);
		if(!empty($data)){
			foreach($data as $item){
				if(!empty($item['deleted_at'])){
					$result[$item['control_id']] = $item['control_id'];
				}
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
