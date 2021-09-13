<?php
$routeConfig = [
    // 这四个没找到哪里用
    ['department/tree', 'tree'],
    ['department/all-tree', 'allTree'],
    // 客户端部门用户
    ['department/get-dept-user', 'getDeptUserArr'],
    ['department/except-tree/{deptId}', 'exceptTree'],
    ['department/family/{deptId}', 'family'],
    // 组织首页获取部门数量
    ['department/total', 'getTotalDepartment'],



    // 获取下级部门(部门管理和部门选择器)
    ['department/children/{deptId}', 'children'],
    // 获取部门列表(部门选择器)
    ['department', 'listDept'],
    ['department/dept-tree-search', 'deptTreeSearch'],
    // 获取部门详情(绩效和用户都用到)
    ['department/{deptId}', 'getDeptDetail'],
    // 添加部门
    ['department', 'addDepartment', 'post', [240]],
    // 批量添加部门
    ['department/batch', 'addMultipleDepartment', 'post', [240]],
    // 编辑部门
    ['department/{deptId}', 'editDepartment', 'post', [240]],
    // 删除部门
    ['department/{deptId}', 'deleteDepartment', 'delete', [240]],
    // 根据部门ID获取它的根部门信息(表单解析有用到)
    ['department/get-root-dept-info/{deptId}', 'getRootDeptInfoByDeptId'],
    // 根据部门ID获取它的上级部门信息(表单解析有用到)
    ['department/get-parent-dept-info/{deptId}', 'getParentDeptInfoByDeptId'],
    ['department/permission/delete', 'clearDeptPermission', 'delete'],
    // 获取部门权限
    ['department/permission/{deptId}', 'getDeptPermission'],
    // 保存部门权限
    ['department/permission/{deptId}', 'setDeptPermission', 'post'],
];