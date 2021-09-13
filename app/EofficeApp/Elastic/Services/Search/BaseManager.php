<?php


namespace App\EofficeApp\Elastic\Services\Search;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\BaseService;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder as ESClientBuilder;
use Illuminate\Support\Facades\Log;

class BaseManager extends BaseService
{
    /**
     * 创建索引
     *
     * @param array $documents
     *
     */
    public function create($documents)
    {
        $param['body'] = [];

        foreach ($documents as $document)
        {
            $param['body'][] = ['index' => $document['index']];
            $param['body'][] = $document['document'];
        }

        $response =  $this->client->bulk($param);

        return $response;
    }

    /**
     * 删除索引
     *
     * @param string $id
     *
     */
    public function delete($id)
    {
        if (!$id || !$this->alias) {
            return ['succeed' => 0];
        }

        try {
            $response = $this->client->delete([
                'index' => $this->alias,
                'type' => $this->type,
                'id' => $id,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            return json_decode($exception->getMessage());
        }

        return $response;
    }

    /**
     * 部分更新索引
     */
    public function update()
    {
        // TODO
    }
}