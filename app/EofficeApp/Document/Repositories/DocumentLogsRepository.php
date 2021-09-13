<?php
namespace App\EofficeApp\Document\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Document\Entities\DocumentLogsEntity;
use App\EofficeApp\User\Entities\UserEntity;
use App\EofficeApp\System\Department\Entities\DepartmentEntity;
/**
 * 文档日志资源库类
 *
 * @author 李志军
 *
 * @since 2015-11-02
 */
class DocumentLogsRepository extends BaseRepository
{
	/** @var int 默认列表条数 */
	private $limit;

	/** @var int 默认列表页 */
	private $page		= 0;

	/** @var array  默认排序 */
    private $orderBy	= ['created_at' => 'desc', 'log_id' => 'desc'];
    
    private $userEntity;

    private $departmentEntity;

	public function __construct(
        DocumentLogsEntity $entity,
        UserEntity $userEntity,
        DepartmentEntity $departmentEntity)
	{
		parent::__construct($entity);
        $this->userEntity = $userEntity;
        $this->departmentEntity = $departmentEntity;
		$this->limit = config('eoffice.pagesize');
	}

	public function listLog($param)
	{
		$param['fields']	= isset($param['fields'])
							? $param['fields']
							: ['document_logs.*','user.user_name'];

		$param['limit']		= isset($param['limit']) ? $param['limit'] : $this->limit;

		$param['page']		= isset($param['page']) ? $param['page'] : $this->page;

		$param['order_by']	= isset($param['order_by']) ? array_merge($param['order_by'], $this->orderBy) : $this->orderBy;

		$query = $this->entity->select($param['fields'])
				->leftJoin('user', 'user.user_id', '=', 'document_logs.user_id');

		if(isset($param['search']) && !empty($param['search'])){
			$query = $query->wheres($param['search']);
		}

		return $query->orders($param['order_by'])->forPage($param['page'], $param['limit'])->get();
	}
	public function isView($documentId,$userId){
		$where = [
			'document_id' 	=> [$documentId],
			'user_id' 		=> [$userId],
			'operate_type' 	=> [2, 3],
		];

		if($this->entity->wheres($where)->count()){
			return 1;
		}

		return 0;
	}
    public function logViewCounts($documentIds,$userId)
    {
        $logs = $this->entity->selectRaw('document_id,count(document_id) as document_count')->where('user_id', $userId)->whereIn('document_id',$documentIds)->whereIn('operate_type', [2, 3])->groupBy('document_id')->get();
        
        $logMap = [];
        if(count($logs) > 0){
            foreach ($logs as $log){
                $logMap[$log->document_id] = $log->document_count;
            }
        }
        return $logMap;
    }
	public function logCount($param)
	{
		$query = $this->entity->leftJoin('user', 'user.user_id', '=', 'document_logs.user_id');;

		if(isset($param['search']) && !empty($param['search'])){
			$query = $query->wheres($param['search']);
		}

		return $query->count();
	}

    public function logCounts($documentIds)
    {
        $logs = $this->entity->selectRaw('document_id,count(document_id) as document_count')->whereIn('document_id',$documentIds)->groupBy('document_id')->get();
        $logMap = [];
        if(count($logs) > 0){
            foreach ($logs as $log){
                $logMap[$log->document_id] = $log->document_count;
            }
        }
        return $logMap;
    }

    /**
	 * 获取已读文档
	 *
	 * @return array
	 *
	 * @author niuxiaoke
	 *
	 * @since 2017-08-07
	 */
    public function getRead($userId, $where)
    {
		$query = $this->entity->selectRaw('user_id,count(distinct document_id) as count')
							->whereIn('user_id', $userId)
							->where('user_id', '!=', '')
							->whereIn('operate_type', [2, 3]);

		if (isset($where['search'])) {
			$query = $query->wheres($where['search']);
		}

		if(isset($where['date_range'])){
			$dateRange = explode(',', $where['date_range']);
			if (isset($dateRange[0]) && !empty($dateRange[0])) {
                $query->whereRaw("created_at >= '" . $dateRange[0] . " 00:00:00'");
            }
            if (isset($dateRange[1]) && !empty($dateRange[1])) {
                $query->whereRaw("created_at <= '" . $dateRange[1] . " 23:59:59'");
            }
    		// $query->whereBetween('created_at', [$dateRange[0].' 00:00:00', $dateRange[1].' 23:59:59']);
		}
		// dd($query->groupBy('user_id')->toSql());
		return $query->groupBy('user_id')->get()->toArray();
    }

    /**
	 * 获取未读文档
	 *
	 * @return array
	 *
	 * @author niuxiaoke
	 *
	 * @since 2017-08-07
	 */
    public function getUnRead($userId, $documentIds, $where)
    {
        if($documentIds !== ''){
            $query = $this->entity->select('document_id')
                                ->where('user_id', $userId)
                                ->whereNotIn('document_id', $documentIds);

            if(isset($where['date_range'])){
                $dateRange = explode(',', $where['date_range']);
                $query->whereBetween('created_at', [$dateRange[0].' 00:00:00', $dateRange[1].' 23:59:59']);
            }

            return $query->groupBy('document_id')->get()->toArray();
        }else{
            return 0;
        }
    }
    // 获取已读未读
    public function documentReadList($documentId, $users) {
        $readUsers = $this->entity->where('document_id', $documentId)
            ->whereIn('operate_type', [2, 3])
            ->pluck('user_id')->unique()->toArray();
        if (empty($readUsers)) {
            return [];
        }

        $userLists = $this->userEntity->select(['user.user_name', 'user.user_id', 'user_system_info.dept_id'])
			->leftJoin('user_system_info', 'user.user_id', '=', 'user_system_info.user_id')
            ->where('user_system_info.user_status','!=','2')
            ->whereIn('user.user_id', $users)->get();

        $departments = $this->departmentEntity->select(['department.dept_id', 'dept_name'])
        ->leftJoin('user_system_info', 'user_system_info.dept_id', 'department.dept_id')
        ->whereIn('user_id', $users)
        ->groupBy('department.dept_id')->get();

        $result['list'] = [];
        $result['total_reader_count'] = $result['total_unreader_count'] = 0;
        foreach ($departments as $key => $department) {
			$readerCount = $unreaderCount = 0;
			$tempResult = [];
			$tempResult['dept_id'] = $department->dept_id;
			$tempResult['dept_name'] = $department->dept_name;
			$tempResult['users']['reader'] = [];
			$tempResult['users']['unreader'] = [];
			foreach ($userLists as $v => $user) {
				if ($user['dept_id'] == $tempResult['dept_id']) {	//用户属于此部门
					if (in_array($user['user_id'], $readUsers)) {
						$readerCount++;
						$tempResult['users']['reader'][] = $user;
					} else {
						$unreaderCount++;
						$tempResult['users']['unreader'][] = $user;
					}
					unset($userLists[$v]);
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
}
