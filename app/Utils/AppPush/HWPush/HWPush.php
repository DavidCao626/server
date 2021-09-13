<?php
namespace App\Utils\AppPush\HWPush;

include_once (dirname(__FILE__) . '/push_admin/Application.php');
include_once (dirname(__FILE__) . '/push_admin/Constants.php');
include_once (dirname(__FILE__) . '/push_admin/PushLogConfig.php');
include_once (dirname(__FILE__) . '/push_admin/PushConfig.php');

include_once (dirname(__FILE__) . '/push_admin/push_msg/PushMessage.php');

include_once (dirname(__FILE__) . '/push_admin/push_msg/android/AndroidConfig.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/apns/ApnsConfig.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/notification/Notification.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/webpush/WebPushConfig.php');

include_once (dirname(__FILE__) . '/push_admin/push_msg/apns/Alert.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/apns/ApnsHmsOptions.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/apns/ApnsHeaders.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/apns/Aps.php');

include_once (dirname(__FILE__) . '/push_admin/push_msg/instanceapp/InstanceAppConfig.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/instanceapp/InstanceAppPushbody.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/instanceapp/InstanceAppRingtone.php');

include_once (dirname(__FILE__) . '/push_admin/push_msg/android/AndroidNotification.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/webpush/WebPushHeaders.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/webpush/WebPushHmsOptions.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/webpush/WebPushNotification.php');

include_once (dirname(__FILE__) . '/push_admin/push_msg/android/Badge.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/android/ClickAction.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/android/LightSetting.php');
include_once (dirname(__FILE__) . '/push_admin/push_msg/webpush/WebPushNotificationAction.php');

include_once (dirname(__FILE__) . '/push_admin/push_msg/android/LightSettingColor.php');

use push_admin\AndroidConfigDeliveryPriority;
use push_admin\ApnConstant;
use push_admin\Application;
use push_admin\Constants;
use push_admin\NotificationPriority;
use push_admin\push_msg\android\AndroidConfig;
use push_admin\push_msg\android\AndroidNotification;
use push_admin\push_msg\android\Badge;
use push_admin\push_msg\android\ClickAction;
use push_admin\push_msg\android\LightSetting;
use push_admin\push_msg\android\LightSettingColor;
use push_admin\push_msg\apns\Alert;
use push_admin\push_msg\apns\ApnsConfig;
use push_admin\push_msg\apns\ApnsHeaders;
use push_admin\push_msg\apns\ApnsHmsOptions;
use push_admin\push_msg\apns\Aps;

use push_admin\push_msg\instanceapp\InstanceAppConfig;
use push_admin\push_msg\instanceapp\InstanceAppPushbody;
use push_admin\push_msg\instanceapp\InstanceAppRingtone;

use push_admin\push_msg\notification\Notification;
use push_admin\push_msg\PushMessage;
use push_admin\push_msg\webpush\WebPushConfig;
use push_admin\push_msg\webpush\WebPushHeaders;
use push_admin\push_msg\webpush\WebPushHmsOptions;
use push_admin\push_msg\webpush\WebPushNotification;
use push_admin\push_msg\webpush\WebPushNotificationAction;
use push_admin\PushConfig;
use push_admin\PushLogConfig;

class HWPush
{
    // ordinal app
    private $appid;
    private $appsecret;
    // FOR PUSH MSG NOTIFICATION,PASSTHROUGH TOPIC/TOKEN/CONDITION
    public  $hw_push_token_key;
    // FOR APN
    public $apn_push_token_key;    
    // FOR WEBPUSH
    public $webpush_push_token_key;
    
    // fast app
    private $fast_appid;
    private $fast_appsecret;
    // fast app token
    public  $fast_push_token;


    private $hw_token_server;
    private $hw_push_server;
    private $log_suffix_show_start = ".............................";
    private $log_suffix_show_end = "-----------------------------";
    private $push_msg_type;
    private $default_topic = 'defaultTopic';

    private $str_len = 35;
    // eoffice 本地配置消息透传方式
    private $hwMsgType;
    // eoofice 消息内容
    private $message;

    public function __construct()
    {
        $pushConfig = PushConfig::getSingleInstance();
        PushLogConfig::getSingleInstance()->LogMessage(str_pad("HW_APPID", $this->str_len, '=') . ">" . $pushConfig->HW_APPID);
        PushLogConfig::getSingleInstance()->LogMessage(str_pad("HW_APPSECRET", $this->str_len, '=') . ">" . $pushConfig->HW_APPSECRET);
        PushLogConfig::getSingleInstance()->LogMessage(str_pad("HW_TOKEN_SERVER", $this->str_len, '=') . ">" . $pushConfig->HW_TOKEN_SERVER);
        PushLogConfig::getSingleInstance()->LogMessage(str_pad("HW_PUSH_TOKEN_ARR", $this->str_len, '=') . ">" . $pushConfig->HW_PUSH_TOKEN_ARR);
        PushLogConfig::getSingleInstance()->LogMessage(str_pad("WEBPUSH_PUSH_TOKEN_ARR", $this->str_len, '=') . ">" . $pushConfig->WEBPUSH_PUSH_TOKEN_ARR);
        PushLogConfig::getSingleInstance()->LogMessage(str_pad("APN_PUSH_TOKEN_ARR", $this->str_len, '=') . ">" . $pushConfig->APN_PUSH_TOKEN_ARR);
        PushLogConfig::getSingleInstance()->LogMessage(str_pad("HW_TOPIC_SUBSCRIBE_SERVER", $this->str_len, '=') . ">" . $pushConfig->HW_TOPIC_SUBSCRIBE_SERVER);
        PushLogConfig::getSingleInstance()->LogMessage(str_pad("HW_TOPIC_UNSUBSCRIBE_SERVER", $this->str_len, '=') . ">" . $pushConfig->HW_TOPIC_UNSUBSCRIBE_SERVER);
        PushLogConfig::getSingleInstance()->LogMessage(str_pad("HW_TOPIC_QUERY_SUBSCRIBER_SERVER", $this->str_len, '=') . ">" . $pushConfig->HW_TOPIC_QUERY_SUBSCRIBER_SERVER);
        PushLogConfig::getSingleInstance()->LogMessage(str_pad("HW_FAST_APPID", $this->str_len, '=') . ">" . $pushConfig->HW_FAST_APPID);
        PushLogConfig::getSingleInstance()->LogMessage(str_pad("HW_FAST_APPSECRET", $this->str_len, '=') . ">" . $pushConfig->HW_FAST_APPSECRET);
        PushLogConfig::getSingleInstance()->LogMessage(str_pad("HW_FAST_PUSH_TOKEN", $this->str_len, '=') . ">" . $pushConfig->HW_FAST_PUSH_TOKEN);
        
        $this->appsecret = $pushConfig->HW_APPSECRET;
        $this->appid = $pushConfig->HW_APPID;
        $this->hw_token_server = $pushConfig->HW_TOKEN_SERVER;
        $this->hw_push_server = $pushConfig->HW_PUSH_SERVER;
        $this->hw_push_token_key = $pushConfig->HW_PUSH_TOKEN_ARR;
        $this->apn_push_token_key = $pushConfig->APN_PUSH_TOKEN_ARR;
        $this->webpush_push_token_key = $pushConfig->WEBPUSH_PUSH_TOKEN_ARR;
        
        $this->fast_appsecret = $pushConfig->HW_FAST_APPSECRET;
        $this->fast_appid = $pushConfig->HW_FAST_APPID;
        $this->fast_push_token = $pushConfig->HW_FAST_PUSH_TOKEN;
        // eoffice
        $this->hwMsgType = envOverload('HW_TYPE', 3);
  
    }
    // eoffice华为消息推送入口
    public function sendMessage($message, $user, $type) {
        $this->message = $message;
        $this->hw_push_token_key = $user;

        try {
            $this->sendPushMsgMessageByMsgType(Constants::PUSHMSG_NOTIFICATION_MSG_TYPE);
        } catch (\Exception $e) {
            return false;
        }
    }

    function sendPushMsgMessageByMsgType($msg_type, $topic = "")
    {
        $application_server = $this->hw_push_server;
        $this->printLogMethodOperate("push msg start" . $this->log_suffix_show_start, __FUNCTION__ . ':' . __LINE__);

        $this->push_msg_type = $msg_type;
        $message = $this->getMessageByMsgType($msg_type);

        $this->printLogMethodOperate("msg body:" . json_encode($message->getFields()), __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_DEBUG_LEVEL);

        $application = $this->createApplication($application_server);
        $this->printLogMethodOperate("application server:" . json_encode($application->getApplicationFields()), __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_DEBUG_LEVEL);

        $application->push_send_msg($message->getFields());
        $this->printLogMethodOperate("push msg end" . $this->log_suffix_show_end, __FUNCTION__ . ':' . __LINE__);
    }

    function sendPushMsgRealMessage($message,$push_msg_type="")
    {
        $this->printLogMethodOperate("sendPushMsgRealMessage start push_msg_type:" .$push_msg_type, __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_DEBUG_LEVEL);
        if (!empty($push_msg_type)) {
            $this->push_msg_type = $push_msg_type;
        }
        $this->printLogMethodOperate("sendPushMsgRealMessage start push_msg_type:" .$this->push_msg_type, __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_DEBUG_LEVEL);
        $application_server = $this->hw_push_server;
        $application = $this->createApplication($application_server);
        $this->printLogMethodOperate("application server:" . json_encode($application->getApplicationFields()), __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_DEBUG_LEVEL);

        $application->push_send_msg($message);
        $this->printLogMethodOperate("push msg end" . $this->log_suffix_show_end, __FUNCTION__ . ':' . __LINE__);
    }

    function sendPushTopicMsgMessage($topic = "")
    {
        if (empty($topic)) {
            $topic = $this->default_topic;
        }
        $testTopicCommonSample = new TestTopicCommonSample($topic);

        $this->printLogMethodOperate("start subscribe topic:" . $topic, __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_INFO_LEVEL);
        // subscribe msg
        $testTopicCommonSample->sendTopicMessage(Constants::TOPIC_SUBSCRIBE_MSG_TYPE);
        // query subscribe msg
        $testTopicCommonSample->sendTopicMessage(Constants::TOPIC_SUBSCRIBE_QUERY_MSG_TYPE);
    }

    /**
     * $result==>{"msg":"success","code":"80000000","requestId":"157561883923402813000201",
     * "topics":[{"name":"defaultTopic","addDate":"2019-12-06"},
     * {"name":"push-test","addDate":"2019-12-06"},
     * {"name":"targetTopic","addDate":"2019-12-06"},
     * {"name":"weather","addDate":"2019-12-06"}]}
     */
    private function isTopicInTopicList($result, $topic)
    {
        $this->printLogMethodOperate("isTopicInTopicList topic[" . $topic . "],result:" . json_encode($result) . "", __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_INFO_LEVEL);
        if (empty($result)) {
            return FALSE;
        }

        $arrResult = json_decode(json_encode($result), true);
        $this->printLogMethodOperate("isTopicInTopicList arrResult:" . json_encode($arrResult) . "][code:" . $arrResult["code"], __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_DEBUG_LEVEL);

        if (empty($arrResult["code"])) {
            return FALSE;
        }
        $this->printLogMethodOperate("isTopicInTopicList code:" . $arrResult["code"], __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_DEBUG_LEVEL);

        if (! in_array($arrResult["code"], array(
            "80000000",
            80000000
        ))) {
            return FALSE;
        }
        if (empty($arrResult["topics"])) {
            return FALSE;
        }

        $topicArr = $arrResult["topics"];
        $this->printLogMethodOperate("isTopicInTopicList topicArr:" . json_encode($topicArr), __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_DEBUG_LEVEL);
        if (empty($topicArr)) {
            return FALSE;
        }

        foreach ($topicArr as $topicObject) {
            if ($topicObject["name"] == $topic) {
                return TRUE;
            }
        }
        $this->printLogMethodOperate("isTopicInTopicList False,topic is not subscribe", __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_DEBUG_LEVEL);
        return FALSE;
    }

    private function getDefaultAndroidNotificationContent($titel)
    {
        $prefixTitleData = '';
        switch ($this->push_msg_type) {
            case Constants::PUSHMSG_NOTIFICATION_MSG_TYPE:
                {
                    $prefixTitleData = ' notification ';
                    break;
                }
            case Constants::PUSHMSG_PASS_THROUGHT_MSG_TYPE:
                {
                    $prefixTitleData = ' passthrough ';
                    break;
                }

            case Constants::PUSHMSG_FASTAPP_MSG_TYPE:
                {
                    $prefixTitleData = ' fastapp ';
                    break;
                }
            case Constants::PUSHMSG_TOPIC_MSG_TYPE:
                {
                    $prefixTitleData = ' topic ';
                    break;
                }
            case Constants::PUSHMSG_CONDITION_MSG_TYPE:
                {
                    $prefixTitleData = ' condition ';
                    break;
                }

            case Constants::APN_MSG_TYPE:
                {
                    $prefixTitleData = ' apn ';
                    break;
                }
            case Constants::WEB_PUSH_MSG_TYPE:
                {
                    $prefixTitleData = ' webpush ';
                    break;
                }
        }

        return $prefixTitleData . $titel . $prefixTitleData;
    }
    // eoffice安卓信息结构体
    private function createAndroidNotification()
    {
        // API文档地址 https://developer.huawei.com/consumer/cn/doc/development/HMS-References/push-sendapi

        // 点击行为
        $click_action = new ClickAction();
        /**消息点击行为类型，取值如下：
        * 1：用户自定义点击行为
        * 2：点击后打开特定url
        * 3：点击后打开应用App
        * 4：点击后打开富媒体信息
        */
        $click_action->type(3);
        // 自定义页面中intent的实现
        // $click_action->intent("#Intent;compo=com.rvr/.Activity;S.W=U;end");
        // 设置通过action打开应用自定义页面
        // $click_action->action("test add");
        // 设置打开特定URL，本字段填写需要打开的URL，URL使用的协议必须是HTTPS协议。当type为2时必选。
        // $click_action->url("https://www.baidu.com");
        // 设置打开富媒体消息时，本字段填写需要打开的富媒体资源的URL，URL使用的协议必须是HTTPS协议。
        // $click_action->rich_resource("test rich resource");
        $click_action->buildFields();

        // 角标设置
        $badge = new Badge();
        // 角标累加数字，大于0小于100的正整数。
        $badge->add_num(1);
        // 应用包名+应用入口Activity类名。
        $badge->setclass("com.weaver.eoffice.WelcomeActivity");
        // 角标设置数字，大于等于0小于100的正整数。
        // $badge->set_num(99);
        $badge->buildFields();

        // 	呼吸灯设置
        $lightSetting = new LightSetting();
        $lightSetting->light_on_duration("3");
        $lightSetting->light_off_duration("5S");
        // set light setting color
        $LightSettingColor = new LightSettingColor();
        $LightSettingColor->setgenFullcolor(0, 0, 1, 1);
        $LightSettingColor->buildFields();
        $lightSetting->color($LightSettingColor->getFields());
        $lightSetting->buildFields();

        // 构建android notification消息体-2 for android config
        $android_notification = new AndroidNotification();
        // 安卓通知栏消息标题
        $android_notification->title("e-office");
        // 安卓通知栏消息内容
        $android_notification->body(isset($this->message['content']) ? $this->message['content'] : '');
        // $android_notification->icon("https://res.vmallres.com/pimages//common/config/logo/SXppnESYv4K11DBxDFc2.png");
        // $android_notification->color("#AACCDD");
        // $android_notification->sound("https://att.chinauui.com/day_120606/20120606_7fcf2235b44f1eab0b4dadtAkAGMTBHK.mp3");
        // $android_notification->tag("tagBoom");
        // $android_notification->body_loc_key("M.String.body");
        // $android_notification->body_loc_args(array(
        //     "Boy",
        //     "Dog"
        // ));
        // $android_notification->title_loc_key("M.String.title");
        // $android_notification->title_loc_args(array(
        //     "Girl",
        //     "Cat"
        // ));
        // $android_notification->channel_id("RingRing");
        // $android_notification->notify_summary("Some Summary");
        // $android_notification->image("https://developer-portalres-drcn.dbankcdn.com/system/modules/org.opencms.portal.template.core/resources/images/icon_Promotion.png");
        // 通知栏样式
        $android_notification->style(0);
        // $android_notification->big_title("Big Boom Title");
        // $android_notification->big_body("Big Boom Body");
        // 消息展示时长，超过后自动清除，单位为毫秒。
        // $android_notification->auto_clear(86400000);
        // $android_notification->notify_id(486);
        // $android_notification->group("Espace");
        // 安卓通知栏消息优先级，决定用户设备消息通知行为
        $android_notification->importance(NotificationPriority::NOTIFICATION_PRIORITY_HIGH);
        // 状态栏上显示的内容提示。
        $android_notification->ticker("i am a ticker");
        // 安卓通知栏消息保持标识，在点击通知栏消息后，消息是否不显示在通知栏，取值如下：true：不显示, false：显示，需要开通权益
        $android_notification->auto_cancel(true);
        $android_notification->when(date("Y-m-d"));
        // 是否使用系统默认振动模式控制开关。
        $android_notification->use_default_vibrate(true);
        // 是否使用默认呼吸灯模式控制开关。
        $android_notification->use_default_light(false);
        // 安卓通知栏消息可见性
        $android_notification->visibility("PUBLIC");
        // 设备应用在前台时通知栏消息是否前台展示开关
        $android_notification->foreground_show(true);
        // 安卓自定义通知消息振动模式
        // $android_notification->vibrate_config(array(
        //     "1.5",
        //     "2.000000001",
        //     "3"
        // ));
        $android_notification->click_action($click_action->getFields());
        $android_notification->badge($badge->getFields());
        $android_notification->light_settings($lightSetting->getFields());
        // $android_notification->buttons([
        //     [
        //         "name" => "查看详情",
        //         "action_type" => 0,
        //         "data" => ['aaa' => 1, 'bbb' => 2]
        //     ]
        // ]);

        $android_notification->buildFields();

        return $android_notification;
    }

    private function createAndroidConfig()
    {
        $android_notification = $this->createAndroidNotification();

        $android_config = new AndroidConfig();
        $android_config->collapse_key(- 1);
        // 透传消息投递优先级
        $android_config->urgency(AndroidConfigDeliveryPriority::PRIORITY_HIGH);
        // 消息缓存时间，单位是秒。可选，默认值为86400 （1天），最大值为15天
        // $android_config->ttl("1448s");
        // 批量任务消息标识（可选）
        // $android_config->bi_tag("Trump");
        if ($this->push_msg_type == Constants::PUSHMSG_FASTAPP_MSG_TYPE) {
            $android_config->fast_app_target(1);
        } else {
            $android_config->notification($android_notification->getFields());
        }
        $android_config->buildFields();
        return $android_config;
    }

    private function createNotification()
    {
        $notification = new Notification("e-office", isset($this->message['content']) ? $this->message['content'] : '', "");
        $notification->buildFields();
        return $notification;
    }

    private function createApnsConfig()
    {
        // ApnsHeaders
        $apnsHeaders = new ApnsHeaders();
        $apnsHeaders->apns_topic("hmspush");
        $apnsHeaders->apns_priority(ApnConstant::ANP_PRIORITY_SEND_IMMEDIATELY);
        $apnsHeaders->buildFields();

        // ApnHmsOptions
        $apnsHmsOptions = new ApnsHmsOptions();
        $apnsHmsOptions->target_user_type(ApnConstant::APN_TARGET_USER_TEST_USER);
        $apnsHmsOptions->buildFields();

        // Aps
        // Alert
        $alert = new Alert();
        $alert->title("hw default ios message title");
        $alert->body("hw default ios message body");
        $alert->action_loc_key("PLAY");
        $alert->buildFields();

        $aps = new Aps();
        $aps->alert($alert->getFields());
        $aps->badge(5);
        $aps->buildFields();

        $apnsConfig = new ApnsConfig();
        $apnsConfig->headers($apnsHeaders->getFields());
        $apnsConfig->hms_options($apnsHmsOptions->getFields());

        $apn_payload["aps"] = $aps->getFields();
        $apn_payload["acme1"] = "bar";
        $apn_payload["acme2"] = array(
            "bang",
            "whiz"
        );
        $apnsConfig->payload($apn_payload);

        $apnsConfig->buildFields();

        return $apnsConfig;
    }

    private function createWebPush()
    {
        $webPushConfig = new WebPushConfig();
        $webPushConfig->data("test webpush data");

        $webPushHeaders = new WebPushHeaders();
        $webPushHeaders->topic("12313ceshi");
        $webPushHeaders->ttl("990");
        $webPushHeaders->urgency(Constants::WEBPUSH_URGENCY_VERY_LOW);
        $webPushHeaders->buildFields();
        $webPushConfig->headers($webPushHeaders->getFields());

        $webPushHmsOptions = new WebPushHmsOptions();
        $webPushHmsOptions->link("https://www.huawei.com/");
        $webPushHmsOptions->buildFields();
        $webPushConfig->hmsOptions($webPushHmsOptions->getFields());

        $webPUshNotionfication = new WebPushNotification();
        $webPUshNotionfication->title("notication string");
        $webPUshNotionfication->body("web push body");
        $webPUshNotionfication->icon("https://developer-portalres-drcn.dbankcdn.com/system/modules/org.opencms.portal.template.core/resources/images/icon_Promotion.png");
        $webPUshNotionfication->image("https://developer-portalres-drcn.dbankcdn.com/system/modules/org.opencms.portal.template.core/resources/images/icon_Promotion.png");
        $webPUshNotionfication->lang("string");
        $webPUshNotionfication->tag("string");
        $webPUshNotionfication->badge("string");
        $webPUshNotionfication->dir("auto");
        $webPUshNotionfication->vibrate(array(
            1,
            2,
            3
        ));
        $webPUshNotionfication->renotify(false);
        $webPUshNotionfication->require_interaction(false);
        $webPUshNotionfication->silent(false);
        $webPUshNotionfication->timestamp(1545201266);
        $webPushNotificationAction = new WebPushNotificationAction();
        $webPushNotificationAction->title("string");
        $webPushNotificationAction->action("123");
        $webPushNotificationAction->icon("https://developer-portalres-drcn.dbankcdn.com/system/modules/org.opencms.portal.template.core/resources/images/icon_Promotion.png");
        $webPushNotificationAction->buildFields();
        $webPUshNotionfication->actions(array(
            $webPushNotificationAction->getFields()
        ));
        $webPUshNotionfication->buildFields();
        $webPushConfig->notification($webPUshNotionfication->getFields());
        $webPushConfig->buildFields();

        return $webPushConfig;
    }

    private function printLogMethodOperate($dataFlow, $functionName = "", $logLevel = "")
    {
        $logModule = Constants::HW_PUSH_LOG_PUSH_MSG_MODULE;

        if (empty($logLevel)) {
            $logLevel = Constants::HW_PUSH_LOG_INFO_LEVEL;
        }

        if (empty($functionName)) {
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . $dataFlow, $logLevel, $logModule);
        } else {
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . '[' . $functionName . ']' . $dataFlow, $logLevel, $logModule);
        }
    }

    private function createApplication($application_server)
    {
        $this->printLogMethodOperate("createApplication push_msg_type:".$this->push_msg_type, __FUNCTION__ . ':' . __LINE__,Constants::HW_PUSH_LOG_DEBUG_LEVEL);
        if ($this->push_msg_type == Constants::PUSHMSG_FASTAPP_MSG_TYPE){
            $this->printLogMethodOperate("createApplication PUSHMSG_FASTAPP_MSG_TYPE", __FUNCTION__ . ':' . __LINE__,Constants::HW_PUSH_LOG_DEBUG_LEVEL);
            $application = new Application($this->fast_appid, $this->fast_appsecret, $this->hw_token_server, $application_server);
            return $application;
        }
        $application = new Application($this->appid, $this->appsecret, $this->hw_token_server, $application_server);
        return $application;
    }

    private function getMessageByMsgType($msg_type)
    {
        switch ($msg_type) {
            case Constants::PUSHMSG_NOTIFICATION_MSG_TYPE:
                {
                    return $this->createNotificationMsg();
                }
            case Constants::PUSHMSG_PASS_THROUGHT_MSG_TYPE:
                {
                    return $this->createPassThroughMsg();
                }

            case Constants::PUSHMSG_FASTAPP_MSG_TYPE:
                {
                    return $this->createFastAppMsg();
                }
            case Constants::PUSHMSG_TOPIC_MSG_TYPE:
                {
                    return $this->createTopicMsg();
                }
            case Constants::PUSHMSG_CONDITION_MSG_TYPE:
                {
                    return $this->createConditionMsg();
                }

            case Constants::APN_MSG_TYPE:
                {
                    return $this->createApnsMsg();
                }
            case Constants::WEB_PUSH_MSG_TYPE:
                {
                    return $this->createWebPushMsg();
                }
        }
    }
    
    private function createFastAppConfigNotificationData(){
        $instanceAppConfig = new InstanceAppConfig();
        $instanceAppConfig->pushtype(0);
        
        $instanceAppPushbody = new InstanceAppPushbody();
        $instanceAppPushbody->title("test fast app");
        $instanceAppPushbody->description("test fast app description");
        $instanceAppPushbody->page("/");
        $instanceAppPushbody->params(array(
            "key1"=>"test1",
            "key2"=>"test2"
        ));
        
        $instanceAppRingtone = new InstanceAppRingtone();
        $instanceAppRingtone->breathLight(true);
        $instanceAppRingtone->vibration(true);
        $instanceAppRingtone->buildFields();
        
        $instanceAppPushbody->ringtone($instanceAppRingtone->getFields());
        $instanceAppPushbody->buildFields();
        
        $instanceAppConfig->pushbody($instanceAppPushbody->getFields());
        $instanceAppConfig->buildFields();
        
        return $instanceAppConfig;
        
    }
    
    private function createFastAppConfigPassThroughData(){
        $instanceAppConfig = new InstanceAppConfig();
        $instanceAppConfig->pushtype(1);
        
        $instanceAppPushbody = new InstanceAppPushbody();
        $instanceAppPushbody->messageId("111110001");
        $instanceAppPushbody->data("hw default passthroug test");
        $instanceAppPushbody->buildFields();
        
        $instanceAppConfig->pushbody($instanceAppPushbody->getFields());
        $instanceAppConfig->buildFields();
        
        return $instanceAppConfig;
        
    }

    private function createFastAppMsg()
    {
        $this->printLogMethodOperate("push msg notification start", __FUNCTION__ . ':' . __LINE__);
        $message = new PushMessage();

        $message->data($this->createFastAppConfigNotificationData()->getFields());

        $message->android($this->createAndroidConfig()
            ->getFields());
 
        $message->token(array(
            $this->fast_push_token
        ));

        $message->buildFields();
        $this->printLogMethodOperate("push msg notification end", __FUNCTION__ . ':' . __LINE__);
        return $message;
    }

    private function createNotificationMsg()
    {
        $this->printLogMethodOperate("push msg notification start", __FUNCTION__ . ':' . __LINE__);
        $message = new PushMessage();
        $message->android($this->createAndroidConfig()
            ->getFields());
        $message->notification($this->createNotification()
            ->getFields());
        // eoffice 修改
        $message->data(json_encode($this->message));
        $message->token($this->hw_push_token_key);

        $message->buildFields();
        $this->printLogMethodOperate("push msg notification end", __FUNCTION__ . ':' . __LINE__);
        return $message;
    }

    private function createTopicMsg()
    {
        $this->printLogMethodOperate("push msg createTopicMsg start", __FUNCTION__ . ':' . __LINE__);
        $message = new PushMessage();

        $message->android($this->createAndroidConfig()
            ->getFields());
        // $message->notification($this->createNotification()->buildFields());

        $message->topic($this->default_topic);

        $message->buildFields();
        $this->printLogMethodOperate("push msg createTopicMsg end", __FUNCTION__ . ':' . __LINE__);
        return $message;
    }

    private function createConditionMsg()
    {
        $this->printLogMethodOperate("push msg createTopicMsg start", __FUNCTION__ . ':' . __LINE__);
        $message = new PushMessage();

        $message->android($this->createAndroidConfig()
            ->getFields());
        // $message->notification($this->createNotification()->buildFields());
        $message->condition("'defaultTopic' in topics");
        // $message->condition("'weather' in topics || ('TopicB' in topics && 'TopicC' in topics)");

        $message->buildFields();
        $this->printLogMethodOperate("push msg createTopicMsg end", __FUNCTION__ . ':' . __LINE__);
        return $message;
    }

    private function createPassThroughMsg()
    {
        $this->printLogMethodOperate("push msg passthrough start", __FUNCTION__ . ':' . __LINE__);
        $message = new PushMessage();

        $message->data($this->message['content']);
        $message->token($this->hw_push_token_key);

        $message->buildFields();
        $this->printLogMethodOperate("push msg passthrough end", __FUNCTION__ . ':' . __LINE__);
        return $message;
    }

    private function createApnsMsg()
    {
        $this->printLogMethodOperate("push msg apns start", __FUNCTION__ . ':' . __LINE__);
        $message = new PushMessage();
        $apnsConfig = $this->createApnsConfig();
        $message->apns($apnsConfig->getFields());

        $message->token(array(
            $this->apn_push_token_key
        ));
        $message->buildFields();

        $this->printLogMethodOperate("push msg apns end", __FUNCTION__ . ':' . __LINE__);
        return $message;
    }

    private function createWebPushMsg()
    {
        $this->printLogMethodOperate("push msg webpush start", __FUNCTION__ . ':' . __LINE__);
        $message = new PushMessage();

        $message->webpush($this->createWebPush()
            ->getFields());
        $message->token(array(
            $this->webpush_push_token_key
        ));

        PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . '[web-token:' . json_encode($message->get_token()) . ']', Constants::HW_PUSH_LOG_DEBUG_LEVEL);

        $message->buildFields();

        $this->printLogMethodOperate("push msg webpush end", __FUNCTION__ . ':' . __LINE__);
        return $message;
    }
}

class TestTopicCommonSample
{

    private $topic = "defaultTopic";

    private $topic_msg_create_type = 1;

    private $appsecret;

    private $appid;

    private $hw_token_server;

    private $hw_topic_subscriber_server;

    private $hw_topic_unsubscriber_server;

    private $hw_topic_query_subscriber_server;

    private $tokenServerKey;

    private $log_suffix_show = ".............................";

    public function __construct($topic_value = "", $default_msg_create_type = "")
    {
        if (! empty($topic_value)) {
            $this->topic = $topic_value;
        }
        if (! empty($default_msg_create_type)) {
            $this->topic_msg_create_type = $default_msg_create_type;
        }

        $pushConfig = PushConfig::getSingleInstance();

        $this->appsecret = $pushConfig->HW_APPSECRET;
        $this->appid = $pushConfig->HW_APPID;
        $this->hw_token_server = $pushConfig->HW_TOKEN_SERVER;
        $this->tokenServerKey = $pushConfig->HW_PUSH_TOKEN_ARR;

        $this->hw_topic_subscriber_server = $pushConfig->HW_TOPIC_SUBSCRIBE_SERVER;
        $this->hw_topic_unsubscriber_server = $pushConfig->HW_TOPIC_UNSUBSCRIBE_SERVER;
        $this->hw_topic_query_subscriber_server = $pushConfig->HW_TOPIC_QUERY_SUBSCRIBER_SERVER;
    }

    private function createTopicData()
    {
        $topicMsg = new TopicMsg();
        $topicMsg->topic($this->topic);
        $topicMsg->tokenArray(array(
            $this->tokenServerKey
        ));
        $topicMsg->buildFields();

        return $topicMsg;
    }

    private function createApplication($application_server)
    {
        $application = new Application($this->appid, $this->appsecret, $this->hw_token_server, $application_server);
        return $application;
    }

    private function printLogMethodOperate($msg_type, $dataFlow, $functionName = "", $logLevel = "")
    {
        $dataFlow = 'subscribe topic ' . $dataFlow;
        $logModule = Constants::HW_PUSH_LOG_TOPIC_SUBSCRIBE_MODULE;
        switch ($msg_type) {
            case Constants::TOPIC_UNSUBSCRIBE_MSG_TYPE:
                {
                    $dataFlow = 'unsubscribe topic' . $dataFlow;
                    $logModule = Constants::HW_PUSH_LOG_TOPIC_UNSUBSCRIBE_MODULE;
                }
                break;
        }
        if (empty($logLevel)) {
            $logLevel = Constants::HW_PUSH_LOG_INFO_LEVEL;
        }

        if (empty($functionName)) {
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . $dataFlow . $this->log_suffix_show, $logLevel, $logModule);
        } else {
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . '[' . $functionName . ']' . $dataFlow . $this->log_suffix_show, $logLevel, $logModule);
        }
    }

    private function printLogMsgOperate($msg_type, $dataFlow, $functionName = "", $logLevel = "")
    {
        $logModule = Constants::HW_PUSH_LOG_TOPIC_SUBSCRIBE_MODULE;
        switch ($msg_type) {
            case Constants::TOPIC_UNSUBSCRIBE_MSG_TYPE:
                {
                    $logModule = Constants::HW_PUSH_LOG_TOPIC_UNSUBSCRIBE_MODULE;
                }
                break;
        }
        if (empty($logLevel)) {
            $logLevel = Constants::HW_PUSH_LOG_INFO_LEVEL;
        }

        if (empty($functionName)) {
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . $dataFlow . $this->log_suffix_show, $logLevel, $logModule);
        } else {
            PushLogConfig::getSingleInstance()->LogMessage('[' . __CLASS__ . ']' . '[' . $functionName . ']' . $dataFlow . $this->log_suffix_show, $logLevel, $logModule);
        }
    }

    /**
     * topic subscribe/unsubscribe
     */
    function sendTopicMessage($msg_type)
    {
        $this->printLogMethodOperate($msg_type, "start", __FUNCTION__ . ':' . __LINE__);
        $topicMsg = $this->createTopicData();
        if ($this->topic_msg_create_type == 1) {
            $this->printLogMsgOperate($msg_type, "topicMsg:" . json_encode($topicMsg->getFields()), __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_DEBUG_LEVEL);
        }

        $application_server = $this->hw_topic_subscriber_server;
        if ($msg_type == Constants::TOPIC_UNSUBSCRIBE_MSG_TYPE) {
            $application_server = $this->hw_topic_unsubscriber_server;
        } else if ($msg_type == Constants::TOPIC_SUBSCRIBE_QUERY_MSG_TYPE) {
            $application_server = $this->hw_topic_query_subscriber_server;
            $topicMsg = array(
                'token' => $this->tokenServerKey
            );
        }
        $application = $this->createApplication($application_server);
        $this->printLogMsgOperate($msg_type, "application server:" . json_encode($application->getApplicationFields()), __FUNCTION__ . ':' . __LINE__, Constants::HW_PUSH_LOG_DEBUG_LEVEL);

        $topicResult = "";
        if ($msg_type == Constants::TOPIC_SUBSCRIBE_QUERY_MSG_TYPE) {
            $topicResult = $application->common_send_msg($topicMsg);
        } else {
            $topicResult = $application->common_send_msg($topicMsg->getFields());
        }

        $this->printLogMethodOperate($msg_type, "end", __FUNCTION__ . ':' . __LINE__);
        return $topicResult;
    }
}

class TopicMsg
{

    // madatory
    private $topic;

    // madatory
    private $tokenArray;

    private $fields;

    public function __construct()
    {
        $this->fields = array();
    }

    public function topic($value)
    {
        $this->topic = $value;
    }

    public function tokenArray($value)
    {
        $this->tokenArray = $value;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function buildFields()
    {
        $keys = array(
            'topic',
            'tokenArray'
        );
        foreach ($keys as $key) {
            if (isset($this->$key)) {
                $this->fields[$key] = $this->$key;
            }
        }
    }
}


