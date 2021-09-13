<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowFormControlStructureEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程表单控件结构表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormControlStructureRepository extends BaseRepository
{
    public function __construct(FlowFormControlStructureEntity $entity) {
        parent::__construct($entity);
        $this->flowFormTypeRepository = 'App\EofficeApp\Flow\Repositories\FlowFormTypeRepository';
        $this->flowFormEditionRepository = 'App\EofficeApp\Flow\Repositories\FlowFormEditionRepository';
    }

    /**
     * 获取基本信息
     *
     * @method FlowFormControlStructureRepository
     *
     * @param  [type]                             $param [description]
     *
     * @return [type]                                    [description]
     */
    function getFlowFormControlStructure($param)
    {
        $default = array(
            'fields' => ['*'],
            'search' => [],
            'order_by'  => ['sort' => 'asc'],
            'returntype' => 'array'
        );
        $param = array_merge($default, $param);
        // 如果传入的查询条件中没有传递form_version_no参数，需要根据form_id或者history_form_id来查出表单版本号带入查询条件，这里公共处理，其他地方就不需要再传了
        if (isset($param['search'])) {
            if (isset($param['search']['form_id'][0]) && !isset($param['search']['form_version_no'])) {
                $flowFormTypeInfo = app($this->flowFormTypeRepository)->getDetail($param['search']['form_id'][0]);
                // 20180710里的脚本调用了这个方法，历史里还没有form_version_no这个字段，所以这里getDetail暂不查询指定字段
                if (!empty($flowFormTypeInfo->form_version_no)) {
                    $param['search']['form_version_no'] = [$flowFormTypeInfo->form_version_no];
                }
            } else if (isset($param['search']['history_form_id'][0]) && !isset($param['search']['form_version_no'])) {
                $flowFormEditionInfo = app($this->flowFormEditionRepository)->getDetail($param['search']['history_form_id'][0], false, ['form_version_no', 'form_id']);
                if (!empty($flowFormEditionInfo->form_version_no)) {
                    $param['search']['form_version_no'] = [$flowFormEditionInfo->form_version_no];
                    $param['search']['form_id'] = [$flowFormEditionInfo->form_id];
                }
                unset($param['search']['history_form_id']);
            }
        }
        $query = $this->entity
                      ->select($param['fields'])
                      ->wheres($param['search'])
                      ->orders($param['order_by'])->withTrashed();

        if ($param['returntype'] == 'object') {
            return $query->get();
        } else {
            return $query->get()->toArray();
        }
    }


    /**
     * 通过 control_id 和 form_id 获取控件基本信息
     * @param $formId
     * @param $controlId
     * @return mixed
     */
    public function getControlInfoByControlId($formId, $controlId)
    {
       return $this->entity->where('form_id', $formId)->where('control_id', $controlId)->first();
    }
}
