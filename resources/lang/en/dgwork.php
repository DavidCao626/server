<?php

return [
    // 官方全局错误码
    'MissingParameter' => 'Request input parameter lost',
    'InvalidAPIKey' => 'Access_key illegal',
    'InvalidAPIKeyID.NotFound' => 'Access_key no found',
    'InvalidAPI.NotFound' => 'Access API service no found',
    'Forbidden' => 'The application has not activated the service access',
    'NoSuchVersion' => 'Service version does not match',
    'SignatureNonceUsed' => 'The same request is repeatedly rejected in a short time',
    'UnsupportedOperation' => 'Request pattern does not match',
    'SignatureDoesNotMatch' => 'The signature result does not meet the gateway service standard',
    'FlowCtrlBanned' => 'Service is disabled after triggering current limit',
    'FlowCtrlHitMaxCount' => 'Service triggers current limit',
    'SPParamMiss' => 'Backend service parameters are missing',
    'spInvokeErr' => 'Backend service failed abnormally',
    'IpWhiteListValidate' => 'Client IP is not in the whitelist of service settings',
    'InvalidTimeStamp' => 'Client timestamp is invalid',
    'OauthInvokeError' => 'Sso Login state verification failed',
    'SecurityValidate' => 'Http Request method does not match',
    'InvalidSPKeyID.NotFound' => 'The back-end application service is not provided or the service is not turned on',
    '100' => 'An error occurred inside the gateway, please refer to the Code field description for details',
    '1006' => 'The current user does not have permission to access the app',
    '1016' => 'Your password has expired, please reset your password and log in with the new password',
    '1019' => 'Invalid parameters (for example, parameters that are not allowed to be empty are empty)',
    '1020' => 'Request expired',
    '1021' => 'Signature verification failed',
    '1200' => 'AppCode illegal',
    '1205' => 'The parameter is empty when ValidateAccessToken is requested',
    '1206' => 'AccessToken invalid',
    '1207' => 'AccessToken expired',
    '1208' => 'Cannot get UserProfile information based on AccessToken',
    '1209' => 'The authorization method to obtain the AccessToken is not authorized in the protected API',
    '1210' => 'The authorization method to obtain AccessToken is lower than that of API protection',
    '1211' => 'Invalid client type',
    '1220' => 'Failed to refresh accessToken, accessToken has been updated',
    '1222' => 'Inaccurate application information, accessToken is not generated by the current application',
    '1230' => 'appCode and token are not the same',
    
    'lack_param'=> 'Missing parameters',
    'request_api_error'=> 'Request api error',
    'Authorization_expires'=> 'Authorization expires',
    'auth_code_empty'=> 'Auth code empty',
    'data_type_error'=> 'Data type error',
    'bind_exist'=> 'User bind exist',
    'data_empty'=> 'Data return empty',
    'user_not_bind'=> 'User not bind',
];