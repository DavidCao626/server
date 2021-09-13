<?php
namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\LinkmanEntity;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use App\EofficeApp\FormModeling\Repositories;
use DB;
use App\EofficeApp\LogCenter\Facades\LogCenter;

class LinkmanRepository extends BaseRepository
{
    // 表名
    const TABLE_NAME = 'customer_linkman';

    const MAX_WHERE_IN = 2000;

    // 主联系人标识值
    const IS_MAIN = 1;

    private $customerRepository;

    public function __construct(LinkmanEntity $entity)
    {
        parent::__construct($entity);
        $this->customerRepository = 'App\EofficeApp\Customer\Repositories\CustomerRepository';
        $this->formModelingRepository = 'App\EofficeApp\FormModeling\Repositories\FormModelingRepository';
    }

    public function getHasPermissionIds(array $ids, array $own)
    {
        return $ids;
    }

    public static function validateInput(array &$data, array $own = [])
    {
        if(isset($data['linkman_name'])){
            $name = $data['linkman_name'] ?? '';
            if(!$name){
                return ['code' => ['linkman_name_empty', 'customer']];
            }
            list($data['linkman_name_py'], $data['linkman_name_zm']) = convert_pinyin($name);
        }

        if(!isset($data['mobile_phone_number'])){
            $data['mobile_phone_number'] = '';
        }
        $customerId = $data['customer_id'] ?? '';
        if ($customerId && !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$customerId], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        
        // 编辑客户时手机号检测
        if(isset($data['linkman_id']) && $data['mobile_phone_number']){
            if($phones = DB::table(self::TABLE_NAME)
                ->where(['customer_id'=>$data['customer_id'],'mobile_phone_number'=>$data['mobile_phone_number']])
                ->where('linkman_id','!=',$data['linkman_id'])->exists()){
                return ['code' => ['repeat_phone', 'customer']];
            };
        }else{
            // 同一客户,相同手机号检测
            if(!empty($data['mobile_phone_number'])){
                if($phones = DB::table(self::TABLE_NAME)->where(['customer_id'=>$data['customer_id'],'mobile_phone_number'=>$data['mobile_phone_number']])->exists()){
                    return ['code' => ['repeat_phone', 'customer']];
                };
            }
        }

        return true;
    }

    public static function validatePermission(array $types, $id, array $own)
    {
        $result = 0;
        $list   = DB::table(self::TABLE_NAME)->select(['linkman_creator', 'customer_id','is_all','user_ids','role_ids','dept_ids'])->where('linkman_id', $id)->first();
        if(!$list){
           return  $result;
        }
        array_map(function ($type) use (&$result, $list, $own) {
            switch ($type) {
                case CustomerRepository::VIEW_MARK:
                    if ($list->linkman_creator == $own['user_id']) {
                        $result = $result | $type;
                    } elseif ($list->customer_id && ($validate = CustomerRepository::validatePermission([$type], [$list->customer_id], $own))) {
                        $result = $result | $type;
                    }
                    break;

                case CustomerRepository::UPDATE_MARK:
                    if ($list->linkman_creator == $own['user_id']) {
                        $result = $result | $type;
                    } elseif ($list->customer_id && $validate = CustomerRepository::validatePermission([$type], [$list->customer_id], $own)) {
                        $result = $result | $type;
                    }
                    break;

                case CustomerRepository::DELETE_MARK:
                    if ($validate = CustomerRepository::validatePermission([$type], [$list->customer_id], $own)) {
                        $result = $result | $type;
                    }
                    break;

                default:
                    # code...
                    break;
            }
        }, $types);
        return $result;
    }

    public static function getPermissionIds(array $types, array $own, array $ids)
    {
        $result = [];
        $query  = DB::table(self::TABLE_NAME)->select(['linkman_id', 'customer_id', 'linkman_creator']);
        if (!empty($ids)) {
            $query = $query->whereIn('linkman_id', $ids);
        }
        $lists = $query->get();
        if ($lists->isEmpty()) {
            return array_pad($result, count($types), []);
        }
        $userId = $own['user_id'] ?? '';
        array_map(function ($type) use (&$result, $own, $ids, $lists, $userId) {
            switch ($type) {
                case CustomerRepository::UPDATE_MARK:
                    $result[$type] = [];
                    // 编辑权限,对客户具有编辑权限 + 创建人
                    $customerIds = [];
                    foreach ($lists as $index => $item) {
                        // 创建人
                        if ($item->linkman_creator == $userId) {
                            $result[$type][] = $item->linkman_id;
                            continue;
                        }
                        if (!isset($customerIds[$item->customer_id])) {
                            $customerIds[$item->customer_id] = [];
                        }
                        $customerIds[$item->customer_id][] = $item->linkman_id;
                    }
                    if (!empty($customerIds)) {
                        // 获取有编辑权限的客户id
                        $updateCustomerIds = CustomerRepository::getUpdateIds($own, array_keys($customerIds));
                        if (!empty($updateCustomerIds)) {
                            foreach ($customerIds as $iCustomerId => $iLinkmanIds) {
                                if (in_array($iCustomerId, $updateCustomerIds)) {
                                    $result[$type] = array_merge($result[$type], $iLinkmanIds);
                                }
                            }
                        }
                    }
                    break;

                default:
                    break;
            }
        }, $types);
        return array_merge($result);
    }

    public static function customerLinkmans(int $customerId)
    {
        $own = own();
        $query = DB::table(self::TABLE_NAME)->select(['linkman_id', 'linkman_name']);
        if($own){
            if($own['dept_id'] || $own['user_id'] || $own['role_id']){
                $query = $query->where(function ($query) use($own,$customerId){
                    $query->where('customer_id',$customerId);
                    $query->where(function ($query) use($own,$customerId){
                        $query->orWhereRaw('FIND_IN_SET(?,user_ids)',[$own['user_id']])->orWhereRaw('FIND_IN_SET(?,dept_ids)',[$own['dept_id']]);
                        if($own['role_id'] && is_array($own['role_id'])){
                            foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw('FIND_IN_SET(?,role_ids)', [$roleId]);
                            }
                        }
                    });

                });
            }
        }
        $query = $query->orWhere(function ($query) use($own,$customerId){
            $query->where('customer_id',$customerId);
            $query->where(function ($query) use($own,$customerId){
                $query->orWhere(['is_all'=>1,'linkman_creator'=>$own['user_id']]);
            });
        });
        return $query->orderby('linkman_id', 'desc')->get();
//        ->where('customer_id', $customerId)->orderby('linkman_id', 'desc')->get();
    }

    public static function customerDetailMenuCount($customerId,$own =null)
    {
        $query = DB::table(self::TABLE_NAME);
        if($own){
            if($own['dept_id'] || $own['user_id'] || $own['role_id']){
                $query = $query->where(function ($query) use($own,$customerId){
                    $query->where('customer_id',$customerId);
                    $query->where(function ($query) use($own,$customerId){
                        $query->orWhereRaw('FIND_IN_SET(?,user_ids)', [$own['user_id']])->orWhereRaw('FIND_IN_SET(?,dept_ids)', [$own['dept_id']]);
                        if($own['role_id'] && is_array($own['role_id'])){
                            foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw('FIND_IN_SET(?,role_ids)',[$roleId]);
                            }
                        }
                    });

                });
            }
        }
        $query = $query->orWhere(function ($query) use($own,$customerId){
            $query->where('customer_id',$customerId);
            $query->where(function ($query) use($own,$customerId){
                $query->orWhere(['is_all'=>1,'linkman_creator'=>$own['user_id']]);
            });
        });

        return $query->count();
    }

    // 客户合并
    public static function mergeToCustomer($targetCustomerId, $customerIds)
    {
        // 主客户联系人列表
        $targetPhones = DB::table(self::TABLE_NAME)->where('customer_id',$targetCustomerId)->where('mobile_phone_number','!=','')->pluck('mobile_phone_number')->toArray();
        // 合并客户联系人列表
        $mergePhones = DB::table(self::TABLE_NAME)->whereIn('customer_id',$customerIds)->where('mobile_phone_number','!=','')->pluck('mobile_phone_number')->toArray();
        // 取交集查看合并的客户联系人
        if($merge = array_intersect($targetPhones,$mergePhones)){
            // 如果有重复的联系人，则删掉
            DB::table(self::TABLE_NAME)->whereIn('mobile_phone_number',$merge)->whereIn('customer_id',$customerIds)->delete();
        }
        return DB::table(self::TABLE_NAME)->whereIn('customer_id', $customerIds)->update([
            'customer_id'  => $targetCustomerId,
            'main_linkman' => 0,
        ]);
    }

    // 设置主联系人
    public static function setMainLinkman(int $customerId, int $linkmanId)
    {
        DB::table(self::TABLE_NAME)->where('customer_id', $customerId)->update(['main_linkman' => 0]);
        DB::table(self::TABLE_NAME)->where('customer_id', $customerId)->where('linkman_id', $linkmanId)->update(['main_linkman' => self::IS_MAIN]);
        return true;
    }

    public function customerBirthdayReminds()
    {
        $monthDate = date("m-d");
        return $this->entity->select(['customer_id', 'linkman_name', 'mobile_phone_number', 'company_phone_number'])->has('linkmanCustomer')->whereRaw("RIGHT(birthday, 5) = '" . $monthDate . "'")->with(['linkmanCustomer' => function ($query) {
            $query->select(['customer_id', 'customer_name', 'customer_manager', 'customer_service_manager']);
        }])->get();
    }

    public static function updateLinkman($linkmanId, $data)
    {
        return DB::table(self::TABLE_NAME)->where('linkman_id', $linkmanId)->update($data);
    }

    public static function getLinkmanByCustomerId($customerId)
    {
        $own = own();
        $query = DB::table(self::TABLE_NAME);
        if($own['dept_id'] || $own['user_id'] || $own['role_id']){
            $query = $query->where(function ($query) use($own,$customerId){
                $query->where('customer_id',$customerId);
                $query->where(function ($query) use($own,$customerId){
                    $query->orWhereRaw('FIND_IN_SET(?,user_ids)', [$own['user_id']])->orWhereRaw('FIND_IN_SET(?,dept_ids)',[$own['dept_id']]);
                    if($own['role_id'] && is_array($own['role_id'])){
                        foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw('FIND_IN_SET(?,role_ids)', [$roleId]);
                        }
                    }
                });

            });
        }
        $query = $query->orWhere(function ($query) use($own,$customerId){
            $query->where('customer_id',$customerId);
            $query->where(function ($query) use($own,$customerId){
                $query->orWhere(['is_all'=>1,'linkman_creator'=>$own['user_id']]);
            });
        });
        return $query->first();
//        return DB::table(self::TABLE_NAME)->where('customer_id', $customerId)->first();
    }

    //获取当前客户下的主联系人
    public static function getMainLinkMans($customer_ids){
        return DB::table(self::TABLE_NAME)->where(['customer_id'=>$customer_ids,'main_linkman'=>1])->first();
    }

    // 获取对应权限下的联系人
    public static function getViewsLinkmanIds($params,$own,$ids = []){
        $query = DB::table(self::TABLE_NAME);
        if(isset($params['search'])){
            if(!isset($params['search']['birthday'])){
                $query = app('App\EofficeApp\System\CustomFields\Repositories\FieldsRepository')->wheres($query, $params['search']);
            }
        }
        if($ids){
            $query = self::tempTableJoin($query, $ids);
        }
        $query = $query->where(function ($query) use($own){
            $query->orWhere(['is_all'=>1,'linkman_creator'=>$own['user_id']]);
            if($own['dept_id'] || $own['user_id'] || $own['role_id']){
                $query->orWhere(function ($query) use($own){
                    $query->orWhereRaw('FIND_IN_SET(?,user_ids)', [$own['user_id']])->orWhereRaw('FIND_IN_SET(?,dept_ids)',[$own['dept_id']]);
                    if($own['role_id'] && is_array($own['role_id'])){
                        foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw('FIND_IN_SET(?,role_ids)',[$roleId]);
                        }
                    }
                });
            }
        });

        return $query->pluck('linkman_id')->toArray();
    }

    // 获取当前客户下的联系人
    public static function getCustomerDetailLinkMan($customerId,$own){
        $query = DB::table(self::TABLE_NAME)->where('customer_id',$customerId);
        $query = $query->where(function ($query) use($own){
            $query->orWhere(['is_all'=>1,'linkman_creator'=>$own['user_id']]);
            if($own['dept_id'] || $own['user_id'] || $own['role_id']){
                $query = $query->orWhere(function ($query) use($own){
                    $query->orWhereRaw('FIND_IN_SET(?,user_ids)', [$own['user_id']])->orWhereRaw('FIND_IN_SET(?,dept_ids)',[$own['dept_id']]);
                    if($own['role_id'] && is_array($own['role_id'])){
                        foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw('FIND_IN_SET(?,role_ids)', [$roleId]);
                        }
                    }
                });
            }
        });

        return $query->pluck('linkman_id')->toArray();
    }

    public static function checkLinkmanPrivilege($data,$own){
        if($data){
            if($data->is_all == 1){
                return true;
            }
            if(in_array($own['user_id'],explode(',',$data->user_ids)) || in_array($own['dept_id'],explode(',',$data->dept_ids)) || array_intersect($own['role_id'],explode(',',$data->role_ids))){
                return true;
            }
        }
        return false;
    }

    // 保存日志
    public static function saveLogs($id, $content, $userId,$identify,$title)
    {
        logCenter::info($identify,[
            'creator' => $userId,
            'content' => $content,
            'relation_id' => $id,
            'relation_table' => 'customer_linkman',
            'relation_title' => $title,
        ]);
//        $data = [
//            'log_creator' => $userId,
//            'log_type' => 'customer_linkman',
//            'log_relation_table' => 'customer_linkman',
//            'log_relation_id' => $id,
//            'log_content' => $content,
//        ];
//        add_system_log($data);
    }

    public static function getCustomFields($search,$fields = null){
        $query = DB::table('custom_fields_table')->where($search);
        if($fields){
            $query = $query->value($fields);
        }else{
            $query = $query->first();
        }
        return $query;
    }

    public static function getLists(array $linkmanIds, array $fields = [])
    {
        $query = DB::table(self::TABLE_NAME);
        if (!empty($fields)) {
            $query = $query->select($fields);
        }

        return $query->whereIn('linkman_id', $linkmanIds)->whereNull('deleted_at')->get();
    }
    // 处理公共排序
    public static function parseOrder($table, $param){
        if (isset($param['order_by']) && !empty($param['order_by'])) {
            $orderBy = $param['order_by'];
            $orderBy = app('App\EofficeApp\FormModeling\Repositories\FormModelingRepository')->multiOrders($orderBy,$table);
        } else {
            //获取默认排序
            if (isset($param['defaultOrder']) && !empty($param['defaultOrder'])) {
                $orderBy = app('App\EofficeApp\FormModeling\Repositories\FormModelingRepository')->multiOrders($param['defaultOrder'],$table,'default');
            }else {
                $filterConfig = config('customfields.dataListRepositoryOrder.' . $table);
                if ($filterConfig) {
                    $orderBy = $filterConfig;
                } else {
                    $orderBy = [$table . '.created_at' => 'desc'];
                }
            }
        }
        return $orderBy;
    }

    public static function orders($query, $orders, $numberFields = [])
    {
        if (!empty($orders)) {
            foreach ($orders as $field => $order) {
                if (in_array($field, $numberFields)) {
                    //处理文本框数值排序
                    $query = $query->orderByRaw("$field+0  $order");
                } else {
                    $query = $query->orderBy($field, $order);
                }
            }
        }
        return $query;
    }

    // sql 太长导致mysql gone away
    public static function tempTableJoin($query, &$searchs)
    {
        $whereValues = $searchs ?? [];
        if (!empty($whereValues) && count($whereValues) > self::MAX_WHERE_IN) {
            $tableName = 'customer_'.rand() . uniqid();
            DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` int(6) NOT NULL,PRIMARY KEY (`data_id`))");
            $tempIds = array_chunk($whereValues, self::MAX_WHERE_IN, true);
            foreach ($tempIds as $key => $item) {
                $ids      = implode("),(", $item);
                $tSql = "insert into {$tableName} (data_id) values ({$ids});";
                DB::insert($tSql);
            }
            $query = $query->join("$tableName", $tableName . ".data_id", '=', 'customer_id');
        }else if($whereValues){
            $query = $query->whereIn('customer_id',$whereValues);
        }
        return $query;
    }


    public static function checkBinding($where){
        return DB::table('customer_linkman_relation_wechat')->where($where)->first();
    }


    public static function bindData($data){
        return DB::table('customer_linkman_relation_wechat')->insert($data);
    }


    public static function cancelBinding($where){
        return DB::table('customer_linkman_relation_wechat')->where($where)->delete();
    }

    public static function getBindUserId($external_userid){

        return DB::table('customer_linkman_relation_wechat')->where('external_contact_user_id',$external_userid)->first();
    }

    public static function getList($params,$customer_id){

        $query = DB::table(self::TABLE_NAME);
        if(isset($params['linkman_name'])){
            $query = $query->where('linkman_name','like','%'.$params['linkman_name'].'%');
        }
        if(isset($params['mobile_phone_number'])){
            $query = $query->where('mobile_phone_number','like','%'.$params['mobile_phone_number'].'%');
        }
        if($customer_id){
            $query = self::cacheTableJoin($query,$customer_id);
        }
        $ids = $query->pluck('linkman_id')->toArray();
        if(!$ids){
           return [];
        }
        return DB::table('customer_linkman_relation_wechat')->whereIn('linkman_id',$ids)->pluck('external_contact_user_id')->toarray();

    }

    public static function cacheTableJoin($query, $customer_id)
    {
        if (count($customer_id) > 2000) {
            $tableName = 'customer_linkman'.rand() . uniqid();
            DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` int(6) NOT NULL,PRIMARY KEY (`data_id`))");
            $tempIds = array_chunk($customer_id, 2000, true);
            foreach ($tempIds as $key => $item) {
                $ids      = implode("),(", $item);
                $tSql = "insert into {$tableName} (data_id) values ({$ids});";
                DB::insert($tSql);
            }
            $query = $query->join("$tableName", $tableName . ".data_id", '=', 'customer_id');

        }else {
            $query = $query->whereIn('customer_id',$customer_id);
        }
        return $query;
    }

    public static function getRelationData($external_userid){
        $query = DB::table('customer_linkman_relation_wechat as a')->select('b.linkman_id','b.customer_id','b.mobile_phone_number','a.external_contact_user_id')->whereIn('a.external_contact_user_id',$external_userid)
            ->leftJoin('customer_linkman as b','b.linkman_id','=','a.linkman_id')->get()->toArray();
        return $query;
    }

}
