<?php

namespace App\Utils;

use App\Utils\DocParser;

class CreateApiDoc
{
    private $apiVersion;
    private $docParser;
    private $baseNamespace = '\App\EofficeApp\\';
    private $eofficeAppDir = './app/EofficeApp';
    private $twoLevelFolder;
    private $ctrl = "\r\n";
    private $outFile;
    private $generalModules;
    private $errorCodeDir;
    private $apiDocDir;
    private $apiDocDateDir = '/api_doc_data/';
    private $apiDocFileDir = '/api_doc_file/';
    private $apiDocTemplate;
    private $newApiDocDataDir;
    private $newApiDocFileDir;
    private $apiDocVersion;
    private $modulesName;

    public function __construct()
    {

        $this->docParser = new DocParser();
        $this->apiVersion = $this->apiConfig('version', '1.0.0');
        $this->twoLevelFolder = $this->apiConfig('second_leave_modules', []);
        $this->outDir = $this->apiConfig('out_dir', './public/src-api-doc/');
        $this->modulesName = $this->apiConfig('modules_name', []);
        $this->outFile = $this->outDir . 'doc.php';
        $this->apiDir = $this->apiConfig('api_dir', './public/api-doc/');
        $this->apiDocDir = $this->apiConfig('api_doc_dir', './public/api_doc');
        $this->apiDocTemplate = $this->apiConfig('api_doc_template', './public/api_doc/template');
        $this->errorCodeDir = rtrim($this->apiConfig('error_code_dir', './resources/lang/zh-cn'), '/');
    }

    public function run($version)
    {
        if (!is_numeric($version)) {
            echo "版本号必须是数字";
            die;
        }
        $this->apiDocVersion = '10.0.' . $version;
        $this->newApiDocDataDir = $this->apiDocDir . $this->apiDocDateDir;
        $this->newApiDocFileDir = $this->apiDocDir . $this->apiDocFileDir;
        if (!is_dir($this->newApiDocDataDir)) {
            if (mkdir($this->newApiDocDataDir, 0777, true)) {
                mkdir($this->newApiDocFileDir, 0777, true);
            }
        }
//        $dirName = $this->checkApiDoc();
//        dd($dirName);
        $this->outFile = $this->newApiDocDataDir . $version . 'doc.php';
        $commonData = $this->newApiDocDataDir . $version . 'common.php';
        $sourceCommonData = $this->outDir . 'common.php';
        if (is_file($sourceCommonData)) {
            if (!copy($sourceCommonData, $commonData)) {
                echo "api-doc升级错误";
                die;
            } else {
                $replace = '@apiVersion 10.0.' . $version;
                file_put_contents($commonData, str_replace('@apiVersion 10.0.20200318', $replace, file_get_contents($commonData)));
            }
        }
        file_put_contents($this->outFile, '<?php' . $this->ctrl);

        $this->generalModules = $this->apiConfig('modules', []);

        $modules = $this->getModules(); //获取所有模块路由
        $this->handleEofficeAppApiDoc($modules);
        $this->handleErrorCodeDoc();
        exec('..' . DIRECTORY_SEPARATOR . "node_modules" . DIRECTORY_SEPARATOR . ".bin" . DIRECTORY_SEPARATOR . "apidoc -t " . $this->apiDocTemplate . " -i " . $this->newApiDocDataDir . ' -o ' . $this->newApiDocFileDir);
        //exec('..'.DIRECTORY_SEPARATOR."node_modules".DIRECTORY_SEPARATOR.".bin".DIRECTORY_SEPARATOR."apidoc -t api_doc/template -i " . $this->newApiDocDataDir . ' -o ' . $this->newApiDocFileDir);
        //../node_modules/.bin/apidoc -i ./public/dome/dome -o ./public/dome/dome1
        //D:\dsyAllProgram\e-office10\www\eoffice10_dev\server\public\dome
        //apidoc -i D:\dsyAllProgram\e-office10\www\eoffice10_dev\server\public\dome\dome -o D:\dsyAllProgram\e-office10\www\eoffice10_dev\server\public\dome\dome1
        //exec('..'.DIRECTORY_SEPARATOR."node_modules".DIRECTORY_SEPARATOR.".bin".DIRECTORY_SEPARATOR."apidoc -i " . $this->outDir . ' -o ' . $this->apiDir);
    }
//    private function checkApiDoc(){
//        $apiDocDir = './public/api_doc';
//        $fileList = [];
//        if (is_dir($apiDocDir)) {
//            $handler = opendir($apiDocDir);
//            while (($filename = readdir($handler)) !== false) {
//                if ($filename != "." && $filename != "..") {
//                    $check = $apiDocDir.'/'.$filename;
//                    if(is_dir($check)){
//                        $fileList[] = $filename;
//                    }
//                }
//            }
//            closedir($handler);
//            return $fileList;
//        }
//    }
    private function apiConfig($key, $default = '')
    {
        static $apiConfig = [];

        if (empty($apiConfig)) {
            $apiConfig = require './config/apidoc.php';
        }

        return (isset($apiConfig[$key]) && !empty($apiConfig[$key])) ? $apiConfig[$key] : $default;
    }

    private function handleErrorCodeDoc()
    {
        $string = '/**' . $this->ctrl;
        $string .= '* @apiGroup 异常码' . $this->ctrl;
        $string .= '* @apiName errorCode' . $this->ctrl;
        $string .= '* @apiVersion ' . $this->apiDocVersion . $this->ctrl;
        $string .= '* @api {any} {api_url} 异常码速查表' . $this->ctrl;
        $string .= '* @apiDescription ' . $this->ctrl;
        $string .= '* 以下示例列出了所有的异常码和对应的异常信息' . $this->ctrl;
        $string .= '* @apiErrorExample 异常码列表' . $this->ctrl;
        $codes = [];
        $handler = opendir($this->errorCodeDir);
        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {
                if (file_exists($this->errorCodeDir . DIRECTORY_SEPARATOR . $filename)) {
                    $errorCodes = require $this->errorCodeDir . DIRECTORY_SEPARATOR . $filename;
                    foreach ($errorCodes as $key => $value) {
                        if (strpos($key, '0x') === 0) {
                            $codes[$key] = $value;
                        }
                    }
                }
            }
        }
        if (!empty($codes)) {
            $string .= '* ' . json_encode($codes) . $this->ctrl;
        }
        $string .= '*/';
        file_put_contents($this->outFile, $string, FILE_APPEND);
    }

    private function handleEofficeAppApiDoc($modules)
    {
        if (is_array($this->generalModules)) {
            //array(12) {
            //  [0]=>
            //  string(4) "Auth"
            //  [1]=>
            //  string(4) "Flow"
            $generalModules = array_keys($this->generalModules);
        } else {
            $generalModules = '*';
        }
        $systemModules = $this->apiConfig('system_module', []);
        if ($generalModules == '*') {
            foreach ($modules[0] as $module => $controller) {
                $routes = $modules[1][$module];
                $isSystemModule = in_array($module, $systemModules) ? true : false;
                $this->generateApiDoc($module, $controller, $routes, $isSystemModule, true);
            }
        } else {
            foreach ($modules[0] as $module => $controller) {
                if (!in_array($module, $generalModules)) {
                    continue;
                }
                $isSystemModule = in_array($module, $systemModules) ? true : false;
                $routes = $modules[1][$module];
                $this->generateApiDoc($module, $controller, $routes, $isSystemModule, false);
            }
        }

    }

    private function generateApiDoc($module, $controller, $routes, $isSystemModule = false, $isAll = true)
    {
        $reflection = new \ReflectionClass($controller);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        $generalMethods = $isAll ? '*' : ($this->generalModules[$module] ?? []);

        if (!empty($methods)) {
            if ($generalMethods == '*') {
                foreach ($methods as $key => $method) {
                    $methodName = $method->getName();
                    $this->generalApiDocTerminal($method, $controller, $methodName, $this->handleRoutes($routes, $isSystemModule), $module);
                }
            } else {
                foreach ($methods as $key => $method) {
                    $methodName = $method->getName();
                    if (!in_array($methodName, $generalMethods)) {
                        continue;
                    }
                    $this->generalApiDocTerminal($method, $controller, $methodName, $this->handleRoutes($routes, $isSystemModule), $module);
                }
            }
        }
    }

    private function generalApiDocTerminal($method, $controller, $methodName, $routes, $module)
    {
        if ('\\' . $method->class == $controller && $methodName != '__construct' && $methodName != '__call') {
            $methodDocString = $method->getDocComment();
            if (isset($routes[$methodName])) { // login不在模块路由里面，所以没有
                $docs = $this->dealMethodDoc($methodDocString, $module, $methodName, $routes[$methodName]);

                return file_put_contents($this->outFile, $docs, FILE_APPEND | LOCK_EX);
            }
        }
    }

    private function dealMethodDoc($methodDoc, $module, $methodName, $route)
    {

        $string = '/**' . $this->ctrl;
        if (isset( $this->modulesName[$module])){
            $string .= '* @apiGroup ' . $this->modulesName[$module] . $this->ctrl;
        }else{
            echo '配置没有写完整：'.$module.'未在apidoc.php文件中配置modules_name参数';
            $string .= '* @apiGroup ' . $module . $this->ctrl;
        }
       // $string .= '* @apiGroup ' . $module . $this->ctrl;
        $string .= '* @apiVersion ' . $this->apiDocVersion . $this->ctrl;
        $string .= '* @apiName ' . $methodName . $this->ctrl;
        if (strpos($methodDoc, '@apiTitle', 0)) { //此处自定义标签 @apiTitle
            $apiTitleStart = strpos($methodDoc, '@apiTitle', 0);
            $apiTitleEnd = strpos($methodDoc, '*', strpos($methodDoc, '@apiTitle', 0));
            $length = $apiTitleEnd - $apiTitleStart;
            $apiTitle = trim(substr($methodDoc, $apiTitleStart + 9, $length - 9));
            if (empty($apiTitle)) {
                $string .= '* @api {' . $route[1] . '} ' . $route[0] . ' ' . $methodName . $this->ctrl;
            } else {
                $string .= '* @api {' . $route[1] . '} ' . $route[0] . ' ' . $apiTitle . $this->ctrl;
            }
        } else {
            $string .= '* @api {' . $route[1] . '} ' . $route[0] . ' ' . $methodName . $this->ctrl;
        }

        $methodDoc = str_replace('/**', '* @apiDescription', $methodDoc);
        $methodDoc = str_replace('@param', '@apiParam', $methodDoc);
        $methodDoc = str_replace('@paramExample', '@apiParamExample', $methodDoc);
        $methodDoc = str_replace('@success', '@apiSuccess', $methodDoc);
        $methodDoc = str_replace('@successExample', '* @apiSuccessExample', $methodDoc);
        $methodDoc = str_replace('@error', '* @apiError', $methodDoc);
        $methodDoc = str_replace('@errorExample', '* @apiErrorExample', $methodDoc);
        //dd($string . $methodDoc. $this->ctrl);
        return $string . $methodDoc . $this->ctrl;
    }

    private function handleRoutes($routes, $isSystemModule = false)
    {
        $handleRoutes = [];

        foreach ($routes as $route) {
            if (isset($route[2])) {
                $method = is_array($route[2]) ? 'get' : strtolower($route[2]);
            } else {
                $method = 'get';
            }
            $prefix = $isSystemModule ? 'system/' : '';
            $handleRoutes[$route[1]] = ['api/' . $prefix . $route[0], $method];
        }

        return $handleRoutes;
    }

    /**
     * 获取系统所有模块
     *
     * @return array
     */
    private function getModules()
    {
        $classes = $routes = [];

        $handler = opendir($this->eofficeAppDir);

        while (($moduleName = readdir($handler)) !== false) {
            if ($moduleName != "." && $moduleName != ".." && $moduleName !== 'Base') {
                if (in_array($moduleName, $this->twoLevelFolder)) {
                    $hander2 = opendir($this->eofficeAppDir . DIRECTORY_SEPARATOR . $moduleName);

                    while (($twoLevelModuleName = readdir($hander2)) !== false) {
                        if ($twoLevelModuleName != "." && $twoLevelModuleName != "..") {
                            $this->getRoutes($routes, $moduleName, $twoLevelModuleName);
                            $this->getClass($classes, $moduleName, $twoLevelModuleName);
                        }
                    }
                } else {
                    if (file_exists($this->eofficeAppDir . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'routes.php')) {
                        require $this->eofficeAppDir . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'routes.php';
                    }
                    $this->getRoutes($routes, $moduleName);
                    $this->getClass($classes, $moduleName);
                }
            }
        }

        closedir($handler);

        return [$classes, $routes];
    }

    private function getRoutes(&$routes, $moduleName, $twoLevelModuleName = false)
    {
        $routePath = $this->eofficeAppDir . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . ($twoLevelModuleName ? $twoLevelModuleName . DIRECTORY_SEPARATOR : '') . 'routes.php';
        if (file_exists($routePath)) {
            require $routePath;

            $moduleKey = $twoLevelModuleName ? $twoLevelModuleName : $moduleName;

            $routes[$moduleKey] = $routeConfig;
        }
    }

    /**
     * 获取类名称
     * @param string $modules
     * @param type $moduleName
     * @param type $twoLevelModuleName
     */
    private function getClass(&$modules, $moduleName, $twoLevelModuleName = false)
    {
        $handlerControllerDir = $this->eofficeAppDir . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . ($twoLevelModuleName ? $twoLevelModuleName . DIRECTORY_SEPARATOR : '') . 'Controllers';

        if (file_exists($handlerControllerDir)) {
            $handlerController = opendir($handlerControllerDir);

            while (($controller = readdir($handlerController)) !== false) {
                if (strpos($controller, 'Controller.php')) {
                    $filenameArray = explode('.', $controller);

                    $moduleKey = $twoLevelModuleName ? $twoLevelModuleName : $moduleName;

                    $modules[$moduleKey] = $this->baseNamespace . $moduleName . ($twoLevelModuleName ? '\\' . $twoLevelModuleName : '') . '\\Controllers\\' . $filenameArray[0];
                }
            }
        }
    }
}
