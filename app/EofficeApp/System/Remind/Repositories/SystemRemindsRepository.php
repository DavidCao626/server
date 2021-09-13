<?php

namespace App\EofficeApp\System\Remind\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Remind\Entities\SystemRemindsEntity;
use App\EofficeApp\Lang\Services\LangService;
use Lang;
use DB;

/**
 * system_reminds表资源库
 *
 * @author  朱从玺
 *
 * @since  2016-02-26 创建
 */
class SystemRemindsRepository extends BaseRepository
{
	public function __construct(SystemRemindsEntity $entity, LangService $langService)
	{
		parent::__construct($entity);
		$this->langService = $langService;
	}

	/**
	 * [insert 批量插入数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array] $data [插入数据]
	 *
	 * @return [bool]        [插入结果]
	 */
	public function insert($data)
	{
		return $this->entity->insert($data);
	}

	/**
	 * [getRemindsList 消息提醒设置列表]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]          $param [查询条件]
	 *
	 * @return [object]                [查询结果]
	 */
	public function getRemindsList($param)
	{
		$local = Lang::getLocale();
        $langTable = $this->langService->getLangTable($local);
		$defaultParam = [
			'fields' => ['*'],
			'order_by' => ['id' => 'asc'],
			'search' => []
		];

		$param = array_merge($defaultParam, $param);
		$param['fields'] = ['*'];
		$param['lang_table'] = $langTable;
		$query = $this->entity;
		if (isset($param['search']['remind_name']) && !empty($param['search']['remind_name'])) {
            if (isset($param['lang_table']) && !empty($param['lang_table']) && isset($param['search']['remind_name']) && !empty($param['search']['remind_name'])) {
                $tempSearchParam = [
                    'lang_value' => $param['search']['remind_name'],
                    'table' => ['system_reminds', 'like']
                ];
                $tempQuery = DB::table($param['lang_table']);
                $tempQuery = $this->parseWheres($tempQuery, $tempSearchParam);
                $langKeys  = $tempQuery->get()->pluck('lang_key')->toArray();
                if (!empty($langKeys)) {
                    $param['search']['remind_name'] = [$langKeys, 'in'];
                } else {
                    return [];
                }
            }
        }
		return $query->select($param['fields'])
					->wheres($param['search'])
					->orders($param['order_by'])
					->with(['systemReminds' => function($query)
					{
						$query->select(['*']);
					}])
					->groupBy('remind_menu')
					->get();
	}
	public function getRemindsParent($param)
	{
		$defaultParam = [
			'fields' => ['id', 'remind_name', 'remind_menu', 'remind_type', 'remind_time', 'receive_range', 'remind_content'],
			'order_by' => ['id' => 'asc'],
			'search' => []
		];

		$param = array_merge($defaultParam, $param);
		return $this->entity
					->select($param['fields'])
					->wheres($param['search'])
					->orders($param['order_by'])
					->groupBy('remind_menu')
					->get();
	}

	public function getRemindsChild($param)
	{

		$local = Lang::getLocale();
        $langTable = $this->langService->getLangTable($local);
		$defaultParam = [
			'fields' => ['*'],
			'order_by' => ['id' => 'asc'],
			'search' => []
		];

		$param = array_merge($defaultParam, $param);
		$param['lang_table'] = $langTable;
		if(isset($param['search'])&&!is_array($param['search'])){
    		$param['search'] = json_decode($param['search'],true);
    	}
    	// 多语言查询支持 对field_name字段的查询转换为lang表lang_value字段的查询
        if (isset($param['search']['remind_name']) && !empty($param['search']['remind_name'])) {
            if (isset($param['lang_table']) && !empty($param['lang_table']) && isset($param['search']['remind_name']) && !empty($param['search']['remind_name'])) {
                $tempSearchParam = [
                    'lang_value' => $param['search']['remind_name'],
                    'table' => ['system_reminds', 'like']
                ];
                $tempQuery = DB::table($param['lang_table']);
                $tempQuery = $this->parseWheres($tempQuery, $tempSearchParam);
                $langKeys  = $tempQuery->get()->pluck('lang_key')->toArray();
                if (!empty($langKeys)) {
                    $param['search']['remind_name'] = [$langKeys, 'in'];
                } else {
                    return [];
                }
            }
        }
		return $this->entity
					->select($param['fields'])
					->wheres($param['search'])
					->orders($param['order_by'])
					->get();
	}

	/**
	 * [getRemindDetail 根据查询条件获取消息提醒数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]           $search [查询条件]
	 *
	 * @return [object]                  [查询结果]
	 */
	public function getRemindDetail($search, $fields=['*'])
	{
		return $this->entity->select($fields)
					->wheres($search)
					->first();
	}

	public function getAllReminds()
	{
		return $this->entity->get()->toArray();
	}
	function getMaxId() {
		return $this->entity->max('id');
	}
	function getRemindId() {
		$list = $this->entity->select('id')->get()->toArray();
		$remindArr = [];
		if ($list) {
			$remindArr = array_column($list, 'id');
		}
		return $remindArr;
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
    /**
     * [updateByMenuType 通过菜单类型更新数据]
     *
     * @param  array           $data  [更新数据]
     * @param  array           $where [查询条件]
     *
     * @since
     *
     * @return [bool]                 [更新结果]
     */
    public function updateByMenuType($menu, $type, $modifyData)
    {
        return $this->entity
                    ->where('remind_menu', $menu)
                    ->where('remind_type', $type)
                    ->update(['reminds_select'=>$modifyData]);
    }
}