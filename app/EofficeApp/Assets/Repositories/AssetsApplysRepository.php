<?php

namespace App\EofficeApp\Assets\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Assets\Entities\AssetsApplysEntity;
use DB;

/**
 * 资产类型列表
 *
 * @author zw
 *
 * @since  2018-03-30 创建
 */
class AssetsApplysRepository extends BaseRepository
{

    public function __construct(AssetsApplysEntity $entity)
    {
        parent::__construct($entity);
    }



    /**
     * 申请使用Total
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function applysListsTotal($params){

        if(isset($params['senior']) && $params['senior']){
            $search['search'] = $params['senior'];
        }
        $query = $this->entity->wheres($params['search']);
        if(isset($params['search']['return_at'])){
            $query = $query->where('return_at','<>','0000-00-00');
        }

        $query = $query->with(['assets'=>function($query){
            $query->select('id','assets_name','assets_code','managers');
        }]);
        if(isset($search) && $search){
            $query = $query->whereHas('assets', function($query) use (&$search)
            {
                $query->wheres($search['search']);
            });
        }
        $query = $query->with(['assets_type'=>function($query){
            $query->select('id','type_name');
        }]);
        return $query->count();
    }

    /**
     * 申请记录
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function applysLists($params){
        $default = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['assets_applys.created_at' => 'desc'],
        ];
        $params = array_merge($default, array_filter($params));
        if(isset($params['senior']) && $params['senior']){
            $search['search'] = $params['senior'];
        }
        $query = $this->entity->select($params['fields'])->wheres($params['search']);

        if(isset($params['search']['return_at'])){
            $query = $query->where('return_at','<>','0000-00-00');
        }
        $query = $query->with(['assets'=>function($query){
            $query->select('id','assets_name','assets_code','managers','type');
        }]);
        if(isset($search) && $search){
            $query = $query->whereHas('assets', function($query) use (&$search)
            {
                $query->wheres($search['search']);
            });
        }
        $query = $query->with(['assets_type'=>function($query){
            $query->select('id','type_name');
        }]);
        $query = $query->orders($params['order_by'])->parsePage($params['page'], $params['limit'])->get()->toArray();
        return $query;
    }

    /**
     * 申请记录详情
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-06-06
     */
    public function ApplysDetail($id){
        $query = $this->entity->select('*');
        $query = $query->where('assets_applys.id',$id)->with(['assets'=>function($query){
            $query->select('id','assets_name');
        }]);
        $query = $query->with(['assets'=>function($query){
            $query->select('id','assets_name','managers','assets_code','price','product_at','user_time','residual_amount','remark','status');
        }]);
        $query->with(['applyBelongsToUserSystemInfo' => function($query) {
            $query->with(['userSystemInfoBelongsToDepartment' => function($query) {
                $query->select('dept_id', 'dept_name');
            }]);
        }]);
        $query->with(['applyBelongsToUser' => function($query) {
            $query->select('*');
        }]);
        return $query->get()->first();
    }


    /**
     * 盘点清单Total
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function viewListTotal($params){
        $start_at = strtotime($params['start_at']) ? $params['start_at'] : 0;
        $end_at = strtotime($params['end_at']) ? $params['end_at'] : date('Y-m-d H:i:s',time());
        $query = $this->entity->select('assets_id')->where(['type'=>$params['type'],'apply_type'=>'apply'])
            ->where('apply_user',$params['apply_user'])
            ->where('managers','like','%'.$params['managers'].'%')
            ->with(['assets'=>function($query) use(&$start_at,&$end_at){
                $query->select('id','assets_name','assets_code','created_at','status','type')
                    ->whereBetween('created_at',[$start_at,$end_at]);
            }]);
        return $query->count();
    }

    /**
     * 盘点清单count
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-06-29
     */
    public function inViewListTotal($params){
        $count = $this->inViewList($params);
        return count($count);
    }

    /**
     * 盘点清单
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-06-29
     */
    public function inViewList($params){
        $default = [
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['created_at' => 'desc'],
        ];
        $start_at = strtotime($params['start_at']) ? $params['start_at'] : 0;
        $end_at = strtotime($params['end_at']) ? $params['end_at'] : date('Y-m-d H:i:s',time());
        $query = $this->entity->select('*')->whereBetween('created_at',[$start_at,$end_at]);
        if(isset($params['apply_user']) && $params['apply_user']){
            $query = $query->where('apply_user',$params['apply_user']);
        }
        if($params['managers']){
            $query = $query->where('apply_user','like','%'.$params['apply_user'].'%');
        }
        if($params['type']){
            $query = $query->where('type',$params['type']);
        }
        return $query->orders($default['order_by'])->parsePage($default['page'], $default['limit'])->get()->toArray();

//        $query = $this->entity->select('assets_id','type')->where(['type'=>$params['type'],'apply_type'=>'apply']);
//        if(isset($params['apply_user']) && $params['apply_user']){
//            $query = $query->where('apply_user',$params['apply_user']);
//        }
//        if($params['managers']){
//            $query = $query->where('managers','like','%'.$params['managers'].'%');
//        }
//        $query = $query->with(['assets'=>function($query) use(&$start_at,&$end_at){
//            $query->select('id','assets_name','assets_code','created_at','status','type','is_inventory')
//                ->whereBetween('created_at',[$start_at,$end_at]);
//        }]);
//
//        $query = $query->with(['assets_type'=>function($query){
//                $query->select('id','type_name');
//            }]);


//        return $query->orders($default['order_by'])->parsePage($default['page'], $default['limit'])->get()->toArray();
    }


    public function existsApplyData($id){
        return $this->entity->where('assets_id',$id)->exists();
    }


}