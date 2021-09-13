<?php

namespace App\EofficeApp\Calendar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Calendar\Entities\CalendarAttentionEntity;

/**
 * 日程关注Repository类:提供日程关注表操作
 *
 */
class CalendarAttentionRepository extends BaseRepository
{
    public function __construct(CalendarAttentionEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 查询日程关注人
     *
     * @param  array  $param 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function calendarAttentionList($param = [])
    {
        $default = [
            'fields'    => [
                                'attention_id','attention_person',
                                'attention_to_person','attention_status', 'created_at', 'group_id',
                                'is_read'
                           ],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['attention_id' => 'asc'],
        ];

        $param = array_merge($default, $param);
        $userId = $param['user_id'] ?? '';
        $query = $this->entity
        ->select($param['fields']);
        if (isset($param['search']['user_name'])) {
            $userSearch = $param['search']['user_name'];
            $query = $query->whereHas('userAttentionToPerson', function ($query) use ($userSearch,$param) {
                $query->where("user_name" ,'like', "%". $userSearch[0]. "%");
            });
            unset($param['search']['user_name']);
        }

        $query = $query->wheres($param['search'])
        ->orders($param['order_by'])
        ->with(['userAttentionToPerson' => function($query) use ($param) {
            $query->select(['user_id', 'user_name'])
            ->with('userHasOneInfo');
            if (isset($param['withDept'])) {
                $query->with('userToDept');
            }
        }])
        ->with(['userAttention' => function($query) use ($param) {
            $query = $query->select(['user_id', 'user_name'])
            ->with('userHasOneInfo');
            if (isset($param['withDeptMy'])) {
                $query->with('userToDept');
            }
        }]);
        if (isset($param['no_group'])) {
            $query = $query->where('attention_person', $userId)->whereNull('group_id')->where('attention_status', 2);
        }
        if (!isset($param['getAll'])) {
            $query = $query->parsePage($param['page'], $param['limit']);
        }
        return $query->get()->toArray();
    }

    /**
     * 查询日程关注人
     *
     * @param  array  $where 查询条件
     * @param string $userName
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function calendarAttentionToPerson($where = [], $whereName = [])
    {
        return $this->entity
                ->wheres($where)
                ->whereHas('userAttentionToPerson', function ($query) use ($whereName) {
                    if (!empty($whereName)) {
                        $query->wheres($whereName);
                    }
                })
                ->pluck('attention_to_person')
                ->toArray();
    }
    /**
     * 解除离职人员关注关系
     */
    public function delectUnemployedAttention($userId)
    {
        $query = $this->entity;
        $query = $query->where('attention_person',$userId)
        ->orWhere('attention_to_person',$userId);

        return $query->delete();
    }

    public function getAttentionByWhere($attentionId, $attentionToId) {
        $query = $this->entity;
        $query = $query->where('attention_person',$attentionId)
        ->where('attention_to_person',$attentionToId);
        return $query->get()->toArray();
    }
    public function getAttentionById($attentionId, $attentionToId) {
        $query = $this->entity;
        $query = $query->where('attention_person',$attentionId)
        ->orWhere('attention_to_person',$attentionToId)->orWhere('attention_id', $attentionToId);
        return $query->get()->toArray();
    }

    public function getAllGroupUser($groupId, $userId) {
        $query = $this->entity;
        if ($groupId) {
            $query = $query->where('group_id',$groupId);
        } else {
            $query = $query->where('attention_person', $userId)->whereNull('group_id');
        }
        
        return $query->where('attention_status', 2)->get()->toArray();
    }
    public function getNoGroupUser($userId) {
        $query = $this->entity;
        $query = $query->where('attention_person', $userId)->where('attention_status', '2')->whereNull('group_id');
        return $query->get()->toArray();
    }
    public function updateAttentionUserRead($attentionUserId,$attentionToUserId) {
        return $query = $this->entity
            ->where('attention_person', $attentionUserId)
            ->whereIn('attention_to_person', $attentionToUserId)
            ->update(['is_read' => 0]);
    }

    public function SetAttentionUserUnread($attentionToUserId, $data) {
        return $query = $this->entity
            ->whereIn('attention_to_person', $attentionToUserId)
            ->update($data);
    }
    public function getAllAttentionUser($userIds) {
        return $query = $this->entity->select(['attention_person', 'attention_to_person'])
            ->whereIn('attention_to_person', $userIds)
            ->where('attention_status', 2)
            ->get()->toArray();
    }

}
