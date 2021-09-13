<?php


namespace App\EofficeApp\Elastic\Services;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Utils\WindowsSystemInfo;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder as ESClientBuilder;

class BaseService extends \App\EofficeApp\Base\BaseService
{
    /**
     * @param Client $client
     */
    public $client;

    /**
     * @param string $alias
     */
    public $alias;

    /**
     * @param string $type
     */
    public $type;

    public function __construct()
    {
        parent::__construct();
        // 从配置文件读取 Elasticsearch 服务器列表
        $config = config('elastic.elasticsearch.hosts');
        if (is_string($config)) {
            $config = explode(',', $config);
        }
        $builder = ESClientBuilder::create()->setHosts($config);

        $this->client = $builder->build();
        $this->type = Constant::COMMON_INDEX_TYPE;
    }

    /**
     * 判断对应的索引/文档是否存在
     *
     * @param array $param
     *
     * @return bool
     */
    public function exists($param)
    {
        $exist = false;
        /**
         * $param['index'] 判断索引是否存在, 可为以英文逗号分隔的字符串(去空格)
         * $param['id'] 判断文档是否存在
         * $param['type'] 仅在判断文档是否存在时使用, 未确定类型可传 _all
         */
        if (isset($param['index'])) {
            if (isset($param['id'])) {
                $param['type'] = $param['type'] ? : '_all';

                $exist = $this->client->exists($param);
            } else {
                $exist = $this->client->indices()->exists($param);
            }
        }

        return $exist;
    }

    /**
     * 判断别名是否存在
     *
     * @return bool
     */
    public function existsAlias($params)
    {
        return $this->client->indices()->existsAlias($params);
    }

    /**
     * 获取索引别名
     *
     * @param array $param
     *
     * @return array
     */
    public function getAlias($param)
    {
        /**
         * $param['name'] 索引别名
         * $param['index'] 索引
         */
        $response = $this->client->indices()->getAlias($param);

        array_walk($response, function (&$value, $key){
            $aliases = $value['aliases'];
            $value = key($aliases);
        });
        unset($value);

        return $response;
    }

    /**
     * ES服务是否运行
     *
     * @param bool
     */
    public function isElasticSearchRun()
    {
        return $this->client->ping();
    }

    /**
     * 获取计算机内存
     */
    public function getWindowsSystemMemory()
    {
        $info = new WindowsSystemInfo();
        $memory = $info->getMemoryUsage();

        return $memory;
    }

    /**
     * 获取项目基本目录
     *
     * @return string
     */
    protected function getBasePathInfo()
    {
        // 安装路径为硬盘最外层
        $serverPath = base_path();
        $basePath = explode('www', $serverPath);
        $basePathInfo = $basePath[0];

        return $basePathInfo;
    }

    /**
     * 获取es目录
     *
     * @return  array
     */
    protected function getEsBasePathInfo()
    {
        $basePathInfo = $this->getBasePathInfo();
        $pathInfo = [];
        $pathInfo['basePath'] = $basePathInfo;
        $pathInfo['esPath'] = $basePathInfo . 'elastic/elasticsearch-5.6.0/';

        return $pathInfo;
    }

    /**
     * 获取项目ES日志目录
     *
     * @param string $basePathInfo 项目目录
     * @param string $logsPathInfo 日志路径
     *
     * @return string
     */
    protected function getLogsDir($basePathInfo, $logsPathInfo)
    {
        return $basePathInfo.'logs/es/'.$logsPathInfo;
    }

    /**
     * 获取扩展词典文件
     *
     * @return string
     */
    protected function getExtensionDictionaryFile(): string
    {
        $path = $this->getIKConfigPath();

        return $path.'dic\ext.dic';
    }

    /**
     * 获取ik插件目录
     *
     * @return string
     */
    protected function getIKConfigPath(): string
    {
        $path = $this->getEsBasePathInfo();

        $esPath = $path['esPath'];

        return $esPath.'plugins\ik\config\\';
    }

    /**
     * 获取 analysis 目录
     *
     * @return string
     */
    protected function getAnalysisDir(): string
    {
        $path = $this->getEsBasePathInfo();
        $esPath = $path['esPath'];

        return $esPath.'config/analysis/';
    }

    /**
     *  获取同义词词典文件
     *
     * @return string
     */
    protected function getSynonymDictionaryFile(): string
    {
        return $this->getAnalysisDir().'synonym.txt';
    }
}