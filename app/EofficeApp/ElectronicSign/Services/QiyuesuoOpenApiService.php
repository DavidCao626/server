<?php

namespace App\EofficeApp\ElectronicSign\Services;

use App\EofficeApp\Base\BaseService;

/**
 * 契约锁开放API接口 service
 */
class QiyuesuoOpenApiService extends BaseService
{
    /**
     * 构造函数  设置访问API地址 key和secert
     *
     * @param [type] $serverUrl
     * @param [type] $accessKey
     * @param [type] $accessSecret
     */
    public function __construct($serverUrl, $accessKey, $accessSecret, $serverType = 'private', $fileMaxSize = 10)
    {
        $this->serverUrl = $serverUrl; //. ':9082'
        $this->accessKey = $accessKey;
        $this->accessSecret = $accessSecret;
        $this->serverType = $serverType;
        $this->fileMaxSize = $fileMaxSize;
    }

	/**
	 * 获取签名并设置headers数组
	 *
	 * @return array
	 */
	public function getHeaders()
	{
		$time = $this->msec_time();
		$signature = md5(str_replace(' ', '', $this->accessKey . $this->accessSecret . $time));
		if ($this->serverType == 'private') {
			$headers = array(
				'x-qys-accesstoken:' . $this->accessKey,
				'x-qys-signature:' . $signature,
				'x-qys-timestamp:' . $time,
				'User-Agent: privateapp-java-client',
			);
		} else {
			$headers = array(
				'x-qys-open-accesstoken:' . $this->accessKey,
				'x-qys-open-signature:' . $signature,
				'x-qys-open-timestamp:' . $time,
			);
		}

		return $headers;
	}

	function msec_time()
	{
		list($msec, $sec) = explode(' ', microtime());
		$msectime = sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
		return $msectime;
	}

	/**
	 * 生成文档
	 *
	 * @param [type] $url
	 * @param [type] $params
	 */
	public function createDocument($url, $params)
	{
		return $this->sendRequest($url, $params);
	}
	/**
	 * 根据上传文档生成文档
	 *
	 * @param [type] $url
	 * @param [type] $params
	 * @return array
	 */
	public function createDocumentByFile($url, $params)
	{
		return $this->service($url, $params);
	}

	/**
	 * 创建合同
	 *
	 * @param [int] $flowId 定义流程ID
	 * @param [array] $data
	 * @return array
	 */
	public function createContract($url, $params)
	{
		$header[] = 'content-type:application/json';
		return $this->service($url, json_encode($params), $header);
	}

	/**
	 * 公共开放请求
	 *
	 * @param [type] $url     请求地址
	 * @param [type] $params  参数
	 * @param string $method  请求方法   POST GET  无参数时可不传为GET
	 *
	 * @return void
	 * @author yuanmenglin
	 * @since
	 */
	public function sendRequest($url, $params = [], $method = 'POST')
	{
	    $header = [];
		if ($method == 'GET') {
			$url = $this->getUrlHandle($url, $params);
			$params = [];
		} else if ($method == 'POST') {
			$privateUrl = ['/contract/create', '/contract/createbycategory', '/contract/send', '/seal/apply/multiple', '/seal/apply/mulitipleByCategory', '/contract/signbycompany', '/contract/signbylegalperson', '/contract/signbyperson', '/contract/signurl'];
			$publicUrl = ['/v2/contract/pageurl', '/v2/contract/send', '/v2/document/addbytemplate', '/v2/contract/companysign', '/v2/contract/legalpersonsign', '/v2/contract/invalid', '/v2/contract/appointurl',];
			if (in_array($url, array_merge($privateUrl, $publicUrl))) {
				$header[] = 'content-type:application/json';
				$params = json_encode($params);
			}
			if ($url == '/binary/signurl') {
//                $header[] = 'content-type:application/json';
			    $header[] = 'content-type:multipart/form-data';
			}
		} else {
			return false;
		}
		// dd($url, $params, $header);
		return $this->service($url, $params, $header);
	}

    /**
     * 请求处理
     *
     * @param string $serviceUrl
     * @param array $paramers
     * @return array
     */
    public function service($serviceUrl, $paramers = [], $heads = [])
    {
        $flag = 1;
        while ($flag <= 3) {
            $url = $this->serverUrl . $serviceUrl;
            $headers = $heads ? array_merge($this->getHeaders(), $heads) : $this->getHeaders();
            $oldResult = $this->getHttps($url, $headers, $paramers);
            if ($oldResult) {
                break;
            }
            $flag++;
        }
        if (strpos($serviceUrl, 'download') === false) {
            $result = json_decode($oldResult, true);
            if ($this->serverType == 'private') {
                if (!$result) {
                    if (strpos($oldResult, 'HTTP ERROR') !== false) {
                        \Log::error($oldResult);
                        return ['code' => ['request_param_error', 'electronicsign']];
                    } else {
                        return ['code' => ['request_error', 'electronicsign']];
                    }
                }
                //错误提示信息整理 和外部的错误提示数据格式统一
                if (isset($result['code'])) {
                    if (!$result['code']) {
                        $result['qys_code'] = $result['code'];
                        unset($result['code']);
                    } else {
                        $message = isset($result['message']) && !empty($result['message']) ? $result['message'] : trans('electronicsign.request_error');
                        return ['code' => ['', $message], 'dynamic' => $message];
                    }
                }
            } else {
                // 公有云报错处理
                if (isset($result['code'])) {
                    if (!$result['code']) {
                        $result['qys_code'] = $result['code'];
                        unset($result['code']);
                    } else {
                        //1801, 1802, 1601, 403, 440, 441, 442, 443, 444, 446, 447, 400
                        if (in_array($result['code'], [1001, 1002])) {
                            $errorMessage = trans('electronicsign.public_error_' . $result['code']) ?? '';
                        }
                        $message = $errorMessage ?? (isset($result['message']) ? $result['message'] : trans('electronicsign.request_error'));
                        return ['code' => ['', $message], 'dynamic' => $message];
                    }
                }
            }
        } else {
            $result = $oldResult;
        }
        return $result;
    }

	/**
	 * get组装路由
	 *
	 * @param [type] $url
	 * @param [type] $params
	 * @return string
	 */
	private function getUrlHandle($url, $params)
	{
		if ($params) {
			$url .= '?';
			foreach ($params as $k => $param) {
				// 汉字不用url_encode请求失败
				$url .= $k . '=' . urlencode($param) . '&';
			}
			$url = substr($url, 0, -1);
		}
		return $url;
	}

	/**
	 * getHttps
	 * 函数的含义说明
	 *
	 * @access public
	 * @param $url 必填 API地址
	 * @param $heads 发送头信息 为空
	 * @param $data post方式下需要,需要urldecode
	 * @version     1.0
	 * @return array
	 */
	public function getHttps($url, $heads, $data = null)
	{
		try {
			if (function_exists('curl_init')) {
				$ssl = substr($url, 0, 8) == "https://" ? true : false;
				$curl = curl_init();
				if ($ssl) {
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				}
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 上传文件过大时会10s超时 设为30S
                /**
                 * 当遇到下载时 文件过大导致超时下载失败，调整下载时超时时间设为5分钟 20210419
                 */
                if (strpos($url, 'download') !== false) {
                    curl_setopt($curl, CURLOPT_TIMEOUT, 300);
                }
				curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

				if ($data) {
					curl_setopt($curl, CURLOPT_POST, 1); //启用时会发送一个常规的POST请求，类型为：application/x-www-form-urlencoded，就像表单提交的一样。
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data); //全部数据使用HTTP协议中的"POST"操作来发送。
				}
				curl_setopt($curl, CURLOPT_HTTPHEADER, $heads);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //设定是否显示头信息

				$output = curl_exec($curl);
				if ($output === false) {
					$res = array(
						"code" => 1001,
						"message" => curl_error($curl),
					);
					if (strpos(strtolower($res['message']), 'could not resolve host') !== false) {
						$res['message'] = trans('electronicsign.link_address_failed');
					}
					$output = json_encode($res);
				}
				curl_close($curl);
			} else {
				$res = array(
					"code" => 1001,
					"message" => "CURL扩展没有开启",
				);
				$output = json_encode($res);
			}
		} catch (\Exception $exc) {
			$res = array(
				"code" => 1001,
				"message" => $exc->getTraceAsString(),
			);
			$output = json_encode($res);
		}
		return $output;
	}
}
