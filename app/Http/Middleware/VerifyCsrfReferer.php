<?php
/**
 * Created by PhpStorm.
 * User: yangxingqiang
 * Date: 2021/1/18
 * Time: 17:27
 */

namespace App\Http\Middleware;

use App\EofficeApp\OpenApi\Services\OpenApiService;
use Closure;
use Illuminate\Http\JsonResponse;

class VerifyCsrfReferer
{
    private $openApiService;

    public function __construct(OpenApiService $openApiService)
    {
        $this->openApiService = $openApiService;
    }

    public function handle($request, Closure $next)
    {
        $REFERER_VERIFY = envOverload('REFERER_VERIFY', 'off');
        if($REFERER_VERIFY == 'on'){
            if(isset($_SERVER["HTTP_REFERER"]) && isset($_SERVER["HTTP_HOST"])) {
                $HTTP_HOST = $_SERVER["HTTP_HOST"];
                $HTTP_REFERER = explode("/",explode("://",$_SERVER["HTTP_REFERER"])[1])[0];
                if((explode(":",$HTTP_REFERER)[0] != 'localhost' && explode(":",$HTTP_REFERER)[0] != '127.0.0.1')
                    || (explode(":",$HTTP_HOST)[0] != 'localhost' && explode(":",$HTTP_HOST)[0] != '127.0.0.1')){
                    if(strncmp($HTTP_REFERER, $HTTP_HOST, strlen($HTTP_HOST))) {
                        return new JsonResponse(error_response('0x000029', 'unifiedMessage'), 200);
                    }
                }
            }else{
                return new JsonResponse(error_response('0x000029', 'unifiedMessage'), 200);
            }
        }
        return $next($request);
    }

}
