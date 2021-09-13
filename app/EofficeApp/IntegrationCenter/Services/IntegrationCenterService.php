<?php

namespace App\EofficeApp\IntegrationCenter\Services;

use App\EofficeApp\Base\BaseService;

/**
 * 集成中心模块，后端service
 *
 * @author: 王炜锋
 *
 * @since：2019-09-05
 */
class IntegrationCenterService extends BaseService
{

    public function __construct(
    ) {
        parent::__construct();
        $this->integrationCenterRepository = "App\EofficeApp\Dingtalk\Repositories\IntegrationCenterRepository";
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->yonyouVoucherService = 'App\EofficeApp\YonyouVoucher\Services\YonyouVoucherService';
        $this->kingdeeService = 'App\EofficeApp\Kingdee\Services\KingdeeService';
    }

    public function getBoard($params)
    {
        $currentUserId = own('user_id');

        // 20190905-从菜单取看板信息
        $userMenuObject = app('App\EofficeApp\Menu\Services\UserMenuService');
        $menus = app($this->userMenuService)->getIntegrationBoardMenus($currentUserId, 'PC', '280');
        $menus = array_column($menus, NULL, 'menu_id');

        // 取看板配置(带排序，带分类，不包含id-281的菜单)
        // 看板分类属性 function功能集成,production产品集成
        $modelConfig = config('integrationcenter.module');

        $result = [];
        // 处理方式-20200810-按照数据库查到的$menus的排序来；$modelConfig提供看板类别
        if(!empty($menus)) {
            foreach ($menus as $menuId => $menusInfo) {
                $configMenuInfo = $modelConfig[$menuId] ?? [];
                // 排除id-281
                if(!empty($menusInfo) && $menuId != '281') {
                    $menusInfo['board_classify'] = $configMenuInfo['classify'] ?? '';
                    array_push($result, $menusInfo);
                }
            }
        }
        // 新处理方式-20200603-排序跟着config走
        // if(!empty($modelConfig)) {
        //     foreach ($modelConfig as $configMenuId => $configMenuInfo) {
        //         $menusInfo = $menus[$configMenuId] ?? [];
        //         if(!empty($menusInfo)) {
        //             $menusInfo['board_classify'] = $configMenuInfo['classify'] ?? '';
        //             array_push($result, $menusInfo);
        //         }
        //     }
        // }
        // 判断是否带classify参数来筛选返回
        if (!empty($params['search'])) {
            $search = json_decode($params['search'], true);
            $classify = $search['classify'];
            if (!empty($classify) && $classify != 'all') {
                $result = array_filter($result, function ($value) use ($classify) {
                    return $value['board_classify'] == $classify ? true : false;
                });
                $result = array_values($result); //重建索引
            }
        }

        return $result;
    }

    /**
     * 为“凭证配置”选择器提供数据的路由(定义流程-外发到凭证配置时用到)
     * @author dingpeng <[<email address>]>
     * @return [type] [description]
     */
    public function getVocherIntergrationConfig($param) {
        // 凭证类型（1-u8/2-k3...）
        $voucherCategory = isset($param['category']) ? $param['category'] : '';
        if($voucherCategory == '1') {
            // $param = [];
            $configList = app($this->yonyouVoucherService)->getVoucherMainConfig($param);
            return $configList;
        }
        if($voucherCategory == '2') {
            $configList = app($this->kingdeeService)->getK3TableSelectByFlow($param);
            return $configList;
        }
        return "";
    }

    /**
     * 获取凭证集成的基本信息配置，要传入凭证类型
     * @author dingpeng <[<email address>]>
     * @return [type] [description]
     */
    public function getVocherIntergrationBaseInfo($param, $vocheType) {
        $config = app($this->yonyouVoucherService)->getBaseInfo($vocheType);
        return $config;
    }

    /**
     * 保存凭证集成的基本信息配置--主要是数据库
     * @param  [type] $param  [description]
     * @param  [type] $baseId [description]
     * @return [type]         [description]
     */
    public function saveVocherIntergrationBaseInfo($param, $baseId)
    {
        $res = app($this->yonyouVoucherService)->saveBaseInfo($param, $baseId);
        return $res;
    }

    /**
     * 功能函数，由流程模块调用，传入凭证配置信息&流程信息，实现外发生成凭证功能
     * @param  [type] $param    [外发配置信息等;包含: voucher_category - 凭证大类;voucher_config - 凭证配置id]
     * @param  [type] $flowData [流程信息]
     * @return [type]           [description]
     */
    public function voucherIntergrationOutSend($param, $flowData)
    {
        // 凭证大类
        $voucherCategory = isset($param['voucher_category']) ? $param['voucher_category'] : '';
        // u8集成
        if($voucherCategory == 1) {
            return app($this->yonyouVoucherService)->yonyouVoucherOutSend($param, $flowData);
        }
        if($voucherCategory == 2) {
            return app($this->kingdeeService)->k3OutSend($param, $flowData);
        }
    }
}
