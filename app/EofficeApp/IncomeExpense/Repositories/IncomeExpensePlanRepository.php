<?php
namespace App\EofficeApp\IncomeExpense\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\IncomeExpense\Entities\IncomeExpensePlanEntity;
use App\EofficeApp\IncomeExpense\Entities\IncomeExpensePlanSubEntity;

/**
 * 收支方案资源库对象
 *
 * @author 李志军
 *
 * @since 2015-10-20
 */
class IncomeExpensePlanRepository extends BaseRepository
{
    /** @var object 收支方案子表实体对象 */
    private $incomeExpensePlanSubEntity;

    /** @var string 主键 */
    private $primaryKey = 'plan_id';

    /** @var int 默认列表条数 */
    private $limit = 20;

    /** @var int 默认列表页 */
    private $page = 0;

    /** @var array  默认排序 */
    private $orderBy = ['created_at' => 'desc'];

    /**
     * 注册收支方案相关实体对象
     *
     * @param \App\EofficeApp\IncomeExpense\Entities\IncomeExpensePlanEntity $entity
     * @param \App\EofficeApp\IncomeExpense\Entities\IncomeExpensePlanSubEntity $incomeExpensePlanSubEntity
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function __construct(
        IncomeExpensePlanEntity $entity,
        IncomeExpensePlanSubEntity $incomeExpensePlanSubEntity
    ) {
        parent::__construct($entity);

        $this->incomeExpensePlanSubEntity = $incomeExpensePlanSubEntity;
    }
    /**
     * 获取收支方案列表
     *
     * @param array $param 查询参数
     *
     * @return array 收支方案列表
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function listPlan($param)
    {
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['*'];

        $param['limit'] = isset($param['limit']) ? $param['limit'] : $this->limit;

        $param['page'] = isset($param['page']) ? $param['page'] : $this->page;

        $param['order_by'] = isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
        $own = $param['own'];
        $query = $this->entity->select($param['fields'])
            ->where(function ($query) use ($own) {
                $query->where('all_user', 1)
                    ->orWhere('creator', $own['user_id'])
                    ->orWhere(function ($query) use ($own) {
                        $query->orWhereRaw("FIND_IN_SET(?, user_id)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_id)", [$own['dept_id']]);
                        foreach ($own['role_id'] as $roleId) {
                            $query->orWhereRaw("FIND_IN_SET(?, role_id)", [$roleId]);
                        }
                    });
            });

        if (isset($param['search']) && !empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }

        return $query->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get();
    }
    public function listAllPlan($param)
    {
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['*'];

        $param['limit'] = isset($param['limit']) ? $param['limit'] : $this->limit;

        $param['page'] = isset($param['page']) ? $param['page'] : $this->page;

        $param['order_by'] = isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
        $query = $this->entity->select($param['fields']);
        if (isset($param['search']) && !empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }

        return $query->orders($param['order_by'])
            ->forPage($param['page'], $param['limit'])
            ->get();
    }
    public function getAllPlanCount($param)
    {
        $query = $this->entity;

        if (!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }

        return $query->count();
    }
    /**
     * 获取收支方案数量
     *
     * @param array $search 查询参数
     *
     * @return int 收支方案数量
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function getPlanCount($search)
    {
        $own = $search['own'];
        $query = $this->entity
            ->where(function ($query) use ($own) {
                $query->where('all_user', 1)
                    ->orWhere('creator', $own['user_id'])
                    ->orWhere(function ($query) use ($own) {
                        $query->orWhereRaw("FIND_IN_SET(?, user_id)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_id)", [$own['dept_id']]);
                        foreach ($own['role_id'] as $roleId) {
                            $query->orWhereRaw("FIND_IN_SET(?, role_id)", [$roleId]);
                        }
                    });
            });

        if (!empty($search['search'])) {
            $query = $query->wheres($search['search']);
        }

        return $query->count();
    }
    public function hasShowPurview($planId, $own)
    {
        $query = $this->entity
            ->where(function ($query) use ($own) {
                $query->where('all_user', 1)
                    ->orWhere('creator', $own['user_id'])
                    ->orWhere(function ($query) use ($own) {
                        $query->orWhereRaw("FIND_IN_SET(?, user_id)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_id)", [$own['dept_id']]);
                        foreach ($own['role_id'] as $roleId) {
                            $query->orWhereRaw("FIND_IN_SET(?, role_id)", [$roleId]);
                        }
                    });
            })->where('plan_id', $planId);
        return $query->count();
    }
    /**
     * 新建收支方案
     *
     * @param array $data 收支方案数据
     *
     * @return int 方案id
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function addPlan($data)
    {
        $subData['plan_description'] = $data['plan_description'];

        unset($data['plan_description']);

        if (!$plan = $this->entity->create($data)) {
            return false;
        }

        $subData['plan_id'] = $plan->plan_id;

        $this->incomeExpensePlanSubEntity->insert($subData);

        return $plan->plan_id;
    }
    /**
     * 编辑收支方案
     *
     * @param array $data 方案数据
     * @param int $planId 方案id
     *
     * @return boolean 编辑结果
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function editPlan($data, $planId)
    {
        if (isset($data['plan_description'])) {
            $subData['plan_description'] = $data['plan_description'];
            unset($data['plan_description']);
        }
        if ($this->entity->where($this->primaryKey, $planId)->update($data)) {
            if (isset($subData)) {
                $this->incomeExpensePlanSubEntity->where($this->primaryKey, $planId)->update($subData);
            }
            return true;
        }

        return false;
    }
    /**
     * 获取收支方案详情
     *
     * @param int $planId 方案id
     *
     * @return object 方案详情
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function showPlan($planId, $all = false)
    {
        if ($all) {
            return $this->entity
                ->select(['income_expense_plan.*', 'income_expense_plan_sub.plan_description', 'user.user_name as creator_name', 'income_expense_plan_type.plan_type_name'])
                ->leftJoin('income_expense_plan_sub', 'income_expense_plan_sub.plan_id', '=', 'income_expense_plan.plan_id')
                ->leftJoin('user', 'user.user_id', '=', 'income_expense_plan.creator')
                ->leftJoin('income_expense_plan_type', 'income_expense_plan_type.plan_type_id', '=', 'income_expense_plan.plan_type_id')
                ->where('income_expense_plan.' . $this->primaryKey, $planId)->first();
        }

        return $this->entity->where($this->primaryKey, $planId)->first();
    }
    /**
     * 删除收支方案
     *
     * @param int $planId
     *
     * @return boolean 删除结果
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function deletePlan($planId)
    {
        return $this->entity->destroy($planId);
    }
    /**
     * 获取假删除收支方案列表
     *
     * @param array $param 查询参数
     *
     * @return array 假删除收支方案列表
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function listTrashedPlan($param)
    {
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['*'];

        $param['limit'] = isset($param['limit']) ? $param['limit'] : $this->limit;

        $param['page'] = isset($param['page']) ? $param['page'] : $this->page;

        $param['order_by'] = isset($param['order_by']) ? $param['order_by'] : $this->orderBy;

        $query = $this->entity->onlyTrashed()->select($param['fields']);

        if (isset($param['search']) && !empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }

        return $query->orders($param['order_by'])
            ->forPage($param['page'], $param['limit'])
            ->get();
    }
    /**
     * 获取假删除方案数量
     *
     * @param array $search 查询参数
     *
     * @return int 被假删除的方案数量
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function getTrashedPlanCount($search)
    {
        $query = $this->entity->onlyTrashed();

        if (!empty($search['search'])) {
            $query = $query->wheres($search['search']);
        }

        return $query->count();
    }
    /**
     * 恢复软删除的方案
     *
     * @param array $planId
     *
     * @return boolean  删除结果
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function recoverTrashedPlan($planId)
    {
        return $this->entity->onlyTrashed()
            ->whereIn('plan_id', $planId)
            ->restore();
    }
    /**
     * 彻底销毁软删除的方案
     *
     * @param array $planId
     *
     * @return boolean 销毁结果
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function destroyTrashedPlan($planId)
    {
        if ($this->entity->onlyTrashed()->whereIn('plan_id', $planId)->forceDelete()) {
            $this->incomeExpensePlanSubEntity->whereIn('plan_id', $planId)->delete();
            return true;
        }

        return false;
    }
    /**
     * 获取最多方案id
     *
     * @return int 方案id
     *
     * @author 李志军
     *
     * @since 2015-10-27
     */
    public function getPlanCode()
    {
        $planId = $this->entity->withTrashed()->max('plan_id');

        return $planId ? $planId + 1 : 1;
    }
}
