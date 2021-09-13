<?php

namespace App\EofficeApp\Notify\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Notify\Entities\NotifyEntity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 公告资源库类
 *
 * @author 李志军
 *
 * @since 2015-10-20
 */
class NotifyRepository extends BaseRepository
{
    /** @var int 默认列表条数 */
    private $limit = 20;

    /** @var int 默认列表页 */
    private $page = 0;

    /** @var array  默认排序 */
    private $orderBy = ['created_at' => 'desc'];

    /**
     * 注册公告实体对象
     *
     * @param \App\EofficeApp\Notify\Entities\NotifyEntity $entity
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function __construct(NotifyEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取公告详情
     *
     * @param int $notifyId
     *
     * @return object 公告详情
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function showNotify($notifyId)
    {
        $query = $this->entity->where(function ($query) {
        })->with([
            'notifyType' => function ($query) {
                $query->select(['notify_type_id', 'notify_type_name']);
            }
        ])->with([
            'user' => function ($query) {
                $query->withTrashed()->select(['user_id', 'user_name']);
            }
        ])->find($notifyId);
        return $query;
    }

    /**
     * 获取公告详情权限
     *
     * @param int $notifyId $param
     *
     *
     */
    public function showNotifyAccess($notifyId, $param)
    {
        $own = $param['own'];
        $own['dept_id'] = isset($own['dept_id']) ? $own['dept_id'] : '';
        $query = $this->entity->where(function ($query) use ($own) {
            $query = $query->where('notify.from_id', $own['user_id'])
                ->orWhere(function ($query) use ($own) {
                    $query = $query->where(function ($query) use ($own) {
                        $query->where('notify.priv_scope', 1)
                            ->orWhere(function ($query) use ($own) {
                                $query->orWhereRaw('find_in_set(?,notify.user_id)' , [$own['user_id']])->orWhereRaw('find_in_set(?,notify.dept_id)' , [$own['dept_id']]);
                                foreach ($own['role_id'] as $roleId) {
                                    $query->orWhereRaw('find_in_set(?,notify.role_id)' , [$roleId]);
                                }
                            });
                    });
                });
        })->find($notifyId);
        return $query;
    }

    /**
     * 查询用户可访问的全部公告id
     *
     * @param $own
     * @param $expiredSettings
     *
     * @return array
     */
    public function getAllAccessibleNotifyIdsToPerson($own, $expiredSettings)
    {
        $query = $this->entity->newQuery();
        $query->where(function ($query) use ($own, $expiredSettings){
            // 自己发布的的不做任何限制
//            $query->where('from_id', $own['user_id']);
            // 发布状态且有权限
            $query->orWhere(function ($query)  use ($own, $expiredSettings){
                $query->where('publish', 1);
                $query->where(function ($query) use ($own, $expiredSettings) {
                    $query->where('priv_scope', 1);
                    $query->orWhere(function ($query)  use ($own, $expiredSettings) {
                        $query->whereRaw('find_in_set(?,notify.user_id)' , [$own['user_id']])->orWhereRaw('find_in_set(?,notify.dept_id)' , [$own['dept_id']]);

                        foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw('find_in_set(?,notify.role_id)' , [$roleId]);
                        }
                    });
                });
            });

            /**
             * 设置公告过期可见性
             */
            // 如果为全部隐藏, 则只要存在过期时间则不可见
            if (isset($expiredSettings['expired_visible_scope']) && $expiredSettings['expired_visible_scope'] == 1){
                $query->where(function ($query) {
                    $query->where('end_date', '>=', date('Y-m-d'))->orWhere('end_date', '0000-00-00');
                });
            }

            // 如果为指定范围过期可见
            if ($expiredSettings['expired_visible_scope'] == 2) {
                $userVisible = false;
                if (in_array($own['dept_id'], explode(',', $expiredSettings['expired_visible_dept']))
                    || in_array($own['user_id'], explode(',', $expiredSettings['expired_visible_user']))
                ){
                    $userVisible = true;
                }
                foreach($own['role_id'] as $value){
                    if (in_array($value, explode(',', $expiredSettings['expired_visible_role']))){
                        $userVisible = true;
                    }
                }

                // 如果用户不在指定范围, 则无法查看过期公告
                if (!$userVisible) {
                    $query->where(function ($query) {
                        $query->where('end_date', '>=', date('Y-m-d'))->orWhere('end_date', '0000-00-00');
                    });
                }
            }
        });

        return $query->pluck('notify_id')->toArray();
    }

    /**
     * 获取公告列表
     *
     * @param array $param
     *
     * @return array 公告列表
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function listNotify($param)
    {
        $own = $param['own'];
        $own['dept_id'] = isset($own['dept_id']) ? $own['dept_id'] : '';
        $default = [
            'page'     => 0,
            'order_by' => ['notify.notify_id' => 'desc'],
            'limit'    => 10,
            'fields'   => ['notify.notify_id', 'notify.from_id', 'notify.priv_scope',
                'notify.dept_id', 'notify.role_id', 'notify.user_id',
                'notify.subject', 'notify.begin_date', 'notify.end_date',
                'notify.publish', 'notify.notify_type_id', 'notify.status',
                'notify.last_check_time', 'notify.created_at', 'notify.content',
                'notify.top', 'notify.top_end_time', 'notify.top_create_time',
                'notify.creator', 'notify.creator_type']
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity
            ->select($param['fields'])
            ->with([
                'notifyType' => function ($query) {
                    $query->select('notify_type_id', 'notify_type_name');
                },
                'user'       => function ($query) {
                    $query->select('user_id', 'user_name')
                        ->withTrashed()
                        ->with([
                            'userHasOneSystemInfo' => function ($query) {
                                $query->select('user_id', 'dept_id')
                                    ->withTrashed()
                                    ->with([
                                        'userSystemInfoBelongsToDepartment' => function ($query) {
                                            $query->select('dept_id', 'dept_name');
                                        }
                                    ]);
                            }
                        ]);
                }
            ])
            ->withCount([
                'reader' => function ($query) use ($own) {
                    $query->where('user_id', $own['user_id']);
                }
            ]);
        $expiredNotVisible = isset($param['expired_not_visible']) && $param['expired_not_visible'] == 1;
        /*
         * 1.非管理员：(自己发布&&未发布) || (已发布 && 设置过期？&& (自己发布||(范围内&&开始)) )
         * 2.管理员：  (自己发布&&未发布) || (已发布 && 设置过期?)
        */
        $query->where(function($query) use ($own, $expiredNotVisible){
            $query->where(function ($query) use ($own) {
                $query->where('notify.from_id', $own['user_id'])
                    ->where('notify.publish', '!=', 1);
            })
            ->orWhere(function ($query) use ($own, $expiredNotVisible) {
                $query->where('notify.publish', 1);
                // 过期不可见
                if ($expiredNotVisible) {
                    $query->where('notify.status', 0)
                        ->where(function ($query) {
                            $query->where('notify.end_date', '>=', date('Y-m-d'))
                                ->orWhere('notify.end_date', '=', '0000-00-00');
                        });
                }
                //非管理员
                if ($own['user_id'] != 'admin') {
                    $query->where(function ($query) use ($own) {
                        $query->where('notify.from_id', $own['user_id'])
                            ->orWhere(function ($query) use ($own) {
                                //范围内
                                $query->where(function ($query) use ($own) {
                                    $query->where('notify.priv_scope', 1)
                                        ->orWhere(function ($query) use ($own) {
                                            $query->orWhereRaw('find_in_set(?,notify.user_id)' , [$own['user_id']])->orWhereRaw('find_in_set(?,notify.dept_id)' , [$own['dept_id']]);
                                            foreach ($own['role_id'] as $roleId) {
                                                $query->orWhereRaw('find_in_set(?,notify.role_id)' , [$roleId]);
                                            }
                                        });
                                })
                                    //开始
                                    ->where('notify.begin_date', '<=', date('Y-m-d'));
                            });
                    });
                }
            });
        });


        if (isset($param['search']['read'])) {
            $query->whereHas('reader', function ($query) use ($own) {
                $query->where('user_id', $own['user_id']);
            })
            ->where('notify.publish', 1);
            unset($param['search']['read']);
        }
        if (isset($param['search']['unread'])) {
            $query->whereDoesntHave('reader', function ($query) use ($own) {
                $query->where('user_id', $own['user_id']);
            })
            ->where('notify.publish', 1);
            unset($param['search']['unread']);
        }

        if (isset($param['search']['notify.outtime'])) {
            if($expiredNotVisible) {
                return [];
            }
            $query->where('notify.publish', 1)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('notify.end_date', '!=', '0000-00-00')
                    ->where('notify.end_date', '<', date('Y-m-d'));
                })
                ->orWhere('notify.status', 1);
            });
            unset($param['search']['notify.outtime']);
        }

        if (isset($param['search']['notify.inforever'])) {
            $query = $query->where(function ($query) use ($param) {
                $query->where('notify.end_date', '>=', $param['search']['notify.end_date'][0])
                    ->orWhere('notify.end_date', '=', '0000-00-00');
            });
            unset($param['search']['notify.inforever']);
            unset($param['search']['notify.end_date']);
        }
        if (isset($param['search']['notify.outforever'])) {
            $query = $query->where(function ($query) use ($param) {
                $query->where('notify.end_date', '<=', $param['search']['notify.end_date'][0])
                    ->where('notify.end_date', '!=', '0000-00-00');
            });
            unset($param['search']['notify.outforever']);
            unset($param['search']['notify.end_date']);
        }
        if (isset($param['search']['notify.valid'])) {
            $query = $query->where('notify.publish', '=', '1')
                ->where('notify.status', '=', '0');
            $query = $query->where(function ($query) use ($param) {
                $query->where('notify.end_date', '>=', date('Y-m-d'))
                    ->orWhere('notify.end_date', '=', '0000-00-00');
            });
            unset($param['search']['notify.valid']);
        }
        if (!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        // 门户
        if(isset($param['portal']) && $param['portal'] == true) {
            $query = $query->where('publish', 1);
        }

        // 首先按照置顶排序
        $result = $query->orders(['notify.top' => 'desc'])->orders($param['order_by'])
            ->forPage($param['page'], $param['limit'])
            ->get();

        return $result;
    }

    /**
     * 获取公告数量
     *
     * @param array $param
     *
     * @return int 公告数量
     *
     * @author nth
     *
     */
    public function getNotifyCount($param)
    {
        $own = $param['own'];
        $own['dept_id'] = isset($own['dept_id']) ? $own['dept_id'] : '';

        $query = $this->entity->select(['notify_id']);
        $expiredNotVisible = isset($param['expired_not_visible']) && $param['expired_not_visible'] == 1;
        /*
         * 1.非管理员：(自己发布&&未发布) || (发布 && 设置过期？&& (自己发布||(范围内&&开始)) )
         * 2.管理员：  (自己发布&&未发布) || (发布 && 设置过期?)
        */
        $query->where(function($query) use ($own, $expiredNotVisible) {
            $query->where(function ($query) use ($own) {
                $query->where('notify.from_id', $own['user_id'])
                    ->where('notify.publish', '!=', 1);
            })
                ->orWhere(function ($query) use ($own, $expiredNotVisible) {
                    $query->where('notify.publish', 1);
                    // 过期不可见
                    if ($expiredNotVisible) {
                        $query->where('notify.status', 0)
                            ->where(function ($query) {
                                $query->where('notify.end_date', '>=', date('Y-m-d'))
                                    ->orWhere('notify.end_date', '=', '0000-00-00');
                            });
                    }
                    //非管理员
                    if ($own['user_id'] != 'admin') {
                        $query->where(function ($query) use ($own) {
                            $query->where('notify.from_id', $own['user_id'])
                                ->orWhere(function ($query) use ($own) {
                                    //范围内
                                    $query->where(function ($query) use ($own) {
                                        $query->where('notify.priv_scope', 1)
                                            ->orWhere(function ($query) use ($own) {
                                                $query->orWhereRaw('find_in_set(?,notify.user_id)' , [$own['user_id']])->orWhereRaw('find_in_set(?,notify.dept_id)' , [$own['dept_id']]);
                                                foreach ($own['role_id'] as $roleId) {
                                                    $query->orWhereRaw('find_in_set(?,notify.role_id)' , [$roleId]);
                                                }
                                            });
                                    })
                                        //开始
                                        ->where('notify.begin_date', '<=', date('Y-m-d'));
                                });
                        });
                    }
                });
        });
        if (isset($param['search']['read'])) {
            $query->whereHas('reader', function ($query) use ($own) {
                $query->where('user_id', $own['user_id']);
            })
                ->where('notify.publish', 1);
            unset($param['search']['read']);
        }
        if (isset($param['search']['unread'])) {
            $query->whereDoesntHave('reader', function ($query) use ($own) {
                $query->where('user_id', $own['user_id']);
            })
                ->where('notify.publish', 1);
            unset($param['search']['unread']);
        }

        if (isset($param['search']['notify.outtime'])) {
            if($expiredNotVisible) {
                return 0;
            }
            $query->where('notify.publish', 1)
                ->where(function ($query) {
                    $query->where(function ($query) {
                        $query->where('notify.end_date', '!=', '0000-00-00')
                            ->where('notify.end_date', '<', date('Y-m-d'));
                    })
                        ->orWhere('notify.status', 1);
                });
            unset($param['search']['notify.outtime']);
        }

        if (isset($param['search']['notify.inforever'])) {
            $query = $query->where(function ($query) use ($param) {
                $query->where('notify.end_date', '>=', $param['search']['notify.end_date'][0])
                    ->orWhere('notify.end_date', '=', '0000-00-00');
            });
            unset($param['search']['notify.inforever']);
            unset($param['search']['notify.end_date']);
        }
        if (isset($param['search']['notify.outforever'])) {
            $query = $query->where(function ($query) use ($param) {
                $query->where('notify.end_date', '<=', $param['search']['notify.end_date'][0])
                    ->where('notify.end_date', '!=', '0000-00-00');
            });
            unset($param['search']['notify.outforever']);
            unset($param['search']['notify.end_date']);
        }
        if (isset($param['search']['notify.valid'])) {
            $query = $query->where('notify.publish', '=', '1')
                ->where('notify.status', '=', '0');
            $query = $query->where(function ($query) use ($param) {
                $query->where('notify.end_date', '>=', date('Y-m-d'))
                    ->orWhere('notify.end_date', '=', '0000-00-00');
            });
            unset($param['search']['notify.valid']);
        }
        if (!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }

        // 门户
        if(isset($param['portal']) && $param['portal'] == true) {
            $query = $query->where('publish', 1);
        }

        return $query->count();
    }

    /**
     * 获取审核公告列表
     *
     * @param array $param
     *
     * @return array 审核公告列表
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function listVerifyNotify($param)
    {
        $default = [
            'page'     => 0,
            'order_by' => ['created_at' => 'desc'],
            'limit'    => 10,
            'fields'   => ['notify.notify_id', 'notify.from_id', 'notify.priv_scope',
                'notify.dept_id', 'notify.role_id', 'notify.user_id',
                'notify.subject', 'notify.begin_date', 'notify.end_date',
                'notify.publish', 'notify.notify_type_id', 'notify.status',
                'notify.last_check_time', 'notify.created_at', 'user.user_name',
                'notify.top', 'notify.top_end_time', 'notify.top_create_time',
                'notify.creator', 'notify.creator_type']
        ];

        $param = array_merge($default, array_filter($param));
        // 添加用户部门查询
        $param['fields'][] = 'department.dept_name';
        $param['fields'][] = 'department.dept_id';

        $own = $param['own'];
        $query = $this->entity
            ->select($param['fields'])
            ->leftJoin('user', 'user.user_id', '=', 'notify.from_id')
            ->leftJoin('user_system_info', 'user_system_info.user_id', 'user.user_id')
            ->leftJoin('department', 'department.dept_id', 'user_system_info.dept_id')
            ->where('notify.publish', 2);
//            ->where('notify.status', 0)
//            ->where(function ($query) {
//                $query = $query->where('notify.end_date', '0000-00-00')
//                    ->orWhere('notify.end_date', '>=', date('Y-m-d'));
//            });

        if (!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }

        return $query->orders($param['order_by'])
            ->forPage($param['page'], $param['limit'])
            ->get();
    }

    /**
     * 获取审核公告数量
     *
     * @param array $search
     *
     * @return int 审核公告数量
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function getVerifyNotifyCount($param)
    {

        // $search = isset($param['search']) ? $param['search'] : [];
        // $own = $param['own'];
        // $own['dept_id'] = isset($own['dept_id']) ? $own['dept_id'] : '';
        // $query = $this->entity
        // 	->leftJoin('user', 'user.user_id', '=', 'notify.from_id')
        // 	->where(function ($query) use($own){
        // 		$query = $query->where('notify.from_id',$own['user_id'])
        // 			->orWhere(function($query) use($own){
        // 				$query = $query->where(function($query) use($own) {
        // 					$query->where('notify.priv_scope', 1)
        // 					->orWhere(function ($query) use($own) {
        // 						$query->orWhereRaw('find_in_set(\'' . $own['user_id'] . '\',notify.user_id)')
        // 						//->orWhereRaw('find_in_set(\'' . $own['role_id'] . '\',notify.role_id)')
        // 						->orWhereRaw('find_in_set(\'' . $own['dept_id'] . '\',notify.dept_id)');
        //                                   foreach($own['role_id'] as $roleId){
        //                                       $query->orWhereRaw('find_in_set(\''.$roleId.'\',notify.role_id)');
        //                                   }
        // 					});
        // 				});
        // 			});
        // 	})->where('notify.publish', 2)
        // 	->where('notify.status', 0)
        // 	->where(function($query) {
        // 		$query = $query->where('notify.end_date', '0000-00-00')
        // 			->orWhere('notify.end_date', '>=', date('Y-m-d'));
        // 	});

        // if(!empty($search)) {
        // 	$query = $query->wheres($search);
        // }
        $default = [
            'page'     => 0,
            'order_by' => ['created_at' => 'desc'],
            'limit'    => 10,
            'fields'   => ['notify.notify_id', 'notify.from_id', 'notify.priv_scope', 'notify.dept_id', 'notify.role_id', 'notify.user_id', 'notify.subject', 'notify.begin_date', 'notify.end_date', 'notify.publish', 'notify.notify_type_id', 'notify.status', 'notify.last_check_time', 'notify.created_at', 'user.user_name', 'notify.top', 'notify.top_end_time', 'notify.top_create_time']
        ];

        $param = array_merge($default, array_filter($param));


        $own = $param['own'];
        $query = $this->entity
            ->select($param['fields'])
            ->leftJoin('user', 'user.user_id', '=', 'notify.from_id')
            ->where('notify.publish', 2);
//            ->where('notify.status', 0)
//            ->where(function ($query) {
//                $query = $query->where('notify.end_date', '0000-00-00')
//                    ->orWhere('notify.end_date', '>=', date('Y-m-d'));
//            });

        if (!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        return $query->count();
    }

    /**
     * 编辑新闻
     *
     * @param  array $data 更新数据
     * @param  array $wheres 更新条件
     *
     * @return bool          更新结果
     *
     * @author qishaobo
     *
     * @since  2015-11-12
     */
    function editNotify($data, $wheres)
    {
        $query = $this->entity;
        foreach ($wheres as $field => $where) {
            if (is_array($where)) {
                $query = $query->whereIn($field, $where);
            } else {
                $query = $query->where($field, $where);
            }
        }
        return $query = $query->update($data);
    }

    /**
     * 获取当天生效公告列表
     *
     * @param
     *
     * @return array 当天生效公告列表
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function listBeginNotify($param = [])
    {
        $default = [
            'page'     => 0,
            'order_by' => ['created_at' => 'desc'],
            'limit'    => 10,
            'fields'   => ['notify.notify_id', 'notify.from_id', 'notify.priv_scope', 'notify.dept_id', 'notify.role_id', 'notify.user_id', 'notify.subject', 'notify.begin_date', 'notify.end_date', 'notify.publish', 'notify.notify_type_id', 'notify.status', 'notify.last_check_time', 'notify.created_at', 'user.user_name', 'notify.top', 'notify.top_end_time', 'notify.top_create_time']
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity
            ->select($param['fields'])
            ->leftJoin('user', 'user.user_id', '=', 'notify.from_id')
            ->where('notify.publish', 1)
            ->where('notify.status', 0)
            ->where('notify.begin_date', date('Y-m-d'))
            ->where(function ($query) {
                $query = $query->where('notify.end_date', "0000-00-00")
                    ->orWhere('notify.end_date', ">=", date('Y-m-d'));
            });
        return $query->orders($param['order_by'])
            ->get()->toArray();
    }

    /**
     * 定时清除置顶时间
     * @return [type] [description]
     */
    public function cancelOutTimeTop()
    {
        $currentTime = date("Y-m-d H:i:s");
        $query = $this->entity;
        $query = $query->where('top', [1])->where('top_end_time', '<', $currentTime)->where('top_end_time', '!=', '0000-00-00 00:00:00');
        return $query = $query->update(['top' => 0, 'top_end_time' => "0000-00-00 00:00:00", 'top_create_time' => "0000-00-00 00:00:00"]);
    }

    public function getAfterLoginOpenList($params = [])
    {
        $own = own();
        $own['dept_id'] = isset($own['dept_id']) ? $own['dept_id'] : '';
        $date = date('Y-m-d');

        $userCreateAt = DB::table('user')
            ->where('user_id', $own['user_id'])
            ->value('created_at');
        $userCreateAt = Carbon::create($userCreateAt)->format('Y-m-d');
        $query = $this->entity
//            ->select($param['fields'])
            ->where(function ($query) use ($own) {
                $query->where('notify.priv_scope', 1)
                    ->orWhere(function ($query) use ($own) {
                        $query->orWhereRaw('find_in_set(?,notify.user_id)' , [$own['user_id']])->orWhereRaw('find_in_set(?,notify.dept_id)' , [$own['dept_id']]);
                        foreach ($own['role_id'] as $roleId) {
                            $query->orWhereRaw('find_in_set(?,notify.role_id)' , [$roleId]);
                        }
                    });
            })
            ->where('notify.publish', 1)
            ->where('notify.open_unread_after_login', 1)
            ->where('notify.begin_date', '<=', $date)
            ->where('notify.begin_date', '>=', $userCreateAt)
            ->whereDoesntHave('reader', function ($query) use ($own) {
                $query->where('user_id', $own['user_id']);
            });
            if(isset($params['expired_visible']) && !$params['expired_visible']){
                $query->where('notify.status', 0)
                    ->where(function ($query) use ($date){
                        $query->where('notify.end_date', '>=', $date)
                            ->orWhere('notify.end_date', '0000-00-00');
                    });
            };
            return $query->get()->toArray();
    }
}
