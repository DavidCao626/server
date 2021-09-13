<?php

namespace App\Http\Middleware;

use Closure;

// 解密路由中的参数
class DecodeParams
{
    public function handle($request, Closure $next)
    {
    	if (!empty($request->input('sql'))) {
			$sqlString = decrypt_params($request->input('sql'), true, false, false, true);
			$request['sql'] = $sqlString;
		}
		// if (!empty($request->input('eoffice_encrypt_data'))) {
		// 	$params = decrypt_params($request->input('eoffice_encrypt_data'), true);
		// 	if (!empty($params)) {
		// 		$params = json_decode($params, true);
		// 		if (!empty($params)) {
		// 			$request->merge($params);
		// 		}
		// 	}
		// 	unset($request['eoffice_encrypt_data']);
		// }
        return $next($request);
    }
}