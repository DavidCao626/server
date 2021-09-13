<?php


namespace App\EofficeApp\Elastic\Services\Dictionary;

use App\EofficeApp\Elastic\Configurations\ElasticTables;
use App\EofficeApp\Elastic\Repositories\ElasticDicExtensionRepository;
use App\EofficeApp\Elastic\Services\BaseService;
use App\EofficeApp\Elastic\Services\Log\LogService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExtensionDictionaryService extends BaseService
{
    use DictionaryTrait;

    /**
     * @var ElasticDicExtensionRepository $repository
     */
    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = app('App\EofficeApp\Elastic\Repositories\ElasticDicExtensionRepository');
    }

    /**
     * 初始化数据库, 将词典中扩展词更新到数据库中
     */
    public function initializedDatabase(): void
    {
        $initData = $this->getExtensionDictionary();

        array_walk($initData, function (&$token) {
            $token = ['new_words' => $token, 'operation' => ElasticTables::OPERATION_ADD];
        });

        $this->repository->initExtensionDic($initData);
    }

    /**
     * 获取扩展词典
     *
     * @return array
     */
    public function getExtensionDictionary(): array
    {
        // 获取扩展词典文件
        $file = $this->getExtensionDictionaryFile();
        $content = $this->getFileContent($file);
        // 将字符串根据换行符转为数组
        $tokens = $this->stringConvertToArrByEOL($content);

        return $tokens;
    }

    /**
     * 配置扩展词典
     *
     */
    public function configExtensionDictionary($tokens): void
    {
        $content = implode(PHP_EOL, $tokens);

        // 获取扩展词典文件
        $file = $this->getExtensionDictionaryFile();

        file_put_contents($file, $content);
    }

    /**
     * 从数据库中获取扩展词
     *
     * @param Request $request
     *
     * @return array
     */
    public function getExtensionWords(Request $request): array
    {
        $initParams = $this->parseParams($request->all());
        $params = [];
        $orders = $initParams['order_by'] ?? [];
        $params['fields'] = ['id', 'new_words', 'created_at',  'updated_at', 'operator'];
        $params['page'] = $initParams['page'] ?? 1;
        $params['limit'] = $initParams['limit'] ?? 20;

        $relation = [
            'table' => 'user',
            'primaryId' => 'user_id',
            'fields' => 'user_name'
        ];

        $data = $this->repository->getWordsPageList($params, $orders, $relation);

        array_walk($data['list'], function (&$token) {

            if (isset($token['user']['user_name'])) {
                $token['user'] = $token['user']['user_name'];
            } else {
                $token['user'] = '';
            }
        });

        return $data;
    }

    /**
     * 更新数据库中扩展词
     *
     * @param Request $request
     * @param array $own
     *
     * @return array
     */
    public function updateExtensionWords(Request $request, $own): array
    {
        $wordsId = $request->request->getInt('wordsId');
        $newWords = $request->request->get('newWords');
        $userId = $own['user_id'] ?? 0;

        if (!$newWords) {
            return ['code' =>[ '0x055002', 'elastic']];
        }

        $record = $this->repository->getDetail($wordsId);

        $data = [
            'new_words' => $newWords,
            'operator' => $userId
        ];
        /** @var LogService $logService */
        $logService = app('App\EofficeApp\Elastic\Services\Log\LogService');

        if ($record) {
            $data['old_words'] = $record['new_words'];
            $data['operation'] = ElasticTables::OPERATION_UPDATE;
            $data['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');
            $where = ['id' => $wordsId];
            $this->repository->updateExtension($data, $where, false);

            // 将修改写入操作日志
            $logService->addEditExtensionLog($userId, $data['old_words'], $data['new_words']);
        } else {
            $data['operation'] = ElasticTables::OPERATION_ADD;
            $data['created_at'] = Carbon::now()->toDateTimeString();
            $this->repository->insertData($data);

            // 将修改写入操作日志
            $logService->addNewExtensionLog($userId, $data['new_words']);
        }

        // 同步扩展词词典
        $this->syncExtensionWords();

        return [];
    }

    /**
     * 新增扩展词
     *
     * @param Request $request
     * @param array $own
     */
    public function addExtensionWords(Request $request, $own): void
    {
        $newWords = $request->request->get('newWords');
        $userId = $own['user_id'] ?? 0;

        $data = [
            'new_words' => $newWords,
            'operator' => $userId,
            'operation' =>  ElasticTables::OPERATION_ADD,
            'created_at' => Carbon::now()->toDateTimeString(),
        ];

        $this->repository->insertData($data);
    }

    /**
     * 删除扩展词
     *
     * @param Request $request
     * @param array $own
     *
     * @return array
     */
    public function removeExtensionWords(Request $request, $own): array
    {
        $wordsId = $request->query->getInt('wordsId');
        $userId = $own['user_id'] ?? 0;

        $words = $this->repository->getWords($wordsId);

        if ($words['exists']) {
            $data = [
                'operator' => $userId,
                'operation' => ElasticTables::OPERATION_DELETE,
                'deleted_at' => new \DateTime(),
            ];

            $where = ['id' => $wordsId];

            $this->repository->updateData($data, $where);

            /** @var LogService $logService */
            $logService = app('App\EofficeApp\Elastic\Services\Log\LogService');
            // 将修改写入操作日志
            $logService->addRemoveExtensionLog($userId, $words['words']);

            // 同步扩展词
            $this->syncExtensionWords();
        } else {
            return ['code' => ['0x055004', 'elastic']];
        }

        return [];
    }

    /**
     * 恢复扩展词
     *
     * @param Request $request
     * @param array $own
     */
    public function restoreExtensionWords(Request $request, $own): void
    {
        $wordsId = $request->request->get('wordsId');
        $userId = $own['user_id'] ?? 0;

        $this->repository->restoreWords($userId, $wordsId);

        // 同步扩展词
        $this->syncExtensionWords();
    }

    /**
     * 同步同义词典
     */
    public function syncExtensionWords(): void
    {
        // 获取获取库中全部同义词
        $tokens = $this->repository->getList(['fields' => ['new_words']]);
        $tokens = array_column($tokens, 'new_words');

        // 同步本地文件
        $this->configExtensionDictionary($tokens);
    }
}