<?php
namespace App\EofficeApp\Address\Repositories;

use App\EofficeApp\Base\BaseRepository;
use \App\EofficeApp\Address\Entities\AddressEntity;
use \App\EofficeApp\Address\Entities\AddressSubEntity;

/**
 * @通讯录资源库类
 *
 * @author 李志军
 */
class AddressRepository extends BaseRepository
{
    private $primaryKey = 'address_id';

    private $addressSubEntity;

    private $limit = 20;

    private $page = 0;

    private $orderBy = ['address.primary_7' => 'asc'];
    /**
     * @注册通讯录实体对象
     * @param \App\EofficeApp\Entities\AddressEntity $entity
     */
    public function __construct(AddressEntity $entity)
    {
        parent::__construct($entity);

        if (class_exists('App\EofficeApp\Address\Entities\AddressSubEntity')) {
            $this->addressSubEntity = new AddressSubEntity; //通讯录子表实体对象
        }
    }
    public function getExportAddress($groupType, $groupId, $currentUserId)
    {
        $groupFix = $groupType == 1 ? 'address_public_group' : 'address_person_group';

        $query = $this->entity
            ->leftJoin('user', 'user.user_id', '=', 'address.primary_5')
            ->leftJoin($groupFix, $groupFix . '.group_id', '=', 'address.primary_4');
        if (class_exists('App\EofficeApp\Address\Entities\AddressSubEntity')) {
            $query->leftJoin('address_sub', 'address_sub.address_id', '=', 'address.address_id');
        }
        $query->where('group_type', $groupType)->whereIn('address.primary_4', $groupId);
        if ($groupType == 2) {
            $query = $query->where('address.primary_5', $currentUserId);
        }
        return $query->orders($this->orderBy)->get();
    }
    /**
     * @获取通讯录列表
     * @param type $groupType
     * @param type $param
     * @param type $groupId
     * @return 通讯录列表
     */
    public function listAddress($groupType, $param = [], $groupId = false, $currentUserId)
    {
        $groupFix = $groupType == 1 ? 'address_public_group' : 'address_person_group';

        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['*'];

        $param['limit'] = isset($param['limit']) ? $param['limit'] : $this->limit;

        $param['page'] = isset($param['page']) ? $param['page'] : $this->page;

        $param['order_by'] = isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
        $query             = $this->entity
            ->select($param['fields'])
            ->leftJoin('user', 'user.user_id', '=', 'address.primary_5')
            ->leftJoin($groupFix, $groupFix . '.group_id', '=', 'address.primary_4');
        if (class_exists('App\EofficeApp\Address\Entities\AddressSubEntity')) {
            $query->leftJoin('address_sub', 'address_sub.address_id', '=', 'address.address_id');
        }
        $query->where('group_type', $groupType);

        if (!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        if (isset($param['orSearch']) && !empty($param['orSearch'])) {
            $orSearch = json_decode($param['orSearch'], true);

            $query = $query->where(function ($query) use ($orSearch) {
                foreach ($orSearch as $key => $wheres) {
                    foreach ($wheres as $where) {
                        $query->orWhere('address.' . $key, isset($where[1]) ? $where[1] : '=', '%' . $where[0]);
                    }
                }
            });
        }
        if ($groupType == 1) {
            $query = $query->whereIn('address.primary_4', $groupId);
        } else if ($groupType == 2) {
            $query = $query->where('address.primary_5', $currentUserId);
        }

        return $query->orders($param['order_by'])->forPage($param['page'], $param['limit'])->get();
    }
    public function getAddressByGroupId($groupType, $groupId, $filter)
    {
        $groupFix = $groupType == 1 ? 'address_public_group' : 'address_person_group';

        $query = $this->entity
            ->select(['*', 'address.address_id as addr_id'])
            ->leftJoin('user', 'user.user_id', '=', 'address.primary_5')
            ->leftJoin($groupFix, $groupFix . '.group_id', '=', 'address.primary_4');
        if (class_exists('App\EofficeApp\Address\Entities\AddressSubEntity')) {
            $query->leftJoin('address_sub', 'address_sub.address_id', '=', 'address.address_id');
        }
        if ($filter == 'phone') {
            $query->where('primary_3', '<>', null)->where('primary_3', '<>', '');
        } else if ($filter == 'email') {
            $query->where('primary_9', '<>', null)->where('primary_9', '<>', '');
        }
        return $query->where('group_type', $groupType)->where('primary_4', $groupId)->get();
    }
    /**
     * @获取通讯录个数
     * @param type $groupType
     * @param type $param
     * @param type $groupId
     * @return 数量
     */
    public function getAddressCount($groupType, $param = [], $groupId = [], $currentUserId)
    {
        $groupFix = $groupType == 1 ? 'address_public_group' : 'address_person_group';

        $query = $this->entity
            ->leftJoin('user', 'user.user_id', '=', 'address.primary_5')
            ->leftJoin($groupFix, $groupFix . '.group_id', '=', 'address.primary_4');
        if (class_exists('App\EofficeApp\Address\Entities\AddressSubEntity')) {
            $query->leftJoin('address_sub', 'address_sub.address_id', '=', 'address.address_id');
        }
        $query->where('group_type', $groupType);

        if (!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        if (isset($param['orSearch']) && !empty($param['orSearch'])) {
            $orSearch = json_decode($param['orSearch'], true);

            $query = $query->where(function ($query) use ($orSearch) {
                foreach ($orSearch as $key => $wheres) {
                    foreach ($wheres as $where) {
                        $query->orWhere('address.' . $key, isset($where[1]) ? $where[1] : '=', '%' . $where[0]);
                    }
                }
            });
        }
        if ($groupType == 1 && $groupId) {
            $query = $query->whereIn('address.primary_4', $groupId);
        } else if ($groupType == 2) {
            $query = $query->where('address.primary_5', $currentUserId);
        }

        return $query->count();
    }
    /**
     * @新建通讯录
     * @param type $data
     * @return id
     */
    public function addAddress($data)
    {
        $primaryData = $subData = [];

        foreach ($data as $k => $v) {
            if (strstr($k, "sub_")) {
                $subData[$k] = $v;
            } else if (strstr($k, "primary_") || in_array($k, ['group_type', 'name_pinyin', 'name_index'])) {
                $primaryData[$k] = $v;
            }
        }

        $result = $this->entity->create($primaryData);

        if ($result && is_object($this->addressSubEntity) && !empty($subData)) {
            $subData['address_id'] = $result->address_id;

            if (!$this->addressSubEntity->create($subData)) {
                return false;
            }
        }

        return $result->address_id;
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

        return $result->address_id;
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
     * @编辑通讯录
     * @param type $data
     * @param type $addressId
     * @return boolean
     */
    public function editAddress($data, $addressId)
    {
        $primaryData = $subData = [];

        foreach ($data as $k => $v) {
            if (strstr($k, "sub_")) {
                if ($v != null) {
                    $subData[$k] = $v;
                }
            } else if (strstr($k, "primary_") || in_array($k, ['group_type', 'name_pinyin', 'name_index'])) {
                $primaryData[$k] = $v;
            }
        }

        $result = $this->entity->where($this->primaryKey, $addressId)->update($primaryData);

        if ($result && is_object($this->addressSubEntity) && !empty($subData)) {
            if ($this->addressSubEntity->where($this->primaryKey, $addressId)->count() == 1) {
                if (!$this->addressSubEntity->where($this->primaryKey, $addressId)->update($subData)) {
                    return false;
                }
            } else {
                $subData['address_id'] = $addressId;
                if (!$this->addressSubEntity->create($subData)) {
                    return false;
                }
            }
        }

        return $result;
    }
    /**
     * @获取通讯录详情
     * @param type $groupType
     * @param type $addressId
     * @return object 通讯录详情
     */
    public function showAddress($groupType, $addressId)
    {
        $groupFix = $groupType == 1 ? 'address_public_group' : 'address_person_group';

        $fields = ['address.*', 'user.user_name', $groupFix . '.group_name'];
        if (is_object($this->addressSubEntity)) {
            $fields[] = 'address_sub.*';
        }
        $query = $this->entity
            ->select($fields)
            ->leftJoin('user', 'user.user_id', '=', 'address.primary_5')
            ->leftJoin($groupFix, $groupFix . '.group_id', '=', 'address.primary_4');

        if (is_object($this->addressSubEntity)) {
            $query = $query->leftJoin('address_sub', 'address_sub.address_id', '=', 'address.address_id');
        }

        $query = $query->where('address.group_type', $groupType)
            ->where('address.address_id', $addressId);

        return $query->first();
    }
    /**
     * @删除通讯录
     * @param type $addressId
     * @return boolean
     */
    public function deleteAddress($addressId)
    {
        $result = $this->entity->destroy($addressId);

        if ($result && is_object($this->addressSubEntity)) {
            foreach ($addressId as $id) {
                $this->addressSubEntity->where('address_id', $id)->delete();
            }
        }

        return $result;
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
        foreach ($addressId as $v) {
            $primaryData = $this->entity->where($this->primaryKey, $v)->first()->toArray();

            unset($primaryData['address_id']);
            unset($primaryData['deleted_at']);
            unset($primaryData['created_at']);
            unset($primaryData['updated_at']);
            $primaryData['group_type'] = 2;
            $primaryData['primary_5']  = $currentUserId;
            $primaryData['primary_6']  = date('Y-m-d H:i:s');
            $primaryData['primary_4']  = $groupId;
            $result                    = $this->entity->create($primaryData);

            if (!$result) {
                return false;
            }

            if ($this->addressSubEntity->where($this->primaryKey, $v)->count() > 0) {
                $subData = $this->addressSubEntity->where($this->primaryKey, $v)->first()->toArray();
                unset($subData['deleted_at']);
                unset($subData['created_at']);
                unset($subData['updated_at']);
                $subData['address_id'] = $result->address_id;
                if (!$this->addressSubEntity->create($subData)) {
                    return false;
                }
            }
        }

        return true;
    }
    /**
     * @判断通讯录组下是否有通讯录
     * @param type $groupType
     * @param type $groupId
     * @return type
     */
    public function isHasAddressByGroup($groupType, $groupId)
    {
        return $this->entity->where('group_type', $groupType)->where('primary_4', $groupId)->count();
    }
    public function clearData($groupType)
    {
        $addresss = $this->entity->select(['address_id'])->where('group_type', $groupType)->get();
        if (!count($addresss)) {
            return true;
        }
        $addressId = [];
        foreach ($addresss as $address) {
            $addressId[] = $address->address_id;
        }
        $result = $this->entity->destroy($addressId);

        if ($result && is_object($this->addressSubEntity)) {
            foreach ($addressId as $id) {
                $this->addressSubEntity->where('address_id', $id)->delete();
            }
        }
        return true;
    }
}
