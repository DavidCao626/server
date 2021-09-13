<?php

namespace App\EofficeApp\Api\Services;

use App\EofficeApp\Base\BaseService;
use DB;
use Cache;
use GuzzleHttp\Client;
use App\EofficeApp\System\ExternalDatabase\Services\ExternalDatabaseService;
use Illuminate\Support\Facades\Log;

/**
 * Api Service类:提供Api模块的相关服务
 *
 * @author qishaobo
 *
 * @since  2016-08-23 创建
 */
class ApiService extends BaseService
{
    private $externalDatabaseService;

    public function __construct(
        ExternalDatabaseService $externalDatabaseService
    ) {
        $this->externalDatabaseService = $externalDatabaseService;
    }
    /**
     * 创建$dispatcher
     *
     * @author 齐少博
     *
     * @since  2016-08-23 创建
     *
     * @return object
     */
    public function createApp() {
        $app = app();
        $routeBase = config("app.route_base");
        $moduleDir = dirname(dirname(__DIR__));

        $modules = getDirRoutes($moduleDir);

        $app->group(['namespace' => 'App\EofficeApp', 'middleware'=>'authCheck', 'prefix'=> $routeBase.'/api' ], function ($app) use ($moduleDir, $modules) {
            addRoutes($app, $moduleDir, $modules);
        });

        return $app;
    }

    /**
     * 测试sql
     *
     * @param string $sql sql语句
     * @param int $handle 执行或测试
     *
     * @author 齐少博
     *
     * @since  2016-09-13 创建
     *
     * @return json 测试结果
     */
    function testSql($param, $handle) {
        if(!isset($param['sql']) || empty($param['sql'])) {
            return ['code' => ['0x000014','common']];
        }
        $system = true;
        if (isset($param['database_id']) && !empty($param['database_id'])) {
            $system = false;
        }/* else if ($param['database_id'] == 0) {
            $system = false;
        }*/
        if(!$system) {
            //获取外部数据库配置
            $databaseInfo = $this->externalDatabaseService->getExternalDatabase($param['database_id']);

            if (!$databaseInfo || empty($databaseInfo['driver'])) {
                return ['code' => ['external_database_not_exist', 'system']];
            }

            $method = $databaseInfo['driver'] . 'Test';

            $externalDatabaseInfo = $this->$method($databaseInfo);

            config(['database.connections.external_database' => $externalDatabaseInfo]);
        }
        if ($this->injectCheck($param['sql'])) {
            return ['code' => ['0x000015','common']];
        }
        if ($handle == 'execute') {
            // 过滤slq语句 前端解析会出现/'类似数据
            $param['sql'] = preg_replace('/\\\\"/','"',$param['sql']);
            $param['sql'] = preg_replace("/\\\\'/","'",$param['sql']);
            try {
                if($system) {
                    $query = DB::connection('mysql');
                }else{
                    $query = DB::connection('external_database');
                }

                $default = [
                    'page'  => 0,
                    'limit' => 10,
                ];

                $param = array_merge($default, $param);
                $start = $param['page'] * $param['limit'] - $param['limit'];
                $query = $query->select($param['sql']);

                $total = count($query);
                if($param['page'] == 0) {
                    $list = $query;
                }else{
                    $list = array_slice($query, $start, $param['limit']);
                }
                return ['list' => $list, 'total' => $total];
            } catch (\Exception $e) {
                return ['list' => [], 'total' => 0];
            } catch (\Error $error) {
                return ['list' => [], 'total' => 0];
            }

        } else if ($handle == 'examine') {
            //解析条件
            //找到所有标签
            $rule = "/(?<=<\/)[^>]+/";
            preg_match_all($rule,$param['sql'],$matches);
            $sql_param = [];

            if(isset($matches[0]) && !empty($matches[0])) {
                $matches[0] = array_unique($matches[0]);
                foreach ($matches[0] as $value) {
                    $sql = $param['sql'];
                    //保留当前标签 去掉其他标签内容
                    $sql = preg_replace("/<".$value.">/",' ',$sql);
                    $sql = preg_replace("/<\/".$value.">/",' ',$sql);
                    $sql = preg_replace("/<(\w+)>(.|\n)*<\/(\w+)>/",'',$sql);
                    $sql_param[] = $sql;
                }
            }
            try {
                if($system) {
                    $query = DB::connection('mysql');
                    if(!empty($sql_param)) {
                        foreach ($sql_param as  $value) {
                            $query->select('EXPLAIN '.$value);
                        }
                    }else{
                        $query->select('EXPLAIN '.$param['sql']);
                    }
                    return 'success';
                }else{
                    $query = DB::connection('external_database');
                    $type = config('database.connections.external_database')['driver'];
                    if(!empty($sql_param)) {
                        foreach ($sql_param as  $value) {
                            if($type == 'mysql') {
                                $query->select('EXPLAIN '.$value);
                            }else{
                                $query->select($value);
                            }
                        }
                    }else{
                        if($type == 'mysql') {
                            $query->select('EXPLAIN '.$param['sql']);
                        }else{
                            $query->select($param['sql']);
                        }
                    }
                    return 'success';
                }

            } catch (\Exception $e) {
                if(json_encode($e->getMessage()) === false){
                    $error = iconv('gbk','utf-8',$e->getMessage());
                }else{
                    $error = $e->getMessage();
                }
                return trans('system.connect_error').$error;
            } catch (\Error $error) {
                return 'error';
            }
        }

    }

    /**
     * 获取sql字段信息
     *
     * @param string $sql sql语句
     *
     * @author 齐少博
     *
     * @since  2016-11-18 创建
     *
     * @return json 测试结果
     */
    function getSqlFields($sql) {
        if(empty($sql)) {
            return ['code' => ['0x000014','common']];
        }

        if ($this->injectCheck($sql)) {
            return ['code' => ['0x000015','common']];
        }

        if (strpos($sql, 'from') === false || strpos($sql, 'select') === false && strpos($sql, 'SELECT') === false) {
            return ['code' => ['0x000016','common']];
        }

        $sqls = explode('from', $sql);
        $fields = explode(',', str_replace(['select','SELECT',''],['','',''], $sqls[0]));
        $fields = array_map(function($var){
            return trim($var);
        }, $fields);

        $sqls = explode(' ', trim($sqls[1]));
        $table = $sqls[0];

        try {
            $data = [];
            $columns = DB::select("SHOW FULL COLUMNS FROM customer");

            foreach ($columns as $v) {
                $field = (array)$v;
                if (in_array($field['Field'], $fields) || $fields[0] == '*') {
                    $data[] = [
                        "field" => $field['Field'],
                        "comment" => $field['Comment'],
                    ];
                }
            }

            return $data;
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * sql过滤
     *
     * @author 齐少博
     *
     * @since  2016-09-13 创建
     *
     * @return bool
     */
    function injectCheck($sql)
    {
        return preg_match('/insert\s+|update\s+|delete\s+|union|into|load_file|outfile/i', $sql);
    }

    /**
     * 测试url是否可以访问
     *
     * @param string $url
     *
     * @author 齐少博
     *
     * @since  2016-12-05 创建
     *
     * @return json 测试结果
     */
    function testUrl($param) {
        $param['handle'] = 'test';
        return $this->guzzleHttp($param);
    }

    /**
     * 获取url返回值
     *
     * @param string $url
     *
     * @author 齐少博
     *
     * @since  2016-12-05 创建
     *
     * @return json 测试结果
     */
    function getUrlData($param, $userInfo = []) {
        $param['handle'] = 'data';
        return $this->guzzleHttp($param, $userInfo);
    }

    /**
     * 处理url请求
     *
     * @param string $url
     *
     * @author 齐少博
     *
     * @since  2016-12-05 创建
     *
     * @return json 测试结果
     */
    function guzzleHttp($param, $userInfo = []) {
        $param['user_id'] = $userInfo['user_id'] ?? '';
        if (!isset($param['url'])) {
            return ['code' => ['0x000019','common']];
        }
        $check = check_white_list($param['url']);
        if (!$check) {
            // 去除url参数再验证
            if (strpos($param['url'], '?') !== false) {
                $pos = strpos($param['url'], '?');
                $tepm_url = substr($param['url'], 0, $pos);
                $check = check_white_list($tepm_url);
                if (!$check) {
                    return ['code' => ['0x000025','common']];
                }
            }elseif(strpos($param['url'], '#') !== false){
                $pos = strpos($param['url'], '#');
                $tepm_url = substr($param['url'], 0, $pos);
                $check = check_white_list($tepm_url);
                if (!$check) {
                    return ['code' => ['0x000025','common']];
                }
            }else {
                return ['code' => ['0x000025','common']];
            }
        }
        try {
            $method = 'POST';
            if (isset($param['method'])) {
                $method = strtoupper($param['method']);
                unset($param['method']);
            }
            $handle = 'test';
            if (isset($param['handle'])) {
                $handle = $param['handle'];
                unset($param['handle']);
            }

            if ($method == 'POST') {
                $guzzleResponse = $this->guzzleHttpByPost($param);
            } else if ($method == 'GET') {
                $guzzleResponse = $this->guzzleHttpByGet($param);
            } else {
                return 0;
            }

            $status = $guzzleResponse->getStatusCode();
            $body = $guzzleResponse->getBody();

            if ($status != '200' && empty($body->getContents())) {
                return 0;
            }

            if ($handle == 'test') {
                return 1;
            }

            return ['content' => $body->getContents()];

        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return 0;
        }
    }

    /**
     * [guzzleHttpByGet 通过GET请求将前端传递来的参数和文件地址中的参数拼接到一起]
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function guzzleHttpByGet($param)
    {
        $url = $param['url'];
        $url = parse_relative_path_url($param['url']);
        $urlParse = parse_url($url);
        if(isset($urlParse["query"]) && $urlParse["query"]) {
            // 有参数
            $url .= "&";
        } else {
            $url .= "?";
        }
        $otherParamsString = "";
        if(count($param)) {
            foreach ($param as $key => $value) {
                // 排除 url 参数、handle 参数
                if($key != "url" && $key != "handle" && $key != "method") {
                    if(is_array($value)){
                        $value = json_encode($value);
                    }
                    $otherParamsString .= $key."=".$value."&";
                }
            }
        }
        $otherParamsString = rtrim($otherParamsString,"&");
        $guzzleParam = [
            'allow_redirects' => true,
            'timeout' => config("app.url_request_time")
        ];
        // 父级控件的值，包含在 $otherParamsString 里面了
        // $parent = isset($param['parent']) ? $param['parent'] : "";
        $url .= $otherParamsString;
        return (new Client())->request('GET', $url, $guzzleParam);
    }

    /**
     * [guzzleHttpByPost 通过POST请求将前端传递来的参数和文件地址中的参数发送]
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function guzzleHttpByPost($param)
    {
        $url = $param['url'];
        $url = parse_relative_path_url($param['url']);
        unset($param['url']);
        // 解析文件地址URL地址中的参数
        $urlParse = parse_url($url);
        if(isset($urlParse["query"]) && $urlParse["query"]) {
            parse_str($urlParse["query"], $urlParams);
            if (!empty($urlParams)) {
                $param = array_merge($param, $urlParams); // 与外部请求过来的参数合并
            }
        }
        $guzzleParam = [
            'form_params' => $param,
            'allow_redirects' => true,
            'timeout' => config("app.url_request_time")
        ];
        return (new Client())->request('POST', $url, $guzzleParam);
    }

    /**
     * 测试msyql数据库
     *
     * @param  array $param 查询条件
     *
     * @return array 查询条件
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function mysqlTest($param)
    {
        return [
            'driver'   => 'mysql',
            'host'     => $param['host'],
            'port'     => $param['port'],
            'username' => $param['username'],
            'password' => isset($param['password']) ? $param['password'] : '',
            'database' => $param['database'],
            'charset'  => $param['charset'],
        ];
    }

    /**
     * 测试sqlserver数据库
     *
     * @param  array $param 查询条件
     *
     * @return array 查询条件
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function sqlsrvTest($param)
    {
        return [
            'driver'   => 'sqlsrv',
            'host'     => $param['host'],
            'port'     => $param['port'],
            'username' => $param['username'],
            'password' => isset($param['password']) ? $param['password'] : '',
            'database' => $param['database'],
            'charset'           => $param['charset'],
        ];
    }

    /**
     * 测试oracle数据库
     *
     * @param  array $param 查询条件
     *
     * @return array 查询条件
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function oracleTest($param)
    {
        return [
            'driver'            => 'oracle',
            'host'              => $param['host'],
            'port'     => $param['port'],
            'username' => $param['username'],
            'password' => isset($param['password']) ? $param['password'] : '',
            'database' => $param['database'],
            'charset'           => $param['charset'],
        ];
    }
}
