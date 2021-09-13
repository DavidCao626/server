<?php

namespace App\EofficeApp\Vacation\Traits;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

trait VacationCacheTrait
{

    /**
     * 假期类型成员缓存key
     * @param $vacationId
     * @return string
     */
    private function getMemberCacheKey($vacationId)
    {
        $cacheKey = 'member_vacation_' . $vacationId;
        return $cacheKey;
    }

    /**
     * 假期类型详情缓存key
     * @param $vacationId
     * @return string
     */
    private function getVacationCacheKey($vacationId)
    {
        $cacheKey = 'vacation_' . $vacationId;
        return $cacheKey;
    }

    /**
     * 所有假期类型缓存key
     * @param $vacationId
     * @return string
     */
    private function getAllVacationCacheKey()
    {
        $cacheKey = 'vacation_all';
        return $cacheKey;
    }

    /**
     * 获取假期成员详情
     * 加入缓存，编辑假期类别和添加用户时需要清除缓存
     * @param $id
     * @return array
     */
    public function getVacationMemberUseId($vacationId)
    {
        $cacheKey = $this->getMemberCacheKey($vacationId);
        if (Cache::get($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $member = $this->getVacationMember($vacationId);
        if (!$member) {
            $userIds = [];
        } else {
            if ($member->all_member) {
//                $userIds = app($this->userRepository)->getAllUserIdString();
//                $userIds = explode(',', $userIds);
                $userIds = true;
            } else {
                $userIds = [];
                if ($member->dept_id) {
                    $userIds = array_merge($userIds, app($this->userRepository)->getUserIdsByDeptIds($member->dept_id));
                }
                if ($member->role_id) {
                    $userIds = array_merge($userIds, app($this->userRepository)->getUserIdsByRoleIds($member->role_id));
                }
                if ($member->user_id) {
                    $userIds = array_merge($userIds, $member->user_id);
                }
                $userIds = array_unique($userIds);
            }
        }
        Cache::forever($cacheKey, $userIds);
        return $userIds;
    }

    /**
     * 假期类型缓存
     * @param $vacationId
     * @return mixed
     */
    private function getVacationDetailFromCache($vacationId)
    {
        $cacheKey = $this->getVacationCacheKey($vacationId);
        if (Cache::get($cacheKey)) {
            return Cache::get($cacheKey);
        } else {
            $vacation = app($this->vacationRepository)->getDetail($vacationId);
            Cache::forever($cacheKey, $vacation);
            return $vacation;
        }
    }

    /**
     * 获取所有的假期类别
     */
    private function getAllVacations()
    {
        $cacheKey = $this->getAllVacationCacheKey();
        if (Cache::get($cacheKey)) {
            return Cache::get($cacheKey);
        } else {
            $vacations = app($this->vacationRepository)->getVacationList([])->toArray();
            Cache::forever($cacheKey, $vacations);
            return $vacations;
        }
    }

    private function delAllVacationsCache()
    {
        $cacheKey = $this->getAllVacationCacheKey();
        return Cache::forget($cacheKey);
    }

    private function delVacationCacheKey($vacationId)
    {
        $cacheKey = $this->getVacationCacheKey($vacationId);
        return Cache::forget($cacheKey);
    }

    /**
     * 获取启用的假期类别
     * @param null $field
     * @return array
     */
    private function getEnableVacations($field = null)
    {
        $vacations = $this->getAllVacations();
        $vacations = array_filter($vacations, function ($item) {
            return $item['enable'] == 1;
        });
        if (!$vacations) {
            return [];
        }
        $vacations = array_values($vacations);
        if ($field) {
            return array_column($vacations, $field);
        }
        return $vacations;
    }

    /**
     * 获取启用&&限制的假期类别
     * @param null $field
     * @return array
     */
    private function getEnableLimitVacations($field = null)
    {
        $vacations = $this->getAllVacations();
        $vacations = array_filter($vacations, function ($item) {
            return $item['enable'] == 1 && $item['is_limit'] == 1;
        });
        if (!$vacations) {
            return [];
        }
        $vacations = array_values($vacations);
        if ($field) {
            return array_column($vacations, $field);
        }
        return $vacations;
    }
}
