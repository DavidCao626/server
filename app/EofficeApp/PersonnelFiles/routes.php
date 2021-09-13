<?php
    $routeConfig = [
        // 415模块
        // 416: 档案查询,
        // 417: 档案管理,
        // 418: 新增档案,
        // 419: 我的档案
        // 414: 档案权限
    	//获取人事档案列表
        ['personnel-files', 'getPersonnelFilesList', [416]],
        // 检查人事档案用户的有效性--李旭
        // ['personnel-files/check', 'checkPersonnelFiles', 'post'],
        //获取人事档案管理列表
        ['personnel-files/manage', 'getPersonnelFilesManageList', [417]],
        //新建人事文档
        ['personnel-files', 'createPersonnelFile', 'post', [418]],
        // 获取用户性别
        ['personnel-files/sex/{parent_id}', 'getPersonnelFileSex'],
        // 获取所有档案信息,系统数据需要调用
        ['personnel-files/all', 'getPersonnelFileAll', 'post', [415]],
        //根据当前登录用户获取人事档案某条记录的id
        ['personnel-files/one', 'getOnePersonnelFile', 'post', [419]],
        // 权限列表
        ['personnel-files/permissions', 'getPermissionList', [414]],
        // 设置权限
        ['personnel-files/permissions', 'setPermission', 'post', [414]],
        // 获取权限
        ['personnel-files/permissions/{id}', 'getPermission', 'get', [414]],
        // 删除权限
        ['personnel-files/permissions/{id}', 'deletePermission', 'delete', [414]],
        /**
         * 部门相关
         */
        // 获取直接下属部门
        ['personnel-files/query/department/children/{deptId}', 'queryDeptChildren'],
        ['personnel-files/manage/department/children/{deptId}', 'manageDeptChildren'],
        // 获取分权后的部门
        ['personnel-files/query/department', 'getFilteredQueryDepartments'],
        ['personnel-files/manage/department', 'getFilteredManageDepartments'],
        // 选择器获取所有后裔
        ['personnel-files/query/department/descendant', 'getQueryDescendantDepartments'],
        ['personnel-files/manage/department/descendant', 'getManageDescendantDepartments'],
        ['personnel-files/base/set', 'getSecurityOption'],

        ['personnel-files/base/set', 'modifySecurityOption','put'],
        // 编辑人事档案
        ['personnel-files/{personnelFileId}', 'modifyPersonnelFile', 'put', [417]],
        //删除一条数据
        ['personnel-files/{personnelFileId}', 'deletePersonnelFile', 'delete', [417]],
        // 获取某条人事档案
        ['personnel-files/{personnel_file_id}', 'getPersonnelFile', [417, 416]],
        // ['personnel-files/get-personnel-files-tree/{deptId}', 'getOrganizationPersonnelMembers', 'post'],
    ];
