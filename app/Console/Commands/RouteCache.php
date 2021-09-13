<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Lumen\Routing\Router;
class RouteCache extends Command
{
    /**
     * 控制台命令名称.
     *
     * @var string
     */
    protected $signature = 'eoffice:route';

    /**
     * 控制台命令描述.
     *
     * @var string
     */
    protected $description = 'Caching e-office 10.0 api route';
    /**
     * 执行控制台命令.
     *
     * @return mixed
     */
    public function handle()
    {
        $router = new Router($this);
        $router->group([], function ($router) {
           $allEofficeModules = $this->getAllModules();
            require __DIR__.'/../../../routes/web.php';
        });
        file_put_contents(__DIR__.'/../../../routes/routes.php', '<?php' . "\r\n" . ' return ' .  var_export($router->getRoutes(), true) . ';', LOCK_EX);
    }
    
    public function getAllModules()
    {
        $allModules = [];
        $eofficeAppDir = __DIR__.'/../../EofficeApp';
        $handler = opendir($eofficeAppDir);
        
        while (($filename = readdir($handler)) !== false) {
            if ($filename == 'System') {
                $eofficeAppSystemDir = $eofficeAppDir . '/System';
                $systemHandler = opendir($eofficeAppSystemDir);
                while (($ststemFilename = readdir($systemHandler)) !== false) {
                    $allModules[] = ['System', $ststemFilename];
                }
            } else {
                $allModules[] = $filename;
            }
        }
        return $allModules;
    }
     /**
    * 注册需要验证权限的路由
    */
    public function addAuthRoutes($router, $moduleDir,$modules) {
       $router->group([
           'namespace'  => 'App\EofficeApp',
           'middleware' => 'authCheck|ModulePermissionsCheck|menuPower',
           'prefix'     => '/api'], function ($router) use ($moduleDir, $modules) {
           register_routes($router, $moduleDir, $modules);
       });
    }
    /**
    * 注册不需要权限验证的路由
    */
    public function addNoAuthRoutes($router, $noTokenApi, $moduleDir, $modules) {
       if (is_array($modules)) {
           $currentRoutes = isset($noTokenApi[$modules[0]]) ? (isset($noTokenApi[$modules[0]][$modules[1]]) ? $noTokenApi[$modules[0]][$modules[1]] : []) : [];
       } else {
           $currentRoutes = isset($noTokenApi[$modules]) ? $noTokenApi[$modules] : [];
       }
       if (!empty($currentRoutes)) {
           $router->group(['namespace' => 'App\EofficeApp', 'prefix' => '/api'], function ($router) use ($currentRoutes, $moduleDir, $modules) {
               register_routes($router, $moduleDir, $modules, $currentRoutes);
           });
       }
    }
}
