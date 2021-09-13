<?php


namespace App\EofficeApp\Elastic\Services\Dictionary;


use App\EofficeApp\Elastic\Configurations\ElasticTables;
use App\EofficeApp\Elastic\Repositories\ElasticDicExtensionRepository;
use App\EofficeApp\Elastic\Repositories\ElasticDicSynonymRepository;
use App\EofficeApp\Elastic\Services\BaseService;
use App\EofficeApp\Elastic\Services\Log\LogService;
use App\EofficeApp\Elastic\Utils\FileHandling;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SynonymDictionaryService extends BaseService
{
    use DictionaryTrait;

    /**
     * @var ElasticDicSynonymRepository $repository
     */
    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = app('App\EofficeApp\Elastic\Repositories\ElasticDicSynonymRepository');
    }

    /**
     * 获取同义词词典
     *
     * @return array
     */
    public function getSynonymDictionary(): array
    {
        // 获取同义词词典文件
        $file = $this->getSynonymDictionaryFile();
        $content = $this->getFileContent($file);
        // 字符串按换行符转为数组
        $tokens = $this->stringConvertToArrByEOL($content);

//        array_walk($tokens, function (&$value) {
//            // 字符串逗号转为数组
//            $value = $this->stringConvertToArrByComma($value);
//        });

        return $tokens;
    }

    /**
     * 配置同义词词典
     *
     */
    public function configSynonymDictionary(array $tokens): void
    {
        /**
         * 目标格式为
         *  a1,a2,a3
         *  b1,b2,b3
         * 其中a1,a2,a3为同义词, b1,b2,b3为同义词
         */
        $content = $this->arrayConvertToStringByEOL($tokens);
        $filePath = $this->getSynonymDictionaryFile();

        // 判断是否为utf-8编码, 需以UTF-8编码写入
        if (FileHandling::isEncodedByUTF8($filePath)) {
            file_put_contents($filePath, $content);
        } else {
            FileHandling::putFileContent($filePath, $content, FileHandling::ENCODING_BY_UTF8);
        }

    }

    /**
     * 初始化数据库, 将词典中同义词更新到数据库中
     */
    public function initializedDatabase(): void
    {
        $initData = $this->getSynonymDictionary();

        array_walk($initData, function (&$token) {

            $token = ['new_words' => $token, 'operation' => ElasticTables::OPERATION_ADD];
        });

        $this->repository->initSynonymDic($initData);
    }

    /**
     * 从数据库中获取同义词
     *
     * @param Request $request
     *
     * @return array
     */
    public function getSynonymWords(Request $request): array
    {
        $initParams = $this->parseParams($request->all());
        $params = [];
        $orders = $initParams['order_by'] ?? [];
        $params['fields'] = ['id', 'new_words', 'created_at', 'updated_at', 'operator'];
        $params['page'] = $initParams['page'] ?? 1;
        $params['limit'] = $initParams['limit'] ?? 20;

        // 去除起始页参数, TODO 参考其他列表 统一列表形式
//        unset($params['autoFixPage']);
//        unset($params['order_by']);

        // 获取关联用户名
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
     * 更新数据库中同义词
     *
     * @param Request $request
     * @param array $own
     *
     * @return array
     */
    public function updateSynonymWords(Request $request, $own): array
    {
        $wordsId = $request->request->getInt('wordsId');
        $newWords = $request->request->get('newWords');
        $userId = $own['user_id'] ?? 0;

        if (!$newWords) {
            return ['code' =>[ '0x055003', 'elastic']];
        }

        $record = $this->repository->getDetail($wordsId);

        // 同义词必须大于一个
        $newWordsArr = explode(',', $newWords);
        if (count($newWordsArr) <= 1) {
            return ['code' =>[ '0x055006', 'elastic']];
        }

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

            $this->repository->updateSynonym($data, $where, false);

            // 将修改写入操作日志
            $logService->addEditSynonymLog($userId, $data['old_words'], $data['new_words']);
        } else {
            $data['operation'] = ElasticTables::OPERATION_ADD;
            $data['created_at'] = Carbon::now()->toDateTimeString();
            $this->repository->insertData($data);

            // 将修改写入操作日志
            $logService->addNewSynonymLog($userId, $data['new_words']);
        }

        // 同步同义词
        $this->syncSynonymWords();

        return [];
    }

    /**
     * 删除同义词
     *
     * @param Request $request
     * @param array $own
     *
     * @return array
     */
    public function removeSynonymWords(Request $request, $own): array
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
            $logService->addRemoveSynonymLog($userId,  $words['words']);
            // 同步同义词
            $this->syncSynonymWords();
        } else {
            return ['code' => ['0x055004', 'elastic']];
        }

        return [];
    }

    /**
     * 恢复已删除同义词
     *
     * @param Request $request
     * @param array $own
     */
    public function restoreSynonymWords(Request $request, $own): void
    {
        $wordsId = $request->request->get('wordsId');
        $userId = $own['user_id'] ?? 0;

        $this->repository->restoreWords($userId, $wordsId);
        // 同步同义词
        $this->syncSynonymWords();
    }

    /**
     * 同步同义词典
     */
    public function syncSynonymWords(): void
    {
        // 获取获取库中全部同义词
        $tokens = $this->repository->getList(['fields' => ['new_words']]);
        $tokens = array_column($tokens, 'new_words');

        // 同步本地文件
        $this->configSynonymDictionary($tokens);

        // TODO 记录同步日志
    }
}