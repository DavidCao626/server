<?php
namespace App\EofficeApp\LogCenter\Facades;

use Illuminate\Support\Facades\Facade;
/**
 * Description of LogCenter
 *
 * LogCenter::info($identifier, $data, $historyData, $currentData) 添加信息类日志函数
 * $identifier: 日志标识由（模块.日志类别.生成日志的操作类别）组成，如：system.user.delete。必填
 * $data: 日志数据。必填
 * $data = [
            'creator' => 'admin', \\ 必填
            'content' => '查看客户信息',\\ 必填
            'relation_id' => 12,\\ 可选
            'relation_table' => 'customer',\\ 可选
            'relation_title' => '测试客户名称'\\ 可选
        ];
 * $historyData: 关联数据的历史数据。用于记录变更记录。可选
 * $currentData: 关联数据的当前数据。用于记录变更记录。可选
 * 
 * 其他添加日志的函数，参数和添加信息类日志函数相同
 * LogCenter::error(...)
 * LogCenter::warning(...)
 * LogCenter::important(...)
 * LogCenter::syncInfo(...)
 * LogCenter::syncError(...)
 * LogCenter::syncWarning(...)
 * LogCenter::syncImportant(...)
 * 
 * 关于日志的操作类别系统关键字：
 * 删除（delete）,添加（add）,编辑（edit）,查看（view）,导入（import），导出（export），登录（login），登出（logout）
 * 不在以上关键字类的自行定义，如果是以上范围类的必须使用以上key。
 * 
 * @author lizhijun
 */
class LogCenter extends Facade 
{
    protected static function getFacadeAccessor()
    {
        return 'App\EofficeApp\LogCenter\Services\LogCenterService';
    }
}
