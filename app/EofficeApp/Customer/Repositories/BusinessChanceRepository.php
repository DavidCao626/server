<?php

namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\BusinessChanceEntity;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use DB;

class BusinessChanceRepository extends BaseRepository
{
    // 系统下拉框商机等级参数标识
    const SYSTEM_START_INDEX = 15;
    // 具备删除权限得id
    const DELETE_PERMISSION_USER_IDS = ['admin'];
    // 表名
    const TABLE_NAME = 'customer_business_chance';

    public function __construct(BusinessChanceEntity $entity)
    {
        parent::__construct($entity);
        $this->systemComboboxService = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->customerRepository    = 'App\EofficeApp\Customer\Repositories\CustomerRepository';
    }

    /**
     * 新增和修改得数据验证
     * @param $flag 默认新增
     * @return bool
     */
    public static function validateInput(array &$input, int $id = 0, array $own = [])
    {
        if (!isset($input['chance_name']) || empty($input['chance_name'])) {
            return ['code' => ['0x024017', 'customer']];
        }
        $input['business_star'] = '';
        $input['chance_possibility'] = $input['chance_possibility'] ? intval($input['chance_possibility']) : 0;
        if ($input['chance_possibility'] > 100) {
            return ['code' => ['0x024031', 'customer']];
        }
        if (isset($input['chance_step'])) {
            $input['business_star'] = self::getBusinessChancesStar($input['chance_possibility'], $input['chance_step']);
        }
        list($input['chance_name_py'], $input['chance_name_zm']) = convert_pinyin($input['chance_name']);
        $customerId = $input['customer_id'] ?? '';
        if ($id) {
            $creator = DB::table(self::TABLE_NAME)->where('chance_id', $id)->value('chance_creator');
            if(!$creator){
                return ['code' => ['0x000021', 'common']];
            }
            if ($creator != $own['user_id'] && $customerId && !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$customerId], $own)) {
                return ['code' => ['0x024003', 'customer']];
            }
        }
        if ($customerId && !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$customerId], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        return true;
    }

    /**
     * 计算商机等级
     */
    public static function getBusinessChancesStar($chancePossibility, $chanceStep)
    {
        $chanceSteps = app('App\EofficeApp\System\Combobox\Services\SystemComboboxService')->getComboboxFieldsById(self::SYSTEM_START_INDEX);
        if (!empty($chanceSteps['combobox_fields'])) {
            $combobox = array_column($chanceSteps['combobox_fields'], 'field_order', 'field_value');
            if (isset($combobox[$chanceStep])) {
                $optionIndex      = $combobox[$chanceStep];
                $chanceStepsCount = count($combobox);
            } else {
                $optionIndex      = 1;
                $chanceStepsCount = 100;
            }
        } else {
            $optionIndex      = 1;
            $chanceStepsCount = 100;
        }
        $chancePossibility = $chancePossibility ? $chancePossibility : 0;
        $optionIndex       = $optionIndex ? $optionIndex : 0;
        return ($chancePossibility / 100 + $optionIndex / $chanceStepsCount) * 2.5;
    }

    public static function getPermissionIds(array $types, $own, $ids = [])
    {
        $result = [];
        $query  = DB::table(self::TABLE_NAME)->select(['chance_id', 'customer_id', 'chance_creator']);
        if (!empty($ids)) {
            $query = $query->whereIn('chance_id', $ids);
        }
        $lists = $query->get();
        if ($lists->isEmpty()) {
            return array_pad($result, count($types), []);
        }
        $userId = $own['user_id'] ?? '';
        array_map(function ($type) use (&$result, $own, $lists, $userId) {
            switch ($type) {
                case CustomerRepository::UPDATE_MARK:
                    $result[$type] = [];
                    // 编辑权限,对客户具有编辑权限 + 创建人
                    $customerIds = [];
                    foreach ($lists as $index => $item) {
                        // 创建人
                        if ($item->chance_creator == $userId) {
                            $result[$type][] = $item->chance_id;
                            continue;
                        }
                        if (!isset($customerIds[$item->customer_id])) {
                            $customerIds[$item->customer_id] = [];
                        }
                        $customerIds[$item->customer_id][] = $item->chance_id;
                    }
                    if (!empty($customerIds)) {
                        $updateCustomerIds = CustomerRepository::getUpdateIds($own, array_keys($customerIds));
                        if (!empty($updateCustomerIds)) {
                            foreach ($customerIds as $iCustomerId => $iContractIds) {
                                if (in_array($iCustomerId, $updateCustomerIds)) {
                                    $result[$type] = array_merge($result[$type], $iContractIds);
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

    public static function validatePermission(array $types, $id, array $own)
    {
        $result = 0;
        $list   = DB::table(self::TABLE_NAME)->select(['chance_creator', 'customer_id'])->where('chance_id', $id)->first();
        if (empty($list)) {
            return $result;
        }
        array_map(function ($type) use (&$result, $list, $own) {
            switch ($type) {
                case CustomerRepository::VIEW_MARK:
                    if ($list->chance_creator == $own['user_id']) {
                        $result = $result | $type;
                    } elseif ($list->customer_id && $validate = CustomerRepository::validatePermission([$type], [$list->customer_id], $own)) {
                        $result = $result | $type;
                    }
                    break;

                case CustomerRepository::UPDATE_MARK:
                    if ($list->chance_creator == $own['user_id']) {
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

    public static function customerDetailMenuCount($customerId)
    {
        return DB::table(self::TABLE_NAME)->where('customer_id', $customerId)->count();
    }

    // 客户合并
    public static function mergeToCustomer($targetCustomerId, $customerIds)
    {
        return DB::table(self::TABLE_NAME)->whereIn('customer_id', $customerIds)->update([
            'customer_id' => $targetCustomerId,
        ]);
    }

    public function multiSearchIds(array $searchs, $customerIds)
    {
        $query = $this->entity->multiWheres($searchs);
        if (is_array($customerIds)) {
            $query->whereIn('customer_id', $customerIds);
        }
        return $query->pluck('chance_id')->toArray();
    }

    public static function getCustomerIds(array $chanceIds)
    {
        $result = [];
        $lists = DB::table(self::TABLE_NAME)->select(['customer_id'])->whereIn('chance_id', $chanceIds)->get();
        if (!$lists->isEmpty()) {
            $result = array_column($lists->toArray(), 'customer_id');
        }
        return $result;
    }
}
