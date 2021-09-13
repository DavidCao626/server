<?php


namespace App\EofficeApp\System\Params\Entities;


use App\EofficeApp\Base\BaseEntity;

class SystemParamsEntity extends BaseEntity
{
    // =====================================================================
    //                          文档插件类型相关参数
    // =====================================================================
    const ONLINE_READ_TYPE = 'online_read_type'; // 参数类型为文档在线查看插件
    const ONLINE_READ_REMARK = '文档在线查看插件参数配置 0:金格 1:永中 2:wps';
    const ONLINE_READ_TYPE_GOLDGRID = 0; // 金格
    const ONLINE_READ_TYPE_YOZO = 1; // 永中
    const ONLINE_READ_TYPE_WPS = 2; // wps

    const ONLINE_READ_TYPE_YOZO_TOMCAT_ADDR = 'tomcat_addr'; //tomcat内网地址
    const ONLINE_READ_TYPE_YOZO_TOMCAT_ADDR_OUT = 'tomcat_addr_out'; //tomcat外网地址
    const ONLINE_READ_TYPE_YOZO_APACHE_LISTEN_PORT = 'apache_listen_port'; //服务器apache监听端口号
    const ONLINE_READ_TYPE_YOZO_COPY_DISABLE = 'tomcat_copy_disable';   // 永中防复制
    const ONLINE_READ_TYPE_YOZO_WATERMARK = 'tomcat_watermark';  //永中水印
    const ONLINE_READ_TYPE_YOZO_deploy_SERVICE = 'deploy_service';  //tomcat服务部署方案
    const ONLINE_READ_TYPE_YOZO_OA_ADDR_INNER = 'oa_addr_inner';  //OA系统内网地址
    const ONLINE_READ_TYPE_YOZO_OA_ADDR_OUT = 'oa_addr_out';  //OA系统外网地址

    const ONLINE_READ_TYPE_WPS_APP_ID = 'wps_app_id';   // wps在线查看服务id
    const ONLINE_READ_TYPE_WPS_APP_KEY = 'wps_app_key';  // wps在线查看服务key
    const ONLINE_READ_PARSE_TYPE = 'wps_parse_type'; // wps解析方式 1:云文档 2:国产化插件

    // 金格iweboffice配置
    const ONLINE_READ_TYPE_GOLDGRID_IWEBOFFICE_VERSION = 'iweboffice_version'; // 金格插件版本
    const ONLINE_READ_TYPE_GOLDGRID_IWEBOFFICE_VERSION_NUMBER = 'iweboffice_version_number'; // 2015-采购版本号
    const ONLINE_READ_TYPE_GOLDGRID_IWEBOFFICE_COMPANY_AUTH_CODE = 'iweboffice_company_auth_code'; // 2015-企业授权码

    // 文档插件类型验证
    const ONLINE_READ_TYPE_VALIDATE = [
        self::ONLINE_READ_TYPE_GOLDGRID,
        self::ONLINE_READ_TYPE_YOZO,
        self::ONLINE_READ_TYPE_WPS
    ];

    // 文档插件全部配置
    const ONLINE_READ_ALL_PARAMS = [
        self::ONLINE_READ_TYPE,
        self::ONLINE_READ_TYPE_YOZO_TOMCAT_ADDR,
        self::ONLINE_READ_TYPE_YOZO_TOMCAT_ADDR_OUT,
        self::ONLINE_READ_TYPE_YOZO_APACHE_LISTEN_PORT,
        self::ONLINE_READ_TYPE_WPS_APP_ID,
        self::ONLINE_READ_TYPE_WPS_APP_KEY,
        self::ONLINE_READ_TYPE_YOZO_COPY_DISABLE,
        self::ONLINE_READ_TYPE_YOZO_WATERMARK,
        self::ONLINE_READ_PARSE_TYPE,
        self::ONLINE_READ_TYPE_YOZO_OA_ADDR_INNER,
        self::ONLINE_READ_TYPE_YOZO_OA_ADDR_OUT,
        self::ONLINE_READ_TYPE_YOZO_deploy_SERVICE,
        self::ONLINE_READ_TYPE_GOLDGRID_IWEBOFFICE_VERSION,
        self::ONLINE_READ_TYPE_GOLDGRID_IWEBOFFICE_VERSION_NUMBER,
        self::ONLINE_READ_TYPE_GOLDGRID_IWEBOFFICE_COMPANY_AUTH_CODE,
    ];
    // TODO 配置参数类型较多 后续可添加configuration/constant 或 枚举对象, 将常量配置从本类中移除

    /** @var string 系统参数表 */
    public $table = 'system_params';
}