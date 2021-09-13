<?php

namespace EassistantClient;

use EassistantClient\Utils\Utils;
use EassistantClient\Utils\Request;

/**
 * 小e助手
 * Class Eassistant
 */
class  Eassistant
{
    /**
     * AppId
     * @var
     */
    private $appId;
    /**
     * AppSecret
     * @var
     */
    private $appSecret;
    /**
     * 当前用户
     * @var
     */
    private $user;
    /**
     * 当前平台
     * @var
     */
    private $platform;
    /**
     * 设备
     * @var
     */
    private $deviceType;
    /**
     * 当前主题
     * @var
     */
    private $theme;
    /**
     * token
     * @var
     */
    private $apiToken;
    /**
     * 前一页的url
     * @var
     */
    private $fromUrl;
    /**
     * 简单封装的请求类
     * @var
     */
    private $request;

    public function __construct()
    {
        /**
         * 请求类
         */
        $this->request = new Request();
        /**
         * 配置文件
         */
        $config = $this->getConfig();
        $this->appId = $config['appId'] ?? false;
        $this->appSecret = $config['appSecret'] ?? false;
        /**
         * 一些必要的参数
         */
        $this->platform = $this->request->get('platform', 'app');
        $this->deviceType = $this->request->get('device_type');
        $this->fromUrl = $this->request->get('fromUrl');
        $this->apiToken = $this->request->get('api_token');
        $this->theme = $this->request->get('theme', 3);
        $this->user = Utils::getUser($this->apiToken);
    }

    /**
     * 运行
     */
    public function run()
    {
        if (!$this->validate()) {
            return Utils::errorResponse($this->fromUrl);
        }
        //验证通过执行主要方法
        return $this->index();
    }

    /**
     * 入口方法
     */
    private function index()
    {
        //根据环境加载sdk
        $debug = envOverload('XIAOE_DEBUG', 0);//是否打开调试模式，0不打开，1打开测试服，2打开测试服且显示控制台调试
        $dir = 'sdk' . DIRECTORY_SEPARATOR . ($debug ? 'dev' : 'official');//sdk里面会使用该变量，需定义
        $sdk = $dir . DIRECTORY_SEPARATOR . 'index.php';
        //视图模板里面需要的数据，统一放在data里面后期好维护
        $data['timestamp'] = time();
        $data['sign'] = $this->getSignature($this->appId, $this->appSecret, $this->user['user_id'], $data['timestamp']);
        $data['sdk'] = $sdk;
        $data['app_id'] = $this->appId;
        $data['app_secret'] = $this->appSecret;
        $data['user_id'] = $this->user['user_id'];
        $data['api_token'] = $this->apiToken;
        $data['platform'] = $this->platform;
        $data['fromUrl'] = $this->fromUrl;
        //$data['theme'] = $this->theme;
        $data['theme'] = 3;//先屏蔽主题功能
        $data['scripts'] = $this->getExtendScripts();//二开脚本
        $data['style'] = $this->getStyle($data['theme']);//主题样式
        $data['console'] = envOverload('XIAOE_CONSOLE', false);
        return include_once __DIR__ . DIRECTORY_SEPARATOR . 'template.php';
    }

    /**
     * 初始化之前做一些验证
     */
    private function validate()
    {
        if (!$this->appId || !$this->appSecret || !$this->user) {
            return false;
        }
        return true;
    }

    /**
     * 获取配置
     */
    private function getConfig()
    {
        return app('App\EofficeApp\XiaoE\Services\SystemService')->getSecretInfo();
    }

    /**
     * 签名
     */
    private function getSignature($appId, $appSecret, $userId, $timestamp)
    {
        return md5($appId . $timestamp . $userId . $appSecret);
    }

    /**
     * 主题样式
     * @param $themeIndex
     * @return array
     */
    private function getStyle($themeIndex)
    {
        $themes = ['52,67,110', '3,101,146', '34,78,153', '154,12,11', '0,0,0', '7,89,40', '132,132,132'];
        $color = $themes[$themeIndex - 1] ?? $themes[3];
        $colorHex = Utils::rgbToHex($color);
        return [$color, $colorHex, $themeIndex];
    }

    /**
     * 加载二次开发js
     * @return array
     */
    public function getExtendScripts()
    {
        $scripts = array();
        foreach (glob('./extend/*', GLOB_ONLYDIR) as $path) {
            $bootJs = $path . '/client/boot.js';
            if (file_exists($bootJs)) {
                $scripts[] = $bootJs;
            }
        }
        return $scripts;
    }
}

session_start();

require_once __DIR__ . '/../../bootstrap/app.php';
require_once __DIR__ . '/main/utils/utils.php';
require_once __DIR__ . '/main/utils/request.php';

$eassistant = new Eassistant();
$eassistant->run();
?>
