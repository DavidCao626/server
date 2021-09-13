<?php

namespace App\EofficeApp\Vote\Repositories;

use App\EofficeApp\Vote\Entities\VoteDeptEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;

/**
 * 调查表知识库
 *
 * @author 史瑶
 *
 * @since  2017-06-21 创建
 */
class VoteDeptRepository extends BaseRepository {

    public function __construct(VoteDeptEntity $entity) {
        parent::__construct($entity);
    }
    /** @var int 默认列表条数 */
    private $limit      = 10;
    
    /** @var int 默认列表页 */
    private $page       = 0;
    
    function getinfo($param) {
    	$defaultParams = [
    	    'fields' => ['*'],
    	    'search' => []
    	];

    	$params = array_merge($defaultParams, $param);

    	$query = $this->entity->select($params['fields'])
    	        ->Wheres($params['search'])->groupBy('vote_id');
    	return $query->get()->toArray();
    }
}
