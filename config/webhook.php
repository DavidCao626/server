<?php

return [
    'webhook' => [
        'system' => [
            'dept'  => [
                'add'       => 'DepartmentController@addDepartment',
                'add-batch' => 'DepartmentController@addMultipleDepartment',
                'edit'      => 'DepartmentController@editDepartment',
                'delete'    => 'DepartmentController@deleteDepartment'
            ],
            'user'  => [
                'add'    => 'UserController@userSystemCreate',
                'add-quick-batch' => 'UserController@mutipleUserSystemCreate',
                'add-quick-single' => 'UserController@addDeptUser',
                'edit'   => 'UserController@userSystemEdit',
                'delete' => 'UserController@userSystemDelete',
                'empty-password' => 'UserController@userSystemEmptyPassword',
            ],
            'role'  => [
                'add'       => 'RoleController@createRoles',
                'edit'      => 'RoleController@editRoles',
                'delete'    => 'RoleController@deleteRoles'
            ]
        ],
        'customer' => [
            'info'  => [
                'add'       => 'CustomerController@storeCustomer',
                'edit'      => 'CustomerController@updateCustomer',
                'delete'    => 'CustomerController@deleteCustomer'
            ]
        ],
        'flow' => [
            'turning'  => [
                'arrive'          => 'FlowController@flowTurning',
                'return'          => 'FlowController@flowTurning',
                'submit'          => 'FlowController@flowTurning',
                'othersubmit'     => 'FlowController@flowTurningOther',
                'delete-run-flow' => 'FlowController@deleteFlow'
            ]
        ],
        'notify' => [
            'info'  => [
                'add'       => 'NotifyController@addNotify',
                'edit'      => 'NotifyController@editNotify',
                'delete'    => 'NotifyController@deleteNotify'
            ]
        ],
        'project' => [
            'task'  => [
                'add'       => 'ProjectController@taskAddV2', // Todo v2需要处理
                'add-son'       => 'ProjectController@sonTaskAddV2',
                'edit'      => 'ProjectController@taskEditV2',
            ]
        ],
    ]
];
