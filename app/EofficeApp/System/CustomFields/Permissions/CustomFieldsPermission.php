<?php
namespace App\EofficeApp\System\CustomFields\Permissions;
class CustomFieldsPermission
{
    public function __construct()
    {
        $this->fieldsService = 'App\EofficeApp\System\CustomFields\Services\FieldsService';
    }
    //  /**
    //  * 验证页面列表菜单权限
    //  * @param type $own // 当前用户信息
    //  * @param type $data // 前端提交的数据或参数
    //  * @param type $urlData // restful api 上带的参数
    //  * @return boolean
    //  */
    // public function getCustomDataLists($own, $data, $urlData)
    // {
    //     if(!isset($urlData['table_key']) || empty($urlData['table_key'])) {
    //         return ['code' => ['0x009003', 'menu']];
    //     }
    //     $tableKey = $urlData['table_key'];
    //     return app($this->fieldsService)->checkPermission($tableKey);
    // }
    // /**
    //  * 验证查询列表菜单权限
    //  * @param type $own // 当前用户信息
    //  * @param type $data // 前端提交的数据或参数
    //  * @param type $urlData // restful api 上带的参数
    //  * @return boolean
    //  */
    // public function getCustomDataAutoSearchLists($own, $data, $urlData)
    // {
    //     if(!isset($urlData['table_key']) || empty($urlData['table_key'])) {
    //         return ['code' => ['0x009003', 'menu']];
    //     }
    //     $tableKey = $urlData['table_key'];
    //     return app($this->fieldsService)->checkPermission($tableKey);
    // }
    // /**
    //  * 验证页面详情菜单权限
    //  * @param type $own // 当前用户信息
    //  * @param type $data // 前端提交的数据或参数
    //  * @param type $urlData // restful api 上带的参数
    //  * @return boolean
    //  */
    // public function getCustomDataDetail($own, $data, $urlData)
    // {
    //     if(!isset($urlData['table_key']) || empty($urlData['table_key'])) {
    //         return ['code' => ['0x009003', 'menu']];
    //     }
    //     $tableKey = $urlData['table_key'];
    //     return app($this->fieldsService)->checkPermission($tableKey,'detailMenu');
    // }
    /**
     * 验证删除数据菜单权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function deleteCustomData($own, $data, $urlData)
    {
        if(!isset($urlData['table_key']) || empty($urlData['table_key'])) {
            return ['code' => ['0x009003', 'menu']];
        }
        $tableKey = $urlData['table_key'];
        return app($this->fieldsService)->checkPermission($tableKey,'deleteMenu');
    }
    /**
     * 验证编辑数据菜单权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function editCustomData($own, $data, $urlData)
    {
        if(!isset($urlData['table_key']) || empty($urlData['table_key'])) {
            return ['code' => ['0x009003', 'menu']];
        }
        $tableKey = $urlData['table_key'];
        return app($this->fieldsService)->checkPermission($tableKey,'editMenu');
    }
     /**
     * 验证新建数据菜单权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function addCustomData($own, $data, $urlData)
    {
        if(!isset($urlData['table_key']) || empty($urlData['table_key'])) {
            return ['code' => ['0x009003', 'menu']];
        }
        $tableKey = $urlData['table_key'];
        return app($this->fieldsService)->checkPermission($tableKey,'addMenu');
    }

    
    
}
