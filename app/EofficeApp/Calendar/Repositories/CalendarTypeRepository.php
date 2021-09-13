<?php
namespace App\EofficeApp\Calendar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Calendar\Entities\CalendarTypeEntity;
use App\EofficeApp\Lang\Services\LangService;
use Lang;
use DB;

class CalendarTypeRepository extends BaseRepository
{
    public function __construct(CalendarTypeEntity $entity, LangService $langService) {
        parent::__construct($entity);
        $this->langService = $langService;
    }
    
    public function getAllCalendarType($param)
    {
        $query = $this->entity;
        if (isset($param['search']) && !empty($param['search'])) {
            $local = Lang::getLocale();
            $langTable = $this->langService->getLangTable($local);
            $param['lang_table'] = $langTable;
            if (isset($param['lang_table']) && !empty($param['lang_table']) && isset($param['search']['type_name']) && !empty($param['search']['type_name'])) {
                $tempSearchParam = [
                    'lang_value' => $param['search']['type_name'],
                    'table' => ['calendar_type', 'like']
                ];
                $tempQuery = DB::table($param['lang_table']);
                $tempQuery = $this->parseWheres($tempQuery, $tempSearchParam);
                $langKeys  = $tempQuery->get()->pluck('lang_key')->toArray();
                if (!empty($langKeys)) {
                    $param['search']['type_name'] = [$langKeys, 'in'];
                } else {
                    return [];
                }
            }
            $query = $query->wheres($param['search']); 
        }
        return $query->orderBy('sort', 'asc')->get();
    }
    public function getDefalutCalendarType()
    {
        return $this->entity->where('is_default', 1)->first();
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
