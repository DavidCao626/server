<?php
namespace App\EofficeApp\Document\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Document\Entities\DocumentRevertEntity;
/**
 * 文档回复资源库类
 *
 * @author 李志军
 *
 * @since 2015-11-02
 */
class DocumentRevertRepository extends BaseRepository
{
	/** @var array  默认排序 */
	private $orderBy	= ['created_at' => 'desc'];
	/**
	 * 注册文档回复实体
	 *
	 * @param \App\EofficeApp\Document\Entities\DocumentRevertEntity $entity
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function __construct(DocumentRevertEntity $entity)
	{
		parent::__construct($entity);
	}
	/**
	 * 获取回复数量
	 *
	 * @param array $param
	 * @param int $documentId
	 *
	 * @return int 回复数量
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getRevertCount($param)
	{
		$query = $this->entity;

		if(isset($param['search']) && !empty($param['search'])){
			$query = $query->wheres($param['search']);
		}

		return $query->where("revert_parent","0")->count();
	}
	/**
	 * 获取回复列表
	 *
	 * @param array $param
	 * @param int $documentId
	 *
	 * @return array 回复列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function listRevert($param)
	{
		$fields		= isset($param['fields']) ? $param['fields'] : ['document_revert.*','user.user_name'];

		$orderBy	= isset($param['order_by']) ? $param['order_by'] : $this->orderBy;

		$param['limit']		= (isset($param['limit']) && $param['limit']) ? $param['limit'] : $this->limit;

		$param['page']		= (isset($param['page']) && $param['page']) ? $param['page'] : $this->page;

		$query = $this->entity->select($fields)
							->where("revert_parent","0")
							->leftJoin('user','user.user_id','=','document_revert.user_id')
							->with(["revertHasOneUser" => function($query) {
                                $query->select("user_id","user_name");
                            }])
							->with(["firstRevertHasManyRevert" => function($query) {
			                    $query->select("*")
			                            ->with(["revertHasOneUser" => function($query) {
			                                $query->select("user_id","user_name");
			                            }]);
			                }])
			                ->with(["revertHasOneBlockquote" => function($query) {
			                    $query->select("*")
			                            ->with(["revertHasOneUser" => function($query) {
			                                $query->select("user_id","user_name");
			                            }]);
			                }]);
		if(isset($param['search']) && !empty($param['search'])){
			$query = $query->wheres($param['search']);
		}

		return $query->orders($orderBy)->parsePage($param['page'], $param['limit'])->get();
	}
    public function getRevertCounts($documentIds)
    {
        $reverts = $this->entity->selectRaw('document_id,count(revert_id) as revert_count')->whereIn('document_id',$documentIds)->groupBy('document_id')->get();
        $map = [];
        if(count($reverts) > 0){
            foreach ($reverts as $log){
                $map[$log->document_id] = $log->revert_count;
            }
        }
        return $map;
    }
	public function mulitAddRevert($data)
	{
		return $this->entity->insert($data);
	}
	//获取所有文档回复数量
	public function getAllRevertCount($userIds, $where)
	{
		$query = $this->entity->selectRaw('user_id, count(*) as count')
							  ->whereIn('user_id', $userIds);

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

		return	$query->groupBy('user_id')
						->get()
						->toArray();
	}
}
