<?php

require __DIR__ . '/../../bootstrap/app.php';

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;


$companyName = $_REQUEST['company_name'] ?? '';
if (is_string($companyName) && $companyName != '') {
    $result = request('https://service.e-office.cn/api/projects/oa', ['project_name' => $companyName], 'query', 'array', 'GET');
    if (is_array($result)) {
        echo json_encode($result);
        die;
    }
}

$empty = [
    'total' => 0,
    'list'  => []
];
echo json_encode($empty);

/**
 * @param $url
 * @param array $formParams
 * @param string $type 'form_params'|'multipart'|'body'
 * @param string $returnType 'array' | 'string'
 * @return array|bool
 * @throws \GuzzleHttp\Exception\GuzzleException
 */
function request($url, $formParams = [], $type = 'form_params', $returnType = 'array', $requestType = 'POST', $timeout = 300)
{
    $returnType = $returnType == 'string' ? 'string' : 'array';
    $clientParams = [
        'timeout' => $timeout
    ];
    $client = new Client($clientParams);
    try {
        $response = $client->request($requestType, $url, [
            $type    => $formParams,
            'verify' => false,
        ]);
        $code = $response->getStatusCode();
        if ($code == 200) {
            $body = $response->getBody();
            $stringBody = (string) $body;
            if ($returnType == 'array') {
                return json_decode($stringBody, true);
            }
            return $stringBody;
        }
    } catch (\Exception $e) {
        Log::info($e->getMessage());
    }
    return false;
}
