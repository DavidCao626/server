<?php
$routeConfig = [
    //获取用户选择器用数据
    ['directive/user-relation', 'getUserRelation'],
    //通过用户ID、部门ID、角色ID、公共组ID以及自定义组ID获取用户选择器用数据
    ['directive/user-all-relation', 'getUserRelationById'],
    //根据部门ID获取子部门和所属部门的用户
    ['directive/organization-members/{deptId}', 'getOrganizationMembers', 'post'],
    ['directive/groups/user-id', 'getUserIdByGroup', 'post']
];