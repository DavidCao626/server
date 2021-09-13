<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowOutsendEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程数据外发数据表知识库
 *
 * @author 史瑶
 *
 * @since  2016-01-11 创建
 */
class FlowOutsendRepository extends BaseRepository
{
    public function __construct(FlowOutsendEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取数据
     *
     * @method getList
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    function getList($id)
    {
        return $this->entity
                    ->where("node_id",$id)
                    ->orderBy("id", "asc")
                    ->get();
    }
    /**
     * 获取数据
     *
     * @method getList
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    function getLists($param)
    {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => 10,
            'search'    => [],
            'order_by'  => ['id'=>'asc'],
        ];
        $param = array_merge($default, $param);

        $query = $this->entity->select($param['fields']);
        if(!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        $query = $query->orders($param['order_by']);

        if($param['page']>0) {
            $query = $query->forPage($param['page'], $param['limit']);
        }
        return $query->get()->toArray();
    }
    
    function insertGetId($param = [])
    {
        $query = $this->entity->insertGetId($param);
        return $query;
    }
}
