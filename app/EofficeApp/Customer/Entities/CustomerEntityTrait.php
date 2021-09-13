<?php

namespace App\EofficeApp\Customer\Entities;

trait CustomerEntityTrait
{
    /**
    * 客户信息查看权限
    *
    * @param  object $query    查询条件
    * @param  array  $users    下级用户id
    * @param  array  $userInfo 用户信息
    *
    * @return array 查询列表
    *
    * @author qishaobo
    *
    * @since  2015-10-21
    */
    public function scopeCustomerPermission($query, $users, $userInfo)
    {
        if (count($users) == 1 && isset($users['origin'])) {
            $users = $users['origin'];
            return  $query->where(function ($query) use ($users, $userInfo) {
                    $query->whereIn('customer_manager', $users)
                    ->orWhere('customer_service_manager', $userInfo['user_id'])
                    ->orWhere('view_permission', 2)
                    ->orWhere(function ($query) use ($userInfo) {
                        $query->where('view_permission', 3)
                        ->where(function ($query) use ($userInfo) {
                            $deptId = [$userInfo['dept_id']];
                            $roleId = $userInfo['role_id'];
                            $userId = $userInfo['user_id'];
                            $query
                            ->orWhereHas('hasManyDept', function ($query) use ($deptId) {
                                $query->wheres(['dept_id' => [$deptId, 'in']]);
                            })
                            ->orWhereHas('hasManyRole', function ($query) use ($roleId) {
                                $query->wheres(['role_id' => [$roleId, 'in']]);
                            })
                            ->orWhereHas('hasManyUser', function ($query) use ($userId) {
                                $query->wheres(['user_id' => [$userId]]);
                            });
                        });
                    });
                });
        }
        if (isset($users['customer_manager,customer_service_manager']) && !empty($users['customer_manager,customer_service_manager']['id'])) {
            if (isset($users['customer_manager']) && !empty($users['customer_manager']['id'])) {
                $users['customer_manager']['id'] = array_unique(array_merge($users['customer_manager']['id'], $users['customer_manager,customer_service_manager']['id']));
            } else {
                $users['customer_manager']['id'] = $users['customer_manager,customer_service_manager']['id'];
            }
            if (isset($users['customer_service_manager']) && !empty($users['customer_service_manager']['id'])) {
                $users['customer_service_manager']['id'] = array_unique(array_merge($users['customer_service_manager']['id'], $users['customer_manager,customer_service_manager']['id']));
            } else {
                $users['customer_service_manager']['id'] = $users['customer_manager,customer_service_manager']['id'];
            }
        }
        if (!isset($users['customer_manager'])) {
            $users['customer_manager']['id'] = [];
        }
        if (!isset($users['customer_service_manager'])) {
            $users['customer_service_manager']['id'] = [];
        }
        $customer_manager_ids = $users['customer_manager']['id'];
        $customer_service_manager_ids = $users['customer_service_manager']['id'];
        array_push($customer_manager_ids, $userInfo['user_id']);
        array_push($customer_service_manager_ids, $userInfo['user_id']);
        return $query->where(function ($query) use ($customer_manager_ids, $customer_service_manager_ids, $userInfo) {
            $query->whereIn('customer_manager', $customer_manager_ids)
            ->orWhereIn('customer_service_manager', $customer_service_manager_ids)
            ->orWhere('view_permission', 2)
                    ->orWhere(function ($query) use ($userInfo) {
                        $query->where('view_permission', 3)
                        ->where(function ($query) use ($userInfo) {
                            $deptId = [$userInfo['dept_id']];
                            $roleId = $userInfo['role_id'];
                            $userId = $userInfo['user_id'];
                            $query
                            ->orWhereHas('hasManyDept', function ($query) use ($deptId) {
                                $query->wheres(['dept_id' => [$deptId, 'in']]);
                            })
                            ->orWhereHas('hasManyRole', function ($query) use ($roleId) {
                                $query->wheres(['role_id' => [$roleId, 'in']]);
                            })
                            ->orWhereHas('hasManyUser', function ($query) use ($userId) {
                                $query->wheres(['user_id' => [$userId]]);
                            });
                        });
                    });
        });
        return  $query->where(function ($query) use ($users, $userInfo) {
                    $query->whereIn('customer_manager', $users)
                    ->orWhere('customer_service_manager', $userInfo['user_id'])
                    ->orWhere('view_permission', 2)
                    ->orWhere(function ($query) use ($userInfo) {
                        $query->where('view_permission', 3)
                        ->where(function ($query) use ($userInfo) {
                            $deptId = [$userInfo['dept_id']];
                            $roleId = $userInfo['role_id'];
                            $userId = $userInfo['user_id'];
                            $query
                            ->orWhereHas('hasManyDept', function ($query) use ($deptId) {
                                $query->wheres(['dept_id' => [$deptId, 'in']]);
                            })
                            ->orWhereHas('hasManyRole', function ($query) use ($roleId) {
                                $query->wheres(['role_id' => [$roleId, 'in']]);
                            })
                            ->orWhereHas('hasManyUser', function ($query) use ($userId) {
                                $query->wheres(['user_id' => [$userId]]);
                            });
                        });
                    });
                });
    }

    /**
    * 客户信息编辑权限
    *
    * @param  object $query    查询条件
    * @param  array  $users    下级用户id
    * @param  array  $userInfo 用户信息
    *
    * @return array 查询列表
    *
    * @author qishaobo
    *
    * @since  2015-10-21
    */
    public function scopeCustomerEditPermission($query, $users, $userInfo)
    {
        /*return  $query->where(function ($query) use ($users, $userInfo) {
                    $query->whereIn('customer_manager', $users['origin'])
                    ->orWhere('customer_service_manager', $userInfo['user_id']);
                });*/

        $customer_manager_ids = isset($users['origin']) ? $users['origin'] : [];
        $customer_service_manager_ids = [];
        if (isset($userInfo['user_id'])) {
            array_push($customer_manager_ids, $userInfo['user_id']);
            array_push($customer_service_manager_ids, $userInfo['user_id']);
        }
        if (!empty($users)) {
            foreach ($users as $key => $value) {
                if (!empty($value['id']) && !empty($key) && isset($value['detail_permission']) && !empty($value['detail_permission']) && in_array(1, $value['detail_permission'])) {
                    if ($key == 'customer_manager') {
                        $customer_manager_ids = array_merge($customer_manager_ids, $value['id']);
                    } else if ($key == 'customer_service_manager') {
                        $customer_service_manager_ids = array_merge($customer_service_manager_ids, $value['id']);
                    } else {
                        $customer_manager_ids = array_merge($customer_manager_ids, $value['id']);
                        $customer_service_manager_ids = array_merge($customer_service_manager_ids, $value['id']);
                    }
                }
            }
        }
        $customer_manager_ids = array_unique($customer_manager_ids);
        $customer_service_manager_ids = array_unique($customer_service_manager_ids);
        return  $query->where(function ($query) use ($customer_manager_ids, $customer_service_manager_ids) {
                    $query->whereIn('customer_manager', $customer_manager_ids)
                    ->orwhereIn('customer_service_manager', $customer_service_manager_ids);
                });
    }
}