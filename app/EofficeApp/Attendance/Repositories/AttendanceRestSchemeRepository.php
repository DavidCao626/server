<?php

namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceRestSchemeEntity;

/**
 * 节假日方案类
 *
 * @author 施奇
 *
 * @since 2018-10-11
 */
class AttendanceRestSchemeRepository extends BaseRepository
{
    public function __construct(AttendanceRestSchemeEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取所有方案
     */
    public function getAllSchemes($withTrashed = false)
    {
        $query = $this->entity;
        if ($withTrashed) {
            $query = $query->withTrashed();
        }
        return $query->get();
    }
    public function getSchemesByIds($schemeId)
    {
        return $this->entity->whereIn('scheme_id', $schemeId)->get();
    }
    /**
     * 获取方案列表
     */
    public function getRestSchemeList($params)
    {
        $defaultParams = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => ['scheme_id' => 'asc'],
            'search' => []
        ];

        $params = array_merge($defaultParams, $params);

        return $this->entity->select($params['fields'])
            ->wheres($params['search'])
            ->orders($params['order_by'])
            ->parsePage($params['page'], $params['limit'])
            ->get();
    }

    /**
     * 获取方案总数
     */
    public function getRestSchemeTotal($params)
    {
        $search = isset($params['search']) ? $params['search'] : [];

        return $this->entity->wheres($search)->count();
    }

    /**
     * 通过条件获取某个方案的详细信息
     * @param bool $where
     * @param array $fields
     * @return object
     */
    public function getOneScheme($where = false, $rest = false, $fields = ['*'])
    {
        if ($where) {
            $query = $this->entity->select($fields);
            if ($rest) {
                $query = $query->with(['rest' => function ($query) {
                    $query->select('scheme_id', 'rest_id', 'rest_name', 'start_date', 'end_date');
                }]);
            }
            return $query->wheres($where)->first();
        }
        return false;
    }

    /**
     * 通过scheme_id获取某个方案的详细信息
     * @param $scheme_id
     * @param array $fields
     * @return object
     */
    public function getOneSchemeById($scheme_id, $rest = false, $fields = ['*'])
    {
        if ($scheme_id) {
            $wheres = ['scheme_id' => [$scheme_id]];
            return $this->getOneScheme($wheres, $rest, $fields);
        }
        return false;
    }
}
