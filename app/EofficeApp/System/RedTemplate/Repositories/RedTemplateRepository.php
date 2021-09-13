<?php

namespace App\EofficeApp\System\RedTemplate\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\RedTemplate\Entities\RedTemplateEntity;

/**
 * 套红模板Repository类:提供套红模板表操作资源
 *
 * @author miaochenchen
 *
 * @since  2016-09-28 创建
 */
class RedTemplateRepository extends BaseRepository
{
    public function __construct(RedTemplateEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取套红模板列表
     *
     * @param  array $param 查询参数
     *
     * @return array  套红模板列表
     *
     * @author miaochenchen
     *
     * @since  2016-09-28
     */
    public function getRedTemplateList(array $param = [])
    {
        $default = [
            'fields'   => ['template_id', 'template_name', 'template_description', 'template_content'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['template_id' => 'desc'],
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity->select($param['fields'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit']);
        $query = $this->parseRedTemplateWhere($query, $param['search']);
        return $query->get()->toArray();
    }

    /**
     * 获取当前用户的套红模板列表
     *
     * @param  array $param 查询参数
     *
     * @return array  套红模板列表
     *
     * @author nitianhua
     *
     * @since  2018-12-18
     */
    public function getMyRedTemplateList(array $param = [])
    {
        $own = $param['own'];
        $default = [
            'fields'   => ['template_id', 'template_name', 'template_description', 'template_content'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['template_id' => 'desc'],
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity->select($param['fields'])
            ->where(function ($query) {
                $query->where('user_id', '')
                    ->where('dept_id', '')
                    ->where('role_id', '');
            })
            ->orWhere(function ($query) use ($own) {
                $query->orWhereRaw('find_in_set(?,user_id)', [$own['user_id']])
                    ->orWhereRaw('find_in_set(?,dept_id)', [$own['dept_id']]);
                foreach ($own['role_id'] as $roleId) {
                    $query->orWhereRaw('find_in_set(?,role_id)', [$roleId]);
                }
            })
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit']);
        $query = $this->parseRedTemplateWhere($query, $param['search']);
        return $query->get()->toArray();
    }

    /**
     * 查询数量
     *
     * @param  array $param 查询条件
     *
     * @return int    数量
     *
     * @author miaochenchen
     *
     * @since  2016-09-28
     */
    public function getRedTemplateTotal(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];

        $query = $this->parseRedTemplateWhere($this->entity, $where);

        return $query->count();
    }

    /**
     * 查询数量
     *
     * @param  array $param 查询条件
     *
     * @return int    数量
     *
     * @author miaochenchen
     *
     * @since  2016-09-28
     */
    public function getMyRedTemplateTotal(array $param = [])
    {
        $own = $param['own'];
        $where = isset($param['search']) ? $param['search'] : [];

        $query = $this->entity
                ->where(function ($query) {
                    $query->where('user_id', '')
                        ->where('dept_id', '')
                        ->where('role_id', '');
                })
                ->orWhere(function ($query) use ($own) {
                    $query->orWhereRaw('find_in_set(?,user_id)', $own['user_id'])
                        ->orWhereRaw('find_in_set(?,dept_id)', $own['dept_id']);
                    foreach ($own['role_id'] as $roleId) {
                        $query->orWhereRaw('find_in_set(?,role_id)', [$roleId]);
                    }
                });

        $query = $this->parseRedTemplateWhere($query, $where);

        return $query->count();
    }

    /**
     * 获取套红模板where条件解析
     *
     * @param  array $where 查询条件
     *
     * @return object
     *
     * @author miaochenchen
     *
     * @since  2016-09-28
     */
    public function parseRedTemplateWhere($query, array $where = [])
    {
        return $query->wheres($where);
    }

}
