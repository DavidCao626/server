<?php
namespace App\EofficeApp\LogCenter\Tests;
use Tests\UnitTest;
use App\EofficeApp\LogCenter\Facades\LogCenter;
/**
 * Description of LogCenterTest
 * $this->response($data) // 打印
 * $this->reaponseJson($data) // 打印json格式
 * $this->getCurrentMemory() // 获取当前内存占用情况
 * @author lizhijun
 */
class LogCenterTest extends UnitTest
{  
    public function run()
    {
        $this->ipAreaTest();
    }
    private function ipAreaTest()
    {
        LogCenter::ipArea('114.93.25.33');
        
        // 登录成功日志
        $data = [
            'creator' => 'admin',
            'content' => '登录成功',
        ];
        LogCenter::info('system.login.login', $data); // 异步
        LogCenter::syncInfo('system.login.login', $data);  // 同步
        // 登录失败日志
        $data = [
            'creator' => 'admin',
            'content' => '密码错误',
        ];
        LogCenter::error('system.login.login', $data); // 异步
        LogCenter::syncError('system.login.login', $data); // 同步
        // 删除用户日志
        $data = [
            'creator' => 'admin',
            'content' => '删除用户',
            'relation_id' => 'wv00000001',
            'relation_table' => 'user',
            'relation_title' => '测试用户'
        ];
        LogCenter::warning('system.user.delete', $data); // 异步
        LogCenter::syncWarning('system.user.delete', $data); // 同步
        // 查看客户
        $data = [
            'creator' => 'admin',
            'content' => '查看客户信息',
            'relation_id' => 12,
            'relation_table' => 'customer',
            'relation_title' => '测试客户名称'
        ];
        LogCenter::info('customer.customer.view', $data); // 异步
        LogCenter::syncInfo('customer.customer.view', $data); // 同步
    }
    
    
    public function table() {
        
    }
    public function header() {
        
    }
    
    
    
    private function getLogDetailTest() {
        $logRecordService = app('App\EofficeApp\LogCenter\Services\LogRecordsService');
        $params = [
            'module_key' => 'document',
            'log_id' => 11968
        ];
        $result = $logRecordService->getLogDetail($params);
        $this->responseJson($result);
    }
    private function getChangeDataByRelationIdTest()
    {
        $logRecordService = app('App\EofficeApp\LogCenter\Services\LogRecordsService');
        $params = [
            'module_key' => 'document',
            'relation_id' => 157,
            'relation_table' => 'document_content'
        ];
        $result = $logRecordService->getChangeDataByRelationId($params);
        $this->responseJson($result);
    }
    private function addLogTest()
    {
        $identifier='document.document.edit';
        $data = [
            'creator' => 'admin',
            'content' => 'edit document',
            'relation_table' => 'document_content',
            'relation_id' => 157
        ];
        $history = [
            'title' => '123',
            'content' => '测试测试测试测试测试测试测试测试测试',
            'folder_id' => 1,
            'attachment_id' => ['111111', '222222']
        ];
        $current = [
            'title' => '123456',
            'content' => '测试测试测试测试测试测试测试测试测试1231231323',
            'folder_id' => 2,
            'attachment_id' => ['111111', '3333333']
        ];
        for ($i = 1; $i < 10; $i ++) {
            $history['folder_id'] = $history['folder_id'] + 1;
            $current['folder_id'] = $current['folder_id'] + 1;
            LogCenter::syncInfo($identifier ,$data , $history , $current); //history_data和current_data要不要做成可选参数？
        }
    }
    private function test()
    {
        $data = [
            [
                'log_id' => 1,
                'diff' => [
                    ['from' => '', 'to' => '', 'field' => ''],
                    ['from' => '', 'to' => '', 'field' => '']
                ]
            ],[
                'log_id' => 2,
                'creator' => 'admin',
                'created_at' => '2020-12-12 12:19:12',
                'diff' => [
                    ['from' => '', 'to' => '', 'field' => ''],
                    ['from' => '', 'to' => '', 'field' => '']
                ]
            ],[
                'log_id' => 3,
                'creator' => 'admin',
                'created_at' => '2020-12-12 12:19:12',
                'diff' => [
                    ['from' => '', 'to' => '', 'field' => ''],
                    ['from' => '', 'to' => '', 'field' => '']
                ]
            ]
        ];
    }
    /**
     * 测试获取日志列表
     */
    private function listsTest()
    {
        $logRecordService = app('App\EofficeApp\LogCenter\Services\LogRecordsService');
        $params = [
           'page' => 1,
            'limit' => 10,
            'order_by' => ['log_time' => 'desc'],
            'search' => [
                'log_time' => [['2020-01-01 00:00:00','2020-03-01 00:00:00'], 'between'],
                'creator' => [['admin','WV000001'], 'in'],
                'log_level' => [1, '='],
                'log_operate' => ['delete', '='],
                'relation_id' => ['admin', '='],
                'relation_table' => ['user', '='],
                'log_id' => [1, '='],
                'log_category' => ['user', '=']
            ]
       ];
        $result = $logRecordService->lists($params, 'system');
        $this->response($result);
    }
}
