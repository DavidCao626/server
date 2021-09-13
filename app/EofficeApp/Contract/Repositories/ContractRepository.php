<?php
namespace App\EofficeApp\Contract\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Contract\Entities\ContractEntity;
use DB;

/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractRepository extends BaseRepository
{

    const TABLE_NAME = 'contract_t';

    public function __construct(ContractEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 列表
     * @param  array $param 查找条件
     * @return object
     */
    public function getContractLists($param)
    {
        $default = array(
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['id' => 'asc'],
            'search'   => [],
        );

        $param = array_merge($default, $param);
        $query = $this->entity->wheres($param['search'])
            ->select($param['fields'])
            ->with('users')
            ->parsePage($param['page'], $param['limit'])
            ->orders($param['order_by']);

        return $query->get();
    }

    /**
     * 查询合同
     * @param  int $id 合同id
     * @return object
     */
    public function getContractDetail($id)
    {
        return $this->entity->with(['orders' => function ($query) {
            $query->select(['id', 'contract_t_id', 'product_id', 'shipping_date', 'number', 'run_id', 'remarks'])->with('product');
        }])->with(['projects' => function ($query) {
            $query->select(['id', 'contract_t_id', 'type', 'money', 'pay_way', 'pay_account', 'pay_time', 'run_id', 'remarks', 'pay_type', 'invoice_time']);
        }])->with(['flows' => function ($query) {
            $query->select(['id', 'contract_t_id', 'run_id', 'run_name']);
        }])->with(['reminds' => function ($query) {
            $query->select(['id', 'contract_t_id', 'user_name', 'contract_t_remind.user_id', 'remind_date', 'content', 'remarks'])->leftJoin('user', 'user.user_id', '=', 'contract_t_remind.user_id');
        }])->find($id);
    }
    /**
     * 根据合同ID获取合同信息
     *
     * @param type $contractIds
     * @param type $fields
     * @param type $withTrashed
     * @return type
     */
    public function getContractsByIds($contractIds, $fields = ['*'], $withTrashed = false)
    {
        $query = $this->entity->select($fields)->whereIn('id', $contractIds);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->get();
    }

    public function getChildLists($id)
    {
        return $this->entity->select(['id', 'title', 'number', 'money','type_id'])->where('main_id', $id)->get();
    }

    public function refreshChilds(int $id, array $contractChilds)
    {
        $this->entity->where('main_id', $id)->whereNotIn('id', $contractChilds)->update([
            'main_id' => 0,
        ]);
        $this->entity->whereIn('id', $contractChilds)->where('main_id', 0)->update([
            'main_id' => $id,
        ]);
        return true;
    }

    public function childLists(int $id)
    {
        return $this->entity->select(['id', 'title','type_id'])->where('main_id', $id)->with(['projects' => function ($query) {
            $query->select(['id', 'contract_t_id', 'type', 'money', 'pay_way', 'pay_account', 'pay_time', 'run_id', 'remarks', 'pay_type', 'invoice_time']);
        }])->get();
    }


    public function getContractIdByNumber($number)
    {
        return $this->entity->where('number', $number)->value('id');
    }

    public function checkDelete($ids, $flag = false)
    {
        $lists = $this->entity->select(['id'])->whereIn('id', $ids)->where('main_id', '!=', '')->get();
        if (!$lists->isEmpty()) {
            return ['code' => ['0x066007', 'contract']];
        }
        $lists = $this->entity->select(['id'])->whereIn('main_id', $ids)->get();
        if (!$lists->isEmpty()) {
            return ['code' => ['0x066007', 'contract']];
        }
        if ($flag) {
            $lists = $this->entity->select(['id'])->where('recycle_status', 0)->whereIn('id', $ids)->get();
            if (!$lists->isEmpty()) {
                return ['code' => ['0x066007', 'contract']];
            }
        }
        return true;
    }

    public function multiSearchIds(array $multiSearchs,array $searchs =[])
    {
        $query = $this->entity;
        if($multiSearchs){
            $query = $query->multiWheres($multiSearchs);
        }
        if($searchs){
            $query = $query->wheres($searchs);
        }

        return $query->pluck('id')->toArray();
    }

    public function searchIds(array $searchs){
        $query = $this->entity;
        if(isset($searchs['multiSearch'])){
            $multiSearch = $searchs['multiSearch'];
            unset($searchs['multiSearch']);
            $query = $query->multiWheres($multiSearch);
        }
        $query = $query->wheres($searchs)->where(['recycle_status'=>0]);
        return $query->pluck('id')->toArray();
    }

    public static function existContractTypeId($typeId)
    {
        return DB::table(self::TABLE_NAME)->where('type_id', $typeId)->value('id');
    }

    public static function checkRepeatTitle($title,$id = null){
        $query = DB::table(self::TABLE_NAME)->where('title',$title)->where('recycle_status',0)->whereNull('deleted_at');
        if($id){
            $query = $query->where('id','!=',$id);
        }
        return $query->first();
    }

    public static function getContractTypeId($ids){
        return $query = DB::table(self::TABLE_NAME)->whereIn('id',$ids)->pluck('type_id')->toArray();
    }

    // 批量更新
    public static function batchUpdateData($ids,$data){
        return DB::table(self::TABLE_NAME)->whereIn('id',$ids)->update($data);
    }

    public function getContractIds($searchs){
        $query = $this->entity->wheres($searchs);
        return $query->pluck('id')->toArray();
    }

    public static function getContractById($id,$fields = '*'){
        if(is_array($id)){
            return DB::table(self::TABLE_NAME)->select($fields)->whereIn('id',$id)->get();
        }else{
            return DB::table(self::TABLE_NAME)->select($fields)->where('id',$id)->first();
        }
    }

    public function getContractCount($type_id,$params){
        $query = $this->entity->where(['recycle_status'=> 0,'type_id'=>$type_id]);
        if($params && isset($params['search'])){
            $query = $query->wheres($params['search']);
        }
        return $query->count();
    }

    public function getContractMoney($type_id,$params){
        $query = $this->entity->where(['recycle_status'=> 0,'type_id'=>$type_id]);
        if($params && isset($params['search'])){
            $query = $query->wheres($params['search']);
        }
        return $query->sum('money');
    }

    public static function getUserSelector($search){
        return DB::table('custom_fields_table')->select('*')->where($search)->get()->toArray();
    }
    public static function checkUnique($search){
        return DB::table('custom_fields_table')->where($search)->first();
    }

    public function getContractIdsByFields($params,$fields,$multiple = 0){

        $user_ids = $params[$fields] ?? [];
        unset($params[$fields]);
        $query = $this->entity->wheres($params);
        if($user_ids && is_array($user_ids)){
            // 单选 or 多选
            if($multiple){
                $query = $query->where(function ($query) use($user_ids,$fields){
                    if($user_ids && is_array($user_ids)){
                        foreach($user_ids as $id){
                            $query->orWhereRaw('FIND_IN_SET(?,'.$fields.')',[$id]);
                        }
                    }
                });
            }else{
                $query = $this->tempTableJoin($query,$fields,$user_ids);
            }
        }
        $query = $query->pluck('id')->toArray();
        return $query;
    }

    public function tempTableJoin($query, $fields,$searchs)
    {
        if (!empty($searchs) && count($searchs) > 2000) {
            $tableName = 'contract_t_'.rand() . uniqid();
            DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` char (20) NOT NULL,PRIMARY KEY (`data_id`))");
            $tempIds = array_chunk($searchs, 2000, true);
            foreach ($tempIds as $key => $item) {
                $ids      = implode("),(", $item);
                $tSql = "insert into {$tableName} (data_id) values (\"{$ids}\");";
                DB::insert($tSql);
            }
            $query = $query->join("$tableName", $tableName . ".data_id", '=', $fields);
        }else{
            $query = $query->whereIn($fields,$searchs);
        }
        return $query;
    }

    public static function getOtherShareIds($ids,$own){
       return DB::table(self::TABLE_NAME)->whereIn('id', $ids)->where('user_id','not like','%'.$own['user_id'].'%')->where('recycle_status',0)->pluck('id')->toArray();
    }

}
