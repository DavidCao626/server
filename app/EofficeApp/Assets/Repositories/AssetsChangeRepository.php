<?php

namespace App\EofficeApp\Assets\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Assets\Entities\AssetsChangeEntity;

class AssetsChangeRepository extends BaseRepository
{

    public function __construct(AssetsChangeEntity $entity)
    {
        parent::__construct($entity);
    }


    /**
     * 获取变更记录详情
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function getChangeData($id){
        return $this->entity->where('assets_id',$id)->first();
    }

    /**
     * 变更记录total
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function changeListTotal($params){
        $query = $this->entity->wheres($params['search']);
        if(isset($params['senior']) && $params['senior']){
            $query = $query->whereHas('assets', function($query) use (&$params)
            {
                $query->wheres($params['senior']);
            });
        }
        return $query->count();
    }

    /**
     * 变更记录list
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function changeList($data){
        $default = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['created_at' => 'desc'],
        ];
        $params = array_merge($default, array_filter($data));
        $query = $this->entity->select('id','assets_id','created_at')->wheres($params['search']);
        $query = $query->with(['assets'=>function($query){
            $query->select('*');
        }]);

        if(isset($params['senior']) && $params['senior']){
            $query = $query->whereHas('assets', function($query) use (&$params)
            {
                $query->wheres($params['senior']);
            });
        }
        $query = $query->orders($params['order_by'])->parsePage($params['page'], $params['limit'])->get()->toArray();
        return $query;
    }

    public function existsChangeData($id){
        return $this->entity->where('assets_id',$id)->exists();
    }
}