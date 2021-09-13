<?php


namespace App\EofficeApp\Elastic\Services\MessageQueue;

use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Configurations\ElasticTables;
use App\EofficeApp\Elastic\Configurations\ConfigOptions;
use App\Jobs\Elasticsearch\ElasticsearchJob;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * es消息队列发生器
 *  将要通过消息队列发送的消息在这里封装实现
 *  TODO 增加队列优先级 需配置单独队列处理索引
 */
class ElasticsearchProducer
{
    /**
     * 发送日志相关消息
     *
     * @param string $table
     * @param int $id
     */
    public static function sendGlobalSearchSystemLogMessage($table, $id): void
    {

        $primaryKey = $table.':'.$id;
        // 日志暂不处理
        // self::sendGlobalSearchMessage(Constant::SYSTEM_LOG_CATEGORY, $primaryKey);
    }

    /**
     * 发送流程相关消息
     *
     * @param int $id
     */
    public static function sendGlobalSearchFlowMessage($id): void
    {
        self::sendGlobalSearchMessage(Constant::FLOW_CATEGORY, $id);
    }

    /**
     * 发送文档相关消息
     *
     * @param int|array $ids
     */
    public static function sendGlobalSearchDocumentMessage($ids): void
    {
        if (is_array($ids)) {
            foreach ($ids as $id) {
                self:self::sendGlobalSearchDocumentMessage($id);
            }
        } else {
            self::sendGlobalSearchMessage(Constant::DOCUMENT_CATEGORY, $ids);
        }
    }

    /**
     * 发送邮件相关消息
     *
     * @param int|array $ids
     */
    public static function sendGlobalSearchEmailMessage($ids): void
    {
        if (is_array($ids)) {
            foreach ($ids as $id) {
                self:self::sendGlobalSearchEmailMessage($id);
            }
        } else {
            self::sendGlobalSearchMessage(Constant::EMAIL_CATEGORY, $ids);
        }
    }

    /**
     * 发送客户相关消息
     *
     * @param int|array
     */
    public static function sendGlobalSearchCustomerMessage($ids): void
    {
        if (is_array($ids)) {
            foreach ($ids as $id) {
                self:self::sendGlobalSearchCustomerMessage($id);
            }
        } else {
            self::sendGlobalSearchMessage(Constant::CUSTOMER_CATEGORY, $ids);
        }
    }

    /**
     * 发送联系记录相关消息
     *
     * @param int|array
     */
    public static function sendGlobalSearchContactRecordMessage($ids): void
    {
        if (is_array($ids)) {
            foreach ($ids as $id) {
                self:self::sendGlobalSearchContactRecordMessage($id);
            }
        } else {
            self::sendGlobalSearchMessage(Constant::CUSTOMER_CONTACT_RECORD_CATEGORY, $ids);
        }
    }



    /**
     * 发送客户联系人相关消息
     *
     * @param int|array $ids
     */
    public static function sendGlobalSearchCustomerLinkmanMessage($ids): void
    {
        if (is_array($ids)) {
            foreach ($ids as $id) {
                self:self::sendGlobalSearchCustomerLinkmanMessage($id);
            }
        } else {
            self::sendGlobalSearchMessage(Constant::CUSTOMER_LINKMAN_CATEGORY, $ids);
        }
    }

    /**
     * 发送客户业务机会相关消息
     *
     * @param int|array $ids
     */
    public static function sendGlobalSearchCustomerBusinessChanceMessage($ids): void
    {
        if (is_array($ids)) {
            foreach ($ids as $id) {
                self:self::sendGlobalSearchCustomerBusinessChanceMessage($id);
            }
        } else {
            self::sendGlobalSearchMessage(Constant::CUSTOMER_BUSINESS_CHANCE_CATEGORY, $ids);
        }
    }

    /**
     * 发送客户合同相关消息
     *
     * @param int|array $ids
     */
    public static function sendGlobalSearchCustomerContractMessage($ids): void
    {
        if (is_array($ids)) {
            foreach ($ids as $id) {
                self:self::sendGlobalSearchCustomerContractMessage($id);
            }
        } else {
            self::sendGlobalSearchMessage(Constant::CUSTOMER_CONTRACT_CATEGORY, $ids);
        }
    }

    /**
     * 发送客户提醒相关消息
     *
     * @param int|array $ids
     */
    public static function sendGlobalSearchCustomerWillVisitMessage($ids): void
    {
        if (is_array($ids)) {
            foreach ($ids as $id) {
                self:self::sendGlobalSearchCustomerWillVisitMessage($id);
            }
        } else {
            self::sendGlobalSearchMessage(Constant::CUSTOMER_WILL_VISIT_CATEGORY, $ids);
        }
    }

    /**
     * 发送人事档案相关消息
     *
     * @param int $id
     */
    public static function sendGlobalSearchPersonnelFilesMessage($id): void
    {
        self::sendGlobalSearchMessage(Constant::PERSONNEL_FILES_CATEGORY, $id);
    }

    /**
     * 发送公告相关消息
     *
     * @param int|array $ids
     */
    public static function sendGlobalSearchNotifyMessage($ids): void
    {
        if (is_array($ids)) {
            foreach ($ids as $id) {
                self:self::sendGlobalSearchNotifyMessage($id);
            }
        } else {
            self::sendGlobalSearchMessage(Constant::NOTIFY_CATEGORY, (int)$ids);
        }
    }

    /**
     * 发送新闻相关消息
     *
     * @param int|array
     */
    public static function sendGlobalSearchNewsMessage($ids): void
    {
        if (is_array($ids)) {
            foreach ($ids as $id) {
                self:self::sendGlobalSearchNewsMessage($id);
            }
        } else {
            self::sendGlobalSearchMessage(Constant::NEWS_CATEGORY, $ids);
        }
    }

    /**
     * 发送公共通讯录相关消息
     *
     * @param int $id
     */
    public static function sendGlobalSearchPublicAddressMessage($id): void
    {
        self::sendGlobalSearchMessage(Constant::PUBLIC_ADDRESS_CATEGORY, $id);
    }

    /**
     * 发送个人通讯录相关消息
     *
     * @param int $id
     */
    public static function sendGlobalSearchPrivateAddressMessage($id): void
    {
        self::sendGlobalSearchMessage(Constant::PRIVATE_ADDRESS_CATEGORY, $id);
    }

    /**
     * 发送用户相关消息
     *
     * @param string $id
     */
    public static function sendGlobalSearchUserMessage($id = ''): void
    {
        self::sendGlobalSearchMessage(Constant::USER_CATEGORY, $id);
    }

    /**
     * 发送全站搜索消息队列
     *
     * @param string $category  对应分类(11个)
     * @param string|int $id    分类对应的id
     */
    public static function sendGlobalSearchMessage($category, $id = ''):void
    {
        /**
         * 目前更新方式
         *  1. 定时任务更新
         *      通过定时任务将全部索引更新job分发到处理器更新
         *  2. 消息队列更新
         *      通过消息队列实现文档单个更新
         *  3. 手动更新
         *      按照索引分类通过队列进行更新
         */
        if ($id) {
            $type = ConfigOptions::QUEUE_UPDATE_DOCUMENT_REINDEX;       // TODO 需重命名 名称不直观
            $data = ['id' => $id, 'category' => $category];
        } else {
            $type = ConfigOptions::QUEUE_UPDATE_TYPE_REINDEX;
            $data = ['category' => $category];
        }
        // 全站搜索消息队列是否开启
        $globalConfigEnable = self::isPermissionByQueue(ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH);
        // 全站搜素指定的消息队列类型是否开启
        $categoryConfigEnable = self::isPermissionByConfig(ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH_QUEUE, $category);

        if ($globalConfigEnable && $categoryConfigEnable) {
            self::sendMessage($type, $data);
        } else {
            self::indexStashHandler($category, $id);
        }
    }

    /**
     * 发送全站搜索消息队列
     *
     * @param string $table     表名称
     * @param string|int $id    分类对应的id
     */
    public static function sendGlobalSearchMessageByTable($table, $id = ''): void
    {
       switch ($table) {
           case 'address_private':
               self::sendGlobalSearchPrivateAddressMessage($id);
               break;
           case 'address_public':
               self::sendGlobalSearchPublicAddressMessage($id);
               break;
           case 'personnel_files':
               self::sendGlobalSearchPersonnelFilesMessage($id);
               break;
       }
    }

    /**
     * 发送es搜索消息队列
     *  type @see ConfigOptions::ES_QUEUE_UPDATE_TYPE
     *  1.部分更新 type为 @see ConfigOptions::QUEUE_UPDATE_PART_UPDATE 需 data存在category id 及 fields
     *  2.单索引更新 type为 @see ConfigOptions::QUEUE_UPDATE_DOCUMENT_REINDEX 需data存在category及id
     *  3.类型更新  type为 @see ConfigOptions::QUEUE_UPDATE_TYPE_REINDEX 需data存在category
     *  4.按功能索引 type为 @see ConfigOptions::QUEUE_UPDATE_FUNCTION_REINDEX 需data存在feature
     *  5.全部重新索引 type为 @see ConfigOptions::QUEUE_UPDATE_ALL_REINDEX data为空
     *
     * @param string $type 更新类型
     * @param array $data 队列数据
     */
    public static function sendMessage($type, $data): void
    {
        if (self::isPermissionByQueue() && self::validateMessageType($type, $data)) {
            dispatch(new ElasticsearchJob(['type' => $type, 'data' => $data]));
        }
    }

    /**
     *  是否开启消息队列更新
     *
     * @param string $type
     *
     * @return bool
     */
    protected static function isPermissionByQueue($type = ''): bool
    {
        $where = ['key' => ConfigOptions::UPDATE_BY_QUEUE, 'value' => true];

        if ($type) {
            $where['type'] = $type;
        }
        $config = DB::table('elastic_search_config')->where($where)->first();

        return $config ? true : false;
    }

    /**
     *  指定配置是否开启
     *
     * @param string $configType
     * @param string $configKey
     *
     * @return bool
     */
    protected static function isPermissionByConfig($configType, $configKey): bool
    {
        $where = ['type' => $configType, 'key' => $configKey, 'value' => true];

        $config = DB::table('elastic_search_config')->where($where)->first();

        return $config ? true : false;
    }

    /**
     *  验证消息类型
     *
     * @param string $type
     * @param array $extra
     *
     * @return  bool
     */
    protected static function validateMessageType($type, $extra): bool
    {
        if (!in_array($type, ConfigOptions::ES_QUEUE_UPDATE_TYPE)) {
            return false;
        }

        $category = $extra['category'] ?? '';
        $id = $extra['id'] ?? '';
        $fields = $extra['fields'] ?? [];
        $functionType = $extra['type'] ?? '';

        switch ($type){
            case ConfigOptions::QUEUE_UPDATE_PART_UPDATE:
                // 部分更新验证category和id和fields
                return $category && $id && $fields;
            case ConfigOptions::QUEUE_UPDATE_DOCUMENT_REINDEX:
                // 按文档更新验证category和id
                return $category && $id;
            case ConfigOptions::QUEUE_UPDATE_TYPE_REINDEX:
                // 按类型更新验证category
                return (bool) $category;
            case ConfigOptions::QUEUE_UPDATE_FUNCTION_REINDEX:
                // 按功能配置类型更新验证functionType
                return (bool) $functionType;
            case ConfigOptions::QUEUE_UPDATE_ALL_REINDEX:
                return true;
            default:
                return false;
        }
    }

    /**
     * 贮存待处理索引
     *
     * @param string $category
     * @param string|int $id
     */
    private static function indexStashHandler($category, $id)
    {
        if ($id) {
            $data = ['category' => $category, 'index_id' => $id];
            DB::table(ElasticTables::ELASTIC_STASH_INDEX_TABLE)->insert($data);
        }
    }

    /**
     * 附件保存时更新索引
     *
     * @param string $attachmentId
     * @param string $table
     *
     * @return void
     */
    public static function indexUpdateByAttachmentIdAndTable($attachmentId, $table)
    {
        $prefix = 'attachment_relataion_';
        if(Schema::hasTable($prefix.$table)) {
            $entityId = DB::table($prefix.$table)->where('attachment_id', $attachmentId)->pluck('entity_id')->toArray();
            if (isset($entityId[0])) {
                switch ($table) {
                    case 'document_content':
                        self::sendGlobalSearchDocumentMessage($entityId[0]);
                        break;
                    case 'email':
                        self::sendGlobalSearchEmailMessage($entityId[0]);
                        break;
                    case 'flow_run':
                        self::sendGlobalSearchFlowMessage($entityId[0]);
                        break;
                    case 'news':
                        self::sendGlobalSearchNewsMessage($entityId[0]);
                        break;
                    case 'notify':
                        self::sendGlobalSearchNotifyMessage($entityId[0]);
                        break;
                    case 'personnel_files':
                        self::sendGlobalSearchPersonnelFilesMessage($entityId[0]);
                        break;
                    default:
                        // TODO
                }
            }
        }
    }
}