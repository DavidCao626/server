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
//900   档案管理
//908   我的借阅
//907   借阅申请
//910   借阅记录
//909   借阅审批
//901   卷库管理
//902   案卷管理
//903   文件管理
//911   档案鉴定
//912   档案销毁
$routeConfig = [
    //获取案卷详情
    ['archives/seal-up-volume/{volumeId}', 'sealUpVolume', 'put', [902]],
    //获取案卷详情
    ['archives/seal-off-volume/{volumeId}', 'sealOffVolume', 'put', [902]],
    //获取卷库列表(选择器专用)
    ['archives/choice/library', 'getChoiceLibrary',[900]],
    //获取卷库列表
    ['archives/library', 'getIndexArchivesLibrary',[900]],
    //新建卷库
    ['archives/library', 'createArchivesLibrary', 'post',[901]],
    //获取卷库数据详情
    ['archives/library/{id}', 'getArchivesLibrary',[901]],
    //修改卷库数据
    ['archives/library/{id}', 'editArchivesLibrary', 'put',[901]],
    //删除卷库数据
    ['archives/library/{id}', 'deleteArchivesLibrary', 'delete',[901]],
    //获取卷库数据日志
    ['archives/library/{libraryId}/log', 'getIndexArchivesLibraryLog',[901]],
    //获取案卷数据(选择器)
    ['archives/choice/volume', 'getChoiceVolume',[900]],
    //获取案卷列表
    ['archives/volume', 'getIndexArchivesVolume',[900]],
    //新建案卷
    ['archives/volume', 'createArchivesVolume', 'post',[902]],
    //获取案卷详情
    ['archives/volume/{id}', 'getArchivesVolume',[902,907,908,909,912]],//907 移动端
    //修改案卷数据
    ['archives/volume/{id}', 'editArchivesVolume', 'put',[902]],
    //删除案卷数据
    ['archives/volume/{id}', 'deleteArchivesVolume', 'delete',[902]],
    //获取案卷包含的文件
    // ['archives/volume/{volumeId}/files', 'getIndexArchivesVolumeFiles',[902,903]],
    //获取案卷数据日志
    ['archives/volume/{volumeId}/log', 'getIndexArchivesVolumeLog',[902]],
    //获取档案文件
    ['archives/file', 'getIndexArchivesFile',[902,903,907,908,909,911]],
    //新建档案文件
    ['archives/file', 'createArchivesFile', 'post',[903]],
    //获取档案文件详情
    ['archives/file/{id}', 'getArchivesFile',[902,903,907,908,909,911]],
    //修改档案文件
    ['archives/file/{id}', 'editArchivesFile', 'put',[903]],
    //删除档案文件
    ['archives/file/{id}', 'deleteArchivesFile', 'delete',[903]],
    //获取档案文件日志
    ['archives/file/{fileId}/log', 'getIndexArchivesFileLog',[903]],
    //获取档案销毁列表
    ['archives/destroy', 'getIndexArchivesDestroy',[912]],
    //获取档案销毁详情
    ['archives/destroy/{id}', 'getArchivesDestroy',[912]],
    //恢复档案销毁
    ['archives/destroy/{id}', 'editArchivesDestroy', 'put',[912]],
    //删除档案销毁
    ['archives/destroy/{id}', 'deleteArchivesDestroy', 'delete',[912]],
    //获取档案鉴定列表
    ['archives/appraisal', 'getIndexArchivesAppraisal',[911]],
    //提交档案鉴定
    ['archives/appraisal', 'createArchivesAppraisal', 'post',[911]],
    //获取档案鉴定详情
    ['archives/appraisal/{id}', 'getArchivesAppraisal',[911]],
    //获档案借阅记录列表
    ['archives/borrow', 'getIndexArchivesBorrow',[908]],
    //新建档案借阅
    ['archives/borrow', 'createArchivesBorrow', 'post',[907]],
    //获取档案借阅详情
//    ['archives/borrow/{id}', 'getArchivesBorrow',[907,909,910]], //暂时没找到使用的地方
    //修改档案借阅(档案借阅审批)
    ['archives/borrow/{id}', 'editArchivesBorrow', 'put',[908,909]],
    //删除档案借阅
    ['archives/borrow/{id}', 'deleteArchivesBorrow', 'delete',[908,910]],
    //删除档案借阅
    ['archives/borrow/{id}/audit', 'deleteAudit', 'delete',[909]],
    //获取我的档案借阅记录列表
//    ['archives/my/borrow', 'getIndexArchivesMyBorrow',[907,908,909,910]],   //暂时没找到使用的地方
    //获取我的档案借阅记录列表
    ['archives/my/borrow/{id}', 'getArchivesMyBorrow',[908,909,910]],
    //删除我的档案借阅记录列表或档案审批
//    ['archives/my/borrow/{id}', 'deleteArchivesMyBorrow', 'delete',[907,908,909,910]], //暂时没找到使用的地方
    //借阅申请列表
//    ['archives/borrow-apply/apply', 'getIndexArchivesBorrowApply',[907,908,909,910]], //暂时没找到使用的地方
    //获取审批档案借阅列表
    ['archives/borrow-approve/approve', 'getIndexArchivesBorrowApprove',[909,910]],
    //修改档案借阅
//    ['archives/borrow/approve/{id}', 'editArchivesBorrowApprove', 'put',[907,908,909,910]],//暂时没找到使用的地方
    //保管期限
   ['archives/hold/time', 'getIndexHoldTime',[900]],    
    //根据借阅id跟类型获取名称
//    ['archives/file/{borrow_data_id}/{borrow_type}', 'getArchivesName'],
		
];
