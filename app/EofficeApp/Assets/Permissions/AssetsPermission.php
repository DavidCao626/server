<?php
namespace App\EofficeApp\Assets\Permissions;
use DB;
class AssetsPermission
{
    // 验证引擎会优先调用类里拥有的方法，如果没有则从该数组匹配找到对应的方法调用。
//    public $rules = [
//        'repair' => 'commonCreateValidate',
//        'createRetiring' => 'commonCreateValidate',
//    ];

    public function __construct() 
    {
        $this->assetsApplysRepository  = 'App\EofficeApp\Assets\Repositories\AssetsApplysRepository';
        $this->assetsRepository        = 'App\EofficeApp\Assets\Repositories\AssetsRepository';
        $this->userRepository          = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->assetsChangeRepository  = 'App\EofficeApp\Assets\Repositories\AssetsChangeRepository';
    }


    /**
     * 验证资产申请使用权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function apply($own, $data, $urlData){
        $assetsData = app($this->assetsRepository)->getDetail($data['assets_id']);
        if($assetsData['status'] != 0){
            return false;
        }

        //验证该申请人是否有使用该资产的使用权限
        if($assetsData['is_all'] == 0){
            $assetsDept = $assetsData['dept'] ? explode(',',$assetsData['dept']) :[];
            $assetsRole = $assetsData['role'] ? explode(',',$assetsData['role']) :[];
            $assetsUsers = $assetsData['users'] ? explode(',',$assetsData['users']) :[];

            $result = app($this->userRepository)->getUserAllData($data['apply_user'])->toArray();
            if(!$result){
                return false;
            }
            $permissDept = $permissUsers = $permissRole = '';
            $dept = $result['user_has_one_system_info']['dept_id'];
            $user_id = $result['user_id'];
            foreach ($result['user_has_many_role'] as $key => $vo){
                if(in_array($vo['role_id'],$assetsRole)){
                    $permissRole = true;
                };
            }
            $assetsDept && $permissDept = in_array($dept,$assetsDept);
            $assetsUsers && $permissUsers = in_array($user_id,$assetsUsers);
            if(!$permissDept && !$permissUsers && !$permissRole){
                return false;
            }
        }
        return true;
    }
    /**
     * 验证资产申请删除权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function deleteApply($own, $data, $urlData){
        $result = app($this->assetsApplysRepository)->getDetail($urlData['id']);
        $assetsData = app($this->assetsRepository)->getDetail($result['assets_id']);
        if(!$result) return false;
        //当资产申请用户删除数据时
        //0-待审核  3-未通过 5-已验收 这些情况可以删除
        if(($result->status ==3) || ($result->status ==5) || ($result->status ==0)){
            if(($result->apply_user == $own['user_id']) || in_array($own['user_id'],explode(',',$assetsData->managers))){
                return true;
            }

        }
        return false;
    }

    /**
     * 验证资产申请/审批详情权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function applyDetail($own, $data, $urlData){
        // 当用户只有申请菜单时，只能查看自己申请的资产详情
        //922  使用申请
        //923  申请审批
        $result = app($this->assetsApplysRepository)->ApplysDetail($urlData['id']);
        //资产数据
        $assetsData = app($this->assetsRepository)->getDetail($result['assets_id']);

        if(!$result) return ['code' => ['delete_type','assets']];

        // 当用户只有申请菜单时，只能查看自己申请的资产详情
        if(!in_array(923,$own['menus']['menu']) && in_array(922,$own['menus']['menu'])){
            if($result->apply_user != $own['user_id']){
                return false;
            }
        }
        // 当用户有审批菜单时，验证查看详情是否是属于自己所管理的资产
        if(in_array(923,$own['menus']['menu']) && !in_array(922,$own['menus']['menu'])){
            if(!in_array($own['user_id'],explode(',',$assetsData->managers)) && !in_array($own['user_id'],explode(',',$result->managers))){
                return false;
            }
        }
        //同时拥有两个菜单时
        if(in_array(923,$own['menus']['menu']) && in_array(922,$own['menus']['menu'])){
            if(($result->apply_user == $own['user_id']) || in_array($own['user_id'],explode(',',$assetsData->managers)) || in_array($own['user_id'],explode(',',$result->managers))){
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * 验证资产申请归还时权限(只能归还自己申请的资产)
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function returnApply($own, $data, $urlData){
        $applyData = app($this->assetsApplysRepository)->getDetail($urlData['id']);
        //资产数据
        $assetsData = app($this->assetsRepository)->getDetail($applyData['assets_id']);

        if($applyData->status == $data['status']){
            return false;
        }
        if($data['status'] == 4){
            if($applyData && $applyData->apply_user != $own['user_id']){
                return false;
            }
        }
        if($data['status'] != 4){
            // 资产审批验收权限，只能验收自己管理的资产
            if(!in_array($own['user_id'],explode(',',$assetsData['managers']))){
                return false;
            }
        }
        return true;
    }

    /**
     * 验证资产变更详情信息权限(只能查看自己变更的资产详情)
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function changeDetail($own, $data, $urlData){

        $result = app($this->assetsChangeRepository)->getChangeData($urlData['id']);
        if($result){
            $result = $result->toArray();
            if(!in_array($own['user_id'],explode(',',$result['managers']))){
                return false;
            }
        }
        return true;
    }

    /**
     * 验证变更资产，是否是属于自己的资产，且使用中，审核中，不能被变更(只能查看自己变更的资产详情)
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function assetsChange($own, $data, $urlData){
        $result = app($this->assetsRepository)->getDetail($urlData['id']);
        if($result && $result['status'] == 0){
            if(in_array($own['user_id'],explode(',',$result['managers']))){
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * 验证维护详情数据权限(只能查看自己维护资产详情)
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function repairDetail($own, $data, $urlData){
        $result = app($this->assetsApplysRepository)->ApplysDetail($urlData['id']);
        //资产数据
        $assetsData = app($this->assetsRepository)->getDetail($result['assets_id']);
        if($result && $result['apply_type'] == 'repair'){
            //当前资产维护管理员，变更之前资产维护管理员都有权限查看维护详情数据
            if($result['apply_user'] == $own['user_id'] || (in_array($own['user_id'],explode(',',$assetsData['managers']))) || in_array($own['user_id'],explode(',',$result['managers']))){
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * 验证是否是自己可以维护的资产
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function repair($own, $data, $urlData){
        $result = app($this->assetsRepository)->getDetail($data['assets_id']);
        if($result && $result['status'] == 0){
            if(in_array($own['user_id'],explode(',',$result['managers']))){
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * 验证是否是自己可以编辑的的资产(完成维护接口)
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function repairEdit($own, $data, $urlData){
        $result = app($this->assetsApplysRepository)->ApplysDetail($urlData['id']);
        //资产数据
        $assetsData = app($this->assetsRepository)->getDetail($result['assets_id']);
        if($result && $result['status'] == 0){
            if(in_array($own['user_id'],explode(',',$assetsData['managers']))){
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * 验证退库详情权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function retirDetail($own, $data, $urlData){
        $result = app($this->assetsApplysRepository)->ApplysDetail($urlData['id']);
        //资产数据
        $assetsData = app($this->assetsRepository)->getDetail($result['assets_id']);
        if($result && in_array($own['user_id'],explode(',',$assetsData['managers']))){
            return true;
        }
        return false;
    }

    /**
     * 验证退库申请权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function createRetiring($own, $data, $urlData){
        $result = app($this->assetsRepository)->getDetail($data['assets_id']);
        if($result && $result['status'] == 0){
            if(in_array($own['user_id'],explode(',',$result['managers']))){
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * 验证申请批准权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function approvalApply($own, $data, $urlData){
        $result = app($this->assetsApplysRepository)->getDetail($urlData['id']);
        if(!$result) return false;
        //判断审批的资产是否是当前管理员所管理的资产
        if(in_array($own['user_id'],explode(',',$result->managers))){
            if(($result->status == $data['status']) || ($result->status == 3) || ($result->status == 5)){
                return false;
            }
            return true;
        }
    }
}
