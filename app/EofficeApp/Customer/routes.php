<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$routeConfig = [
    // 客户信息
    ['customer', 'storeCustomer', 'post', [501, 543]],
    ['customer/lists', 'customerLists'],
    ['customer/all', 'customerAllLists'],
    ['customer/menus', 'customerMenus'],
    ['customer/merge', 'mergeCustomer', 'post', [514]],
    ['customer/share', 'shareCustomers', 'post', [502]],
    ['customer/linkman', 'linkmanLists'],
    ['customer/linkman', 'storeLinkman', 'post'],
    ['customer/contact-record', 'contactRecordLists'],
    ['customer/contact-record', 'storeContactRecord', 'post', [502]],
    ['customer/contract', 'contractLists'],
    ['customer/contract', 'storeContract', 'post'],
    ['customer/business-chances', 'businessChanceLists'],
    ['customer/business-chance', 'storeBusinessChance', 'post'],
    ['customer/products', 'productLists'],
    ['customer/product', 'storeProduct', 'post', [509]],
    ['customer/sale-record', 'saleRecordLists'],
    ['customer/sale-record', 'storeSaleRecord', 'post', [507]],
    ['customer/supplier-lists', 'supplierLists'],
    ['customer/apply-permissions', 'applyPermissionLists', [515]],
    ['customer/apply-audits', 'applyAuditLists', [516]],
    ['customer/permission-group', 'permissionGroupList', [543,541]],
    ['customer/permission-group/all', 'permissionGroupAllLists', [543,541]],
    ['customer/permission-group', 'storePermissionGroup', 'post', [541]],
    ['customer/seas', 'seasLists'],
    ['customer/seas/pick-up', 'pickUpCustomers', 'post', [543]],
    ['customer/seas/pick-down', 'pickDownCustomers', 'post', [543]],
    ['customer/seas/change-manager', 'changeSeasCustomerManager', 'post', [543]],
    ['customer/seas/transfer-manager', 'transferSeasCustomerManager', 'post', [543]],
    ['customer/seas/change-group', 'changeSeasCustomerGroup', 'post', [543]],
    ['customer/seas/group', 'seasGroupLists'],
    ['customer/supplier', 'storeSupplier', 'post', [508]],
    ['customer/seas/group', 'storeSeasGroup', 'post', [542]],
    ['customer/will-visit', 'visitLists'],
    ['customer/will-visit', 'storeVisit', 'post'],
    // 客户经理拥有客户数量
    ['customer/manager-customers', 'managerCustomers', [510]],
    // 回收站列表，显示所有软删除数据
    ['customer/recycles', 'recycleCustomerLists'],
    ['customer/merge/lists', 'customerMergeLists', [514]],
    ['customer/transfer/lists', 'customerTransferLists', [510]],
    ['customer/transfer', 'transferCustomer', 'post', [510]],
    // 客户表单数据源
    ['customer/data-source-by-customer_id', 'dataSourceByCustomerId'], //客户数据源
    ['customer/data-source-by-customer_supplier_id', 'dataSourceByCustomerSupplierId'],
    ['customer/website-store','storeWebCustomer','post', [502]],

    /**
     * 提醒计划
     */
    ['customer/{customerId}/visit', 'visitCustomerUserIds', [502]],
    ['customer/will-visit/user/{customerId}', 'userVisitLists'],
    ['customer/will-visit/{visitId}/done', 'doneWillVisit', 'put', [502]],
    ['customer/will-visit/{visitId}', 'showVisit'],
    ['customer/will-visit/{visitIds}', 'deleteWillVisit', 'delete', [502]],
    ['customer/will-visit/{visitId}', 'updateWillVisit', 'put', [502]],
    
    
    /**
     * 合同信息
     */
    ['customer/contract/reminds/{contractId}', 'getContractReminds'],
    ['customer/contract/{id}', 'showContract'],
    ['customer/contract/{id}', 'updateContract', 'put'],
    ['customer/contract/{ids}', 'deleteContract', 'delete'],


    /**
     * 联系人
     */
    ['customer/linkman/customer/{id}', 'customerLinkmans'],
    ['customer/linkman/{id}', 'updateLinkman', 'put'],
    ['customer/linkman/{id}', 'showLinkman'],
    ['customer/linkman/{ids}', 'deleteLinkman', 'delete'],
    ['customer/{customerId}/linkmans', 'customersLinkmans'],

    // 联系人绑定
    ['customer/linkman/binding/{id}', 'bindingLinkman'],

    // 联系人解绑
    ['customer/linkman/cancel-binding/{id}', 'cancelBinding','put'],

    // 外部联系人列表
    ['customer/linkman/external-contact/binding-list', 'bindingList'],

    // 微信群列表
    ['customer/external-wechat/wechat-list', 'weChatList'],

    // 微信群绑定客户
    ['customer/external-wechat/wechat/{id}', 'chatBinding'],

    // 微信群解绑客户
    ['customer/external-wechat/cancel-binding/{id}', 'cancelChatBinding'],

    /**
     * 联系记录
     */
    ['customer/contact-record/{id}', 'showContactRecord'],
    ['customer/contact-record/customer/{id}', 'customerContactRecords'],
    ['customer/contact-record/{recordId}/comments/{commentId}', 'deleteContactRecordComment', 'delete', [512]],
    ['customer/contact-record/{recordId}/comments', 'storeContactRecordComment', 'post', [512]],
    ['customer/contact-record/{id}/comments', 'contactRecordComments'],
    ['customer/contact-record/{ids}', 'deleteContactRecords', 'delete', [512]],
    

    /**
     * 业务机会
     */
    ['customer/business-chance/{chanceId}', 'showBusinessChance'],
    ['customer/business-chance/{chanceId}', 'updateBusinessChance', 'put'],
    ['customer/business-chance/{chanceIds}', 'deleteBusinessChance', 'delete'],
    ['customer/business-chance/{chanceId}/logs', 'businessChanceLogLists'],
    ['customer/business-chance/{chanceId}/log', 'storeBusinessChanceLog', 'post'],
    ['customer/{customerId}/business-chances', 'customersBusinessChances'],

    /**
     * 销售记录
     */
    ['customer/sale-record/{salesId}', 'showSaleRecord'],
    ['customer/sale-record/{salesId}', 'updateSaleRecord', 'put', [507]],
    ['customer/sale-record/{salesIds}', 'deleteSaleRecords', 'delete', [507]],

    /**
     * 供应商
     */
    ['customer/supplier/{id}', 'showSupplier'],
    ['customer/supplier/{id}', 'updateSupplier', 'put', [508]],
    ['customer/supplier/{ids}', 'deleteSuppliers', 'delete', [508]],

    /**
     * 产品信息
     */
    ['customer/product/{id}', 'updateProduct', 'put', [509]],
    ['customer/product/{ids}', 'deleteProducts', 'delete', [509]],

    /**
     * 客户报表
     */
    ['customer/report/{types}', 'getCustomerReportByTypes', [518]],
    
    /**
     * 客户权限
     */
    ['customer/permission-group/{groupId}', 'updatePermissionGroup', 'put', [541]],
    ['customer/permission-group/{groupId}', 'showPermissionGroup'],
    ['customer/permission-group/{groupId}', 'deletePermissionGroup', 'delete', [541]],
    ['customer/permission-group-role/{roleId}', 'showPermissionGroupRole'],
    ['customer/permission-group-role/{roleId}', 'updatePermissionGroupRole', 'put', [540]],
    
    /**
     * 客户公海
     */
    ['customer/seas/manager-lists/{id}', 'seasCustomerManagerLists'],
    ['customer/seas/distribute-group/{id}', 'autoDistribute', 'put', [543]],
    ['customer/seas/group/{id}', 'showSeasGroup'],
    ['customer/seas/group/{id}', 'updateSeasGroup', 'put', [542]],
    ['customer/seas/group/{id}', 'deleteSeasGroup', 'delete', [542]],
    ['customer/seas/public/set', 'seasSetting', 'post', [542]],
    ['customer/seas/public/get', 'getSetting', [542,543]],
    ['customer/seas/public/update/{id}', 'updateSetting', 'put',[542]],

    /**
     * 客户信息
     */
    ['customer/face/{customerId}', 'updateCustomerFace', 'put', [502]],
    ['customer/apply-permissions/{id}', 'showApplyPermissions'],
    ['customer/apply-permissions/{id}', 'applyPermissions', 'post', [501, 515]],
    ['customer/apply-permissions/{ids}', 'deleteApplyPermissions', 'delete', [515, 516]],
    ['customer/apply-audits/{id}', 'showApplyAudit'],
    ['customer/apply-audits/{ids}', 'updateApplyAudit', 'put', [516]],
    // ['customer/apply-audits/{id}', 'deleteApplyAudits', 'delete', [516]],
    ['customer/share/{customerId}', 'shareCustomer', 'put', [502]],
    ['customer/merge/{customerIds}', 'showCustomerMerge', [514]],
    ['customer/recycles/{id}', 'showRecycleCustomer'],
    ['customer/recycles/{id}', 'recoverRecycleCustomer', 'put', [513]],
    ['customer/recycles/{ids}', 'deleteRecycleCustomers', 'delete', [513]],
    ['customer/menus/{key}', 'toggleCustomerMenus', 'put', [502]],
    ['customer/{customerId}/logs', 'customerLogLists'],
    ['customer/{customerId}/new-logs', 'customerNewLogLists'],
    ['customer/{customerId}/add-attention', 'addAttention', 'put', [502]],
    ['customer/{customerId}/cancel-attention', 'cancelAttention', 'put', [502]],
    ['customer/{customerId}', 'showCustomer'],
    ['customer/{customerId}', 'updateCustomer', 'put', [502]],
    ['customer/{customerIds}', 'deleteCustomer', 'delete', [502]],
    ['customer/{customerId}/pre', 'showPreCustomer'],
    ['customer/{customerId}/next', 'showNextCustomer'],
    ['customer/cancel/share/{customerId}', 'cancelShare','put'],
    ['customer/modify', 'modifyCustomer','post', [502]],
    ['customer/menus/translate/title', 'translateTitle','post', [502]],
    ['customer/map/customer', 'mapCustomer', [502]],


    /**
     * 合同提醒设置
    */

    ['customer/system/security/{params}', 'modifySecurityOption','put',[44]],
    ['customer/system/security/{params}', 'getSecurityOption',[44]],

    /**
     * 客户标签
     */
    ['customer/label/list', 'labelList',[971,502]],
    ['customer/label/add', 'storeLabel','post',[971]],
    ['customer/label/delete/{id}', 'deleteLabel','delete',[971]],
    ['customer/label/edit/{id}', 'editLabel','put',[971]],
    ['customer/label/relation/{customerIds}', 'relationLabel','put',[971]],
];