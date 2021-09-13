<?php
namespace App\EofficeApp\Notify\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Notify\Entities\NotifyReadersEntity;
use App\EofficeApp\User\Entities\UserSystemInfoEntity;
use App\EofficeApp\Role\Entities\UserRoleEntity;
use App\EofficeApp\User\Entities\UserEntity;
use App\EofficeApp\System\Department\Entities\DepartmentEntity;
use App\EofficeApp\Notify\Entities\NotifyEntity;
use App\EofficeApp\Menu\Services\UserMenuService;
use DB;
/**
 * 公告查看人资源库类
 *
 * @author 李志军
 *
 * @since 2015-10-20
 */
class NotifyReadersRepository extends BaseRepository
{
	/** @var object 部门实体对象 */
	private $departmentEntity;

	/** @var object 用户系统信息实体对象 */
	private $userSystemInfoEntity;
	private $notifyEntity;
	private $userRoleEntity;
	private $userEntity;
	private $userMenuService;
	/**
	 * 注册公告查看情况资源库相关的实体对象
	 *
	 * @param \App\EofficeApp\Notify\Entities\NotifyReadersEntity $entity
	 * @param \App\EofficeApp\User\Entities\UserSystemInfoEntity $userSystemInfoEntity
	 * @param \App\EofficeApp\System\Department\Entities\DepartmentEntity $departmentEntity
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-20
	 */
	public function __construct(
		NotifyReadersEntity $entity,
		UserSystemInfoEntity $userSystemInfoEntity,
		DepartmentEntity $departmentEntity,
		NotifyEntity $notifyEntity,
		UserRoleEntity $userRoleEntity,
		UserEntity $userEntity,
		UserMenuService $userMenuService
		)
	{
		parent::__construct($entity);

		$this->userSystemInfoEntity = $userSystemInfoEntity;
		$this->userEntity = $userEntity;
		$this->departmentEntity = $departmentEntity;
		$this->notifyEntity = $notifyEntity;
		$this->userRoleEntity = $userRoleEntity;
		$this->userMenuService = $userMenuService;
	}
	/**
	 * 判断某用户是否已经阅读了
	 *
	 * @param int $notifyId
	 * @param int $userId
	 *
	 * @return int
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-20
	 */
	public function readerExists($notifyId, $userId)
	{
		return $this->entity->where('notify_id',$notifyId)->where('user_id', $userId)->count();
	}
	/**
	 * 获取阅读情况
	 *
	 * @param int $notifyId
	 *
	 * @return array   阅读情况
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-20
	 */
	public function getReaders($notifyId)
	{
		//公告
		$notifyList = $this->notifyEntity->find($notifyId);
		if (empty($notifyList)) {
			return [];
		}
		$notify_role_id = !empty($notifyList->role_id) ? explode(',', trim($notifyList->role_id, ',')) : [];
		$notify_dept_id = !empty($notifyList->dept_id) ? explode(',', trim($notifyList->dept_id, ',')) : [];
		$notify_user_id = !empty($notifyList->user_id) ? explode(',', trim($notifyList->user_id, ',')) : [];
		//获取阅读记录
		$readers = $this->entity->select(['user_id'])->where('notify_id', $notifyId)->get();

		if(empty($readers)){
			return [];
		}
		$readUserIds = [];
		foreach ($readers as $reader) {
			$readUserIds[] = $reader->user_id;
		}

		$users = $this->userEntity->select(['user.user_name', 'user.user_id', 'user_system_info.dept_id','user_system_info.user_status'])
			->leftJoin('user_system_info', 'user.user_id', '=', 'user_system_info.user_id')
			->leftJoin('user_role', 'user.user_id', '=', 'user_role.user_id');
		if ($notifyList->priv_scope != 1) {
			$users = $users ->orWhere(function ($query) use ($notify_dept_id,$notify_role_id,$notify_user_id) {
				$query->orWhereIn('user_system_info.dept_id', $notify_dept_id)
					->orWhereIn('user_role.role_id', $notify_role_id)
					->orWhereIn('user_system_info.user_id', $notify_user_id);
				
			});
			// $users = $users->whereIn('user_system_info.dept_id', $notify_dept_id)
            //     ->orWhere(function ($users) use($notify_role_id) {
			// 	$users = $users->whereIn('user_role.role_id', $notify_role_id);
        	// })->orWhere(function ($users) use($notify_user_id) {
        	// 	$users = $users->whereIn('user_system_info.user_id', $notify_user_id);
        	// });
		};
		$userLists = $users->where('user_system_info.user_status','!=','2')->groupBy('user.user_id')->get()->toArray();
		$result['list'] = [];
        $result['total_reader_count'] = $result['total_unreader_count'] = 0;
		//获取所有部门
		$departmentLists = $this->departmentEntity->select(['dept_id','dept_name'])->get();
		foreach ($departmentLists as $key => $department) {
			$readerCount = $unreaderCount = 0;
			$tempResult = [];
			$tempResult['dept_id'] = $department->dept_id;
			$tempResult['dept_name'] = $department->dept_name;
			$tempResult['users']['reader'] = [];
			$tempResult['users']['unreader'] = [];
			foreach ($userLists as $j => $user) {
				// if ($user['dept_id'] == $department->dept_id) {	//用户属于此部门
				if ($user['dept_id'] == $tempResult['dept_id']) {	//用户属于此部门
					if (in_array($user['user_id'], $readUserIds)) {
						$readerCount++;
						$tempResult['users']['reader'][] = $user;
					} else {
						$unreaderCount++;
						$tempResult['users']['unreader'][] = $user;
					}
					unset($userLists[$j]);
				}
			}
			$tempResult['users']['reader_count'] = $readerCount;
			$tempResult['users']['unreader_count'] = $unreaderCount;
            $result['total_reader_count'] += $readerCount;
            $result['total_unreader_count'] += $unreaderCount;

			if (empty($tempResult['users']['unreader_count']) && empty($tempResult['users']['reader_count'])) {
				continue;
			}
			$result['list'][] = $tempResult;

			unset($tempResult);
		}
		return $result;
	}

	/**
	 * 获取发布范围人员
	 *
	 * @param int $notifyId
	 *
	 * @return array   发布范围人员
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-20
	 */
	public function getReadersUserid($notifyId)
	{
        $notifyList = $this->notifyEntity->select(['notify.priv_scope','notify.dept_id','notify.role_id','notify.user_id'])->where('notify.notify_id',$notifyId)->first();
        $notify_priv_scope= $notifyList->priv_scope;
        $notify_role_id = !empty($notifyList->role_id) ? explode(',', trim($notifyList->role_id, ',')) : [];
        $notify_dept_id = !empty($notifyList->dept_id) ? explode(',', trim($notifyList->dept_id, ',')) : [];
        $notify_user_id = !empty($notifyList->user_id) ? explode(',', trim($notifyList->user_id, ',')) : [];


		$users = $this->userEntity
			->select(['user.user_id'])
			->leftJoin('user_system_info','user.user_id','=','user_system_info.user_id')
			->leftJoin('user_role','user.user_id','=','user_role.user_id')
            ->where('user_system_info.user_status','!=','2');
		if($notify_priv_scope!=1){
			$users =$users
			->whereIn('user_system_info.dept_id',$notify_dept_id)
			->orWhere(function ($users) use($notify_role_id){
				$users=$users
                ->whereIn('user_role.role_id',$notify_role_id)
                ->groupBy('user.user_id');
        	})
        	->orWhere(function ($users) use($notify_user_id){
        		$users=$users
            ->whereIn('user_system_info.user_id',$notify_user_id);
        	});
		}
		$users=$users->groupBy('user.user_id')->get()->toArray();
		$userId=[];
		foreach ($users as $value) {
			$userId[]=$value['user_id'];
		}
       return $userId;
	}
	/**
	 * 获取角色的部门列表
	 */
	public function getDeptByRole($role)
	{
		$query =  $this->departmentEntity->select(['department.dept_id','department.dept_name'])
		->leftJoin('user_system_info','department.dept_id','=','user_system_info.dept_id')
		->leftJoin('user_role','user_system_info.user_id','=','user_role.user_id')
		->whereIn('user_role.role_id',explode(',',$role))
		->groupBy('department.dept_id');
		return $query->get();
	}

	/**
	 * 根据角色获取所有用户的id数组
	 */
	public function getUserIdsByRoleId($role_ids)
	{
		$userIdsArr = [];
		$lists = DB::table('user_role')->select('user_id')->whereIn('role_id', explode(',', $role_ids))->get();
		foreach ($lists as $key => $value) {
			$userIdsArr[] = $value->user_id;
		}
		return array_unique($userIdsArr);
	}

	public function getUnreadersIds($notifyId)
    {
        //发布范围所有人员
        $allReaders = $this->getReadersUserid($notifyId);
        //已经阅读人员
        $readers = $this->entity->where('notify_id', $notifyId)->pluck('user_id')->toArray();

        $unreaders = array_diff($allReaders, $readers);
        return $unreaders;
    }

    private function handleReaderQuery($notifyList)
    {
        if (empty($notifyList)) {
            return [];
        }
        $notify_role_id = !empty($notifyList->role_id) ? explode(',', trim($notifyList->role_id, ',')) : [];
        $notify_dept_id = !empty($notifyList->dept_id) ? explode(',', trim($notifyList->dept_id, ',')) : [];
        $notify_user_id = !empty($notifyList->user_id) ? explode(',', trim($notifyList->user_id, ',')) : [];
        //获取阅读记录
        $users = $this->userEntity->select(['user.user_name', 'user.user_id', 'user_system_info.dept_id', 'user_system_info.user_status','user_system_info.last_login_time'])
            ->leftJoin('user_system_info', 'user.user_id', '=', 'user_system_info.user_id')
            ->leftJoin('user_role', 'user.user_id', '=', 'user_role.user_id');
        if ($notifyList->priv_scope != 1) {
            $users = $users->orWhere(function ($query) use ($notify_dept_id, $notify_role_id, $notify_user_id) {
                $query->orWhereIn('user_system_info.dept_id', $notify_dept_id)
                    ->orWhereIn('user_role.role_id', $notify_role_id)
                    ->orWhereIn('user_system_info.user_id', $notify_user_id);
            });
        };
        return $users;
    }

    /**
     * 有分页的阅读情况
     * @param $notifyId
     * @param $params
     * @return array
     */
    public function searchReaders($notifyId, $params)
    {
        $notifyList = $this->notifyEntity->find($notifyId);
        $users = $this->handleReaderQuery($notifyList);
        $departmentLists = $this->departmentEntity->select(['dept_id','dept_name'])->get()->toArray();

        //判断查询条件已读还是未读
        if ($params['sign'] == 'unread') {
            $users = $users->leftJoin('notify_readers', 'notify_readers.user_id', '=', 'user_role.user_id')
                ->whereNotIn('user.user_id', function ($query) use ($notifyId) {
                    $query->select('user_id')
                        ->from('notify_readers')
                        ->where('notify_id', $notifyId);
                })
                ->where('user_system_info.user_status', '!=', '2')
                ->groupBy('user.user_id')
                ->orderBy('user_system_info.dept_id')
                ->orderBy('user.user_id')
                ->parsePage($params['page'], $params['limit']);
                $userList = $users->get()->toArray();

                $unReaderTotal = $this->searchReaderCount($notifyId)['total_unreader_count'];
                foreach ($departmentLists as $key=>$value){
                    foreach ($userList as $k=>$v){
                        if($value['dept_id']==$v['dept_id']){
                            $userList[$k]['dept_name']=$value['dept_name'];
                        }
                    }
                }
//                $readerTotal = count($this->entity->select(['user_id'])->where('notify_id', $notifyId)->get()->toArray());
                return ['total' => $unReaderTotal, 'list' => $userList];
        } else {
            $users = $users->rightJoin('notify_readers', 'user.user_id', '=', 'notify_readers.user_id')
                ->where('user_system_info.user_status', '!=', '2')
                ->where('notify_id', $notifyId)
                ->groupBy('user.user_id')
                ->orderBy('user_system_info.dept_id')
                ->parsePage($params['page'], $params['limit']);
                $userList = $users->get()->toArray();
//                $unReaderTotal = count($this->getUnreadersIds($notifyId));
                $reader = $this->entity->select(['user_id'])->where('notify_id', $notifyId)->get();
                $readerTotal=$this->getReadersUserid($notifyId);
                $userId=[];
                foreach ( $reader as $value) {
                    $userId[]=$value['user_id'];
                }
                $count = 0;
                foreach ($userId as $value){
                    foreach ($readerTotal as $v){
                        if($v == $value){
                            $count++;
                        }
                    }
                }
                return ['total' => $count, 'list' => $userList];
        }


    }

    public function searchReaderCount($notifyId)
    {
        //公告
        $notifyList = $this->notifyEntity->find($notifyId);
        if (empty($notifyList)) {
            return [];
        }
        $notify_role_id = !empty($notifyList->role_id) ? explode(',', trim($notifyList->role_id, ',')) : [];
        $notify_dept_id = !empty($notifyList->dept_id) ? explode(',', trim($notifyList->dept_id, ',')) : [];
        $notify_user_id = !empty($notifyList->user_id) ? explode(',', trim($notifyList->user_id, ',')) : [];
        //获取阅读记录
        $readers = $this->entity->select(['user_id'])->where('notify_id', $notifyId)->get();

        if(empty($readers)){
            return [];
        }
        $readUserIds = [];
        foreach ($readers as $reader) {
            $readUserIds[] = $reader->user_id;
        }

        $users = $this->userEntity->select(['user.user_name', 'user.user_id', 'user_system_info.dept_id','user_system_info.user_status'])
            ->leftJoin('user_system_info', 'user.user_id', '=', 'user_system_info.user_id')
            ->leftJoin('user_role', 'user.user_id', '=', 'user_role.user_id');
        if ($notifyList->priv_scope != 1) {
            $users = $users ->orWhere(function ($query) use ($notify_dept_id,$notify_role_id,$notify_user_id) {
                $query->orWhereIn('user_system_info.dept_id', $notify_dept_id)
                    ->orWhereIn('user_role.role_id', $notify_role_id)
                    ->orWhereIn('user_system_info.user_id', $notify_user_id);

            });
        };
        $userLists = $users->where('user_system_info.user_status','!=','2')->groupBy('user.user_id')->get()->toArray();
        $result['total_reader_count'] = $result['total_unreader_count'] = 0;
        //获取所有部门
        $departmentLists = $this->departmentEntity->select(['dept_id','dept_name'])->get();
        foreach ($departmentLists as $key => $department) {
            $readerCount = $unreaderCount = 0;
            $tempResult = [];
            $tempResult['dept_id'] = $department->dept_id;
            $tempResult['dept_name'] = $department->dept_name;
            $tempResult['users']['reader'] = [];
            $tempResult['users']['unreader'] = [];
            foreach ($userLists as $j => $user) {
                // if ($user['dept_id'] == $department->dept_id) {	//用户属于此部门
                if ($user['dept_id'] == $tempResult['dept_id']) {	//用户属于此部门
                    if (in_array($user['user_id'], $readUserIds)) {
                        $readerCount++;
                    } else {
                        $unreaderCount++;
                    }
                    unset($userLists[$j]);
                }
            }
            $tempResult['users']['reader_count'] = $readerCount;
            $tempResult['users']['unreader_count'] = $unreaderCount;
            $result['total_reader_count'] += $readerCount;
            $result['total_unreader_count'] += $unreaderCount;

            if (empty($tempResult['users']['unreader_count']) && empty($tempResult['users']['reader_count'])) {
                continue;
            }
            unset($tempResult);
        }
        return $result;
    }

    public function getNotifyReadRange($notifyId){
        $notifyList = $this->notifyEntity->select(['notify.priv_scope','notify.dept_id','notify.role_id','notify.user_id'])->where('notify.notify_id',$notifyId)->first();
        $notify_role_id = !empty($notifyList->role_id) ? explode(',', trim($notifyList->role_id, ',')) : [];
        $notify_dept_id = !empty($notifyList->dept_id) ? explode(',', trim($notifyList->dept_id, ',')) : [];
        $notify_user_id = !empty($notifyList->user_id) ? explode(',', trim($notifyList->user_id, ',')) : [];
        return ['users' => $notify_user_id , 'roles' => $notify_role_id , 'dept' => $notify_dept_id];
    }
}

