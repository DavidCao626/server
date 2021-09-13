<?php

namespace App\EofficeApp\XiaoE\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\XiaoE\Entities\XiaoESystemParamsEntity;

class XiaoESystemParamsRepository extends BaseRepository
{
    const EXTEND_DICTIONARY = 'extend_dictionary';    // 小E词典

    public function __construct(XiaoESystemParamsEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取小E系统参数
     *
     * @param string $key
     * @return string|null
     */
    public function get($key)
    {
        return $this->entity->where('key', $key)->first()->value ?? null;
    }

    /**
     * 设置小E系统参数
     *
     * @param string $key
     * @param string $value
     */
    public function set($key, $value)
    {
        $instance = $this->entity->where('key', $key)->first();
        if (!is_null($instance)) {
            $instance->value = $value;
            return $instance->save();
        } else {
            return $this->entity->create(['key' => $key, 'value' => $value]);
        }
    }
}
