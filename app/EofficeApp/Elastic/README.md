#Elasticsearch模块

1. 目录说明
    * Commands es相关命令
        - suggestion(建议词相关命令,开发中)
        * createIndexCommand: 创建索引
        * migrationCommand: 数据迁移
        * rebuildCommand: 重建索引（用于定时任务更新）
        * renameAliasCommand: 为索引创建别名
        * preservedVersionCommand: 保留索引指定的版本
        * syncAttachmentContentCommand： 为索引切换版本（暂无版本控制）
    * Configuration es相关配置
        * Constant： es分类常量（比如 `别名` 、 `类型` 等）
        * ConfigOptions: 配置相关常量（比如 `配置类型` `基本配置` 等）
        * InitConfig: 初始化ES的相关配置
        * ElasticTables: elastic模块表相关常量
    * Controllers es控制器
    * Entities es全部实体
    * Foundation es构建
        * BoolQuery： 构建bool查询
        * Param： 查询参数
        * Query： 构建query
        * SearchParam： 构建搜索参数 
    * Resource es相关资源
        * schema: mappings
        * script: 升级中可能会用到的脚本
    * Services es服务
        * Config: 配置相关服务
        * Dictionary: 词典相关服务
        * Document: 附件相关服务
        * Log: 日志相关服务
        * MessageQueue: 队列发生器和处理器
        * Search: 搜索相关服务
        * Suggestion: 建议词相关服务(开发中)
    * Utils es相关工具  
            
2. 功能介绍
    1. 全文检索功能
    2. ES的相关配置
        - 更新方式
        - 词典(扩展词/同义词)
        - 日志
    3. 后续
        - 建议词开发
        - 查询方式配置(相关度/最小匹配/查询方式)
        - 停词
3. 参考文档
 * [Elasticsearch手册(6.0)](https://www.elastic.co/guide/en/elasticsearch/reference/6.0/index.html)
 * [Elasticsearch权威指南(2.x)](https://www.elastic.co/guide/cn/elasticsearch/guide/current/index.html)
 * [Elasticsearch-PHP](https://www.elastic.co/guide/cn/elasticsearch/php/current/index.html)