<?php

return [

    //todo 合并分支后这块要从config/elastic.php读取
//    'elasticsearch' => [
//        // Elasticsearch 支持多台服务器负载均衡，因此这里是一个数组
//        'hosts' => envOverload('ES_HOSTS', 'localhost'),
//    ],
    /**
     * ES日志索引配置
     */

     'index' => 'log_all' ,
     'type'  => 'doc' ,
     'tablePrefix' => 'eo_log_',
     'max_result_window' => 100000

];