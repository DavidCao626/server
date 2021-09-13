<?php
/**
 *  menuId
 *  新建文档 => 7
 *  我的文档 => 8
 */
$routeConfig = [
        // 文档云盘模式
        ['document/cloud', 'getCloudList'],
        // 云盘检查是否可以删除文件夹、文档
        ['document/cloud/check', 'cloudCheckDelete'],
        // 云盘移动文件夹、文档
        ['document/cloud/move', 'cloudMove', 'post'],
        // 云盘删除文件夹、文档
        ['document/cloud/delete', 'cloudDelete', 'delete'],
        // 云盘上传文件
        ['document/cloud/upload', 'cloudUpload', 'post', [7]],
        // 获取云盘下的文件
        ['document/cloud/file', 'getCloudDocument', [8]],
        // 获取文档基本设置
        ['document/base-set', 'getDocumentBaseSet'],
        // 获取文档基本设置
        ['document/view-set', 'getDocumentViewModeSet'],
        // 基本设置
        ['document/base-set', 'documentBaseSet', 'post'],
       // 文档视图模式设置
        ['document/view-set', 'documentViewModeSet', 'post'],
        // 文档数量统计
        ['document/count', 'getDocumentReadCount'],
        // 附件转换，永中在线预览
        ['document/trans', 'transOfficeToHtml'],
        // 删除文档标签
        ['document/tags', 'delDocumentTags', 'delete', [8,196]],
        // 添加文档标签
        ['document/tags', 'addDocumentTags', 'post', [8,196]],
        // 文档设置-基本设置-获取是否有文档创建人权限
        ['document/creator-purview', 'getCreatorPurview', [59]],
        // 文档设置-基本设置-设置文档创建人权限
        ['document/creator-purview/{creatorPur}', 'creatorPurviewSet', [59]],
        // 新建文档样式
        ['document/mode', 'addMode', 'post', [59]],
        // 新建日志
        ['document/log', 'addLog', 'post'],
        // 编辑文档样式
        ['document/mode/{modeId}', 'editMode', 'post', [59]],
        // 文档样式列表
        ['document/mode', 'listMode', [59]],
        // 查看文档样式
        ['document/mode/{modeId}', 'showMode', [59]],
        // 删除文档样式
        ['document/mode/{modeId}', 'deleteMode', 'delete', [59]],
        // 恢复默认文档样式
        ['document/mode/{modeId}/recover', 'recoverDefaultMode', [59]],
        // 有查看权限的文件夹
        ['document/show-folder', 'listShowFolder', [8, 59, 196]],
        // 有管理权限的文件夹
        ['document/manage-folder', 'listManageFolder', [8, 59, 196]],
        // 有新建权限的文件夹
        ['document/create-folder', 'listCreateFolder', [7, 8, 59, 196]],
        // 所有文件夹
        ['document/all-folder', 'listAllFolder'],
        // 删除文件夹
        ['document/folder/{folderId}', 'deleteFolder', 'delete', [59]],
        // 复制文件夹
        ['document/folder/{folderId}/copy', 'copyFolder', 'post', [59]],
        // 获取默认文件夹
        ['document/folder/default', 'getDefaultFolder'],
        // 获取文件夹信息
        ['document/folder/{folderId}', 'showFolder', [7, 8, 59]],
        // 获取文件夹信息（无权限）
        ['document/folder-info/{folderId}', 'showFolderInfo', [7, 8, 59]],
        // 批量设置文件夹权限
        ['document/folder/batch-set', 'batchSetPurview', 'post', [59]],
        // 批量删除文件夹
        ['document/folder/{folderIds}/batch', 'batchDeleteFolder', 'delete', [59]],
        // 批量设置模板样式
        ['document/folder/batch/set-tpl', 'batchSetModeAndTemplate', [59]],
        // 文件夹批量转移
        ['document/folder/move/{fromIds}/{toId}/batch', 'batchMoveFolder', [59]],
        // 文件夹基础设置
        ['document/folder/{folderId}/base-set', 'setFolderBaseInfo', 'post', [59]],
        // 文件夹命名
        ['document/folder/{folderId}/name', 'editFolderName', 'post', [8,59]],
        // 设置模板样式
        ['document/folder/{folderId}/show-mode-set', 'setShowMode', 'post', [59]],
        // 文件夹排序
        ['document/folder/{folderIds}/sort', 'sortFolder', [59]],
        // 设置公共模板
        ['document/folder/{folderId}/template-set', 'setTemplate', 'post', [59]],
        // 文件夹转移
        ['document/folder/migrate/{fromId}/{toId}', 'migrateFolder', [59]],
        // 打开文件夹加载子文件夹
        ['document/folder/children/{parentId}/manage', 'getManageChildrenFolder'],
        // 获取新建文档的子文件夹列表
        ['document/folder/children/{parentId}/create', 'getCreateChildrenFolder'],
        // 用于显示选中文件夹的子文件夹列表
        ['document/folder/children/{parentId}/show', 'getShowChildrenFolder'],
        // 所有子文件夹
        ['document/folder/children/{parentId}/all', 'getAllChildrenFolder'],
        // 我的文档，点击共享，获取该文件夹有查看权限的所有成员
        ['document/folder/view-member/{folderId}/{documentId}', 'showAllViewPurviewMember', [8,196]],
        // 设置共享文档下载权限
        ['document/share-download/{shareDownload}', 'shareDownloadSet', [59]],
        // 获取共享文档下载权限
        ['document/share-download', 'getShareDownload', [59]],
        // 最近浏览
        ['document/recent', 'getDocumentRecent', [8,196]],
        // 新建文档
        ['document', 'addDocument', 'post', [7]],
        // 复制文档
        ['document/copy', 'copyDocument', 'post'],
        // 编辑文档
        ['document/{documentId}', 'editDocument', 'post', [8,196]],
        //重命名
        ['document/rename/{documentId}', 'editDocumentName', 'post'],
        // 删除文档
        ['document/{documentId}', 'deleteDocument', 'delete', [8,196]],
        // 文档详情
        ['document/{documentId}', 'showDocument', [8,196]],
        // 通过系统数据关联打开文档，不受查看权限控制
        ['document/{documentId}/relation', 'showDocumentByRelation', [8,196]],
        // 获取文档详情不记录日志
        ['document/{documentId}/nolog', 'showDocumentNoLog', [8,196]],
        // 锁定文档
        ['document/lock/{documentId}/{lockStatus}', 'documentLock', [8,196]],
        // 获取文件锁定状态
        ['document/lock/{documentId}', 'documentLockInfo', [8,196]],
        // 获取文档附件id
        ['document/attachment/{documentId}', 'documentAttachment', [8,196]],
        // 申请解锁
        ['document/unlock/{documentId}/{userId}/apply', 'applyUnlock', [8,196]],
        // 文档列表
        ['document', 'listDocument', [8, 196]],
        // 文档转移
        ['document/migrate/{documentIds}/{folderId}', 'migrateDocument', [8,196]],
        // 分享文档
        ['document/share/{documentId}', 'shareDocument', 'post', [8,196]],
        // 置顶文档
        ['document/top/{documentId}', 'topDocument', [8,196]],
        // 取消置顶
        ['document/cancel-top/{documentId}', 'cancelTopDocument', [8,196]],
        // 回复
        ['document/{documentId}/revert', 'addRevert', 'post', [8,196]],
        // 文档回复列表
        ['document/{documentId}/revert', 'listRevert', [8,196]],
        // 编辑回复
        ['document/{documentId}/revert/{revertId}', 'editRevert', 'put', [8,196]],
        // 删除回复
        ['document/{documentId}/revert/{revertId}', 'deleteRevert', 'delete', [8,196]],
        // 文档日志
        ['document/logs/{documentId}', 'listLogs', [8,196]],
        // 文档查阅日志
        ['document/readers/{documentId}', 'documentReadList', [8,196]],
        // 设置文件夹权限
        ['document/folder/{folderId}/purview-set', 'setPurview', 'post', [59]],
        // 查看文件夹权限
        ['document/folder/{folderId}/purview', 'getPurview', [59]],
        // 批量新建文件夹
        ['document/folder/batch/add', 'batchAddFolder', 'post', [8,59]],
        // 上一篇文档
        ['document/{documentId}/prv', 'getPrvDocument', [8,196]],
        // 下一篇文档
        ['document/{documentId}/next', 'getNextDocument', [8,196]],
        // 文档下载权限
        ['document/has-down-purview/{documentId}', 'hasDownPurview', [8,196]],
        // 回复权限
        ['document/has-reply-purview/{documentId}', 'hasReplyPurview', [8,196]],
        // 文档共享给哪些成员
        ['document/share-member/{documentId}', 'getDocumentShareMember', [8,196]],
        // 获取文件夹父级id
        ['document/parent/{folderId}', 'getParentId', [8,59]],
        // 获取子文件夹id
        ['document/children/{folderId}', 'getChildrenId', [8,59]],
        // 文档树，文件夹下带有文档，供文档选择器使用
        ['document/list/{parentId}', 'getDocumentTree', [8,196]],
        // 关注文档
        ['document/follow/{documentId}', 'followDocument', [8,196]],
        // 取消关注
        ['document/follow/{documentId}', 'cancelFollow', 'PUT', [8,196]],
        // 获取用户某文件夹的权限
        ['document/folder/purview/{folderId}', 'getFolderPurviewByUser'],
        // 获取文档列表初始化参数
        ['document/list/init/{folderId}', 'getDocumentListInit'],
        // 导入样式模板
        ['document/import/style-template', 'importDocumentStyleTemplate', 'POST'],
        // 导出样式模板
        ['document/export/style-template/{modeId}', 'exportDocumentStyleTemplate'],
        // 我的文档筛选
        ['document/mine/filter', 'getMyDocumentFields'],
        // 获取我的文档标签数字
        ['document/mine/filter-count', 'getMyDocumentFieldsCount'],
        // ===========================================================
        //                      wps云文档
        // ==========================================================
        // 获取wps模板列表
        ['document/wps/url/template/list', 'getTemplateList'],
        // 根据文档id获取wps访问地址
        ['document/wps/view', 'getWpsDocumentUrl'],
        // wps创建文档
        ['document/wps/create', 'wpsCreateDocument', 'post', [7]],
];