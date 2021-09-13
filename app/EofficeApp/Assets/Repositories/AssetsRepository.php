<?php

namespace App\EofficeApp\Assets\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Assets\Entities\AssetsEntity;
use DB;

/**
 * 资产类型列表
 *
 * @author zw
 *
 * @since  2018-03-30 创建
 */
class AssetsRepository extends BaseRepository
{

    public function __construct(AssetsEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取资产入库列表列表
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function assetsListsTotal($params){
        $query = $this->entity;
        $deptId = '';
        $roleIds = '';
        $userId = '';
        if(isset($params['search']['dept']) && $params['search']['dept']){
            $deptId = $params['search']['dept'][0];
            unset($params['search']['dept']);
        }

        if(isset($params['search']['role']) && $params['search']['role']){
            $roleIds = $params['search']['role'][0];
            unset($params['search']['role']);
        }

        if(isset($params['search']['users']) && $params['search']['users']){
            $userId = $params['search']['users'][0];
            unset($params['search']['users']);
        }
        if($deptId || $roleIds || $userId){
            $query = $query->where(function ($query) use($deptId,$roleIds,$userId){
                $query->orWhereRaw("FIND_IN_SET(?, users)", [$userId]);
                $query->orWhereRaw("FIND_IN_SET(?, dept)", [$deptId]);
                if($roleIds && is_array($roleIds)){
                    foreach($roleIds as $roleId){
                        $query->orWhereRaw("FIND_IN_SET(?, role)", [$roleId]);
                    }
                }
            });
        }
        $query = $query->wheres($params['search']);
        //当is_all = 1时 查询处理
        if(isset($params['orSearch']) && !empty($params['orSearch'])) {
            $orSearch = $params['orSearch'];
            if(is_string($params['orSearch'])){
                $orSearch = json_decode($params['orSearch'], true);
                if(isset($params['search']['type']) && $params['search']['type']){
                    $orSearch['type'] = $params['search']['type'];
                }
                if(isset($params['search']['assets_name']) && $params['search']['assets_name']){
                    $orSearch['assets_name'] = $params['search']['assets_name'];
                }
                if(isset($params['search']['id']) && $params['search']['id']){
                    $orSearch['id'] = $params['search']['id'];
                }
            }
            $query = $query->orWhere(function ($query) use ($orSearch){
                $query->Wheres($orSearch);
            });
        }
        return $query->wheres($params['search'])->count();
    }

    public function assetsLists($data){
        $default = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['created_at' => 'desc'],
        ];
        $params = array_merge($default, array_filter($data));
        $deptId = '';
        $roleIds = '';
        $userId = '';
        if(isset($params['search']['dept']) && $params['search']['dept']){
            $deptId = $params['search']['dept'][0];
            unset($params['search']['dept']);
        }

        if(isset($params['search']['role']) && $params['search']['role']){
            $roleIds = $params['search']['role'][0];
            unset($params['search']['role']);
        }

        if(isset($params['search']['users']) && $params['search']['users']){
            $userId = $params['search']['users'][0];
            unset($params['search']['users']);
        }

        $query = $this->entity->select($params['fields']);
        if($deptId || $roleIds || $userId){
            $query = $query->where(function ($query) use($deptId,$roleIds,$userId){
                $query->orWhereRaw("FIND_IN_SET(?, users)", [$userId]);
                $query->orWhereRaw("FIND_IN_SET(?, dept)", [$deptId]);
                if($roleIds && is_array($roleIds)){
                    foreach($roleIds as $roleId){
                        $query->orWhereRaw("FIND_IN_SET(?, role)", [$roleId]);
                    }
                }
            });
        }

        $query = $query->wheres($params['search']);
        $query = $query->with(['assets_type'=>function($query){
            $query->select('id','type_name');
        }]);
        //当is_all = 1时 查询处理
        if(isset($params['orSearch']) && !empty($params['orSearch'])) {
            $orSearch = $params['orSearch'];
            if(is_string($params['orSearch'])){
                $orSearch = json_decode($params['orSearch'], true);
                if(isset($params['search']['type']) && $params['search']['type']){
                    $orSearch['type'] = $params['search']['type'];
                }
                if(isset($params['search']['assets_name']) && $params['search']['assets_name']){
                    $orSearch['assets_name'] = $params['search']['assets_name'];
                }
                if(isset($params['search']['id']) && $params['search']['id']){
                    $orSearch['id'] = $params['search']['id'];
                }
            }
            $query = $query->orWhere(function ($query) use ($orSearch){
                $query->Wheres($orSearch);
            });
        }
        $res = $query->orders($params['order_by'])->parsePage($params['page'], $params['limit'])->get()->toArray();
        return $res;
    }


    /**
     * 门户查询列表
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function portalLists($data){
        $default = [
            'fields'   => ['id', 'assets_name', 'price','assets_code','managers','created_at','status','product_at','operator_id','type'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['created_at' => 'desc'],
        ];
        $params = array_merge($default, array_filter($data));
        $query = $this->entity->select($params['fields'])->wheres($params['search']);
        $query = $query->with(['assets_type'=>function($query){
            $query->select('id','type_name');
        }]);
        return $query->orders($params['order_by'])->parsePage($params['page'], $params['limit'])->get()->toArray();
    }


    /**
     * 门户查询列表total
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function portalListsTotal($data){
        return $this->entity->select('*')->wheres($data['search'])->count();
    }

    /**
     * 资产详情
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function assetsDetail($id){
        return $this->entity->find($id);
    }

    /**
     * 资产状态变更
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function change_status($where,$updateData){
        return $this->entity->where($where)->update($updateData);
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
     * @since  2018-03-29
     */
    public function viewList($params){
        $default = [
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['created_at' => 'desc'],
        ];

        $query = $this->entity->select('*')->where('type',$params['type']);
        $start_at = strtotime($params['start_at']) ? $params['start_at'] : 0;
        $end_at = strtotime($params['end_at']) ? $params['end_at'] : date('Y-m-d H:i:s',time());
        $query = $query->whereBetween('created_at',[$start_at,$end_at]);
        if($params['managers']){
            $query = $query->where('managers','like','%'.$params['managers'].'%');
        }
        if($params['receive_user']){
            $query = $query->where('user','like','%'.$params['receive_user'].'%');
        }
        return $query->orders($default['order_by'])->parsePage($default['page'], $default['limit'])->get()->toArray();
    }


    /**
     * 对账表total
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-06-19
     */
    public function accountTotal($params){
        return $this->entity->wheres($params['search'])->count();
    }

    /**
     * 对账表列表
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-06-19
     */
    public function accountlists($data){
        $default = [
            'fields'   => ['id', 'assets_name', 'price','expire_at','assets_code','residual_amount','created_at','status','product_at','type','updated_at','user_time'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['created_at' => 'desc'],
        ];
        $params = array_merge($default, array_filter($data));
        $query = $this->entity->select($params['fields'])->wheres($params['search']);
        $query = $query->with(['assets_applys'=>function($query){
            $query->select('created_at','assets_id')->where('apply_type','retiring');
        }]);
        return $query->orders($default['order_by'])->parsePage($params['page'], $params['limit'])->get()->toArray();
    }


    public function accountDetail($id){
        $query = $this->entity->select('*')->where('id',$id);
        $query = $query->with(['assets_applys'=>function($query){
            $query->select('created_at','assets_id')->where('apply_type','retiring');
        }]);
        return $query->first();
    }


    /**
     * 资产汇总total
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-06-19
     */
    public function summaryTotal($params){
        return $this->entity->wheres($params['search'])->count();
    }


    /**
     * 资产汇总list
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-06-19
     */
    public function summaryLists($data){
        $default = [
            'fields'   => ['id', 'type','price','residual_amount','product_at','user_time'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['created_at' => 'desc'],
        ];
        $params = array_merge($default, array_filter($data));
//        $query = $this->entity->selectRaw('count(*) as total,type,sum(price) as allprice,assets_name,type,price,created_at,product_at,user_time');
        $result = $this->entity->select($params['fields'])->wheres($params['search'])->get()->toArray();
        return $result;
    }

    /**
     * 资产详情
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-06-19
     */
    public function getAssetsData($where){
        return $this->entity->where($where)->first();
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
    public function inViewList($data){
        $default = [
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['created_at' => 'desc'],
        ];
        $depts = [];
        $start_at = $data['search']['start_at'] ? date('Y-m-d 00:00:00',strtotime($data['search']['start_at'])) : 0;
        $end_at = $data['search']['end_at'] ? date('Y-m-d 23:59:59',strtotime($data['search']['end_at'])) : date('Y-m-d 23:59:59',time());
        $param['search'] = ['created_at'=>[[$start_at,$end_at],'between']];

        $param['search'] = [
            'created_at'=>[[$start_at,$end_at],'between'],
            'managers'=>[$data['search']['managers'],'like'],
            'type'=>[$data['search']['type'],'='],
            'status'=>[[0,1,2,4],'in'],
            'change_user'=>[$data['search']['apply_user'],'='],
        ];
        if($data['search']['depts']){
            $depts = $data['search']['depts'];
        }
        if($data['search']['managers'] == ''){
            unset($param['search']['managers']);
        }
        if($data['search']['type'] == ''){
            unset($param['search']['type']);
        }
        if($data['search']['apply_user'] == ''){
            unset($param['search']['change_user']);
        }else{
            $param['search']['status'] = [[1,2,4],'in'];
        }
        $query = $this->entity->select('id','assets_name','assets_code','product_at','price',
            'user_time','managers','created_at','type','status','change_user')->wheres($param['search']);
        if($depts){
            $query->whereIn('change_user',$depts) ;
        }
        return $query->orders($default['order_by'])->get()->toArray();
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
    public function inViewListTotal($data){
        $start_at = strtotime($data['search']['start_at']) ? $data['search']['start_at'] : 0;
        $end_at = strtotime($data['search']['end_at']) ? $data['search']['end_at'] : date('Y-m-d H:i:s',time());
        $param['search'] = ['created_at'=>[[$start_at,$end_at],'between']];

        $param['search'] = [
            'created_at'=>[[$start_at,$end_at],'between'],
            'managers'=>[$data['search']['managers'],'like'],
            'type'=>[$data['search']['type'],'='],
        ];
        if($data['search']['managers'] == ''){
            unset($param['search']['managers']);
        }
        if($data['search']['type'] == ''){
            unset($param['search']['type']);
        }

        return $this->entity->wheres($param['search'])->count();
    }

    /**
     * 获取资产最后一条数据
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function assetsLatest(){
        return $this->entity->withTrashed()->latest('id')->value('id');
    }

    /**
     * 系统数据下拉total
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function selectDateTotal($params){
        $query = $this->entity;
        $deptId = '';
        $roleIds = '';
        $userId = '';
        $multiSearchs = [];
        if (isset($params['search']['multiSearch'])) {
            $multiSearchs = $params['search']['multiSearch'];
            unset($params['search']['multiSearch']);
            $query = $query->multiWheres($multiSearchs);
        }
        if(isset($params['search']['dept']) && $params['search']['dept']){
            $deptId = $params['search']['dept'][0];
            unset($params['search']['dept']);
        }

        if(isset($params['search']['role']) && $params['search']['role']){
            $roleIds = $params['search']['role'][0];
            unset($params['search']['role']);
        }

        if(isset($params['search']['users']) && $params['search']['users']){
            $userId = $params['search']['users'][0];
            unset($params['search']['users']);
        }
        $query = $query->wheres($params['search']);

        $query = $query->where(function ($query) use($deptId,$roleIds,$userId){
            if($deptId || $roleIds || $userId){
                $query->orWhere(['is_all'=>1]);
                $query->orWhere(function ($query) use($deptId,$roleIds,$userId){
                    $query->orWhereRaw("FIND_IN_SET(?, users)", [$userId]);
                    $query->orWhereRaw("FIND_IN_SET(?, dept)", [$deptId]);
                    if($roleIds && is_array($roleIds)){
                        foreach($roleIds as $roleId){
                            $query->orWhereRaw("FIND_IN_SET(?, role)", [$roleId]);
                        }
                    }
                });
            }
        });


        //当is_all = 1时 查询处理
//        if(isset($params['orSearch']) && !empty($params['orSearch'])) {
//            $orSearch = $params['orSearch'];
//            if(isset($params['search']['type']) && $params['search']['type']){
//                $orSearch['type'] = $params['search']['type'];
//            }
//            if(isset($params['search']['assets_name']) && $params['search']['assets_name']){
//                $orSearch['assets_name'] = $params['search']['assets_name'];
//            }
//            $query = $query->orWhere(function ($query) use ($orSearch,$multiSearchs){
//                $query->where(['is_all'=>1,'status'=>0]);
//                if($multiSearchs){
//                    $query->where(function ($query) use($multiSearchs){
//                        $query->multiWheres($multiSearchs);
//                    });
//                }
//            });
//        }
        return $query->count();
    }

    /**
     * 系统数据下拉list
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function selectDateLists($data){
        $default = [
            'fields'   => ['id', 'assets_name','type','assets_code','created_at','product_at','status'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['created_at' => 'desc'],
        ];
        $params = array_merge($default, array_filter($data));
        $query = $this->entity->select($params['fields']);
        $deptId = '';
        $roleIds = '';
        $userId = '';
        $multiSearchs = [];
        if (isset($params['search']['multiSearch'])) {
            $multiSearchs = $params['search']['multiSearch'];
            unset($params['search']['multiSearch']);
            $query = $query->multiWheres($multiSearchs);
        }

        if(isset($params['search']['dept']) && $params['search']['dept']){
            $deptId = $params['search']['dept'][0];
            unset($params['search']['dept']);
        }

        if(isset($params['search']['role']) && $params['search']['role']){
            $roleIds = $params['search']['role'][0];
            unset($params['search']['role']);
        }

        if(isset($params['search']['users']) && $params['search']['users']){
            $userId = $params['search']['users'][0];
            unset($params['search']['users']);
        }
        $query = $query->wheres($params['search']);
        $query = $query->where(function ($query) use($deptId,$roleIds,$userId){
            if($deptId || $roleIds || $userId){
                $query->orWhere(['is_all'=>1]);
                $query->orWhere(function ($query) use($deptId,$roleIds,$userId){
                    $query->orWhereRaw("FIND_IN_SET(?, users)", [$userId]);
                    $query->orWhereRaw("FIND_IN_SET(?, dept)", [$deptId]);
                    if($roleIds && is_array($roleIds)){
                        foreach($roleIds as $roleId){
                            $query->orWhereRaw("FIND_IN_SET(?, role)", [$roleId]);
                        }
                    }
                });
            }
        });


//        //当is_all = 1时 查询处理
//        if(isset($params['orSearch']) && !empty($params['orSearch'])) {
//            $orSearch = $params['orSearch'];
//            if(isset($params['search']['type']) && $params['search']['type']){
//                $orSearch['type'] = $params['search']['type'];
//            }
//            if(isset($params['search']['assets_name']) && $params['search']['assets_name']){
//                $orSearch['assets_name'] = $params['search']['assets_name'];
//            }
//            $query = $query->orWhere(function ($query) use ($orSearch,$multiSearchs){
//                $query->where(['is_all'=>1,'status'=>0]);
//                if($multiSearchs){
//                    $query->where(function ($query) use($multiSearchs){
//                        $query->multiWheres($multiSearchs);
//                    });
//                }
//            });
//        }
        return $query->orders($params['order_by'])->parsePage($params['page'], $params['limit'])->get()->toArray();
    }


    public static function getSignleFields($fields,$search){
        return DB::table('assets')->where($search)->value($fields);
    }

    public function getDetailByCode($search){
        return $this->entity->where($search)->first();
    }
}