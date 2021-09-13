<?php

namespace App\EofficeApp\LogCenter\Services;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder as ESClientBuilder;

class BaseElasticService extends \App\EofficeApp\Base\BaseService
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

    public $index;

    public function __construct()
    {
        parent::__construct();
        $config = config('elastic.elasticsearch.hosts');
        if (is_string($config)) {
            $config = explode(',', $config);
        }
        $builder = ESClientBuilder::create()->setHosts($config);

        $this->client = $builder->build();
        //todo 包括那些 index ,type这些都要从配置文件里读
        $this->type = config('elastic.logCenter.type');
        $this->index = config('elastic.logCenter.index');

//        $this->type = Constant::COMMON_INDEX_TYPE;
    }

    /**
     * 判断对应的索引/文档是否存在
     *
     * @param array $param
     *
     * @return bool
     */
    public function exists($index)
    {
        $exist = false;
        if (!empty($index)) {
            $exist = $this->client->indices()->exists(['index' => $index]);
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

        array_walk($response, function (&$value, $key) {
            $aliases = $value['aliases'];
            $value = key($aliases);
        });
        unset($value);

        return $response;
    }

    /**
     * 建立表结构
     * @param $module
     * @return array
     */
    protected function setMapping()
    {
        //如果添加的字段不进行搜索,需要再配个 "dynamic" : "false" ，index:false
        $params = [
            'index' => $this->index,
            'body' => [
                'mappings' => [
                    $this->type => [
                        '_source' => [
                            'enabled' => true
                        ],
                        'dynamic_templates' => [
                            [
                                'text_as_keyword' => [
                                    'match_mapping_type' => 'string',
                                    'mapping' => [
                                        'type' => 'keyword',
                                    ]
                                ],

                            ],
                            [
                                'double_as_float' => [
                                    'match_mapping_type' => 'double',
                                    'mapping' => [
                                        'type' => 'float',
                                    ]
                                ]
                            ]
                        ],

                        'properties' => [
                            'log_id' => [
                                'type' => 'long',
                            ],
                            'log_category' => [
                                'type' => 'keyword'
                            ],
                            'log_operate' => [
                                'type' => 'keyword'
                            ],
                            'log_level' => [
                                'type' => 'byte'
                            ],
                            'creator' => [
                                'type' => 'keyword'
                            ],
                            'ip' => [
                                'type' => 'keyword'
                            ],
                            'relation_table' => [
                                'type' => 'keyword'
                            ],
                            'relation_id' => [
                                'type' => 'keyword'
                            ],
                            'relation_title' => [
                                'type' => 'text',
                                'analyzer' => 'ik_max_word'
                            ],
                            'operate_path' => [
                                'type' => 'text',
                                'analyzer' => 'ik_max_word'
                            ],
                            'has_change' => [
                                'type' => 'byte'
                            ],
                            'log_content' => [
                                'type' => 'text',
                                'analyzer' => 'ik_max_word'
                            ],
                            'log_content_type' => [
                                'type' => 'byte'
                            ],
                            'platform' => [
                                'type' => 'byte'
                            ],
                            'log_time' => [
//                                'type' => 'date',
//                                "format" => "yyyy-MM-dd HH:mm:ss"
                                'type' => 'keyword'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->client->indices()->create($params);
        return $response;
    }

    /**
     * 添加一条数据
     * @param $data
     * @return array
     */
    protected function addOneLogData($data)
    {
        $params = [
            'index' => $this->index,
            'type' => $this->type,
            'id' => config('elastic.logCenter.tablePrefix') . $data['module_key'] . "_" . $data['log_id'],
            'body' => []
        ];
        foreach ($data as $k => $v) {
            $params['body'][$k] = $v;
        }

        $response = $this->client->index($params);
        return $response;
    }

    /**
     * 批量添加
     * @param $data
     * @return array
     */
    protected function addManyLogData($data)
    {
        $params = [];
        $content = [];
        foreach ($data as $key => $value) {

            $params['body'][] = [
                'index' => [
                    '_index' => $this->index,
                    '_type' => $this->type,
                    '_id' => config('elastic.logCenter.tablePrefix') . $value['module_key'] . "_" . $value['log_id'],

//                    'raise_on_exception' => false,
//                    'raise_on_error' => false
                ]
            ];
            foreach ($value as $k => $v) {
                $content[$k] = $v;
            }

            $params['body'][] = $content;
        }

        $response = $this->client->bulk($params);
        return $response;
    }

    /**获取用户轨迹
     * @param $params
     * @return array
     */
    protected function searchUserActivityTrack($params)
    {

        $body = $this->baseBody($params['search']);

        $resCount = $this->client->count($body);

        $body['body']['size'] = $params['limit'];
        $body['body']['from'] = ($params['page'] - 1) * $params['limit'];
        $body['body']['sort'] = [
            [
                "log_time" => [
                    "order" => "desc"
                ]
            ]
        ];
        $response = $this->client->search($body);
        $result = [];
        foreach ($response['hits']['hits'] as $k => $v) {
            $result[] = $v['_source'];
        }
        return ['total' => $resCount['count'], 'list' => $result];

    }

    /**获取模块使用率排行
     * @param $params
     * @return array
     */
    protected function searchModuleRank($params, $size)
    {
        $body = $this->baseBody($params['search']);
        $body['body']['size'] = 0;
        $body['body']['aggs'] = [
            'group_module' => [
                'terms' => [
                    'field' => 'module_key',
                    'size' => $size  //显示前几名
                ]
            ]
        ];
        try {
            $response = $this->client->search($body);
        } catch (\Exception $e) {
            $this->throwException(['code' => ['', '全文检索异常']]);
        }

        $result = [];
        foreach ($response['aggregations']['group_module']['buckets'] as $key => $value) {
            $result[] = ['module_key' => $value['key'], 'count' => $value['doc_count']];
        }
        return ['list' => $result];
    }

    /**删除索引
     * @param $index
     * @return bool
     */
    public function deleteIndex($index)
    {
        $params = [
            'index' => $index,
        ];
        $response = $this->client->indices()->delete($params);
        if ($response) {
            return true;
        } else {
            return false;
        }
    }

    /**处理查询条件
     * @param $search
     * @return array
     */
    public function handleEsSearch($search)
    {
        $params = $this->parseParams($search);
        $terms = [];
        foreach ($params['search'] as $k => $v) {
            if (isset($v[1]) && $v[1] == 'like') {
                $query['must'] = [
                    'match_phrase' => [
                        $k => [
                            'query' => $v[0]
                        ]
                    ]
                ];
            } else {
                if (is_array($v[0])) {
                    if (!isset($v[1])) {
                        array_push($terms, ['terms' => [$k => $v[0]]]);
                    } else {

                        if ($v[1] == 'in') {
                            array_push($terms, ['terms' => [$k => $v[0]]]);
                        } else {
                            array_push($terms, ['range' => [$k => ['gte' => $v[0][0], 'lte' => $v[0][1]]]]);
                        }
                    }
                } else {

                    if ($v[0] != 'all') {
                        array_push($terms, ['term' => [$k => $v[0]]]);
                    }

                }
            }


        }
        $query['filter'] = $terms;
        $params['search'] = $query;
        return $params;
    }

    /**基础查询体
     * @param $terms
     * @return array
     */
    private function baseBody($query)
    {

        $body = [
            'index' => $this->index,
            'type' => $this->type,
            'body' => [
                'query' => [
                    'bool' => $query
//                        [
//
//                        'filter' => [
//                            $terms
//                        ],
//                        'must'=>[
//                            'match_phrase' =>[
//                                'log_content'=>[
//                                    'query' => '手机'
//                                ]
//                            ]
//                        ]
//                    ]

                ]
            ]
        ];

        return $body;
    }

    /**查找日志信息
     * @param $params
     * @return array
     */
    protected function searchLogData($params)
    {
        try {
            $body = $this->baseBody($params['search']);
            $resCount = $this->client->count($body);
            if ($resCount == 0) {
                return ['total' => 0, 'list' => []];
            }
            $body['body']['size'] = $params['limit'];
            //es分页从0开始
            $body['body']['from'] = ($params['page'] - 1) * $params['limit'];
            $body['body']['sort'] = [
                [
                    "log_time" => [
                        "order" => "desc"
                    ]
                ]
            ];
            $response = $this->client->search($body);

            $result = [];
            foreach ($response['hits']['hits'] as $k => $v) {
                foreach ($v['_source'] as $key => $val) {
                    $result[$k][$key] = $val;
                }
            }

            return ['total' => $resCount['count'], 'list' => $result];
        } catch (\Exception $e) {
            if (is_array(json_decode($e->getMessage(), true))) {
                $this->throwException(['code' => ['', '全文检索暂不支持早期数据直接查询，如需查询可结合时间范围查询数据']]);
            } else {
                $this->throwException(['code' => ['', '全文检索异常']]);
            }


        }


    }

    /**
     * 获取模块当前最大id 用于日志同步
     * @param $tableConfig
     * @return array
     */
    protected function searchModuleMax($tableConfig)
    {

        $module = [];
        foreach ($tableConfig as $key => $value) {
            $body = [
                'index' => $this->index,
                'type' => $this->type,
                'body' => [
                    'query' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'term' => [
                                        'module_key' => $value,
                                    ]
                                ],
                            ]
                        ]
                    ],
                    'size' => 0, //下发的文档数据，默认5条
                    'aggs' => [
                        'max_module' => [
                            'max' => [
                                'field' => 'log_id',
                            ]
                        ]
                    ]
                ]
            ];
            $response = $this->client->search($body);
            $result = $response['aggregations']['max_module']['value'] ? $response['aggregations']['max_module']['value'] : 0;
            $module[$value] = $result;
        }
        return $module;
    }

    /**
     * 获取当前es数据大小
     * @return mixed
     */
    protected function getEsDataCount()
    {
        $params = array(
            'index' => $this->index,
            'type' => $this->type,
            'body' => array(
                'size' => 5,
                'from' => 0
            )
        );
        $res = $this->client->search($params);
        return $res['hits']['total'];
    }

    protected function throwException($error, $dynamic = null)
    {
        if (isset($error['code'])) {
            echo json_encode(error_response($error['code'][0], $error['code'][1], $dynamic), 200);
            exit;
        }
    }

    /**
     * 控制Es最大返回数量
     */
    protected function setMaxResultWindow()
    {
        $params = [
            'index' => $this->index,
            'body' => ["max_result_window" => config('elastic.logCenter.max_result_window')]
        ];
        $this->client->indices()->putSettings($params);
    }

    // todo 数据缺失测试，后期删除
    public function testDebug()
    {

        $params = array(
            'index' => $this->index,
            'type' => $this->type,
            'body' => array(
                'size' => 5,
                'from' => 0
            )
        );
        $rtn = $this->client->search($params);
        print_r($rtn);
        exit;
        $miss = [];
        for ($i = 1; $i < 10000; $i++) {
            $params = array(
                'index' => $this->index,
                'type' => $this->type,
                'body' => array(
                    'query' => array(
                        'match' => array(
                            '_id' => 'eo_log_workflow_' . $i,
                        )
                    )
                )
            );
            $rtn = $this->client->search($params);
            if (!isset($rtn['hits']['hits'][0]['_source']['log_id'])) {
                $miss[$i] = $i;
            }

        }
        print_r($miss);
        exit;
    }

}