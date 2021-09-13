<?php

namespace App\EofficeApp\Calendar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Calendar\Entities\CalendarEntity;

/**
 * @日程资源库类
 *
 *
 */
class CalendarRepository extends BaseRepository 
{
    private $limit = 10; //列表页默认条数
    private $orderBy = ['calendar_begin' => 'desc']; //默认排序

    /**
     * @注册日程实体
     * @param \App\EofficeApp\Entities\MeetingRecordsEntity $entity
     */

    public function __construct(CalendarEntity $entity) 
    {
        parent::__construct($entity);
    }

    /**
     * @获取我的日程日历初始列表
     * @param type $param
     * @return 日程列表 | array
     */
    public function getInitList($param, $userId)
    {
        $user = $param['user_id'] ?? $userId;
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['calendar.*', 'calendar_type.*'];
        $query = $this->entity->select($param['fields'])->leftJoin('calendar_type', 'calendar.calendar_type_id', 'calendar_type.type_id');
        // 日程的结束时间大于当前月的开始时间
        if (isset($param['calendar_begin']) && $param['calendar_begin'] != "") {
            $query = $query->where(function($query) use($param) {
                $query->where('calendar_end', '>=', $param['calendar_begin'])->orWhere('calendar_end' , '0000-00-00 00:00:00');
            });
        }
        // // 日程的开始时间小于当前月的结束时间
        if (isset($param['calendar_end']) && $param['calendar_end'] != "") {
            $query = $query->where('calendar_begin', '<', $param['calendar_end']);
        }
        if (isset($param['calendar_type_id'])) {
            $query = $query->whereIn('calendar_type_id', $param['calendar_type_id']);
        }
        $param['order_by'] = empty($param['order_by']) ? $this->orderBy : $param['order_by'];
        $query = $query->where('repeat_remove', '=', 0)->orders($param['order_by']);
        if (isset($param['type']) && $param['type'] == 'my' && (!isset($param['filtertype']) || $param['filtertype'] == 'my')) {
            $query = $query->where(function($query) use($param, $userId) {
                $query->WhereExists(function ($query) use($userId) {
                            $query->select(['calendar_handle_user_relation.calendar_id'])
                                    ->from('calendar_handle_user_relation')
                                    ->where('calendar_handle_user_relation.user_id', $userId)
                                    ->where('calendar_handle_user_relation.calendar_status', 0)
                                    ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                        })->orWhereExists(function ($query) use($userId) {
                            $query->select(['calendar_share_user_relation.calendar_id'])
                                    ->from('calendar_share_user_relation')
                                    ->where('calendar_share_user_relation.user_id', $userId)
                                    ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                        });
            });
            
        } else if (isset($param['type']) && ($param['type'] == 'my' || $param['type'] == 'other') && isset($param['filtertype']) && $param['filtertype'] == 'handle') {
            $query = $query->leftJoin('calendar_handle_user_relation as handle', 'handle.calendar_id', 'calendar.calendar_id');
            if ($user && $user != 'subordinate_all' && $user != 'attention_all') {
                $query = $query->where('handle.user_id', $user)->where('handle.calendar_status', 0);
            } else {
                $query = $query->where('handle.user_id', $userId)->where('handle.calendar_status', 0);
            }
        } else if (isset($param['type']) && ($param['type'] == 'my' || $param['type'] == 'other') && isset($param['filtertype']) && $param['filtertype'] == 'share') {
            $query = $query->leftJoin('calendar_share_user_relation as share', 'share.calendar_id', 'calendar.calendar_id');
            if ($user) {
                $query = $query->where('share.user_id', $user);
            } else {
                $query = $query->where('share.user_id', $userId);
            }
        } else if (isset($param['type']) && ($param['type'] == 'my' || $param['type'] == 'other') && isset($param['filtertype']) &&$param['filtertype'] == 'complete') {
            $query = $query->where(function($query) use($param, $user) {
                    $query->WhereExists(function ($query) use($user) {
                                $query->select(['calendar_handle_user_relation.calendar_id'])
                                        ->from('calendar_handle_user_relation')
                                        ->where('calendar_handle_user_relation.user_id', $user)
                                        ->where('calendar_handle_user_relation.calendar_status', 1)
                                        ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                            });
                });
            // $query->addSelect('calendar_handle_user_relation.calendar_status')->leftJoin('calendar_handle_user_relation', 'calendar.calendar_id', 'calendar_handle_user_relation.calendar_id')->where('calendar_handle_user_relation.calendar_status', 1);

        } else if (isset($param['type']) && $param['type'] == 'other') {
            if (isset($param['user_id']) && ($param['user_id'] == 'attention_all' || $param['user_id'] == 'subordinate_all')) {
                if(isset($param['user_scope'])) {
                    $query = $query->where(function($query) use($param) {
                        $query->whereExists(function ($query) use($param) {
                                $query->select(['calendar_handle_user_relation.calendar_id'])
                                        ->from('calendar_handle_user_relation')
                                        ->whereIn('calendar_handle_user_relation.user_id', $param['user_scope'])
                                        ->where('calendar_handle_user_relation.calendar_status', 0)
                                        ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                            })->orWhereExists(function ($query) use($param) {
                                $query->select(['calendar_share_user_relation.calendar_id'])
                                        ->from('calendar_share_user_relation')
                                        ->whereIn('calendar_share_user_relation.user_id', $param['user_scope'])
                                        ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                            });
                    });
                }
            } else  {
                $query = $query->where(function($query) use($param, $user) {
                    $query->WhereExists(function ($query) use($user) {
                                $query->select(['calendar_handle_user_relation.calendar_id'])
                                        ->from('calendar_handle_user_relation')
                                        ->where('calendar_handle_user_relation.user_id', $user)
                                        ->where('calendar_handle_user_relation.calendar_status', 0)
                                        ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                            })->orWhereExists(function ($query) use($user) {
                                $query->select(['calendar_share_user_relation.calendar_id'])
                                        ->from('calendar_share_user_relation')
                                        ->where('calendar_share_user_relation.user_id', $user)
                                        ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                            });
                });
            }
        } 
        if (isset($param['limit']) && isset($param['page'])) {
            $query = $query->parsePage($param['page'], $param['limit']);
        }
        return $query->get();
    }

    /**
     * @获取日程条数
     * @param type $param
     * @return 日程条数 | int
     */
    public function getTotals($param, $userId) 
    {
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['*'];
        $query = $this->entity->select($param['fields'])
                ->leftJoin('calendar_handle_user_relation', 'calendar_handle_user_relation.calendar_id', 'calendar.calendar_id')
                ->leftJoin('calendar_share_user_relation', 'calendar_share_user_relation.calendar_id', 'calendar.calendar_id');
        if (isset($param['type']) && $param['type'] == 'init') {
            //我的日程
            $query = $query->where(function($query) use($userId) {
                $query = $query->where('calendar_handle_user_relation.user_id', $userId)
                        ->orWhere('calendar_share_user_relation.user_id', $userId);
            });
            //我的下属、关注人日程、我的日程
            if (!empty($param['initdate'])) {
                $query = $query->orWhere(function($query) use($param) {
                    foreach ($param['initdate'] as $user_id) {
                        $query = $query->where('calendar_handle_user_relation.user_id', $userId);
                    }
                });
            }
        }
        if (isset($param['type']) && $param['type'] == 'my') {
            $query = $query->where(function($query) use($userId) {
                $query = $query->where('calendar_handle_user_relation.user_id', $userId)
                        ->orWhere('calendar_share_user_relation.user_id', $userId);
            });
        }
        if (isset($param['type']) && $param['type'] == 'mycalendar') {
            $query = $query->where('calendar_handle_user_relation.user_id', $userId);
        }
        if (isset($param['type']) && $param['type'] == 'myshare') {
            $query = $query->where('calendar_share_user_relation.user_id', $userId);
        }
        if (isset($param['type']) && $param['type'] == 'other') {
            if ($param['user_id'] == "attention_all" || $param['user_id'] == "subordinate_all") {
                if (!empty($param['initdate'])) {
                    $query = $query->orWhere(function($query) use($param) {
                        foreach ($param['initdate'] as $user_id) {
                            $query = $query->where('calendar_handle_user_relation.user_id', $user_id);
                        }
                    });
                } else {
                    $query = $query->where('calendar_id', '');
                }
            } else {
                $query = $query->where('calendar_handle_user_relation.user_id', $userId);
            }
        }
        if (isset($param['calendar_begin']) && $param['calendar_begin'] != "") {
            $query = $query->where('calendar_begin', '>=', $param['calendar_begin']);
        }
        if (isset($param['calendar_end']) && $param['calendar_end'] != "") {
            $query = $query->where('calendar_end', '<=', $param['calendar_end']);
        }
        return $query->where('repeat_remove', '=', 0)->count();
    }

    /**
     * @获取日程管理列表
     * @param type $param
     * @return 日程列表 | array
     */
    public function getCalendarList($param, $userId, $type = 'all') 
    {
        $userId = $param['create_id'] ?? $userId;
        return $this->handleCalendarQuery($param, $userId, $type, function() use($param) {
                    $fields = isset($param['fields']) ? $param['fields'] : ['calendar.*'];
                    return $this->entity->select($fields);
                }, function($query) use ($param) {
                    $limit = empty($param['limit']) ? $this->limit : $param['limit'];
                    $orderBy = empty($param['order_by']) ? $this->orderBy : $param['order_by'];
                    $page = empty($param['page']) ? 1 : $param['page'];
                    return $query->orders($orderBy)->parsePage($page, $limit)->get()->toArray();
                });
    }
 /**
     * @获取日程管理列表数量
     * @param type $param
     * @return 日程列表 | array
     */
    public function getCalendarTotal($param, $userId, $type = 'all') 
    {
        $userId = $param['create_id'] ?? $userId;
        return $this->handleCalendarQuery($param, $userId, $type, function() {
                    return $this->entity;
                }, function($query) {
                    return $query->count();
                });
    }
    private function handleCalendarQuery($param, $userId, $type, $before, $terminal)
    {
        $query = $before();
        $query = $query->where('calendar_parent_id', '=', 0)->with('calendarHasManyHandle');
        if ($type === 'mycalendar') {
            $query = $query->whereExists(function ($query) use($userId) {
                $query->select(['calendar_handle_user_relation.calendar_id'])
                        ->from('calendar_handle_user_relation')
                        ->where('calendar_handle_user_relation.user_id', $userId)
                        ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
            });
        } else if ($type === 'mycreate' || $type === 'minecreate') {
            $query = $query->where('creator', $userId);
            $query->where(function($query) use($userId, $param) {
                    if (!empty($param['user_scope']) && in_array($param['create_id'], $param['user_scope'])) {
                        $query->orWhereExists(function ($query) use($param) {
                            $query->select(['calendar_handle_user_relation.calendar_id'])
                                    ->from('calendar_handle_user_relation')
                                    ->whereIn('calendar_handle_user_relation.user_id', $param['user_scope'])
                                    ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['calendar_share_user_relation.calendar_id'])
                                    ->from('calendar_share_user_relation')
                                    ->whereIn('calendar_share_user_relation.user_id', $param['user_scope'])
                                    ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                        });
                    } else {
                        if(!empty($param['user_scope'])){
                            $query->orWhereExists(function ($query) use($param) {
                                $query->select(['calendar_handle_user_relation.calendar_id'])
                                        ->from('calendar_handle_user_relation')
                                        ->whereIn('calendar_handle_user_relation.user_id', $param['user_scope'])
                                        ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                            })->orWhereExists(function ($query) use($param) {
                                $query->select(['calendar_share_user_relation.calendar_id'])
                                        ->from('calendar_share_user_relation')
                                        ->whereIn('calendar_share_user_relation.user_id', $param['user_scope'])
                                        ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                            });
                        }
                    }
            });
        } else if ($type === 'myshare') {
            $query = $query->whereExists(function ($query) use($userId) {
                $query->select(['calendar_share_user_relation.calendar_id'])
                        ->from('calendar_share_user_relation')
                        ->where('calendar_share_user_relation.user_id', $userId)
                        ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
            });
        }  else if ($type === 'all') {
            $query = $query->where(function($query) use($param, $userId) {
                $query->where('creator', $userId)
                       ->orWhereExists(function ($query) use($param) {
                            $query->select(['calendar_handle_user_relation.calendar_id'])
                                    ->from('calendar_handle_user_relation')
                                    ->whereIn('calendar_handle_user_relation.user_id', $param['user_scope'])
                                    ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['calendar_share_user_relation.calendar_id'])
                                    ->from('calendar_share_user_relation')
                                    ->whereIn('calendar_share_user_relation.user_id', $param['user_scope'])
                                    ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                        });
            });
        } else {
            if(isset($param['user_scope'])) {
                $query = $query->where(function($query) use($param) {
                    $query->whereExists(function ($query) use($param) {
                            $query->select(['calendar_handle_user_relation.calendar_id'])
                                    ->from('calendar_handle_user_relation')
                                    ->whereIn('calendar_handle_user_relation.user_id', $param['user_scope'])
                                    ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['calendar_share_user_relation.calendar_id'])
                                    ->from('calendar_share_user_relation')
                                    ->whereIn('calendar_share_user_relation.user_id', $param['user_scope'])
                                    ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                        });
                });
            }
        }
        if (!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        
        return $terminal($query);
    }

    public function getCalendarAllIdList($param, $userId) {
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['*'];
        $query = $this->entity->select(['calendar_id'])->leftJoin('calendar_handle_user_relation as handle', 'handle.calendar_id', 'calendar.calendar_id')
                ->leftJoin('calendar_share_user_relation as share', 'share.calendar_id', 'calendar.calendar_id');;
        if (isset($param['type']) && $param['type'] === 'all') {

            $query = $query->where(function($query) use($param, $userId) {
                if (!empty($param['initdate'])) {
                    foreach ($param['initdate'] as $user_id) {
                        $query = $query->where('handle.user_id', $user_id);
                    }
                }
                $query = $query->where('handle.user_id', $userId)
                        ->orWhere('share.user_id', $userId)
                        ->orWhere('creator', $userId);
            });
        }
        return $query->get()->toArray();
    }
    /**
     * @获取重复日程列表
     * @param type $recordId
     * @return boolean
     */
    public function getRepeatCalendarList($calendarId, $param) 
    {
        $query = $this->entity->with('calendarHasManyHandle')->with('calendarHasManyShare')
                ->where(function($query) use($calendarId) {
                    $query->where('calendar_parent_id', '=', $calendarId)
                    ->orWhere('calendar_id', '=', $calendarId);
                });

        $param['limit'] = empty($param['limit']) ? $this->limit : $param['limit'];

        $param['order_by'] = empty($param['order_by']) ? ['calendar_begin' => 'asc'] : $param['order_by'];
        $param['page'] = empty($param['page']) ? 1 : $param['page'];
        if (isset($param['search']) && isset($param['search']['calendar_begin'])) {

            $query = $query->where(function($query) use($param) {
                $query->where('calendar_begin', '>=', $param['search']['calendar_begin'][0])
                        ->orWhere('calendar_end', '>', $param['search']['calendar_begin'][0]);
            });
            return $query->orders($param['order_by'])
                            ->parsePage($param['page'], $param['limit'])->get()->toArray();
        } elseif (isset($param['search']) && isset($param['search']['calendar_end'])) {
            if (!empty($param['search'])) {
                $query = $query->wheres($param['search']);
            }
            return $query->orderBy('calendar_begin', 'desc')
                            ->parsePage($param['page'], $param['limit'])->get()->toArray();
        } else {
            return $query->orders($param['order_by'])
                            ->parsePage($param['page'], $param['limit'])->get()->toArray();
        }
    }

    /**
     * @获取重复日程列表
     * @param type $calendarId
     * @return boolean
     */
    public function getOnlyRepeatCalendarList($calendarId) 
    {
        return $this->entity->where('calendar_parent_id', '=', $calendarId)->where('repeat_remove', '=', 0)->get()->toArray();
    }

    /**
     * @获取重复日程总数
     * @param type $recordId
     * @return boolean
     */
    public function getRepeatCalendarTotal($calendarId, $param) 
    {
        $query = $this->entity->where(function($query) use($calendarId) {
            $query->where('calendar_parent_id', '=', $calendarId)
                    ->orWhere('calendar_id', '=', $calendarId);
        });
        if (isset($param['search']) && isset($param['search']['calendar_begin'])) {
            $query = $query->where(function($query) use($param) {
                $query->where('calendar_begin', '>=', $param['search']['calendar_begin'][0])
                        ->orWhere('calendar_end', '>', $param['search']['calendar_begin'][0]);
            });
        } elseif (isset($param['search']) && isset($param['search']['calendar_end'])) {
            if (!empty($param['search'])) {
                $query = $query->wheres($param['search']);
            }
        }
        return $query->count();
    }
	/**
     * @获取单条日程
     * @param type $calendar_id 日程id
     * @return  | array
     */
    public function getCalendarOne($calendar_id) 
    {
        return $this->entity->select(['calendar.*', 'calendar_type.*'])
                ->leftJoin('calendar_type', 'calendar.calendar_type_id', 'calendar_type.type_id')
                ->with('calendarHasManyHandle')->with('calendarHasManyShare')
                ->where('calendar.calendar_id', '=', $calendar_id)->get()->toArray();
    }
    public function getConflictCalendarList($calendar_id) 
    {
        return $this->entity->select(['calendar.*', 'calendar_type.*'])
                ->leftJoin('calendar_type', 'calendar.calendar_type_id', 'calendar_type.type_id')
                ->with('calendarHasManyHandle')->with('calendarHasManyShare')
                ->whereIn('calendar.calendar_id',$calendar_id)->get()->toArray();
    }
    /**
     * @删除日程及所有重复日程
     * @param type $calendarId
     * @return boolean
     */
    public function deleteAllRepeatById($calendarId)
    {
        return $this->entity->where('calendar_id',$calendarId)->orWhere('calendar_parent_id',$calendarId)->delete();
    }
    /**
     * @根绝日程id获取日程和重复日程id
     * @param type $calendarId
     * @return boolean
     */
    public function getAllRepeatById($calendarId)
    {
        return $this->entity->select(['calendar_id'])->where('calendar_id',$calendarId)
        ->orWhere('calendar_parent_id',$calendarId)->get()->toArray();
    }
    public function getAllRepeatCalendarIdById($calendarId)
    {
        return $this->entity->select(['calendar_id'])->where('calendar_parent_id',$calendarId)->get()->toArray();
    }
    /**
     * @删除所有重复日程
     * @param type $calendarId
     * @return boolean
     */
    public function deleteAllRepeatByParentId($calendarId)
    {
        return $this->entity->where('calendar_parent_id',$calendarId)->delete();
    }
    /**
     * @判断某一天有无日程
     * @param  string  $date 日期 2019-09
     *
     * @param  string  $userId 用户id
     * @return boolean
     */
    public function getCalendarMonthHasDate($date, $userId) 
    {
        $start = date("Y-m-d 00:00:00", strtotime($date));
        $end = date("Y-m-d 23:59:59", strtotime($date));
        $query = $this->entity->select(['calendar.calendar_id','calendar.creator', 'calendar_level','calendar_type_id', 'calendar_begin', 'calendar_end', 'calendar_content', 'repeat'])
                ->where(function($query) use($start) {
                    $query->where("calendar_end", ">=", $start)->orWhere('calendar_end', '0000-00-00 00:00:00');
                })
                ->where("calendar_begin", "<=", $end)
                ->where('repeat_remove', '=', 0)
                ->orders(['calendar_begin' => 'desc']);
                $query = $query->where(function($query) use($userId) {
                $query->WhereExists(function ($query) use($userId) {
                            $query->select(['calendar_handle_user_relation.calendar_id'])
                                    ->from('calendar_handle_user_relation')
                                    ->where('calendar_handle_user_relation.user_id', $userId)
                                    ->where('calendar_handle_user_relation.calendar_status', 0)
                                    ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                        })->orWhereExists(function ($query) use($userId) {
                            $query->select(['calendar_share_user_relation.calendar_id'])
                                    ->from('calendar_share_user_relation')
                                    ->where('calendar_share_user_relation.user_id', $userId)
                                    ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                        });
            });
                return $query->get();
    }
    public function getCalendarsByDateRange($start, $end, $userId)
    {
        return $this->entity->select(['calendar.calendar_id','calendar.creator', 'calendar_level', 'calendar_begin', 'calendar_end', 'calendar_content', 'repeat'])
                ->where("calendar_end", ">=", $start)
                ->where("calendar_begin", "<=", $end)
                ->where(function($query) use($userId) {
                    $query->whereExists(function ($query) use($userId) {
                            $query->select(['calendar_handle_user_relation.calendar_id'])
                                    ->from('calendar_handle_user_relation')
                                    ->where('calendar_handle_user_relation.user_id', $userId)
                                    ->where('calendar_handle_user_relation.calendar_status', 0)
                                    ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                        })->orWhereExists(function ($query) use($userId) {
                            $query->select(['calendar_share_user_relation.calendar_id'])
                                    ->from('calendar_share_user_relation')
                                    ->where('calendar_share_user_relation.user_id', $userId)
                                    ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                        });
                })->get();
    }
    /**
     * 获取即将开始的日程列表
     *
     * @param
     *
     * @return array 即将开始的日程列表
     *
     */
    public function listBeginCalendar($begin, $end) 
    {
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['*'];
        $param['order_by'] = isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
        return $this->entity
                ->select($param['fields'])->with('calendarHasManyHandle')->with('calendarHasManyShare')
                ->whereBetween('remind_start_datetime', [$begin, $end])
                ->where('calendar_end', '>=', date('Y-m-d') . ' 00:00:00')
                ->where('allow_remind', 1)
                ->where('start_remind', 1)
                ->where('repeat_remove', '=', 0)
                ->orders($param['order_by'])
                ->get()->toArray();
    }

    /**
     * 获取即将结束的日程列表
     *
     * @param
     *
     * @return array 即将开始的日程列表
     *
     */
    public function listEndCalendar($begin, $end) 
    {
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['*'];
        $param['order_by'] = isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
        return $this->entity
                ->select($param['fields'])->with('calendarHasManyHandle')->with('calendarHasManyShare')
                ->whereBetween('remind_end_datetime', [$begin, $end])
                ->where('calendar_end', '>=', date('Y-m-d') . ' 00:00:00')
                ->where('allow_remind', 1)
                ->where('end_remind', 1)
                ->where('repeat_remove', '=', 0)
                ->orders($param['order_by'])
                ->get()->toArray();
    }

    /**
     * 判断当前用户是否有权限查看日程
     *
     */
    public function hasViewPermission($userId, $calendarId, $param) 
    {
        if (empty($param['user_scope'])) {
            return $this->entity->leftJoin('calendar_handle_user_relation as handle', 'handle.calendar_id', 'calendar.calendar_id')
                        ->leftJoin('calendar_share_user_relation as share', 'share.calendar_id', 'calendar.calendar_id')
                ->where('calendar.calendar_id', '=', $calendarId)
                ->where(function($query) use($userId) {
                    $query->where('handle.user_id', $userId)->orWhere('share.user_id', $userId)->orWhere('creator', $userId);
                })->find($calendarId);
        } else {
            $query = $this->entity->select(['calendar.*', 'calendar_type.*'])->leftJoin('calendar_type', 'calendar.calendar_type_id', 'calendar_type.type_id');
            if(!empty($param['user_scope'])) {
                $query = $query->where(function($query) use($param) {
                    $query->whereExists(function ($query) use($param) {
                            $query->select(['calendar_handle_user_relation.calendar_id'])
                                    ->from('calendar_handle_user_relation')
                                    ->whereIn('calendar_handle_user_relation.user_id', $param['user_scope'])
                                    ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['calendar_share_user_relation.calendar_id'])
                                    ->from('calendar_share_user_relation')
                                    ->whereIn('calendar_share_user_relation.user_id', $param['user_scope'])
                                    ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                        });
                });
                return $query->where('calendar.calendar_id', '=', $calendarId)->get()->toArray();
            } 
        }
    }

    public function isCalendarCreator($userId, $calendarId) 
    {
        return $this->entity->where('calendar_id', $calendarId)->where('creator', $userId)->count();
    }

    public function getScheduleViewList($search, $userId)
    {
        $query = $this->entity->select(['*'])->WhereExists(function ($query) use($userId) {
            $query->select(['calendar_handle_user_relation.calendar_id'])
                    ->from('calendar_handle_user_relation')
                    ->whereIn('calendar_handle_user_relation.user_id', $userId)
                    ->where('calendar_handle_user_relation.calendar_status', 0)
                    ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
        });

        if (!empty($search)) {
            $query = $query->where('calendar.repeat_remove', 0)->wheres($search);
        }
       return $query->get()->toArray();
    }

    public function getCalendarsByDateScrope($calendarBegin, $calendarEnd)
    {
        return $this->entity
                ->select(['calendar_id', 'calendar_begin', 'calendar_end','calendar_content'])
                ->where('calendar_begin', '<=', $calendarEnd)
                ->where('calendar_end', '>=', $calendarBegin)
                ->where('calendar.repeat_remove', 0)
                ->get();
    }

    public function getCalendarBeside($calendar_id, $calendar_begin, $calendar_end) 
    {
        return $this->entity
                ->select(['calendar_id', 'calendar_begin', 'calendar_end', 'calendar_content'])
                ->where('calendar_id', '!=', $calendar_id)
                ->where('calendar_begin', '<=', $calendar_end)
                ->where('calendar_end', '>=', $calendar_begin)
                ->get()->toArray();
    }

    public function listCalendarRepeatNever() 
    {
        return $this->entity->select(['*'])->where('repeat_end_type', '=', 0)->where('repeat', '=', 1)->get()->toArray();
    }

    public function getLastRepeatEnd($end, $parentId)
    {
        return $this->entity
                ->select(['*'])
                ->where('calendar_parent_id', $parentId)
                ->where('calendar_end', $end)
                ->get()->toArray();
    }

    public function getRepeatMaxId($parentId) 
    {
        return $this->entity
                ->select(['*'])->with('calendarHasManyHandle')->with('calendarHasManyShare')
                ->where('calendar_parent_id', $parentId)
                ->orderBy('calendar_id', 'desc')
                ->limit(1)
                ->get()->toArray();
    }
    public function getGroupCalendarList($userIds, $param)
    {
        $query = $this->entity->leftJoin('calendar_type', 'calendar.calendar_type_id', 'calendar_type.type_id');
        // 日程的结束时间大于当前月的开始时间
        if (isset($param['calendar_begin']) && $param['calendar_begin'] != "") {
            $query = $query->where('calendar_end', '>=', $param['calendar_begin']);
        }
        // // 日程的开始时间小于当前月的结束时间
        if (isset($param['calendar_end']) && $param['calendar_end'] != "") {
            $query = $query->where('calendar_begin', '<', $param['calendar_end']);
        }
        if (isset($param['calendar_type_id'])) {
            $query = $query->whereIn('calendar_type_id', $param['calendar_type_id']);
        }
        if (isset($param['type']) && ($param['type'] == 'my' || $param['type'] == 'other') && (!isset($param['filtertype']) || $param['filtertype'] == 'my')) {
             $query = $query->where(function($query) use($userIds) {
                $query->WhereExists(function ($query) use($userIds) {
                    $query->select(['calendar_handle_user_relation.calendar_id'])
                            ->from('calendar_handle_user_relation')
                            ->whereIn('calendar_handle_user_relation.user_id', $userIds)
                            ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id')
                            ->where('calendar_handle_user_relation.calendar_status', 0);
                })->orWhereExists(function ($query) use($userIds) {
                    $query->select(['calendar_share_user_relation.calendar_id'])
                            ->from('calendar_share_user_relation')
                            ->whereIn('calendar_share_user_relation.user_id', $userIds)
                            ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                });
            });
        } else if (isset($param['type']) && isset($param['filtertype']) && $param['filtertype'] == 'handle') {
            $query = $query->leftJoin('calendar_handle_user_relation as handle', 'handle.calendar_id', 'calendar.calendar_id');
            $query = $query->whereIn('handle.user_id', $userIds)->where('handle.calendar_status', 0);
        } else if (isset($param['type']) && isset($param['filtertype']) && $param['filtertype'] == 'share') {
            $query = $query->leftJoin('calendar_share_user_relation as share', 'share.calendar_id', 'calendar.calendar_id');
            $query = $query->whereIn('share.user_id', $userIds);
        }
        return $query->where('repeat_remove', '=', 0)->get()->toArray();
    }

    public function getCalendarDetail($calendarId, $userId = '', $params) 
    {
        $userId = $params['user_scope'] ?? [$userId];
        $fields = [
            'calendar.*', 'calendar_outer.calendar_id as outer_calendar_id','calendar_outer.module_id', 'calendar_outer.source_id', 'calendar_outer.source_title', 'calendar_outer.source_from', 'calendar_outer.source_params'
        ];
        if (isset($params['from']) && $params['from'] == 'mine') {
            // $fields = array_merge($fields, ['calendar_handle_user_relation.calendar_id as hcalendar_id', 'calendar_handle_user_relation.user_id', 'calendar_handle_user_relation.calendar_status', 'calendar_share_user_relation.calendar_id as scalendar_id', 'calendar_share_user_relation.user_id']);
        }
        $query = $this->entity->select($fields)->leftJoin('calendar_outer', 'calendar.calendar_id', 'calendar_outer.calendar_id');
        if (isset($params['from']) && $params['from'] == 'mine') {
           $query = $query->where(function($query) use($userId) {
                $query->WhereExists(function ($query) use($userId) {
                    $query->select(['calendar_handle_user_relation.calendar_id', 'calendar_handle_user_relation.calendar_status'])
                            ->from('calendar_handle_user_relation')
                            ->whereIn('calendar_handle_user_relation.user_id', $userId)
                            ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                })->orWhereExists(function ($query) use($userId) {
                    $query->select(['calendar_share_user_relation.calendar_id'])
                            ->from('calendar_share_user_relation')
                            ->whereIn('calendar_share_user_relation.user_id', $userId)
                            ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                });
            });
        }
        return $query->where('calendar.calendar_id', $calendarId)->get()->toArray();
    }
    public function getPortalCalendarList($param, $userId)
    {
        $param['returntype'] = isset($param['returntype']) ? $param['returntype'] : 'array';
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['calendar.*'];
        $query = $this->entity->select($param['fields']);
        // 日程的结束时间大于当前月的开始时间
        
        $param['order_by'] = empty($param['order_by']) ? $this->orderBy : $param['order_by'];
        $query = $query->where('repeat_remove', '=', 0)->with('calendarHasManyHandle')->orders($param['order_by']);
    
        $query = $query->where(function($query) use($param, $userId) {
            $query->WhereExists(function ($query) use($userId) {
                        $query->select(['calendar_handle_user_relation.calendar_id'])
                                ->from('calendar_handle_user_relation')
                                ->where('calendar_handle_user_relation.user_id', $userId)
                                ->where('calendar_handle_user_relation.calendar_status', 0)
                                ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                    })->orWhereExists(function ($query) use($userId) {
                        $query->select(['calendar_share_user_relation.calendar_id'])
                                ->from('calendar_share_user_relation')
                                ->where('calendar_share_user_relation.user_id', $userId)
                                ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                    });
        });
        if (isset($param['returntype']) && $param['returntype'] == 'array') {
            $limit = empty($param['limit']) || !isset($param['limit']) ? $this->limit : $param['limit'];
            $orderBy = empty($param['order_by']) || !isset($param['order_by']) ? $this->orderBy : $param['order_by'];
            $page = empty($param['page']) || !isset($param['page']) ? 1 : $param['page'];
            return $query->orders($orderBy)->parsePage($page, $limit)->get();
        } else {
            return $query->count();
        }
    }
    public function getPortalCalendarCount($param, $userId) {
        $param['returntype'] = 'count';
        return $this->getPortalCalendarList($param, $userId);
    }
    public function getInitListSelector($param, $userId)
    {
        $user = $param['user_id'] ?? '';
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['calendar.*', 'calendar_type.*'];
        $query = $this->entity->select($param['fields'])->leftJoin('calendar_type', 'calendar.calendar_type_id', 'calendar_type.type_id')->with('calendarHasManyHandle');
        
        $param['order_by'] = empty($param['order_by']) ? $this->orderBy : $param['order_by'];
        $query = $query->where('repeat_remove', '=', 0)->orders($param['order_by'])->whereIn('creator', $userId);
        $query = $query->orWhere(function($query) use($param, $userId) {
            $query->WhereExists(function ($query) use($userId) {
                        $query->select(['calendar_handle_user_relation.calendar_id'])
                                ->from('calendar_handle_user_relation')
                                ->whereIn('calendar_handle_user_relation.user_id', $userId)
                                ->where('calendar_handle_user_relation.calendar_status', 0)
                                ->whereRaw('calendar_handle_user_relation.calendar_id=calendar.calendar_id');
                    })->orWhereExists(function ($query) use($userId) {
                        $query->select(['calendar_share_user_relation.calendar_id'])
                                ->from('calendar_share_user_relation')
                                ->whereIn('calendar_share_user_relation.user_id', $userId)
                                ->whereRaw('calendar_share_user_relation.calendar_id=calendar.calendar_id');
                    });
        });
        
        if (isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        if (isset($param['returntype']) && $param['returntype'] == 'array') {
            if (isset($param['limit']) && isset($param['page'])) {
                $query = $query->parsePage($param['page'], $param['limit']);
            }
             return $query->get();
        } else {
             return $query->count();
        }
    }
}
