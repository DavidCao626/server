<?php
namespace App\EofficeApp\LogCenter\Traits;
use Cache;
use Illuminate\Contracts\Cache\LockTimeoutException;
/**
 * Description of LogTrait
 *
 * @author lizhijun
 */
trait LogTrait 
{
    private $logTablePrefix = 'eo_log_';
    private $dataChangePrefix = 'eo_log_data_change_';
    private $parserPrefix = 'ContentParser';
    public $isFastIp = false;
    public function makeChangeTable($moduleKey)
    {
        return $this->dataChangePrefix . $moduleKey;
    }
    public function makeLogTable($moduleKey)
    {
        return $this->logTablePrefix . $moduleKey;
    }
    public function makeChange($relationTable)
    {
        static $changes = [];
        if (isset($changes[$relationTable])) {
            return $changes[$relationTable];
        }
        $name = $this->toCamelCase($relationTable) . 'Change';
        $class = 'App\EofficeApp\LogCenter\Changes\\' . $name;

        if (class_exists($class)) {
            $changes[$relationTable] = app('App\EofficeApp\LogCenter\Changes\\' . $name);
        } else {
            $changes[$relationTable] = null;
        }
        return $changes[$relationTable];
    }

    public function makeParser($moduleKey)
    {

        $class = 'App\EofficeApp\LogCenter\Parser\\' . ucfirst($moduleKey) . $this->parserPrefix;
        if (class_exists($class)) {
            return app($class);
        }
        return false;
    }
    /**
     * 转驼峰
     *
     * @param string $str
     * @param string $delimter
     *
     * @return string
     */
    private function toCamelCase($str, $delimter = '_')
    {
        $array = explode($delimter, $str);

        $name = array_reduce($array, function($carry, $item) {
            return $carry . ucfirst($item);
        });

        return $name;
    }
    private function getIpInfo($ip)
    {
        static $i = 1;
        try {
            $url = config('eoffice.getAddressFromIpUrl') . '?ip=' . $ip;
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'timeout' => 1
                ]
            ];
            $result = @file_get_contents($url, false, stream_context_create($opts));
            $i ++;
            if ($i % 50 === 0) {
                sleep(1);
            }
            if ($result) {
                $result = $this->transEncoding($result, 'UTF-8');
                $result = str_replace(['if(window.IPCallBack) {IPCallBack(', ');}'], ['', ''], $result);
                $result = trim($result, ' ');
                $result = json_decode($result, true);
                return $result;
            }
        } catch(\Exception $e) {
            
        }
        return false;
    }
    public function ipArea($ip = '', $type = null)
    {
        static $staticIps = [];
        if (empty($ip)) {
            return false;
        }

        if (isset($staticIps[$ip])) {
            $ipInfo = $staticIps[$ip];
        } else {
            $lockKey = $this->isFastIp ? 'logCenter:getFastIpArea' : 'logCenter:getIpArea';
            $lock = Cache::lock($lockKey, 10);
            try {
                $lock->block(5);
                $ips = $this->getIpsFromFile();
                if (isset($ips[$ip])) {
                    $ipInfo = $ips[$ip];
                } else {
                    $ipInfo = $this->getIpInfo($ip);
                    $ips[$ip] = $ipInfo;
                    $this->saveIpAsFile($ips);
                }
                $staticIps = array_merge($staticIps, $ips);
                optional($lock)->release();
            } catch (LockTimeoutException $e) {
                optional($lock)->release();
                $ipInfo = null;
            } finally {
                optional($lock)->release();
            }
        }
        return $type ? ($ipInfo[$type] ?? false) : $ipInfo;
    }
    private function getIpsFromFile()
    {
        $path = $this->getIpCacheFile();
        if (file_exists($path)){
            try{
                return json_decode(file_get_contents($path), true);
            } catch(\Exception $e) {
                return [];
            }
        }
        return [];
    }
    private function saveIpAsFile($ips)
    {
        $path = $this->getIpCacheFile();
        
        file_put_contents($path, json_encode($ips));
    }
    private function getIpCacheFile()
    {
        $path = resource_path('ip/');
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        if ($this->isFastIp) {
            return $path . 'fast_ip.json';
        }
        return $path . 'ip.json';
    }
    /**
     * 字符串编码转换
     * @param  [string] $string [要转换的内容]
     * @param  [string] $target [要转换的格式]
     * @return [string]         [description]
     */
    private function transEncoding($string, $target)
    {
        $encoding = mb_detect_encoding($string, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);

        return iconv($encoding, $target, $string);
    }
    public function getLogCategory($moduleKey, $categoryKey)
    {
        static $staticCategorys = [];

        $key = $moduleKey . '.' . $categoryKey;
        if (!isset($staticCategorys[$key])) {
            $option = $this->getLogOption($moduleKey);
            $category = $option['category'][$categoryKey] ?? '';
            $staticCategorys[$key] = $category ? mulit_trans_dynamic('eo_log_module_config.options.category.' . $category) : '';
        }

        return $staticCategorys[$key];
    }
    public function getLogOperate($moduleKey, $categoryKey, $opereateKey)
    {
        static $staticOperates = [];

        $key = $moduleKey . '.' . $categoryKey . '.' . $opereateKey;
        if (!isset($staticOperates[$key])) {
            $option = $this->getLogOption($moduleKey);
            $operate = $option['operate'][$categoryKey][$opereateKey] ?? '';
            $staticOperates[$key] = $operate ? mulit_trans_dynamic('eo_log_module_config.options.operate.' . $operate) : '';
        }

        return $staticOperates[$key];
    }
    private function getLogOption($moduleKey)
    {
        static $options = [];
        
        if (!isset($options[$moduleKey])) {
            $config = app($this->logModuleConfigRepository)->findByModuleKey($moduleKey);
            $options[$moduleKey] = json_decode($config->options,true);
        }
        
        return $options[$moduleKey];
    }

    /**
     * ES服务是否运行
     *
     * @param bool
     */
    public function isElasticSearchRun()
    {
        $status = app('App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService')->getPlugInStatus();
        if($status == 1){
            return true;
        }
        return false;
    }

    /**
     * 二维数组排序
     * @param $arrays
     * @param $sort_key
     * @param int $sort_order
     * @param int $sort_type
     * @return bool
     */
    public function arraySort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){
        if(is_array($arrays)){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $key_arrays[] = $array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
        return $arrays;
    }

//    public function transformModule($result){ //todo 处理文档转换能不能写在trait里面
//        $logModules = LogScheme::getAllLogModules();
//        foreach ($result as $k =>&$v){
//            foreach ($logModules as $key => $val){
//                if($val['module_key'] === $v['module']){
//                    $v['module_name'] = $val['module_name'];
//                    break;
//                }
//            }
//        }
//        return $result;
//    }

    public function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

}
