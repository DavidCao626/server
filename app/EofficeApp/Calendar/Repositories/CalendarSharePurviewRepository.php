<?php

namespace App\EofficeApp\Calendar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Calendar\Entities\CalendarPurviewEntity;

/**
 * @日程资源库类
 *
 *
 */
class CalendarSharePurviewRepository extends BaseRepository 
{
    private $limit = 10; //列表页默认条数

    private $orderBy = ['id' => 'desc']; //默认排序

    /**
     * @注册日程实体
     */

    public function __construct(CalendarPurviewEntity $entity) 
    {
        parent::__construct($entity);
    }

    public function getCalendarPurviewList($param) {
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['calendar_purview.id', 'calendar_purview.group_name', 'calendar_purview.remark'];
        $param['search'] = $param['search'] ?? [];
        $query = $this->entity;
        $query = $query->with('calendarPurviewHasManyUser')
              ->with('calendarPurviewHasManyDept')
              ->with('calendarPurviewHasManyRole')
              ->with('calendarPurviewHasManyManageUser')
              ->wheres($param['search']);
        $param['order_by'] = empty($param['order_by']) ? $this->orderBy : $param['order_by'];
        $query = $query->orders($param['order_by']);
        if (isset($param['limit']) && isset($param['page'])) {
            $query = $query->parsePage($param['page'], $param['limit']);
        }

        return $query->get();

    }

    public function getCalendarPurviewCount($param) {
        $param['search'] = $param['search'] ?? [];
        return $this->entity->wheres($param['search'])->count();
    }

    public function getCalendarPurviewDetail($id)
    {
        return $this->entity->with('calendarPurviewHasManyUser')
              ->with('calendarPurviewHasManyDept')
              ->with('calendarPurviewHasManyRole')
              ->with('calendarPurviewHasManyManageUser')
              ->where('id', $id)->get()->toArray();
    }
    public function getMyCalendarPurview($param, $own)
    {
        $userId = $own['user_id'] ?? '';
        $deptId = $own['dept_id'] ?? '';
        $roleId = $own['role_id'] ?? '';
        $query = $this->entity;
        $query = $query->where(function($query) use($param, $userId, $deptId, $roleId) {
                $query->WhereExists(function ($query) use($userId) {
                            $query->select(['calendar_purview_user.group_id'])
                                    ->from('calendar_purview_user')
                                    ->where('calendar_purview_user.user_id', $userId)
                                    ->whereRaw('calendar_purview_user.group_id=calendar_purview.id');
                        })->orWhereExists(function ($query) use($deptId) {
                            $query->select(['calendar_purview_dept.group_id'])
                                    ->from('calendar_purview_dept')
                                    ->where('calendar_purview_dept.dept_id', $deptId)
                                    ->whereRaw('calendar_purview_dept.group_id=calendar_purview.id');
                        })->orWhereExists(function ($query) use($roleId) {
                            $query->select(['calendar_purview_role.group_id'])
                                    ->from('calendar_purview_role')
                                    ->where('calendar_purview_role.role_id', $roleId)
                                    ->whereRaw('calendar_purview_role.group_id=calendar_purview.id');
                        });
            });
        $query = $query->with('calendarPurviewHasManyManageUser');
        if (isset($param['limit']) && isset($param['page'])) {
            $query = $query->parsePage($param['page'], $param['limit']);
        }
        return $query->get()->toArray();
    }
}
