<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\SystemLog;


use App\EofficeApp\Address\Entities\AddressPrivateEntity;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\EofficeApp\Elastic\Services\Search\GlobalSearch\AddressPrivate\AddressPrivateManager;
use App\EofficeApp\Elastic\Utils\Filter;
use App\EofficeApp\System\Log\Entities\LogEntity;
use App\EofficeApp\System\Log\Entities\SystemDepartmentLogEntity;
use App\EofficeApp\System\Log\Entities\SystemFlowLogEntity;
use App\EofficeApp\System\Log\Entities\SystemLoginLogEntity;
use App\EofficeApp\System\Log\Entities\SystemUserLogEntity;
use App\EofficeApp\System\Log\Entities\SystemWebhookLogEntity;
use Illuminate\Support\Facades\Log;

class SystemLogBuilder extends BaseBuilder
{
    /**
     * @param AddressPrivateEntity $entity
     */
    public $entity;

    /**
     * @param AddressPrivateManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = [
            'App\EofficeApp\System\Log\Entities\LogEntity',
            'App\EofficeApp\System\Log\Entities\SystemLoginLogEntity',
            'App\EofficeApp\System\Log\Entities\SystemDepartmentLogEntity',
            'App\EofficeApp\System\Log\Entities\SystemFlowLogEntity',
            'App\EofficeApp\System\Log\Entities\SystemUserLogEntity',
            //'App\EofficeApp\System\Log\Entities\SystemWebhookLogEntity', 业务平台无此数据表
        ];
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\SystemLog\SystemLogManager';
        $this->alias = Constant::SYSTEM_LOG_ALIAS;
    }

    /**
     * 获取对应的entity
     *
     * @param int|string $id
     *
     * @return LogEntity|SystemLoginLogEntity|SystemUserLogEntity|SystemFlowLogEntity|SystemDepartmentLogEntity|SystemWebhookLogEntity|null
     */
    public function getRebuildEntity($id)
    {
        $log = null;
        $entity = '';
        $paramsArr = explode(':', $id);

        if (isset($paramsArr[0]) && isset($paramsArr[1])){
            $tableId = $paramsArr[1];
            switch ($paramsArr[0]) {
                case 'system_log':
                    $entity = 'App\EofficeApp\System\Log\Entities\LogEntity';
                    break;
                case 'system_user_log':
                    $entity = 'App\EofficeApp\System\Log\Entities\SystemUserLogEntity';
                    break;
                case 'system_flow_log':
                    $entity = 'App\EofficeApp\System\Log\Entities\SystemFlowLogEntity';
                    break;
                case 'system_login_log':
                    $entity = 'App\EofficeApp\System\Log\Entities\SystemLoginLogEntity';
                    break;
                case 'system_department_log':
                    $entity = 'App\EofficeApp\System\Log\Entities\SystemDepartmentLogEntity';
                    break;
                case 'system_webhook_log':
                    $entity = 'App\EofficeApp\System\Log\Entities\SystemWebhookLogEntity';
                    break;
                default:
                    // do nothing
            }

            if ($entity) {
                $logEntity = app($entity);
                $log = $logEntity->where('log_id', $tableId)->first();
            }
        }

        return $log;
    }

    /**
     * 生成索引信息
     *
     * @return array
     */
    public function generateDocument($logEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            // 部分操作者已注销
            $creator = $logEntity->hasOneUser()->withTrashed()->first();
            $table = $logEntity->table;
            $type = $logEntity->log_type;
            $logTypeName = $this->getLogTypeName($table, $type);

            $document = [
                'log_id' => $logEntity->log_id,
                'log_creator' => $creator ? $creator->user_name : '',
                'log_type' => $logEntity->log_type,
                'log_type_name' => $logTypeName,
                'log_relation_table' => $logEntity->log_relation_table,
                'log_relation_id' => $logEntity->log_relation_id,
                'log_ip' => $logEntity->log_ip,
                'log_content' => Filter::emojiFilter(Filter::htmlFilter($logEntity->log_content)),
                'create_time' => $logEntity->log_time,
                'category' => Constant::SYSTEM_LOG_CATEGORY,
            ];
            $document['priority'] = self::getPriority(Constant::SYSTEM_LOG_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $table.':'.$logEntity->log_id,
            ];

            $param['document'] = $document;

            return $param;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return [];
        }
    }

    /**
     * 获取日志类型
     *
     * @param string $table
     * @param string $type
     *
     * @return string
     */
    private function getLogTypeName(string $table, string $type): string
    {

        $tableArr = explode('_', $table);
        $filterArr = ['system', 'log'];
        $categoryArr = array_diff($tableArr, $filterArr);
        $category = implode('', $categoryArr);
        if ($type == 'sunflow') {
            $type = 'sunFlow';
        }

        $trans = 'systemlog.'.$category.$type;
        $logTypeName = trans($trans);

        return $logTypeName;
    }
}