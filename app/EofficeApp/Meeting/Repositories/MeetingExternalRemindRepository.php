<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Meeting\Entities\MeetingExternalRemindEntity;
use App\EofficeApp\Lang\Services\LangService;
use Lang;
use DB;
/**
 * @会议室资源库类
 *
 * @author 李志军
 */
class MeetingExternalRemindRepository extends BaseRepository
{
	private $primaryKey = 'external_remind_id';//主键

	private $limit		= 20;//列表页默认条数

	private $page		= 0;//页数

	private $orderBy	= ['external_remind_id' => 'desc'];//默认排序

	/**
	 * @注册会议室实体
	 * @param \App\EofficeApp\Entities\MeetingRoomsEntity $entity
	 */
	public function __construct(MeetingExternalRemindEntity $entity, LangService $langService) {
		parent::__construct($entity);
		$this->langService = $langService;
	}
	/**
	 * @获取外部人员提醒方式
	 * @param type $param
	 * @return 提醒方式 | array
	 */
	public function listExternalRemindType($param)
	{
		$param['fields']	= empty($param['fields']) ? ['*'] : $param['fields'];

		$param['limit']		= !isset($param['limit']) ? $this->limit : $param['limit'];

		$param['page']		= !isset($param['page']) ? $this->page : $param['page'];

		$param['order_by']	= empty($param['order_by']) ? $this->orderBy : $param['order_by'];
		$local = Lang::getLocale();
		$langTable = $this->langService->getLangTable($local);

		$param['lang_table'] = $langTable;
		$query = $this->entity->select($param['fields']);

		if (isset($param['search']['external_remind_name']) && !empty($param['search']['external_remind_name'])) {
            if (isset($param['lang_table']) && !empty($param['lang_table']) && isset($param['search']['external_remind_name']) && !empty($param['search']['external_remind_name'])) {
                $tempSearchParam = [
                    'lang_value' => $param['search']['external_remind_name'],
                    'table' => ['meeting_external_remind', 'like']
                ];
                $tempQuery = DB::table($param['lang_table']);
                $tempQuery = $this->parseWheres($tempQuery, $tempSearchParam);
                $langKeys  = $tempQuery->get()->pluck('lang_key')->toArray();
                if (!empty($langKeys)) {
                    $param['search']['external_remind_name'] = [$langKeys, 'in'];
                } else {
                    return [];
                }
            }
        }
		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}

		return $query->orders($param['order_by'])
					->parsePage($param['page'], $param['limit'])
					->get()->toArray();
	}
	public function getExternalRemindTypeCount($param)
	{
		$query = $this->entity;

		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}

		return $query->count();
	}
	// 解析DB模式的多条件查询
    public function parseWheres($query, $wheres)
    {
        $operators = [
            'between'       => 'whereBetween',
            'not_between'   => 'whereNotBetween',
            'in'            => 'whereIn',
            'not_in'        => 'whereNotIn'
        ];

        if (empty($wheres)) {
            return $query;
        }

        foreach ($wheres as $field=>$where) {
            $operator = isset($where[1]) ? $where[1] : '=';
            $operator = strtolower($operator);
            if (isset($operators[$operator])) {
                $whereOp = $operators[$operator]; //兼容PHP7写法
                $query = $query->$whereOp($field, $where[0]);
            } else {
                $value = $operator != 'like' ? $where[0] : '%'.$where[0].'%';
                $query = $query->where($field, $operator, $value);
            }
        }
        return $query;
    }
}
