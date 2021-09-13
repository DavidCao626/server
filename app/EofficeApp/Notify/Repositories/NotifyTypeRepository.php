<?php
namespace App\EofficeApp\Notify\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Notify\Entities\NotifyTypeEntity;
use App\EofficeApp\Notify\Entities\NotifyEntity;
/**
 * 公告类别资源库类
 *
 * @author 李志军
 *
 * @since 2015-10-20
 */
class NotifyTypeRepository extends BaseRepository
{
	/** @var int 默认列表条数 */
	private $limit		= 20;

	/** @var int 默认列表页 */
	private $page		= 0;

	/** @var array  默认排序 */
	private $orderBy	= ['sort' => 'asc'];

	/**
	 * 注册公告列表实体对象
	 *
	 * @param \App\EofficeApp\Notify\Entities\NotifyTypeEntity $entity
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-20
	 */
	public function __construct(NotifyTypeEntity $entity)
	{
		parent::__construct($entity);
	}
	/**
	 * 判断公告类别名称是否存在
	 *
	 * @param string $notifyTypeName
	 * @param int $notifyTypeId
	 *
	 * @return int 1存在，0不存在
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-20
	 */
	public function notifyTypeNameExists($notifyTypeName, $notifyTypeId)
	{
		$query = $this->entity
				->where('notify_type_name',$notifyTypeName);

		if($notifyTypeId) {
			$query = $query->where('notify_type_id', '!=', $notifyTypeId);
		}

		return $query->count();
	}
	/**
	 * 获取公告类别数量
	 *
	 * @param array $search
	 *
	 * @return int 公告类别数量
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-20
	 */
	public function getNotifyTypeCount($data)

	{


        $default = [

            'search' => []

        ];

        $param = array_merge($default, array_filter($data));

        return $this->entity

            ->wheres($param['search'])

            ->count();

	}
	/**
	 * 获取公告类别列表
	 *
	 * @param array $param
	 *
	 * @return array 公告类别列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-10-20
	 */
	public function listNotifyType($data)
	{


        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['sort'=>'asc', 'notify_type_id' => 'asc'],
        ];

        $param = array_merge($default, array_filter($data));

        return $this->entity
        	->with(['typeHasManyNotify' => function($query)
        	{
        		$query->selectRaw("notify_type_id, count(*) as number")
        			  ->groupBy('notify_type_id');
        	}])
            ->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get();




	}

    /**
 * 获取公告类别列表
 *
 * @param array $param
 *
 * @return array 公告类别列表
 *
 * @author 李志军
 *
 * @since 2015-10-20
 */
    public function getNotifyTypeName($data)
    {

        return $this->entity
            ->where('notify_type_id', $data)->value('notify_type_name');
    }
}
