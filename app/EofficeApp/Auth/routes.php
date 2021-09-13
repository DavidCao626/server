<?php
$routeConfig = [
    ['auth/theme-images/get', 'getLoginThemeImages'],
    ['auth/theme-image/upload', 'uploadThemeImage', 'post'],
    ['auth/logo/set', 'setLogo', 'post'],
    ['auth/form-left-bg/set', 'setFormLeftBg', 'post'],
    ['auth/element-image/set', 'setElementImage', 'post'],
    ['auth/theme-image/del', 'deleteThemeImage', 'delete'],
    ['auth/mobile-empower/{userId}', 'checkMobileEmpowerAndWapAllow'],
    ['auth/login-info/refresh', 'refreshLoginInfo'],
    ['auth/app-login-info', 'appLoginInfo'],
    ['auth/login/theme', 'setLoginThemeAttribute', 'post'],
    ['auth/default/theme', 'setSystemDefaultTheme', 'post'],
//    ['auth/refresh', 'refreshToken'],
];