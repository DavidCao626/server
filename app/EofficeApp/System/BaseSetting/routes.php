<?php
$routeConfig = [
    ['base-setting/default-avatar', 'setDefaultAvatar', 'post'],
    ['base-setting/default-avatar-type', 'setDefaultAvatarType', 'post'],
    ['base-setting/default-avatar', 'getDefaultAvatarInfo'],
    ['base-setting/common-menu', 'getCommonMenu'],
    ['base-setting/user-common-menu', 'getUserCommonMenu'],
    ['base-setting/common-menu', 'setCommonMenu','post', [110]],
    ['base-setting/common-menu/unity', 'unityCommonMenu','post', [110]],
    ['base-setting/common-module', 'getCommonModule', [110]],
    ['base-setting/common-module', 'setCommonModule','post', [110]],
    ['base-setting/user-type', 'setUserType','post', [110]],
    ['base-setting/user-type', 'getUserType','get', [110]],
];
