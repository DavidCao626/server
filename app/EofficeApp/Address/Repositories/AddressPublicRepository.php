<?php
namespace App\EofficeApp\Address\Repositories;

use App\EofficeApp\Base\BaseRepository;
use \App\EofficeApp\Address\Entities\AddressPrivateEntity;
use \App\EofficeApp\Address\Entities\AddressPublicEntity;
use \App\EofficeApp\Address\Entities\AddressSubEntity;
use Schema;

/**
 * @公共通讯录资源库类
 *
 * @author 牛晓克
 */
class AddressPublicRepository extends BaseRepository
{
    private $primaryKey = 'address_id';

    private $addressSubEntity;

    private $addressPrivateEntity;

    private $orderBy = ['address_public.primary_7' => 'asc'];

    private $limit = 10;

    private $page = 1;

    public function __construct(AddressPublicEntity $entity, AddressSubEntity $addressSubEntity, AddressPrivateEntity $addressPrivateEntity)
    {
        parent::__construct($entity);

        $this->addressSubEntity = $addressSubEntity;

        $this->addressPrivateEntity = $addressPrivateEntity;
    }

    /**
     * @通讯录转移
     * @param type $groupId
     * @param type $addressId
     * @return boolean
     */
    public function migrateAddress($groupId, $addressId)
    {
        return $this->entity->whereIn($this->primaryKey, $addressId)->update($groupId);
    }
    /**
     * @通讯录复制
     * @param type $groupId
     * @param type $addressId
     * @return boolean
     */
    public function copyAddress($groupId, $addressId, $currentUserId)
    {
        $privateColumn = Schema::getColumnListing('address_private');
        $publicColumn = Schema::getColumnListing('address_public');
        $common = array_intersect($privateColumn, $publicColumn);

        foreach ($addressId as $v) {
            $primaryData = $this->entity->where($this->primaryKey, $v)->first()->toArray();
            foreach($primaryData as $key => $value){
                if(!in_array($key,$common) || $value == ''){
                    unset($primaryData[$key]);
                }
            }
            unset($primaryData['address_id']);
            unset($primaryData['deleted_at']);
            unset($primaryData['created_at']);
            unset($primaryData['updated_at']);
            $primaryData['primary_5'] = $currentUserId;
            $primaryData['primary_6'] = date('Y-m-d H:i:s');
            $primaryData['primary_4'] = $groupId;
            $result                   = $this->addressPrivateEntity->create($primaryData);

            if (!$result) {
                return false;
            }

            // if ($this->addressSubEntity->where($this->primaryKey, $v)->count() > 0) {
            //     $subData = $this->addressSubEntity->where($this->primaryKey, $v)->first()->toArray();
            //     unset($subData['deleted_at']);
            //     unset($subData['created_at']);
            //     unset($subData['updated_at']);
            //     $subData['address_id'] = $result->address_id;
            //     if (!$this->addressSubEntity->create($subData)) {
            //         return false;
            //     }
            // }
        }

        return true;
    }

    public function getExportAddress($groupType, $groupId, $currentUserId)
    {
        $groupFix = $groupType == 1 ? 'address_public_group' : 'address_person_group';

        $query = $this->entity
            ->leftJoin('user', 'user.user_id', '=', 'address_public.primary_5')
            ->leftJoin($groupFix, $groupFix . '.group_id', '=', 'address_public.primary_4');

        $query->whereIn('address_public.primary_4', $groupId);

        return $query->orders($this->orderBy)->get();
    }

    public function clearData()
    {
        $addresss = $this->entity->select(['address_id'])->get();
        if (!count($addresss)) {
            return true;
        }
        $addressId = [];
        foreach ($addresss as $address) {
            $addressId[] = $address->address_id;
        }
        $result = $this->entity->destroy($addressId);

        return true;
    }

    public function addImportData($primaryData, $subData)
    {
        $result = $this->entity->create($primaryData);

        if ($result && is_object($this->addressSubEntity) && !empty($subData)) {
            $subData['address_id'] = $result->address_id;

            if (!$this->addressSubEntity->create($subData)) {
                return false;
            }
        }

        return true;
    }

    public function updateImportData($primaryData, $subData)
    {
        $address = $this->entity->where('primary_4', $primaryData['primary_4'])->where('primary_1', $primaryData['primary_1'])->first();
        if ($address) {
            $addressId = $address->address_id;
            $result    = $this->entity->where($this->primaryKey, $addressId)->update($primaryData);

            if ($result && is_object($this->addressSubEntity) && !empty($subData)) {
                if ($this->addressSubEntity->where($this->primaryKey, $addressId)->count() == 1) {
                    return $this->addressSubEntity->where($this->primaryKey, $addressId)->update($subData);
                } else {
                    $subData['address_id'] = $addressId;
                    return $this->addressSubEntity->create($subData);
                }
            }
        }

        return false;
    }

    public function updateAndAddImportData($primaryData, $subData)
    {
        $address = $this->entity->where('primary_4', $primaryData['primary_4'])->where('primary_1', $primaryData['primary_1'])->first();
        if ($address) {
            $addressId = $address->address_id;
            $result    = $this->entity->where($this->primaryKey, $addressId)->update($primaryData);

            if ($result && is_object($this->addressSubEntity) && !empty($subData)) {
                if ($this->addressSubEntity->where($this->primaryKey, $addressId)->count() == 1) {
                    return $this->addressSubEntity->where($this->primaryKey, $addressId)->update($subData);
                } else {
                    $subData['address_id'] = $addressId;
                    return $this->addressSubEntity->create($subData);
                }
            }
        } else {
            $result = $this->entity->create($primaryData);

            if ($result && is_object($this->addressSubEntity) && !empty($subData)) {
                $subData['address_id'] = $result->address_id;

                return $this->addressSubEntity->create($subData);
            }
        }

        return false;
    }

    /**
     * @获取通讯录个数
     * @param type $groupType
     * @param type $param
     * @param type $groupId
     * @return 数量
     */
    public function getAddressCount($param = [], $groupId = [], $currentUserId)
    {
        $groupFix = 'address_public_group';

        $query = $this->entity
            ->leftJoin('user', 'user.user_id', '=', 'address_public.primary_5')
            ->leftJoin($groupFix, $groupFix . '.group_id', '=', 'address_public.primary_4');

        if (!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        if (!empty($groupId)) {
            $query = $query->whereIn('address_public.primary_4', $groupId);
        }

        if (isset($param['orSearch']) && !empty($param['orSearch'])) {
            $orSearch = $param['orSearch'];
            if(is_string($param['orSearch'])){
                $orSearch = json_decode($param['orSearch'], true);
            }

            $query = $query->orWhere(function ($query) use ($orSearch) {
                foreach ($orSearch as $key => $wheres) {
                    foreach ($wheres as $where) {
                        if(isset($where[1])){
                            if($where[1] == 'like'){
                                $query->orWhere('address_public.' . $key, 'like', '%' . $where[0]);
                            }else{
                                $query->orWhere('address_public.' . $key, $where[1], $where[0]);
                            }
                        }else{
                            $query->orWhere('address_public.' . $key, $where[0]);
                        }
                    }
                }
            });
        }

        return $query->count();
    }

    /**
     * @获取通讯录列表
     * @param type $groupType
     * @param type $param
     * @param type $groupId
     * @return 通讯录列表
     */
    public function listAddress($param = [], $groupId = false, $currentUserId)
    {
        $groupFix = 'address_public_group';

        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['*'];

        $param['limit'] = isset($param['limit']) ? $param['limit'] : $this->limit;

        $param['page'] = isset($param['page']) ? $param['page'] : $this->page;

        $param['order_by'] = isset($param['order_by']) ? $param['order_by'] : [];
        if(!empty($param['order_by'])){
            foreach ($param['order_by'] as $key => $value) {
                if(strpos($key, 'address_public') !== 0){
                    $param['order_by']['address_public.'.$key] = $value;
                    unset($param['order_by'][$key]);
                }
            }
        }
        $param['order_by'] = array_merge($this->orderBy, $param['order_by']);

        $query             = $this->entity
            ->select($param['fields'])
            ->leftJoin('user', 'user.user_id', '=', 'address_public.primary_5')
            ->leftJoin($groupFix, $groupFix . '.group_id', '=', 'address_public.primary_4');

        if (!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        if ($groupId) {
            $query = $query->whereIn('address_public.primary_4', $groupId);
        }
        if (isset($param['orSearch']) && !empty($param['orSearch'])) {
            $orSearch = $param['orSearch'];
            if(is_string($param['orSearch'])){
                $orSearch = json_decode($param['orSearch'], true);
            }

            $query = $query->orWhere(function ($query) use ($orSearch) {
                foreach ($orSearch as $key => $wheres) {
                    foreach ($wheres as $where) {
                        if(isset($where[1])){
                            if($where[1] == 'like'){
                                $query->orWhere('address_public.' . $key, 'like', '%' . $where[0]);
                            }else{
                                $query->orWhere('address_public.' . $key, $where[1], $where[0]);
                            }
                        }else{
                            $query->orWhere('address_public.' . $key, $where[0]);
                        }
                    }
                }
            });
        }
        $query = $query->orders($param['order_by']);
        if(isset($param['pages'])){
           $query =  $query->forPage($param['page'], $param['limit']);
        }

        return $query->get();
    }

    public function getAddressByGroupId($groupType, $groupId, $filter)
    {
        $query = $this->entity
            ->select(['*', 'address_public.address_id as addr_id']);
        if ($filter == 'phone') {
            $query->where('primary_3', '<>', null)->where('primary_3', '<>', '');
        } else if ($filter == 'email') {
            $query->where('primary_9', '<>', null)->where('primary_9', '<>', '');
        }
        if ($groupId == 0) {
            $query->where(function($query)use($groupId){
                $query->where('primary_4', $groupId)->orWhere('primary_4', '')->orWhereNull('primary_4');
            });
        } else {
            $query->where('primary_4', $groupId);
        }
        return $query->get();
    }

    public function isHasAddressByGroup($groupType, $groupId)
    {
        return $this->entity->where('primary_4', $groupId)->count();
    }

    public function getAddressInfo ($where)
    {
        return $this->entity->wheres($where)->first();
    }

    public function listAddressesWithAvatar($params)
    {
        $fields = $params['fields'] ?? [];
        $fields = array_merge(['attachment_id'], $fields);
        $search = $params['search'] ?? '';
        $query = $this->entity->select($fields);
        if($search){
            $query = $query->wheres($search);
        }
       return $query
            ->leftJoin('attachment_relataion_address_public', function ($join) {
                $join->on('address_public.address_id', '=', 'attachment_relataion_address_public.entity_id')
                    ->where('attachment_relataion_address_public.entity_column', 'primary_10');
            })
            ->get();
    }
}
