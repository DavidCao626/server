<?php

namespace App\EofficeApp\XiaoE\Traits;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Request;
use Illuminate\Support\Arr;
trait XiaoETrait
{

    private $config;

    /**
     * 模拟post请求
     * @param $url
     * @param array $data
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function httpPost($url, $data = [])
    {
        $guzzleClient = new Client();
        $response = $guzzleClient->request('POST', $url, [
            'form_params' => $data
        ]);
        $content = $response->getBody()->getContents();
        return json_decode($content, true);
    }

    /**
     * 模拟http请求
     * @param $url
     * @param array $data
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function sendRequest($method, $url, $options = [])
    {
        try {
            //不验证安全证书
            $options['verify'] = false;
            $guzzleClient = new Client();
            $response = $guzzleClient->request($method, $url, $options);
            $content = $response->getBody()->getContents();
            return json_decode($content, true);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * 读取配置
     * @param $key
     * @return bool|mixed
     */
    private function config($intention)
    {
        $defaultConfig = [
            'create_type' => '',              //创建流程类型，可选参数，传"sonFlow"时，表明是创建子流程,
            'instancy_type' => 0,             //紧急程度 0：正常、1：重要、2：紧急
            'agentType' => 2,
        ];
        $config = app($this->systemService)->getIntentionParams($intention);
        return array_merge($defaultConfig, $config);
    }

    /**
     * 获取用户信息own
     * @return mixed
     */
    private function getUserInfo()
    {
        return Cache::get($this->getTokenForRequest());
    }

    /**
     * 获取token
     * @return type
     */
    private function getTokenForRequest()
    {
        $token = Request::input('api_token');
        if (empty($token)) {
            $token = Request::bearerToken();
        }
        if (empty($token)) {
            $token = Request::getPassword();
        }
        return $token;
    }

    /**
     * 从返回的用户信息中提取对应的信息,提取角色，上级，下级等信息
     * @param $mainKey
     * @param $hasKey
     * @param $nameKey
     */
    private function getUserHas($user, $mainKey, $hasKey, $nameKey)
    {
        $return = '';
        if (isset($user[$mainKey]) && $user[$mainKey]) {
            $return = array();
            $many = $user[$mainKey];
            foreach ($many as $one) {
                $name = $one[$hasKey][$nameKey] ?? '';
                if ($name) {
                    $return[] = $name;
                }
            }
            $return = implode(',', $return);
        }
        return $return;
    }

    /**
     * 解析微博内容给小e展示
     * @param $blog
     */
    private function parseBlogContent($blog)
    {
        if (!isset($blog['diary_content']) || !isset($blog['plan_template'])) {
            return '未提交微博';
        }
        $template = $blog['plan_template'];
        $html = '';
        //微博模板一
        if ($template == 1) {
            $html = $blog['diary_content'];
        }
        //微博模板二和三解析
        if ($template == 2 || $template == 3) {
            $items = json_decode($blog['diary_content'], true);
            if (!$items) {
                return '';
            }
            foreach ($items as $item) {
                $html .= '<div>' . $item['title'] . '</div>';
                $reports = $item['reports'];
                foreach ($reports as $block) {
                    if ($template == 3) {
                        $html .= '<div>' . $block['time'] . '</div>';
                    }
                    $html .= '<li>' . $block['content'] . '</li>';
                }
            }
        }
        return $html;
    }

    /**
     * 判断一个数组中是否存在这些元素，并且不为空
     * @param $arr
     * @param $keys
     * @return bool
     */
    private function hasAll($arr, $keys)
    {
        foreach ($keys as $key) {
            if (!isset($arr[$key]) || empty($arr[$key])) {
                return false;
            }
        }
        return true;
    }

    private function defaultValue($value, $default = '未知')
    {
        if (!isset($value) || empty($value)) {
            return $default;
        } else {
            return $value;
        }
    }

    /**
     * 获取精简的日期
     * @param $date
     */
    private function getSimpleDate($date)
    {
        if (date('Y', strtotime($date)) == date('Y')) {
            return date('m-d', strtotime($date));
        } else {
            return date('Y-m-d', strtotime($date));
        }
    }

    /**
     * 获取某个月的最后一天
     * @param $year
     * @param $month
     * @return false|string
     */
    private function getMonthLastDay($year, $month, $isOnlyDay = false)
    {
        $date = $year . '-' . $month . '-01';
        $lastDate = date("Y-m-d", strtotime("last day of this month", strtotime($date)));
        if ($isOnlyDay) {
            return date('d', strtotime($lastDate));
        } else {
            return $lastDate;
        }
    }

    /**
     * 前端小e助手对应的展示方法的，生成对应的数据格式，这个方法是我的待办列表，可以用作通用的列表格式
     * @param $list
     * @param $relations
     */
    private function windowViewApproval($data, $relations, $answer = false)
    {
        $return = ['method' => 'windowViewApproval', 'list' => []];
        if (!is_array($data) || empty($data)) {
            return $return;
        }
        foreach ($data as $one) {
            foreach ($relations as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $item[$key] = $value;//直接传值
                } elseif (is_array($value)) {
                    if (is_object($one)) {
                        $item[$key] = $one->{$value[0]};
                    } elseif (is_array($one)) {
                        $item[$key] = Arr::get($one, $value[0]);
                    }
                } else {
                    $item[$key] = $value($one);//回调函数处理复杂的关系
                }
            }
            $return['list'][] = $item;
        }
        if ($answer) {
            $return['answer'] = $answer;
        }
        return $return;
    }

    /**
     * 处理流程未读消息
     *
     * @param array $processedData 已处理好的格式化数据
     * @param array $originalData 原始流程数据
     */
    public function processFlowDataUnreadStatus($processedData, $originalData)
    {
        // 处理未读消息小红点
        foreach ($originalData as $key => $datum) {
            $processedData['list'][$key]['isnew'] = !isset($datum['process_time']) || $datum['process_time'] ? 0 : 1; // process_time不为null则为已读
            $processedData['list'][$key]['id'] = $datum['run_id'];
        }

        return $processedData;
    }

    /**
     * 处理文档未读消息
     *
     * @param array $processedData 已处理好的格式化数据
     * @param array $originalData 原始流程数据
     */
    public function processDocumentDataUnreadStatus($processedData, $originalData)
    {
        // 处理未读消息小红点
        foreach ($originalData as $key => $datum) {
            $processedData['list'][$key]['isnew'] = !($datum->isView); // isView是否已查看, 与isnew相反
            $processedData['list'][$key]['id'] = $datum->document_id;
        }

        return $processedData;
    }

    /**
     * 处理新闻未读消息
     *
     * @param array $processedData 已处理好的格式化数据
     * @param array $originalData 原始流程数据
     */
    public function processNewsDataUnreadStatus($processedData, $originalData)
    {
        // 处理未读消息小红点
        foreach ($originalData as $key => $datum) {
            $processedData['list'][$key]['isnew'] = !($datum['readerExists']);
            $processedData['list'][$key]['id'] = $datum['news_id'];
        }

        return $processedData;
    }

    /**
     * 小e前端打开一个页面
     * @param $url
     * @return array
     */
    private function windowOpen($url)
    {
        $return = ['method' => 'windowOpen', 'url' => $url];
        return $return;
    }

    /**
     * 小e助手回答
     * @param $answer
     */
    private function windowAnswer($answer)
    {
        $return = ['method' => 'windowAnswer', 'answer' => $answer];
        return $return;
    }

    private function windowViewTable($title, $head, $body, $answer = '查询结果如下：')
    {
        $reportColors = ['#1673dd', '#6d3eda', '#0da634', '#db8a3b', '#019ffb', '#176bff', '#CC0033', '#339966', '#f04134', '#f26038'];
        //颜色不够
        $padColors = $reportColors;
        while ($title && count($title) > count($reportColors)) {
            $reportColors = array_merge($reportColors, $padColors);
        }
        $data = [
            'type' => 'Table',
            'answer' => $answer,
            'LineBlockList' => [],
            'tableHeaderList' => [],
            'list' => []
        ];
        if ($title) {
            for ($i = 0; $i < count($title); $i++) {
                $row = $title[$i];
                if (is_array($row)) {
                    $item = [
                        'title' => $row[0] ?? '',
                        'color' => $row[1] ?? '#ffffff',
                        'bgColor' => $row[2] ?? $reportColors[$i],
                    ];
                } else {
                    $item = [
                        'title' => $row,
                        'color' => '#ffffff',
                        'bgColor' => $reportColors[$i],
                    ];
                }
                $data['LineBlockList'][] = $item;
            }
        }
        if ($head && $body) {
            $data['tableHeaderList'] = array_values($head);
            foreach ($body as $item) {
                $row = array();
                if (is_array($item)) {
                    foreach ($head as $key => $value) {
                        $row[] = $item[$key] ?? '';
                    }
                } else {
                    foreach ($head as $key => $value) {
                        $row[] = $item->$key ?? '';
                    }
                }
                $data['list'][] = $row;
            }
        }
        $return = ['method' => 'windowViewTable', 'data' => $data];
        return $return;
    }

    /**
     * 打电话给某人
     * @param $userId
     * @param $phone
     */
    private function windowCall($phone)
    {
        $return = ['method' => 'windowCall', 'phone' => $phone];
        return $return;
    }

    /**
     * 获取用户头像
     * @param $userId
     */
    private function getUserAvatar($userId, $apiToken)
    {
        //判断是否使用上传头像
        if (get_system_param('default_avatar_type') == 2) {
            return '../../public/api/portal/eo/avatar/' . $userId . '?api_token=' . $apiToken;
        } else {
            $prefix = 'EO';
            $userIdCode = 0;
            $numberTotal = '';
            $reg = '/^[0-9]+.?[0-9]*$/';
            for ($i = 0; $i < strlen($userId); $i++) {
                $char = $userId[$i];
                if (preg_match($reg, $char)) {
                    $numberTotal .= $char;
                }
                $charAscii = $this->charCodeAt($userId, $i);
                $userIdCode += $charAscii;
            }
            $prefixCode = '';
            for ($i = 0; $i < strlen($prefix); $i++) {
                $charAscii = $this->charCodeAt($prefix, $i);
                $prefixCode .= $charAscii;
            }
            $numberTotalNumber = $numberTotal === '' ? 0 : intval($numberTotal);
            $img = $prefix . (($userIdCode * intval($prefixCode)) + $numberTotalNumber) . '.png';
            $accessPath = envOverload('ACCESS_PATH', 'access');
            if (!file_exists('./' . $accessPath . '/avatar/' . $img)) {
                return null;
            }
            return '../../public/' . $accessPath . 'avatar/' . $img;
        }
    }

    private function charCodeAt($str, $index)
    {
        $char = mb_substr($str, $index, 1, 'UTF-8');
        if (mb_check_encoding($char, 'UTF-8')) {
            $ret = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
            return hexdec(bin2hex($ret));
        } else {
            return null;
        }
    }
}
