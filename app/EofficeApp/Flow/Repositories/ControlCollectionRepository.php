<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\ControlCollectionEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 控件收藏 
 *
 * @author wz
 *
 * @since  2018-11-02 创建
 */
class ControlCollectionRepository extends BaseRepository
{
    public function __construct(ControlCollectionEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getControlCollectionList($param) {
    	 $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['id'=>'asc'],
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                      ->select($param['fields'])
                      ->wheres($param['search'])
                      ->orders($param['order_by']);

        // 分组参数
        if(isset($param['groupBy'])) {
            $query = $query->groupBy($param['groupBy']);
        }
        // 解析原生 where
        if(isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 解析原生 select
        if(isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        }
        return $query->get();
    }

}
