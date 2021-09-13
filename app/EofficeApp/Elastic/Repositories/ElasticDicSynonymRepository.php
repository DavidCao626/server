<?php


namespace App\EofficeApp\Elastic\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Elastic\Configurations\ElasticTables;
use App\EofficeApp\Elastic\Entities\ElasticDicSynonymEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ElasticDicSynonymRepository extends ElasticBaseRepository
{
    /**
     * 同义词词典
     *
     * @param \App\EofficeApp\Elastic\Entities\ElasticDicSynonymEntity $entity
     *
     */
    public function __construct(ElasticDicSynonymEntity $entity )
    {
        parent::__construct($entity);
    }

    /**
     * 初始化同义词词典
     *
     * @param array $initData
     * @param bool $force
     */
    public function initSynonymDic(array $initData, bool $force = false): void
    {
        $row = $this->entity->first();

        // 数据库中无数据 或者 强制使用 则可初始化
        if (!$row || $force) {
            try {
                $this->entity->truncate();
                DB::table(ElasticTables::ELASTIC_DIC_SYNONYM_TABLE)->insert($initData);
            } catch (\Exception $exception) {
                Log::error($exception->getMessage());
            }
        }
    }

    /**
     * 获取指定同义词
     *
     * @param int $wordsId
     *
     * @return array
     */
    public function getWords($wordsId): array
    {
        $wordsRow = [
            'exists' => false,
            'words' => '',
        ];
        $words = $this->entity->find($wordsId);

        if ($words) {
            $wordsRow['exists'] = true;
            $wordsRow['words'] = $words->new_words;
        }

        return $wordsRow;
    }

    /**
     * 更新数据
     *
     * @param array $data 更新数据
     * @param array $where 更新条件
     * @param bool  $isFileter 是否过滤自动
     * @param array $filter 过滤字段
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2015-11-05
     */
    public function updateSynonym(array $data, array $where, bool $isFilter = true, array $filter = [])
    {
        if ($isFilter) {
            $data = $this->filterUpdateData($data, $filter);
        }

        try {
            if (count($where) == count($where, COUNT_RECURSIVE)) {
                return (bool)$this->entity->where($where)->update($data);
            } else {
                return (bool)$this->entity->wheres($where)->update($data);
            }
        } catch (\Exception $e) {
            return sql_error($e->getCode(), $e->getMessage());
        }
    }
}