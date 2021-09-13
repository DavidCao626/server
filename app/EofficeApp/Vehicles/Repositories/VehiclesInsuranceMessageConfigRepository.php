<?php


namespace App\EofficeApp\Vehicles\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Vehicles\Entities\VehiclesInsuranceMessageConfigEntity;
use Illuminate\Support\Facades\Redis;

class VehiclesInsuranceMessageConfigRepository extends BaseRepository
{
    const VEHICLES_INSURANCE_NOTIFY_CONFIG = 'VEHICLES:INSURANCE:NOTIFY:CONFIG'; // redis中车辆提醒相关配置

    public function __construct(VehiclesInsuranceMessageConfigEntity $entity)
    {
        parent::__construct($entity);
    }
    /**
     * 获取车辆保险/年检通知配置详情
     *
     * @return array
     */
    public function getInsuranceNotifyConfig($type = 'insurance'): array
    {
        $configs = $this->entity->select('key','value')->where('type', $type)->get()->toArray();

        $data = [];

        foreach ($configs as $config) {
            $data[$config['key']] =  $config['value'];
        }

        return $data;
    }

    /**
     * 更新车辆保险/年检通知配置
     *
     * @param array $configs
     *
     * @return void
     */
    public function updateInsuranceNotifyConfig($configs, $type = 'insurance'): void
    {
        if (is_array($configs)) {
            $count = 0;
            foreach ($configs as $key => $value) {
                $incr = $this->entity->where('key', $key)->where('type', $type)->update(['value' => $value]);
                $count += $incr;
            }

            if ($count) {
                $this->refreshConfigCache();
            }
        }
    }

    /**
     * 获取对应配置
     */
    public function getConfigByValue($key, $type = 'insurance', $default = '')
    {
        $item = $this->entity->where('key', $key)->where('type', $type)->pluck('value')->toArray();

        return isset($item[0]) ? $item[0] : $default;
    }

    /**
     * 刷新提醒配置缓存
     *
     * @return array
     */
    public function refreshConfigCache(): array
    {
        $data = [
            'insurance_advance_time_notify_switch' => 0,
            'insurance_current_month_notify_switch' => 0,
            'insurance_next_month_notify_switch' => 0,
            'inspection_advance_time_notify_switch' => 0,
            'inspection_next_month_notify_switch' => 0,
            'insurance_advance_time_notify_config' => [],
            'insurance_current_month_notify_config' => [],
            'insurance_next_month_notify_config' => [],
            'inspection_advance_time_notify_config' => [],
            'inspection_next_month_notify_config' => [],
        ];
        $configData = $this->entity->select('type', 'key', 'value', 'notify_category', 'function_type')->get()->toArray();

        foreach ($configData as $item) {
            $key = $item['type'].'_'.$item['notify_category'].'_notify_'.$item['function_type'];

            if (isset($data[$key])) {
                if ($item['function_type'] === 'switch') {
                    $data[$key] = $item['value'];
                } else {
                    $data[$key][$item['key']] = $item['value'];
                }
            }
        }

        Redis::set(self::VEHICLES_INSURANCE_NOTIFY_CONFIG, json_encode($data));

        return $data;
    }
}