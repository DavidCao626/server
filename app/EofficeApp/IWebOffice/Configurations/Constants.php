<?php


namespace App\EofficeApp\IWebOffice\Configurations;

/**
 * Class Constants
 * IWebOffice相关常量配置
 */
final class Constants
{
    // ===========================================================
    //                       金格插件2003所有配置
    // ===========================================================
    const GRID_IMAGE_SIGNATURE = 'gridImageSignature';

    // ===========================================================
    //                       金格插件2003签章相关配置
    // ===========================================================
    // 金格签章图片样式
    const GRID_SIGNATURE_STYLE = [
        self::IMAGE_ABOVE_TEXT,
        self::IMAGE_UNDER_TEXT,
    ];

    const IMAGE_ABOVE_TEXT = 4; // 图片浮于文字上方
    const IMAGE_UNDER_TEXT = 5; // 图片沉于文字下方
    const GRID_SIGNATURE_STYLE_DEFAULT_CONFIG = self::IMAGE_UNDER_TEXT; // 默认配置
    const APP_DOCUMENT_SIGNATURE_STYLE = 'APP:DOCUMENT:SIGNATURE:STYLE';    // 签章样式在redis中的key

}