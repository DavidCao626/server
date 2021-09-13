<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Flow\Entities\FlowSettingsEntity;

/**
 * 流程设置知识库
 *
 * @author 缪晨晨
 *
 * @since  2019-10-14 创建
 */
class FlowSettingsRepository extends BaseRepository
{
    public function __construct(FlowSettingsEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 根据某个参数key获取参数值
     *
     * @method getFlowSettingsParamValueByParamKey
     *
     * @author 缪晨晨
     *
     * @param  string                 $paramKey [参数key,多个用英文逗号隔开]
     *
     * @return [type]                       [description]
     */
    public function getFlowSettingsParamValueByParamKey($paramKey)
    {
        if (empty($paramKey)) {
            return '';
        }
        if (strpos($paramKey, ',') !== false) {
            $paramKey = explode(',', trim($paramKey, ','));
        }
        if (is_array($paramKey)) {
            $paramInfo = $this->entity->whereIn('param_key', $paramKey)->get();
            return $paramInfo;
        } else {
            $paramInfo = $this->entity->where('param_key', $paramKey)->first();
            return $paramInfo->param_value ?? '';
        }
    }

    /**
     * 根据条件获取流程设置
     *
     * @method getFlowSettingsParamValueByWhere
     *
     * @author 缪晨晨
     *
     * @param  array                 $wheres [查询条件]
     *
     * @return [type]                       [description]
     */
    public function getFlowSettingsParamValueByWhere($wheres)
    {
        return $this->entity->wheres($wheres)->get();
    }

    /**
     * 根据条件设置某个流程设置参数值
     *
     * @method setFlowSettingsParamValueByWhere
     *
     * @author 缪晨晨
     *
     * @param  array                 $data  [需要更新或插入的数据]
     *
     * @return [type]                       [description]
     */
    public function setFlowSettingsParamValueByWhere($data)
    {
        // 流程设置项字段说明
        $paramComment = [
            'flow_page_opening_mode' => '流程新建办理查看页面打开方式：1、浏览器新标签；2、当前标签内弹出框',
            'form_refresh_frequency' => '流程表单自动保存频率设置，单位秒, 默认 3000',
            'flow_search_export_compress_set' => '启用后流程查询结果将导出成一个包含流程详情Excel和流程附件的压缩文件：1、开启；2、关闭',
            'flow_export_pdf' => '流程查看和打印页面可以导出为PDF：1、开启；2、关闭'
        ];
        if (!empty($data) && is_array($data)) {
            $insertData = [];
            foreach ($data as $key => $value) {
                if (!empty($value['param_key']) && isset($value['param_value'])) {
                    $checkExist = $this->entity->where('param_key', $value['param_key'])->count();
                    if (!$checkExist) {
                        $insertData[] = [
                            'param_key'   => $value['param_key'],
                            'param_value' => $value['param_value'],
                            'param_comment' => $paramComment[$value['param_key']] ?? ''
                        ];
                    } else {
                        $this->entity->where('param_key', $value['param_key'])->update(['param_value' => $value['param_value']]);
                    }
                }
            }
            if (!empty($insertData)) {
                $this->entity->insert($insertData);
            }
        }
        return true;
    }
}
