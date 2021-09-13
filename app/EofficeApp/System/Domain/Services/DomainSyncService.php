<?php


namespace App\EofficeApp\System\Domain\Services;


use App\EofficeApp\Base\BaseService;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Redis;

class DomainSyncService extends BaseService
{
    const LDAP_AUTO_SYNC_SWITCH = 'ldap_auto_sync_switch'; // ldap是否开启, 0关闭 1开启
    const LDAP_AUTO_SYNC_PERIOD = 'ldap_auto_sync_period';  // ldap自动同步周期
    const LDAP_AUTO_SYNC_PERIOD_DAY = 1;        // 每天同步
    const LDAP_AUTO_SYNC_PERIOD_WEEK = 2;       // 每周同步
    const LDAP_AUTO_SYNC_PERIOD_MONTH = 3;      // 每月同步
    // 同步周期有效值
    const LDAP_AUTO_SYNC_PERIOD_VALID_VALUE = [
      self::LDAP_AUTO_SYNC_PERIOD_DAY,
      self::LDAP_AUTO_SYNC_PERIOD_WEEK,
      self::LDAP_AUTO_SYNC_PERIOD_WEEK,
    ];
    const LDAP_AUTO_SYNC_WEEK_DAY = 'ldap_auto_sync_week_day';  // ldap自动同步周期
    const LDAP_AUTO_SYNC_MONTH_DAY = 'ldap_auto_sync_month_day';  // ldap自动同步周期
    const LDAP_AUTO_SYNC_TIME = 'ldap_auto_sync_time';  // ldap自动同步周期
    const LDAP_AUTO_SYNC_MESSAGE_RECEIVER = 'ldap_auto_sync_message_receiver'; // 更新成功消息接收人

    const LDAP_AUTO_SYNC_CONFIG = 'LDAP:AUTO:SYNC:CONFIG'; // redis中LDAP相关配置

    /**
     * 获取自动同步配置
     *
     * @return array
     */
    public function getDomainSyncConfig()
    {
        $messageReceiverStr = get_system_param(self::LDAP_AUTO_SYNC_MESSAGE_RECEIVER, '');
        $messageReceiver = explode(',', $messageReceiverStr);

        return [
            self::LDAP_AUTO_SYNC_SWITCH => get_system_param(self::LDAP_AUTO_SYNC_SWITCH, 0),
            self::LDAP_AUTO_SYNC_PERIOD => get_system_param(self::LDAP_AUTO_SYNC_PERIOD , 1),
            self::LDAP_AUTO_SYNC_WEEK_DAY => get_system_param(self::LDAP_AUTO_SYNC_WEEK_DAY, 1),
            self::LDAP_AUTO_SYNC_MONTH_DAY => get_system_param(self::LDAP_AUTO_SYNC_MONTH_DAY, 1),
            self::LDAP_AUTO_SYNC_TIME => get_system_param(self::LDAP_AUTO_SYNC_TIME, '00:00'),
            self::LDAP_AUTO_SYNC_MESSAGE_RECEIVER => $messageReceiver
        ];
    }

    /**
     * 设置自动同步配置
     *
     * @param Request $request
     *
     * @return bool
     */
    public function setDomainSyncConfig(Request $request)
    {
        $period = $request->request->getInt(self::LDAP_AUTO_SYNC_PERIOD);

        if (in_array($period, self::LDAP_AUTO_SYNC_PERIOD_VALID_VALUE)) {

            $switch = $request->request->getInt(self::LDAP_AUTO_SYNC_SWITCH);
            $weekDay = $request->request->getInt(self::LDAP_AUTO_SYNC_WEEK_DAY);
            $monthDay = $request->request->getInt(self::LDAP_AUTO_SYNC_MONTH_DAY);
            $time = $request->request->get(self::LDAP_AUTO_SYNC_TIME);
            $messageReceiver = $request->get(self::LDAP_AUTO_SYNC_MESSAGE_RECEIVER);
            $messageReceiverStr = implode(',', $messageReceiver);

            set_system_param(self::LDAP_AUTO_SYNC_SWITCH, $switch);
            set_system_param(self::LDAP_AUTO_SYNC_PERIOD, $period);
            set_system_param(self::LDAP_AUTO_SYNC_WEEK_DAY, $weekDay);
            set_system_param(self::LDAP_AUTO_SYNC_MONTH_DAY, $monthDay);
            set_system_param(self::LDAP_AUTO_SYNC_TIME, $time);
            set_system_param(self::LDAP_AUTO_SYNC_MESSAGE_RECEIVER, $messageReceiverStr);

            // TODO 实现批量更新

            Redis::del(self::LDAP_AUTO_SYNC_CONFIG);

            return true;
        }

        return false;
    }

    /**
     * 刷新提醒配置缓存
     *
     * @return array
     */
    public function refreshConfigCache(): array
    {
        Redis::del(self::LDAP_AUTO_SYNC_CONFIG);
        $config = [
            self::LDAP_AUTO_SYNC_SWITCH => get_system_param(self::LDAP_AUTO_SYNC_SWITCH, 0),
            self::LDAP_AUTO_SYNC_PERIOD => get_system_param(self::LDAP_AUTO_SYNC_PERIOD , 1),
            self::LDAP_AUTO_SYNC_WEEK_DAY => get_system_param(self::LDAP_AUTO_SYNC_WEEK_DAY, 1),
            self::LDAP_AUTO_SYNC_MONTH_DAY => get_system_param(self::LDAP_AUTO_SYNC_MONTH_DAY, 1),
            self::LDAP_AUTO_SYNC_TIME => get_system_param(self::LDAP_AUTO_SYNC_TIME, '00:00'),
        ];

        Redis::setex(self::LDAP_AUTO_SYNC_CONFIG, 86400,  json_encode($config));

        return $config;
    }
}