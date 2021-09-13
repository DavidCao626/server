<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Redis;
use Schema;
use DB;
class SyncLogToMysql extends Job
{
    public $delay;
    const SIZE = 2000;
    private $startTime;
    public $timeout = 0;
    public $tries = 0;//错误执行次数
    public function __construct($time)
    {
        $this->delay = $time;
    }

    public function handle()
    {
        //防止队列失败后重复执行
        Redis::incr('logCenter:job_lock_in');
        $this->syncOldData();
    }

    public function syncOldData()
    {
        if(Redis::get('logCenter:job_lock_in') == 1){
            DB::table('eo_log_sync_status')->insert(['type' => 3, 'created_at' => date('Y-m-d h:i:s', time())]);//防止队列没有执行完，redis被清空导致数据重复同步
            set_time_limit(0);
            $this->startTime = '2021-01-01 00:00:00'; //todo where < $this->starTime
            $this->systemLog();
            $this->workFlowLog();
            $this->departmentAndUserLog();
            $this->documentLog();
            $this->loginLog();
            $this->webHook();
//        if(Redis::exists('logCenter:job_lock')){
//            Redis::del('logCenter:job_lock');
//        }
            DB::table('eo_log_sync_status')->insert(['type' => 3, 'created_at' => date('Y-m-d h:i:s', time())]);
            app('App\EofficeApp\LogCenter\Services\ElasticService')->syncLogToElasticSearch();
            app('App\EofficeApp\LogCenter\Services\LogRecordsService')->ipTrans();
        }
        return ;
    }

    public function workFlowLog()
    {

        $flowDesign = ['definedFlow', 'defineFlowDelete', 'UserReplace', 'quitUserReplace'];
        $flowRun = ['outsend', 'sunFlow', 'initFlowRunSeq', 'runFlowDelete', 'sunflow'];
        $category = ['definedFlow' => 'edit', 'defineFlowDelete' => 'delete', 'UserReplace' => 'handle_user_replace', 'quitUserReplace' => 'quit_user_replace',
            'outsend' => 'out_send', 'sunFlow' => 'create_sun_flow', 'initFlowRunSeq' => 'init_flow_run_seq', 'runFlowDelete' => 'delete', 'sunflow' => 'create_sun_flow'
        ];
        if (Schema::hasTable('system_flow_log')) {
            DB::table('system_flow_log')->where('log_time', '<', $this->startTime)->orderBy('log_id')->chunk(self::SIZE, function ($list) use ($flowRun, $category) {
                $data = [];
                //todo 判断表是否存在

                foreach ($list as $key => $value) {
                    $data[$key]['log_category'] = in_array($value->log_type, $flowRun) ? 'flow_run' : 'flow_design';
                    $data[$key]['log_operate'] = isset($category[$value->log_type]) ? $category[$value->log_type] : '';
                    $data[$key]['creator'] = $value->log_creator;
                    $data[$key]['ip'] = $value->log_ip;
                    $data[$key]['relation_table'] = $value->log_relation_table;
                    $data[$key]['relation_id'] = $value->log_relation_id;
                    $data[$key]['log_level'] = 1;
                    $data[$key]['log_content'] = $value->log_content;
                    $data[$key]['log_content_type'] = $this->isJson($value->log_content) ? 2 : 1;
                    $data[$key]['platform'] = 1;
                    $data[$key]['log_time'] = $value->log_time;
                    $data[$key]['log_relation_field'] = $value->log_relation_field;
                    $data[$key]['log_relation_id_add'] = $value->log_relation_id_add;
                    $data[$key]['is_failed'] = $value->is_failed;
                    $data[$key]['log_relation_table_add'] = $value->log_relation_table_add;
                    $data[$key]['log_relation_field_add'] = $value->log_relation_field_add;
                }
                if (Schema::hasTable('eo_log_workflow')) {

                    $this->insertLogData($data, 'eo_log_workflow');
                    unset($data);
                }


            });
        }

    }

    public function systemLog()
    {
        $customerType = [
            'add' => ["新建客户", "创建客户", "Create a customer"], 'delete' => ["回收站删除客户", "删除客户",],
            'edit' => ["修改"], 'view' => ["查看客户", "See the customer"], 'import' => ["导入客户"],
            'attention' => ["Add the attention", "添加关注", "取消关注",], 'customer_merge' => ["合并客户", "合并至客户"],
            'customer_recover' => ["恢复客户"], 'customer_back' => ["手动退回公海客户", "退回客户", "Back to seas ,customer name"],
            'customer_appoint' => ["指定客户"], 'customer_pick' => ["Pick up the customer", "捡起客户"],
            'customer_auto_recover' => ["系统自动回收客户", "系统回收客户"], 'distribute' => ["自动分配"]
        ];
        $customerLinkType = [
            'add' => ["新建联系人", "Created linkman"], 'delete' => ["删除联系人"],
            'edit' => ["修改"], 'view' => ["查看联系人"], 'import' => ["导入联系人"],
        ];
        $contract = [
            'destroy' => ["回收站删除合同"],
            'view' => ["查看合同", "View contract"],
            'add' => ["新建合同"],
            'edit' => ["修改"],
            'recover' => ["恢复合同"],
            'delete' => ["删除合同"]

        ];
        if (Schema::hasTable('system_log')) {
            $module = DB::table('system_log')->groupBy('log_type')->select('log_type')->get();
            foreach ($module as $k => $v) {
                //取出指定log_type的所有数据
                $size = isset($customerType[$v->log_type]) ? self::SIZE / 2 : self::SIZE;
                DB::table('system_log')->where('log_type', $v->log_type)->where('log_time', '<', $this->startTime)->orderBy('log_id')->chunk($size, function ($logContentDatas) use ($customerType, $customerLinkType, $contract, $v) {
                    foreach ($logContentDatas as $logKey => $logValue) {
                        //公共部分
                        $data[$logKey]['creator'] = $logValue->log_creator;
                        $data[$logKey]['ip'] = $logValue->log_ip;
                        $data[$logKey]['relation_table'] = $logValue->log_relation_table;
                        $data[$logKey]['relation_id'] = $logValue->log_relation_id;
                        $data[$logKey]['log_level'] = 1;
                        $data[$logKey]['log_content'] = $logValue->log_content;
                        $data[$logKey]['log_content_type'] = $this->isJson($logValue->log_content) ? 2 : 1;
                        $data[$logKey]['platform'] = 1;
                        $data[$logKey]['log_time'] = $logValue->log_time;

                        if ($v->log_type == 1) {
                            break;
                        } else if ($v->log_type == 'charge' || $v->log_type == 'document' || $v->log_type == 'notify') {
                            $data[$logKey]['log_category'] = $v->log_type;
                            $data[$logKey]['log_operate'] = 'delete';
                            $data[$logKey]['log_level'] = 1;
                        } else if ($v->log_type == 'customer') {
                            $data[$logKey]['log_category'] = 'customer_info';
                            $data[$logKey]['log_operate'] = 'seed';
                            foreach ($customerType as $customerTypeKey => $customerTypeValue) {
                                foreach ($customerTypeValue as $contentKey => $contentValue) {
                                    if (strpos($logValue->log_content, $contentValue) === 0) {
                                        $data[$logKey]['log_operate'] = $customerTypeKey;
                                        break 2;
                                    }
                                }
                            }
                        } else if ($v->log_type == 'customer_linkman') {
                            $data[$logKey]['log_category'] = $v->log_type;
                            $data[$logKey]['log_operate'] = 'seed';
                            foreach ($customerLinkType as $customerLinkTypeKey => $customerLinkTypeValue) {
                                foreach ($customerLinkTypeValue as $contentLinkKey => $contentLinkValue) {
                                    if (strpos($logValue->log_content, $contentLinkValue) === 0) {
                                        $data[$logKey]['log_operate'] = $customerLinkTypeKey;
                                        break 2;
                                    }
                                }
                            }
                        } else if ($v->log_type == 'contract_t') {
                            $data[$logKey]['log_category'] = 'contract_info';
                            $data[$logKey]['log_operate'] = 'seed';
                            foreach ($contract as $contractKey => $contractKeyValue) {
                                foreach ($contractKeyValue as $contractK => $contractV) {
                                    if (strpos($logValue->log_content, $contractV) === 0) {
                                        $data[$logKey]['log_operate'] = $contractKey;
                                        break 2;
                                    }
                                }
                            }
                        } else if ($v->log_type == 'whiteListCheck') {
                            $data[$logKey]['log_category'] = $v->log_type;
                            $data[$logKey]['log_operate'] = 'request';
                        } else if ($v->log_type == 'incomeexpense') {
                            $data[$logKey]['log_category'] = $v->log_type;
                            $data[$logKey]['log_operate'] = 'record';
                        } else {
                            $data[$logKey]['log_category'] = 'unknow';
                            $data[$logKey]['log_operate'] = 'unknow';
                        }
                    }

                    if ($v->log_type != 1) {
//                    print_r($data);exit;
                        //  todo 按表名插入 $table = 'eo_log_' . $v->log_type;
                        $table = config('elastic.logCenter.tablePrefix') . $v->log_type;
                        if ($v->log_type == 'customer_linkman') {
                            $table = 'eo_log_customer';
                        }
                        if ($v->log_type == 'whiteListCheck') {
                            $table = 'eo_log_system';
                        }
                        if ($v->log_type == 'contract_t') {
                            $table = 'eo_log_contract';
                        }
                        if (Schema::hasTable($table)) {
                            $this->insertLogData($data, $table);
                            unset($data);
                        }

                    }

                });


            }
        }


    }

    public function departmentAndUserLog()
    {
        $tableKey = ['user', 'department'];
        foreach ($tableKey as $k => $v) {
            if (Schema::hasTable('system_' . $v . '_log')) {
                DB::table('system_' . $v . '_log')->where('log_time', '<', $this->startTime)->orderBy('log_id')->chunk(self::SIZE, function ($list) use ($v) {
                    $data = [];
                    //todo 判断表是否存在
                    foreach ($list as $key => $value) {
                        $data[$key]['log_category'] = $v;
                        $data[$key]['log_operate'] = $value->log_type;
                        $data[$key]['creator'] = $value->log_creator;
                        $data[$key]['ip'] = $value->log_ip;
                        $data[$key]['relation_table'] = $value->log_relation_table;
                        $data[$key]['relation_id'] = $value->log_relation_id;
                        $data[$key]['log_level'] = 1;
                        $data[$key]['log_content'] = $value->log_content;
                        $data[$key]['log_content_type'] = $this->isJson($value->log_content) ? 2 : 1;
                        $data[$key]['platform'] = 1;
                        $data[$key]['log_time'] = $value->log_time;
                    }
                    if (Schema::hasTable('eo_log_system')) {
                        $this->insertLogData($data, 'eo_log_system');
                        unset($data);
                    }


                });
            }

        }

    }

    public function documentLog()
    {
        //todo 文档同步完后要关联内容表把对应的内容填进去
        if (Schema::hasTable('document_logs')) {
            DB::table('document_logs')->where('created_at', '<', $this->startTime)->orderBy('log_id')->chunk(self::SIZE, function ($list) {
                $data = [];
                //todo 判断表是否存在
                foreach ($list as $key => $value) {
                    $data[$key]['log_category'] = 'document';
                    $data[$key]['creator'] = $value->user_id;
                    $data[$key]['ip'] = $value->ip;
                    $data[$key]['relation_table'] = 'document_content';
                    $data[$key]['relation_id'] = $value->document_id;
                    $data[$key]['log_level'] = 1;
                    $data[$key]['log_content'] = $value->log_info ?? '';
                    $data[$key]['log_content_type'] = $this->isJson($value->log_info) ? 2 : 1;
                    $data[$key]['platform'] = 1;
                    $data[$key]['log_time'] = $value->created_at;
                    if ($value->operate_type == 1) {
                        $data[$key]['log_operate'] = 'add';
                    } else if ($value->operate_type == 2) {
                        $data[$key]['log_operate'] = 'edit';
                    } else if ($value->operate_type == 3) {
                        $data[$key]['log_operate'] = 'view';
                    } else if ($value->operate_type == 4) {
                        $data[$key]['log_operate'] = 'delete';
                    } else if ($value->operate_type == 5) {
                        $data[$key]['log_operate'] = 'share';
                    } else if ($value->operate_type == 6) {
                        $data[$key]['log_operate'] = 'move';
                    } else if ($value->operate_type == 8) {
                        $data[$key]['log_operate'] = 'download';
                    } else if ($value->operate_type == 9) {
                        $data[$key]['log_operate'] = 'print';
                    } else {
                        $data[$key]['log_operate'] = 'unknow';
                    }
                }

                if (Schema::hasTable('eo_log_document')) {
                    $this->insertLogData($data, 'eo_log_document');
                    unset($data);
                }


            });
        }

    }

    public function loginLog()
    {
        if (Schema::hasTable('system_login_log')) {
            DB::table('system_login_log')->where('log_time', '<', $this->startTime)->orderBy('log_id')->chunk(self::SIZE, function ($list) {
                $data = [];
                //todo 判断表是否存在
                foreach ($list as $key => $value) {
                    $data[$key]['log_category'] = 'login';
                    $data[$key]['log_operate'] = 'login';
                    $data[$key]['creator'] = $value->log_creator;
                    $data[$key]['ip'] = $value->log_ip;
                    $data[$key]['relation_table'] = $value->log_relation_table;
                    $data[$key]['relation_id'] = $value->log_relation_id;
                    $data[$key]['log_level'] = 1;
                    $data[$key]['log_content'] = $value->log_content;
                    $data[$key]['log_content_type'] = $this->isJson($value->log_content) ? 2 : 1;
                    $data[$key]['platform'] = 1;
                    $data[$key]['log_time'] = $value->log_time;
                    if ($value->log_type == 'erroruname' || $value->log_type == 'ilip' || $value->log_type == 'pwderror') {
                        $data[$key]['log_level'] = 2;
                    } else {
                        if ($value->log_type == 'mobile') {
                            $data[$key]['platform'] = 2;
                        } else if ($value->log_type == 'client') {
                            $data[$key]['platform'] = 3;
                        }
                    }
                }
                if (Schema::hasTable('eo_log_system')) {
                    $this->insertLogData($data, 'eo_log_system');
                    unset($data);
                }

            });
        }

    }

    public function webHook()
    {
        if (Schema::hasTable('system_webhook_log')) {
            DB::table('system_webhook_log')->where('log_time', '<', $this->startTime)->orderBy('log_id')->chunk(self::SIZE, function ($list) {
                $data = [];
                //todo 判断表是否存在
                foreach ($list as $key => $value) {
                    $data[$key]['log_category'] = 'webhook';
                    $data[$key]['log_operate'] = 'fail';
                    $data[$key]['creator'] = $value->log_creator;
                    $data[$key]['ip'] = $value->log_ip;
                    $data[$key]['relation_table'] = '';
                    $data[$key]['relation_id'] = $value->log_relation_id;
                    $data[$key]['log_level'] = 1;
                    $data[$key]['log_content'] = $value->log_content;
                    $data[$key]['log_content_type'] = $this->isJson($value->log_content) ? 2 : 1;
                    $data[$key]['platform'] = 1;
                    $data[$key]['log_time'] = $value->log_time;
                    $data[$key]['webhook_url'] = $value->log_relation_table;
                }
                if (Schema::hasTable('eo_log_integration')) {
                    $this->insertLogData($data, 'eo_log_integration');
                    unset($data);
                }


            });
        }

    }

    public function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function insertLogData($data, $table)
    {
        try {
            DB::table($table)->insert($data);
        } catch (\Exception $e) {
            try {
                $count = count($data);
                $slice = floor($count / 10);
                for ($i = 0; $i <= $slice; $i++) {
                    $offset = $i * 10;
                    $sonArr = array_slice($data, $offset, 10);
                    DB::table($table)->insert($sonArr);
                    unset($sonArr);
                }
            } catch (\Exception $e) {
                \Log::info($table);
            }
        }

    }
}
