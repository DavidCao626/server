<?php
namespace App\EofficeApp\Address\Repositories;

use App\EofficeApp\Base\BaseRepository;
use \App\EofficeApp\Address\Entities\AddressPrivateEntity;
use \App\EofficeApp\Address\Entities\AddressSubEntity;

/**
 * @公共通讯录资源库类
 *
 * @author 牛晓克
 */
class AddressPrivateRepository extends BaseRepository
{
    private $primaryKey = 'address_id';

    private $addressSubEntity;

    private $orderBy = ['address_private.primary_7' => 'asc'];

    private $limit = 10;

    private $page = 1;

    public function __construct(AddressPrivateEntity $entity, AddressSubEntity $addressSubEntity)
    {
        parent::__construct($entity);

        $this->addressSubEntity = $addressSubEntity;
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

    public function getExportAddress($groupType, $groupId, $currentUserId)
    {
        $groupFix = $groupType == 1 ? 'address_public_group' : 'address_person_group';

        $query = $this->entity
            ->leftJoin('user', 'user.user_id', '=', 'address_private.primary_5')
            ->leftJoin($groupFix, $groupFix . '.group_id', '=', 'address_private.primary_4');
        // if (class_exists('App\EofficeApp\Address\Entities\AddressSubEntity')) {
        //     $query->leftJoin('address_sub', 'address_sub.address_id', '=', 'address.address_id');
        // }
        $query->whereIn('address_private.primary_4', $groupId);

        $query = $query->where('address_private.primary_5', $currentUserId);

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
        $groupFix = 'address_person_group';

        $query = $this->entity
            ->leftJoin('user', 'user.user_id', '=', 'address_private.primary_5')
            ->leftJoin($groupFix, $groupFix . '.group_id', '=', 'address_private.primary_4');

        if (!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        if (isset($param['orSearch']) && !empty($param['orSearch'])) {
            $orSearch = $param['orSearch'];
            if(is_string($param['orSearch'])){
                $orSearch = json_decode($param['orSearch'], true);
            }

            $query = $query->where(function ($query) use ($orSearch) {
                foreach ($orSearch as $key => $wheres) {
                    foreach ($wheres as $where) {
                        $query->orWhere('address_private.' . $key, isset($where[1]) ? $where[1] : '=', '%' . $where[0]);
                    }
                }
            });
        }

        $query = $query->where('address_private.primary_5', $currentUserId);

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
        $groupFix = 'address_person_group';

        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['*'];

        $param['limit'] = isset($param['limit']) ? $param['limit'] : $this->limit;

        $param['page'] = isset($param['page']) ? $param['page'] : $this->page;

        $param['order_by'] = isset($param['order_by']) ? $param['order_by'] : [];
        if(!empty($param['order_by'])){
            foreach ($param['order_by'] as $key => $value) {
                if(strpos($key, 'address_private') !== 0){
                    $param['order_by']['address_private.'.$key] = $value;
                    unset($param['order_by'][$key]);
                }
            }
        }
        $param['order_by'] = array_merge($this->orderBy, $param['order_by']);

        $query             = $this->entity
            ->select($param['fields'])
            ->leftJoin('user', 'user.user_id', '=', 'address_private.primary_5')
            ->leftJoin($groupFix, $groupFix . '.group_id', '=', 'address_private.primary_4');

        if (!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        if ($groupId) {
            $query = $query->whereIn('address_private.primary_4', $groupId);
        }
        if (isset($param['orSearch']) && !empty($param['orSearch'])) {
            $orSearch = $param['orSearch'];
            if(is_string($param['orSearch'])){
                $orSearch = json_decode($param['orSearch'], true);
            }

            $query = $query->where(function ($query) use ($orSearch) {
                foreach ($orSearch as $key => $wheres) {
                    foreach ($wheres as $where) {
                        $query->orWhere('address_private.' . $key, isset($where[1]) ? $where[1] : '=', '%' . $where[0]);
                    }
                }
            });
        }

        $query = $query->where('address_private.primary_5', $currentUserId);

        $query = $query->orders($param['order_by']);
        if(isset($param['pages'])){
           $query =  $query->forPage($param['page'], $param['limit']);
        }

        return $query->get();
    }

    public function getAddressByGroupId($groupType, $groupId, $filter, $own)
    {
        $query = $this->entity
            ->select(['*', 'address_private.address_id as addr_id']);
        if ($filter == 'phone') {
            $query->where('primary_3', '<>', null)->where('primary_3', '<>', '');
        } else if ($filter == 'email') {
            $query->where('primary_9', '<>', null)->where('primary_9', '<>', '');
        }
        $query->where('primary_5', $own['user_id']);
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

    public function getAddressInfo ($where) {
        return $this->entity->wheres($where)->first();
    }

    public function isUniqueOnCreate($field, $value, $creator)
    {
        return $this->entity
            ->where($field, $value)
            ->where('primary_5', $creator)
            ->doesntExist();
    }

    public function isUniqueOnUpdate($field, $value, $creator, $primaryKey, $primaryValue)
    {
        return $this->entity
            ->where($field, $value)
            ->where('primary_5', $creator)
            ->where($primaryKey, '<>', $primaryValue)
            ->doesntExist();
    }

}
