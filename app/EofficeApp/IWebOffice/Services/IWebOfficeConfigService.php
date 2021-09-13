<?php


namespace App\EofficeApp\IWebOffice\Services;


use App\EofficeApp\Base\BaseService;
use App\EofficeApp\IWebOffice\Configurations\Constants;
use Illuminate\Support\Facades\Redis;

class IWebOfficeConfigService extends BaseService
{
    /**
     * 获取金格签章样式
     *  4: 图片浮于文字上方
     *  5: 图片沉于文字下方
     *
     * @return int
     */
    public function getSignatureStyle()
    {
        $style = Redis::get(Constants::APP_DOCUMENT_SIGNATURE_STYLE);

        if (!$style) {
            $style= get_system_param(Constants::GRID_IMAGE_SIGNATURE, Constants::GRID_SIGNATURE_STYLE_DEFAULT_CONFIG);

            // 若为非法参数, 返回默认值并更新数据库和缓存
            if(!$this->isValidBySignatureStyle($style)) {
                $style = Constants::GRID_SIGNATURE_STYLE_DEFAULT_CONFIG;
                DB::table('system_params')->insert(['param_key' => Constants::GRID_IMAGE_SIGNATURE, 'param_value' => Constants::GRID_SIGNATURE_STYLE_DEFAULT_CONFIG]);
            }

            Redis::set(Constants::APP_DOCUMENT_SIGNATURE_STYLE, $style);
        }

        return (int)$style;
    }

    /**
     * 设置金格签章样式
     *
     * @param string|int $style
     *
     * @return void
     */
    public function setSignatureStyle($style)
    {
        if ($this->isValidBySignatureStyle($style)) {
            set_system_param(Constants::GRID_IMAGE_SIGNATURE, $style);
            Redis::set(Constants::APP_DOCUMENT_SIGNATURE_STYLE, $style);
        }
    }

    /**
     * 判断签章是否为有效值
     *
     * @param string|int $style
     *
     * @return bool
     */
    private function isValidBySignatureStyle($style)
    {
        $style = (int) $style;

        return in_array($style, Constants::GRID_SIGNATURE_STYLE);
    }

    /**
     * 获取iweboffice2003所有配置
     *
     * @return array
     */
    public function getIWebOffice2003AllConfigurations()
    {
        $signature = $this->getSignatureStyle();
        return [
            Constants::GRID_IMAGE_SIGNATURE => $signature,
        ];
    }
}