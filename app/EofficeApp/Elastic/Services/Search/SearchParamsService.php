<?php


namespace App\EofficeApp\Elastic\Services\Search;


use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Elastic\Configurations\ConfigOptions;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Foundation\SearchParams;
use App\EofficeApp\Elastic\Repositories\ElasticSearchConfigRepository;
use Illuminate\Http\Request;

class SearchParamsService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 创建搜索参数
     *
     * @param Request $request
     * @param array $own
     *
     * @return SearchParams
     */
    public function build(Request $request, $own)
    {
        // 参数验证
        $input = [];
        // 搜索关键字
        $input['keyword'] = $request->query->get('content', '');
        // 获取参数及验证
        $input['index'] = $request->query->get('index', '');
        $input['page'] = max($request->query->getInt('page', 1), 1);
        $input['pageSize'] = max($request->query->getInt('limit', 10), 5);
        $this->validate($input);

        // 获取模块访问权限
        $models = $this->modelPermission($own);

        // 组装参数
        return $this->getSearchParams($input, $models);
    }

    /**
     * 请求参数验证
     *
     * @param array
     */
    public function validate($input)
    {
        // TODO
    }

    /**
     * 获取可访问模块
     *
     * @param array $own 用户信息
     *
     * @return array $menus 用户可搜索的索引
     */
    public function modelPermission($own)
    {
        // 用户可访问的菜单
        $menuIds = isset($own['menus']['menu']) ? $own['menus']['menu'] : [];
        // 获取所有模块的Id, 不存在menuId的均设置为0
        $menus = array_keys( Constant::$allModels,0);

        foreach (Constant::$allModels as $category => $menuId) {
            if (!$menuId) {
                continue;
            }

            // id 可能为数组
            if (is_array($menuId)) {
                foreach ($menuId as $subMenuId) {
                    if (in_array($subMenuId, $menuIds)) {
                        $menus[] = $category;
                    }
                }
            }

            if (in_array($menuId, $menuIds)) {
                $menus[] = $category;
            }
        }

        $menus = array_unique($menus);

        return $menus;
    }

    /**
     * 组装搜索参数
     *
     * @param array
     * @param array
     *
     * @return SearchParams
     */
    public function getSearchParams($arguments, $models)
    {
        $params = New SearchParams();

        // 若查询制定类型索引, 获取对应模块
        if ($arguments['index']) {
            if (in_array($arguments['index'], $models)) {
                $models = [$arguments['index']];
            } else {
                $models = [];
            }
        }

        $params->setKeyword($arguments['keyword'])
            ->setFilters($models)
            ->setFrom(($arguments['page'] - 1) * $arguments['pageSize'])
            ->setSize($arguments['pageSize'])
            ->setExplain(false)
            ->setMinShouldMatch($this->getMinShouldMatch());

        return $params;
    }

    /**
     * 获取最小匹配度
     */
    public function getMinShouldMatch()
    {
        return 1;
    }
}