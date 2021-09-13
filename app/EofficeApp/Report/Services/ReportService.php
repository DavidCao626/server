<?php

namespace App\EofficeApp\Report\Services;

use App\EofficeApp\Base\BaseService;
use DB;
use Carbon\Carbon;

/**
 *
 * 报表管理
 *
 */
class ReportService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->reportChartRepository = "App\EofficeApp\Report\Repositories\ReportChartRepository";
        $this->reportTagRepository = "App\EofficeApp\Report\Repositories\ReportTagRepository";
        $this->reportDatasourceRepository = "App\EofficeApp\Report\Repositories\ReportDatasourceRepository";
        $this->userRepository = "App\EofficeApp\User\Repositories\UserRepository";
        $this->externalDatabaseService = 'App\EofficeApp\System\ExternalDatabase\Services\ExternalDatabaseService';
        $this->importExportService = 'App\EofficeApp\ImportExport\Services\ImportExportService';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->reportFilterRepository = 'App\EofficeApp\Report\Repositories\ReportFilterRepository';
    }

    //添加标签
    public function addTag($data)
    {
        $data = array_intersect_key($data, array_flip(app($this->reportTagRepository)->getTableColumns()));
        if (isset($data['tag_title'])) {
            $infoResult = app($this->reportTagRepository)->getTag('tag_title', $data['tag_title']);
            if (!empty($infoResult)) {
                return ['code' => ['0x065001', 'report']];
            }
        }
        $result = app($this->reportTagRepository)->insertData($data);
        //$tag_id   = $result->tag_id;
        return $result;
    }

    //表签列表
    public function getAllTag($data)
    {
        $return = $this->response(app($this->reportTagRepository), 'getAllTagTotal', 'getAllTag', $this->parseParams($data));
        return $return;
    }

    //标签信息
    public function getOneTagList($data)
    {
        return app($this->reportTagRepository)->findTag($data['tag_id']);
    }

    //编辑标签
    public function editTag($data)
    {
        //$itemInfo = app($this->reportTagRepository)->findTag($data['tag_id']);
        //if(count($itemInfo) == 0) return;
        $itemData = array_intersect_key($data, array_flip(app($this->reportTagRepository)->getTableColumns()));
        $resultStatus = app($this->reportTagRepository)->updateData($itemData, ['tag_id' => $itemData['tag_id']]);
        return $resultStatus;
    }

    //删除标签
    public function deleteTag($itemId)
    {
        //$itemInfo = app($this->reportTagRepository)->findTag($itemId);
        //if(count($itemInfo) == 0) return;
        return app($this->reportTagRepository)->deleteByWhere(['tag_id' => [$itemId]]);
    }

    //数据源列表
    public function getDatasourceMultiList($data)
    {
        $data = $this->parseParams($data);
        $param = app($this->reportTagRepository);
        return app($this->reportDatasourceRepository)->getDatasourceList($param, $data);
    }

    //数据源类型
    public function getDatasourceType($data = array())
    {
        $datasource_type = config('report.datasource_types');
        foreach ($datasource_type as $k => &$v) {
            // 数据源名称
            if (isset($v['datasource_name'])) {
                $v['datasource_name'] = trans($v['datasource_name']);
            }
            //分类
            if (isset($v['tips'])) {
                $v['tips'] = trans($v['tips']);
            }
            if (isset($v['class'])) {
                $v['class'] = trans($v['class']);
            }
            if (isset($v['datasource_custom']['itemName'])) {
                $v['datasource_custom']['itemName'] = trans($v['datasource_custom']['itemName']);
            }

            // 数据源分析字段
            if (isset($v['datasource_data_analysis'])) {
                if (!isset($v['datasource_data_analysis']['key'])) {
                    foreach ($v['datasource_data_analysis'] as $key => &$value) {
                        $value = trans($value);
                    }
                }
            }

            // 分组依据
            if (!isset($v['datasource_group_by']['key'])) {
                if (!isset($v['datasource_group_by']['key'])) {
                    foreach ($v['datasource_group_by'] as $key => &$value) {
                        $value = trans($value);
                    }
                }
            }
        }
        return $datasource_type;
    }

    //添加数据源
    public function addDatasource($data)
    {
        $data = array_intersect_key($data, array_flip(app($this->reportDatasourceRepository)->getTableColumns()));
        if (isset($data['datasource_name'])) {
            $data['datasource_name'] = trim($data['datasource_name']);
            $infoResult = app($this->reportDatasourceRepository)->getItem('datasource_name', trim($data['datasource_name']));
            if (!empty($infoResult)) {
                return ['code' => ['0x065002', 'report']];
            }
        }
        // 这里的字段移动到report_chart中
        if (isset($data['datasource_filter']) && is_array($data['datasource_filter'])) {
            $data['datasource_filter'] = json_encode($data['datasource_filter']);
        }
        return app($this->reportDatasourceRepository)->insertData($data);
    }

    //查询数据源
    public function findDatasource($data)
    {
        $chart_id = isset($data['chart_id']) ? $data['chart_id'] : '';
        $data = app($this->reportDatasourceRepository)->findItem($data['datasource_id'], $chart_id);
        $data['typeConfig'] = config('report.datasource_types');
        foreach ($data['typeConfig'] as &$v) {
            // 数据源名称
            if (isset($v['datasource_name'])) {
                $v['datasource_name'] = trans($v['datasource_name']);
            }
            if (isset($v['tips'])) {
                $v['tips'] = trans($v['tips']);
            }
            if (isset($v['datasource_custom']['itemName'])) {
                $v['datasource_custom']['itemName'] = trans($v['datasource_custom']['itemName']);
            }
            // 数据过滤
            if (isset($v['datasource_filter'])) {
                foreach ($v['datasource_filter'] as $key => &$value) {
                    $value['itemName'] = trans($value['itemName']);
                }
            }

            // 数据源分析字段
            if (isset($v['datasource_data_analysis'])) {
                if (!isset($v['datasource_data_analysis']['key'])) {
                    foreach ($v['datasource_data_analysis'] as $key => &$value) {
                        $value = trans($value);
                    }
                }
            }

            // 分组依据
            if (!isset($v['datasource_group_by']['key'])) {
                if (!isset($v['datasource_group_by']['key'])) {
                    foreach ($v['datasource_group_by'] as $key => &$value) {
                        $value = trans($value);
                    }
                }
            }
        }
        return $data;
    }

    //编辑数据源
    public function editDatasource($data)
    {
        $itemData = array_intersect_key($data, array_flip(app($this->reportDatasourceRepository)->getTableColumns()));
        $itemData['datasource_param'] = !empty($itemData['datasource_param']) ? json_encode($itemData['datasource_param']) : "";
        if (isset($itemData['datasource_type']) && in_array($itemData['datasource_type'], ['workflow', 'notify'])) {
            $charts = app($this->reportChartRepository)->getChart('datasource_id', $itemData['datasource_id'], "array");
            $custom_report = isset($data['custom_report']) ? $data['custom_report'] : "";
            $custom_array = json_decode($custom_report, true);
            if ($itemData['datasource_type'] == "workflow") {
                $idArray = explode(",", isset($custom_array['workflowID']) ? $custom_array['workflowID'] : '');
            } else {
                $idArray = explode(",", isset($custom_array['notifyId']) ? $custom_array['notifyId'] : '');
            }
            if (!empty($charts)) {
                foreach ($charts as $key => $value) {
                    $datasource_param = json_decode($value['datasource_param'], true);
                    $new_datasource = [];
                    $param_data = [];
                    if (!empty($datasource_param)) {
                        foreach ($datasource_param as $k => $v) {
                            if (in_array($v['id'], $idArray)) {
                                $new_datasource[] = $v;
                            }
                        }
                        if (!empty($new_datasource)) {
                            $param_data['datasource_param'] = json_encode($new_datasource);
                            app($this->reportChartRepository)->updateData($param_data, ['chart_id' => $value['chart_id']]);
                        }

                    }

                }

            }

        }
        return app($this->reportDatasourceRepository)->updateData($itemData, ['datasource_id' => $itemData['datasource_id']]);
    }

    //删除数据源
    public function deleteDatasource($itemId)
    {
        app($this->reportDatasourceRepository)->deleteByWhere(['datasource_id' => [$itemId]]);
        app($this->reportChartRepository)->deleteByWhere(['datasource_id' => [$itemId]]);
        return true;
    }

    //左侧报表列表
    public function getChartMultiList($data = array(), $user_info)
    {
        $data = $this->parseParams($data);
        $param = app($this->reportTagRepository);
        return app($this->reportChartRepository)->getChartMultiList($param, $data, $user_info);
    }

    //报表列表
    public function getChartList($data, $user_info)
    {
        $filter = $this->parseParams($data);
        $reportChart = app($this->reportChartRepository);
        $reportChart->user_info = $user_info;
        $data = $this->response($reportChart, 'getTotalNum', 'getAllList', $filter);
        if (!empty($data['list'])) {
            foreach ($data['list'] as &$v) {
                if (isset($v['chart_tag'])) {
                    $v['chart_tag_name'] = app($this->reportTagRepository)->getTagName($v['chart_tag']);
                }
                if (isset($v['create_user'])) {
                    $v['create_user_name'] = app($this->userRepository)->getUserName($v['create_user']);
                }
            }
        }
        return $data;
    }

    public function getChartListPermission($data, $user_info)
    {
        $filter = $this->parseParams($data);
        if (!is_array($filter)) {
            $filter = [];
        }

        $filter['limit_permission'] = 'limit_permission';
        $reportChart = app($this->reportChartRepository);
        $reportChart->user_info = $user_info;
        $data = $this->response($reportChart, 'getTotalNum', 'getAllList', $filter);
        if (!empty($data['list'])) {
            foreach ($data['list'] as &$v) {
                $v['chart_tag_name'] = app($this->reportTagRepository)->getTagName($v['chart_tag']);
                $v['create_user_name'] = app($this->userRepository)->getUserName($v['create_user']);
            }
        }
        return $data;
    }

    //获得报表信息
    public function findChart($data, $user_info = array())
    {
        $selects = [];
        $filters = '';
        $chart_id = isset($data['chart_id']) ? $data['chart_id'] : '';
        if (!empty($chart_id)) {
            $chart_info = app($this->reportChartRepository)->findItem($chart_id);
            if (!empty($chart_info)) {
                if (empty($data['not_limit'])) {
                    if (!app($this->reportChartRepository)->hasPermission($user_info, $chart_info)) {
                        return ['code' => ['0x065003', 'report']];
                    }
                }
            }
            if (!empty($chart_info['datasource_id'])) {
                $datasource_id = $chart_info['datasource_id'];
            }
        }
        // 获取报表关联的显示筛选
        if (!empty($chart_id)) {
            $filters = DB::table('report_filter')->where('chart_id', $chart_id)->first();
            $filters = json_decode(json_encode($filters), true);
        }
        if ($filters) {
            $charts = app($this->reportChartRepository)->findItem($chart_id);
            $result = array_merge($filters, $charts);
            return $result;
        } else {
            $res = app($this->reportChartRepository)->findItem($chart_id);
            if (!empty($res)) {
                return $res;
            }
        }
    }

    //删除报表
    public function deleteChart($chart_id)
    {
        return app($this->reportChartRepository)->deleteChart($chart_id);
    }

    //添加报表
    public function addChart($data)
    {
        $dataSelect = $data;
        $data = array_intersect_key($data, array_flip(app($this->reportChartRepository)->getTableColumns()));
        if (isset($data['chart_title'])) {
            $data['chart_title'] = trim($data['chart_title']);
            $infoResult = app($this->reportChartRepository)->getChart('chart_title', trim($data['chart_title']));
            if (!empty($infoResult)) {
                return ['code' => ['0x065002', 'report']];
            }
        }
        if (isset($data['chart_id'])) {
            unset($data['chart_id']);
        }

        $data['created_at'] = date("Y-m-d H:i:s");
        $result = app($this->reportChartRepository)->insertData($data);
        // 获取插入报表的chart_id，存入筛选表report_filter中
        if (isset($result->chart_id)) {
            $dataSelect['chart_id'] = $result->chart_id;
        }
        // 在筛选关联表report_select中插入关联的数据，保存过滤条件的筛选字段
        $this->addSelectDatasourceFilter($dataSelect);
        return $result;
    }

    //编辑报表
    public function editChart($data)
    {
        // 在筛选关联表report_select中插入关联的数据，保存过滤条件的筛选字段
        $this->addSelectDatasourceFilter($data);
        $itemData = array_intersect_key($data, array_flip(app($this->reportChartRepository)->getTableColumns()));
        return app($this->reportChartRepository)->updateData($itemData, ['chart_id' => $itemData['chart_id']]);
    }

    public function getGridList($data = array(), $user_info = array())
    {

        $result = $this->getChart($data, $user_info);
        if (isset($result['chart_data']) && $result['chart_data']) {
            // 存在一行但是没有任何数据的问题
            if ($result['chart_data']['total'] == 1) {
                $flag = false;
                foreach ($result['chart_data']['list'] as $val) {
                    if (!empty(array_filter($val))) {
                        $flag = true;
                    }
                }
                if (!$flag) {
                    $result['chart_data']['list'] = [];
                    $result['chart_data']['total'] = 0;
                }
            }
            $res = ['list' => $result['chart_data']['list'], 'total' => $result['chart_data']['total']];
            if (isset($result['chart_data']['aggregate'])) {
                $res['aggregate'] = $result['chart_data']['aggregate'];
            }
            return $res;
        }

    }

    //获得报表信息，只获取报表
    public function getChart($data = array(), $user_info = array(), $export = false)
    {
        ini_set('memory_limit', '800M');
        if (empty($data['datasource_id']) && empty($data['chart_id'])) {
            return $this->parse_chart_data();
        }
        $datasource_id = 0;
        $chart_info = [];
        $datasource_info = [];
        $chart_id = isset($data['chart_id']) ? $data['chart_id'] : '';
        // 根据报表获取数据
        if (!empty($data['chart_id'])) {
            $chart_info = app($this->reportChartRepository)->findItem($data['chart_id']);
            if (!empty($chart_info)) {
                // 报表导出不考虑权限
                if (empty($data['not_limit'])) {
                    if (!app($this->reportChartRepository)->hasPermission($user_info, $chart_info)) {
                        return ['code' => ['0x065003', 'report']];
                    }
                }
            }
            if (!empty($chart_info['datasource_id'])) {
                $datasource_id = $chart_info['datasource_id'];
            }
        }
        if (!empty($data['datasource_id'])) {
            $datasource_id = $data['datasource_id'];
        }
        // 根据数据源获取报表
        if (!empty($datasource_id)) {
            $datasource_info = app($this->reportDatasourceRepository)->findItem($datasource_id, $chart_id);
            $datasource_type_id = $this->getDatasourceTypes($datasource_info['datasource_type']);
            if (!empty($datasource_info)) {
                $result = [];
                //自定义数据源
                if (in_array($datasource_info['datasource_type'], ['customSource', 'custom', 'import'])) {
                    if (isset($data['datasource_param']) && !empty($data['datasource_param'])) {
                        //如果页面有数据，则传入页面的分组依据，如果没有，则使用上面数据库中查询出的数据；
                        $datasource_param = json_decode($data['datasource_param'], true);
                        $custom_report = json_decode($datasource_info['custom_report'], true);
                        $datasource_info['custom_report'] = json_encode(array_merge($datasource_param, $custom_report));
                    }
                    // 解析数据源数据，传入自定义报表的方法中
                    $custom_data = $this->parse_datasource_info($datasource_info);
                    if (!isset($custom_data['searchFields']) && !isset($custom_data['showData'])) {
                        $datasource_param = json_decode($datasource_info['datasource_param'], true);
                        $custom_data = array_merge($datasource_param, $custom_data);

                    }
                    //获取搜索条件
                    $chart_search = isset($datasource_info['chart_search']) ? $datasource_info['chart_search'] : '';
                    $editMode = false;
                    if (isset($data['editMode'])) {
                        $editMode = json_decode($data['editMode']);
                    }
                    $data['chart_search'] = isset($data['chart_search']) ? json_decode($data['chart_search'], true) : [];
                    $data['user_info'] = $user_info;
                    // 渲染自定义报表的图表
                    $result = $this->getCustomChart($custom_data, $data);
                    if (isset($result['code'])) {
                        return $result;
                    }
                    if (!is_array($result)) {
                        return ['code' => ['0x065005', 'report']];
                    }
                    $result['datasource_types_id'] = $datasource_type_id;
                    $result['datasource_info'] = $datasource_info;
                    $result['chart_title'] = isset($datasource_info['datasource_name']) ? $datasource_info['datasource_name'] : '';
                    return $this->parse_chart_data($result);
                } else {
                    //公告和流程多选特殊处理
                    if (in_array($datasource_info['datasource_type'], ['workflow', 'notify'])) {
                        if (isset($data['datasource_param']) && !empty($data['datasource_param'])) {
                            if (!is_array($data['datasource_param'])) {
                                $datasource_param = json_decode($data['datasource_param'], true);
                            } else {
                                $datasource_param = $data['datasource_param'];
                            }
                        } else {
                            if (!isset($datasource_info['datasource_param'])) {
                                return ['code' => ['0x065006', 'report']];
                            }
                            $datasource_param = json_decode($datasource_info['datasource_param'], true);
                        }
                        if (!empty($datasource_param)) {
                            $result = app($this->reportDatasourceRepository)->getReportData($datasource_id, $datasource_info, $chart_info, $data, $export, $datasource_param);
                        } else {
                            $result['datasource_info'] = $datasource_info;
                            $result['chart_title'] = isset($datasource_info['datasource_name']) ? $datasource_info['datasource_name'] : '';
                            $result['chart_type'] = "line";
                        }
                    } else {
                        //系统除了公告和流程以为的其他
                        //获取分组依据和数据分析字段
                        if (isset($data['datasource_param'])) {
                            //如果页面有数据，则传入页面的分组依据，如果没有，则使用上面数据库中查询出的数据；
                            $datasource_info['datasource_param'] = $data['datasource_param'];
                        }
                        if (isset($data['datasource_data_analysis'])) {
                            //如果页面有数据，则传入页面的分组依据，如果没有，则使用上面数据库中查询出的数据；
                            $datasource_info['datasource_data_analysis'] = $data['datasource_data_analysis'];
                        }
                        if (isset($data['datasource_group_by'])) {
                            //如果页面有数据，则传入页面的分组依据，如果没有，则使用上面数据库中查询出的数据；
                            $datasource_info['datasource_group_by'] = $data['datasource_group_by'];
                        }
                        // 如果不是自定义数据源，还是走以前逻辑
                        $result = app($this->reportDatasourceRepository)->getReportData($datasource_id, $datasource_info, $chart_info, $data, $export);
                    }
                    $result['datasource_types_id'] = $datasource_type_id;
                    // if (isset($result['chart_data']['xAxis']['data']) && empty($result['chart_data']['xAxis']['data'])) {
                    //     $result['chart_data']['xAxis']['data'] = [trans('report.temporarily_no_data')];
                    // }
                    return $this->parse_chart_data($result);
                }
            }
        }
        return $this->parse_chart_data();
    }

    public function getDatasourceTypes($type)
    {
        switch ($type) {
            case 'import':
                return 3;
                break;
            case 'custom':
                return 2;
                break;
            case 'customSource':
                return 1;
                break;
            default:
                return 0;
                break;
        }
    }

    /**
     * 解析数据源表中的数据，用来渲染图表
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function parse_datasource_info($data)
    {
        $custom_report = isset($data['custom_report']) ? $data['custom_report'] : '';
        $result = json_decode($custom_report, true);
        return $result;
    }

    //parse_chart_data
    public function parse_chart_data(&$data = array())
    {
        if (empty($data['chart_data']) || (isset($data['chart_data']['xAxis']['data']) && empty($data['chart_data']['xAxis']['data']))) {
            $result = [];
            $result['tooltip'] = ['trigger' => 'item', 'formatter' => "{a} <br/>{b} : {c}"];
            $result['legend']['data'] = [];
            $result['title']['text'] = "";
            $result['yAxis']['type'] = '';
            $result['data'][] = "";
            $result['xAxis'] = ['type' => 'category', 'boundaryGap' => 'false', 'data' => []];
            $result['series'] = [];
            //$result['legend']['data'][] = '暂无数据';
            $row = [];
            $row['name'] = trans('report.no_data');
            $row['type'] = 'line';
            $row['smooth'] = true;
            $row['data'] = [];
            $result['xAxis']['data'][] = trans('report.temporarily_no_data');
            $row['data'][] = 0;
            $result['series'][] = $row;
            $data['chart_data'] = $result;
            $data['origin_chart_data'][] = ['group_by' => trans('report.temporarily_no_data'), 'data' => [['name' => '', 'y' => '']]];
        }
        return $data;
    }

    //导出报表
    public function exportReportData($param = array())
    {
        $result = [];
        // not_limit参数，用来判断是否需要考虑权限
        $param['not_limit'] = true;
        $report_data = $this->getChart($param, $param['user_info'], true);
        $title = isset($report_data['datasource_info']['chart_title']) ? $report_data['datasource_info']['chart_title'] : trans('report.export_report');
        if (empty($report_data['origin_chart_data']) || !is_array($report_data['origin_chart_data'])) {
            return $result;
        }
        // $result['export_title'] = isset($report_data['chart_title']) ? $report_data['chart_title'] : "";
        // if (empty($result['export_title']) || $result['export_title'] == trans('report.export_report')) {
        //     return false;
        // }
        //$header = [1,2]; $data[] = [5,6]; $data[] = [8,9];
        $header = [];
        $data = [];
        $arr = [];
        $headers = [];
        $i = 0;
        if (isset($report_data['chart_type']) && $report_data['chart_type'] == 'excel') {
            $chart_data = $report_data['origin_chart_data'];
            $aggregate = isset($report_data['chart_data']['aggregate']) ? $report_data['chart_data']['aggregate'] : '';
            if (isset($chart_data[0]['data'])) {
                $header = array_column($chart_data[0]['data'], 'name');
                $group = isset($chart_data[0]['group_by']) ? $chart_data[0]['group_by'] : "";
            }
            $data[$i] = $header;
            foreach ($chart_data as $v) {
                if (isset($v['data']) && $v['data']) {
                    foreach ($v['data'] as $value) {
//                        if (isset($value['y'])) {
                        if (is_array($value) && array_key_exists('y', $value)) {
                            $flag = true;
                            continue;
                        } else {
                            $flag = false;
                            continue;
                        }
                    }
                    if ($flag) {
                        $arr[0] = $group;
                        $i++;
                        $data[$i] = array_column($v['data'], 'y');
                        $arr[$i] = isset($v['name']) ? $v['name'] : '';
                    } else {
                        $data[$i] = $v['data'];
                        $arr[$i] = isset($v['name']) ? $v['name'] : '';
                        // array_unshift($data[$i], isset($v['name']) ? $v['name'] : '');
                        $i++;
                    }
                }
            }
            $res = app($this->reportDatasourceRepository)->array_merge_more($arr, $data);
            if (!empty($aggregate)) {
                array_push($res, $aggregate);
            }
            foreach ($arr as $key => $value) {
                $headers[$value] = $value;
            }
            if ($headers) {
                return ['data' => $res, 'header' => $headers, 'export_title' => $title];
            }
        } else {
            foreach ($report_data['origin_chart_data'] as $v) {
                $i++;
                if (isset($v['data']) && !empty($v['data'])) {
                    if (!isset($header[0])) {
                        $header[0] = isset($v['group_by']) ? $v['group_by'] : "";
                    }

                    $row = [];
                    $row[] = isset($v['name']) ? $v['name'] : "";
                    foreach ($v['data'] as $k => $val) {
                        if ($i == 1) {
                            $header[] = isset($val['name']) ? $val['name'] : "";
                        }

                        $row[] = isset($val['y']) ? $val['y'] : "";
                    }
                    $data[] = $row;
                } else {
                    $header[0] = trans('report.temporarily_no_data');
                    $row = [];
                    $row[] = '';
                    $data[] = $row;
                }
            }
            $result['header'] = $header;
            $result['data'] = $data;
            $result['export_title'] = $title;

        }

        return $result;
    }

    //数据过滤
    public function getDatasourceFilter($data)
    {
        $result['filter'] = [];
        $result['filter']['selector'] = [];
        $result['filter']['finder'] = [];
        $result['filter']['date'] = [];
        $result['filter']['singleton'] = [];
        $result['datasource_info'] = $this->findDatasource($data);
        if (isset($data['chart_id']) && isset($result['datasource_info']["datasource_param"])) {
            $datasource_param = json_decode($result['datasource_info']["datasource_param"], true);
            if ($datasource_param && count($datasource_param) == 1) {
                if (isset($datasource_param[0])) {
                    $datasource_param = $datasource_param[0];
                    $result['datasource_info']['custom_report'] = json_encode($datasource_param['idJson']);
                }

            }
        }
        // 多语言翻译
        $result['year'] = date("Y");
        $item = [];
        $datasource_type_str = "";
        if (isset($data['param']) && !empty($data['param'])) {
            $param = json_decode($data['param'], true);
            if ($param && count($param) == 1) {
                $datasource_group_by = $param[0]['group'];
                $datasource_data_analysis = $param[0]['analysis'];
            } else {
                $datasource_group_by = "";
                $datasource_data_analysis = "";
                $result['datasource_info']['custom_report'] = "";
            }
        } else {
            $datasource_group_by = isset($result['datasource_info']['datasource_group_by']) ? $result['datasource_info']['datasource_group_by'] : "";
            $datasource_data_analysis = isset($result['datasource_info']['datasource_data_analysis']) ? $result['datasource_info']['datasource_data_analysis'] : "";
        }
        if (!empty($result['datasource_info']['datasource_type'])) {
            $datasource_type_str = $result['datasource_info']['datasource_type'];
            $result['datasource_type'] = $result['datasource_info']['datasource_type'];
            $datasource_type = config('report.datasource_types');
            foreach ($datasource_type as &$v) {
                if ($v['datasource_type'] == $result['datasource_info']['datasource_type']) {
                    // 数据源名称
                    if (isset($v['datasource_name'])) {
                        $v['datasource_name'] = trans($v['datasource_name']);
                    }
                    if (isset($v['datasource_custom']['itemName'])) {
                        $v['datasource_custom']['itemName'] = trans($v['datasource_custom']['itemName']);
                    }
                    // 数据过滤
                    if (isset($v['datasource_filter'])) {
                        foreach ($v['datasource_filter'] as $key => &$value) {
                            $value['itemName'] = trans($value['itemName']);
                        }
                    }

                    // 数据源分析字段
                    if (isset($v['datasource_data_analysis'])) {
                        if (!isset($v['datasource_data_analysis']['key'])) {
                            foreach ($v['datasource_data_analysis'] as $key => &$value) {
                                $value = trans($value);
                            }
                        }
                    }

                    // 分组依据
                    if (!isset($v['datasource_group_by']['key'])) {
                        if (!isset($v['datasource_group_by']['key'])) {
                            foreach ($v['datasource_group_by'] as $key => &$value) {
                                $value = trans($value);
                            }
                        }
                    }
                    $item = $v;
                    break;
                }
            }
        }
        if (!empty($item)) {
            if (!empty($item['datasource_filter_from'])) {
                $filter_param = array();
                if (!empty($result['datasource_info']['custom_report'])) {
                    $filter_param = json_decode($result['datasource_info']['custom_report'], true);
                    if (isset(array_values($filter_param)[0])) {
                        $idArray = explode(",", array_values($filter_param)[0]);
                        if ($idArray && count($idArray) > 1) {
                            $filter_param = [];
                        }
                    }
                }
                $filter_param['datasource_group_by'] = $datasource_group_by;

                $filter_param['datasource_data_analysis'] = $datasource_data_analysis;

                if (isset($result['datasource_info']['datasource_type'])) {
                    $filter_param['datasource_type'] = $result['datasource_info']['datasource_type'];
                }
                $filter_from = app($item['datasource_filter_from'][0])->{$item['datasource_filter_from'][1]}($filter_param);
                if (!empty($filter_from)) {
                    $item['datasource_filter'] = $filter_from;
                }
            }
        }
        $filter = isset($item['datasource_filter']) ? $item['datasource_filter'] : '';
        //excel
        if (!$filter) {
            $filter = [];
            $res = isset($result['datasource_info']['datasource_filter']) ? $result['datasource_info']['datasource_filter'] : '';
            if ($res) {
                $res = json_decode($res, true);
                foreach ($res as $key => $value) {
                    if ($value['field_name'] && $value['show_name']) {
                        $filter[] = ['filter_type' => $value['filter_type'] ?? 'input', 'itemValue' => $value['field_name'], 'itemName' => $value['show_name']];
                    }

                }
            }
        }
        // 报表设置了筛选字段 按报表配置
        if (isset($data['chart_id'])) {
            $chartFilter = app($this->reportFilterRepository)->getOneFieldInfo(['chart_id' => [$data['chart_id']]]);
            if ($chartFilter && $chartFilter->chart_search_filter_types) {
                if (!empty($filter)) {
                    $datasourceFilterkeys = array_column($filter, 'itemValue');
                    $datasourceFilter = array_column($filter, null, 'itemValue');
                }
                if ($result['datasource_info']['datasource_type'] == 'customSource') {
                    $filter = json_decode($chartFilter->chart_search_filter_types, 1);
                } else {
                    // 非自定义数据源需进行处理，处理预设的选择器
                    $filter = array_column($filter, null, 'itemValue');
                    $filterTypes = json_decode($chartFilter->chart_search_filter_types, 1);
                    foreach ($filterTypes as $key => $filterType) {
                        $filterTypes[$key] = isset($filter[$filterType['itemValue']]) && !empty($filter[$filterType['itemValue']]) ? array_merge($filter[$filterType['itemValue']], $filterType) : $filterType;
                    }
                    $filter = $filterTypes;
                }
                // 移除数据源类删除的筛选字段  更新字段名称
                if (isset($datasourceFilterkeys) && !empty($datasourceFilterkeys)) {
                    foreach ($filter as $fkey => $fvalue) {
                        if (!in_array($fvalue['itemValue'], $datasourceFilterkeys)) {
                            unset($filter[$fkey]);
                        } else {
                            if (isset($datasourceFilter[$fvalue['itemValue']]) && isset($datasourceFilter[$fvalue['itemValue']]['itemName']) && !empty($datasourceFilter[$fvalue['itemValue']]['itemName']) && $datasourceFilter[$fvalue['itemValue']]['itemName'] != $fvalue['itemName']) {
                                $filter[$fkey]['itemName'] = $datasourceFilter[$fvalue['itemValue']]['itemName'];
                            }
                        }
                    }
                    // 数据源新增的筛选字段
                    $filterDiff = array_diff($datasourceFilterkeys, array_column($filter, 'itemValue'));
                    if (!empty($filterDiff)) {
                        foreach ($filterDiff as $diff) {
                            if (isset($datasourceFilter[$diff]) && !empty($datasourceFilter[$diff])) {
                                $filter[] = [
                                    'itemName' => $datasourceFilter[$diff]['itemName'],
                                    'itemValue' => $datasourceFilter[$diff]['itemValue'],
                                    'select' => 2,
                                    'filter_type' => $datasourceFilter[$diff]['filter_type'],
                                ];
                            }
                        }
                    }
                }

                // 流程类筛选字段为表单控件，控件名称重新获取 strpos($result['datasource_type'], 'workflow') !== false
                if ($result['datasource_type'] == 'workflow' && $item['datasource_filter']) {
                    $workflowFilterKeys = array_column($item['datasource_filter'], 'itemValue');
                    $workflowFilter = array_column($item['datasource_filter'], null, 'itemValue');
                    foreach ($filter as $fkey => $fvalue) {
                        if (in_array($fvalue['itemValue'], $workflowFilterKeys) && $fvalue['itemName'] != $workflowFilter[$fvalue['itemValue']]['itemName']) {
                            $filter[$fkey]['itemName'] = $workflowFilter[$fvalue['itemValue']]['itemName'];
                        }
                    }
                }
            } else {
                $oldChartFilter = $chartFilter && $chartFilter->chart_search_filter ? json_decode($chartFilter->chart_search_filter, 1) : [];
                $filter = array_column($filter, null, 'itemValue');
                $filter = array_values($filter);
                foreach ($filter as $key => $value) {
                    if ($oldChartFilter && isset($oldChartFilter[$value['itemValue']]) && $oldChartFilter[$value['itemValue']] == 1) {
                        $filter[$key]['select'] = 1;
                    } else {
                        $filter[$key]['select'] = 2;
                    }
                }
            }
        }
        if (isset($filter)) {
            foreach ($filter as $val) {
                if (isset($val['filter_type'])) {
                    if ($val['filter_type'] == "custom_selector") {
                        $result['filter']['finder'][$val['itemValue']] = $val;
                    }
                    if ($val['filter_type'] == "date") {
                        if (isset($val['itemValue']) && $datasource_type_str == "contract") {
                            if ($val['itemValue'] == "contractDate" && $datasource_group_by == "remind_date") {
                                continue;
                            }
                            if ($val['itemValue'] == "remind_date" && $datasource_group_by != "remind_date") {
                                continue;
                            }
                        }
                        $result['filter']['date'][] = $val;
                    }
                    if ($val['filter_type'] == "selector") {
                        $result['filter']['selector'][$val['itemValue']] = $val;
                    }
                    if ($val['filter_type'] == "singleton") {
                        $result['filter']['singleton'][] = $val;
                    }
                    if ($val['filter_type'] == "input") {
                        $result['filter']['input'][] = $val;
                    }
                    if ($val['filter_type'] == "range") {
                        $result['filter']['range'][] = $val;
                    }
                    if ($val['filter_type'] == "flow_form_data_input") {
                        $result['filter']['flowFormDataInput'][] = $val;
                    }
                    if (in_array($val['filter_type'], ['time', 'datetime', 'datetime_range', 'date_range', 'time_range', 'flowFormDataInput'])) {
                        $result['filter'][$val['filter_type']][] = $val;
                    }
                }
            }
        }
        return $result;
    }

    //获取分组依据和数据分析字段
    public function getChartExample($data)
    {
        $workflowID = $data['workflowID'];
        $result = [];
        //分组依据
        $result['datasource_group_by'] = ['DATA_1' => '测试字段1', 'DATA_2' => '测试字段2', 'creator' => '创建人', 'start_time' => '创建时间', 'end_time' => '结束时间'];
        //数据分析字段
        $result['datasource_data_analysis'] = ['DATA_1' => '测试字段1', 'DATA_2' => '测试字段2'];
        return $result;
    }

    //远程数据
    public function getOriginList($data)
    {
        $result = [];
        if (!empty($data['url'])) {
            $result = $this->getCurlData($data['url'] . "?op=update");
            $result = json_decode($result, true);
        }
        return $result;
    }

    //getCurlData
    public function getCurlData($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }

    //获得数据过滤字段
    public function getDatasourceFilterTest($param = array())
    {
        return [
            [
                //创建时间
                'filter_type' => 'date',
                'itemValue' => 'createTime',
                'itemName' => '创建时间',
            ],
            [
                //结束时间
                'filter_type' => 'date',
                'itemValue' => 'overTime',
                'itemName' => '结束时间',
            ],
            [
                //创建人
                'filter_type' => 'selector',
                'selector_type' => 'user',
                'itemValue' => 'creator',
                'itemName' => '创建人',
            ],
            [
                //状态
                'filter_type' => 'singleton',
                'itemValue' => 'status',
                'itemName' => '状态',
                'source' => ['0' => '所有', '2' => '执行中', '1' => '已完成'],
            ],
        ];
    }

    /**
     * 解析筛选条件字段,数据源过滤
     * @param array $data 数据源
     * @return bool
     */
    public function addSelectDatasourceFilter($data)
    {
        // 在筛选关联表report_filter中插入关联的数据
        $filters = [];
        if (isset($data['filters'])) {
            if (empty($data['filters'])) {
                if (isset($data['chart_id']) && isset($data['chart_search_filter'])) {
                    $filters = [
                        'chart_search_filter' => $data['chart_search_filter'],
                        'chart_search_filter_types' => $data['chart_search_filter_types'],
                        'chart_id' => $data['chart_id'],
                    ];
                } else {
                    $filters = [
                        // 'chart_add_select'    => 2,
                        'chart_search_filter' => '',
                        'chart_search_filter_types' => '',
                        'chart_id' => '',
                    ];
                }
            } else {
                // 过滤信息$datas
                $datas = json_decode($data['filters'], true);
                // 设置筛选开关
                // $filters['chart_add_select'] = isset($datas['chart_add_select']) ? $datas['chart_add_select'] : 2;
                // 开启筛选的字段
                $filters['chart_search_filter'] = isset($datas['chart_search_filter']) ? json_encode($datas['chart_search_filter']) : '';
                $filters['chart_search_filter_types'] = isset($datas['chart_search_filter_types']) ? json_encode($datas['chart_search_filter_types']) : '';
                // 关联的报表id
                $filters['chart_id'] = isset($data['chart_id']) && !empty($data['chart_id']) ? $data['chart_id'] : $datas['chart_id'];
            }
        } else {
            $filters = [
                // 'chart_add_select'    => 2,
                'chart_search_filter' => '',
                'chart_search_filter_types' => '',
                'chart_id' => '',
            ];
        }
        // 先判断report_filter表中是否有关联筛选，如果有，则更新，没有，则插入新数据
        $result = DB::table('report_filter')->where('chart_id', $filters['chart_id'])->first();
        if ($result) {
            $result = DB::table('report_filter')->where('chart_id', $filters['chart_id'])->update($filters);
        } else {
            DB::table('report_filter')->insert($filters);
        }
        return true;
    }

    /**
     * 编辑自定义报表数据
     * @param array $data 需要保存的数据
     * @return             是否保存成功
     */
    public function editCustomData($data, $own)
    {
        $user_id = $own['user_id'];
        $datasource_id = isset($data['datasource_id']) ? $data['datasource_id'] : '';
        // $chart_id = isset($data['chart_id']) ? $data['chart_id'] : '';    // 报表id
        // report_chart表的字段数据
        $report_data = [];
        $report_data['datasource_create_user'] = $user_id;
        $report_data['datasource_id'] = isset($data['datasource_id']) ? $data['datasource_id'] : '';
        $report_data['datasource_name'] = isset($data['datasource_name']) ? $data['datasource_name'] : '';
        $report_data['datasource_type'] = isset($data['datasource_type']) ? $data['datasource_type'] : '';
        $report_data['datasource_tag'] = isset($data['datasource_tag']) ? $data['datasource_tag'] : '';
        $report_data['chart_permission_user'] = isset($data['chart_permission_user']) ? $data['chart_permission_user'] : '';
        $report_data['chart_permission_role'] = isset($data['chart_permission_role']) ? $data['chart_permission_role'] : '';
        $report_data['chart_permission_dept'] = isset($data['chart_permission_dept']) ? $data['chart_permission_dept'] : '';
        $report_data['datasource_filter'] = isset($data['datasource_filter']) ? $data['datasource_filter'] : '';
        if (isset($data['custom_report'])) {
            // $check = $this->testData($data['custom_report'], $report_data['datasource_filter']);
            // if (isset($check['code'])) {
            //     return $check;
            // }
            $report_data['custom_report'] = json_encode($data['custom_report']);
        }
        // $itemData, ['chart_id' => $itemData['chart_id']]
        $result = app($this->reportDatasourceRepository)->updateData($report_data, ['datasource_id' => $datasource_id]);
        if ($result) {
            $this->updateChartFilter($datasource_id, json_decode($report_data['datasource_filter'], 1));
        }
        return $result;

    }

    //数据源移除筛选字段 对应报表需处理
    public function updateChartFilter($datasourceId, $filter)
    {
        $filter = array_column($filter, null, 'field_name');
        $chartFilters = app($this->reportFilterRepository)->getChartFilter(['datasource_id' => $datasourceId]);
        if ($chartFilters = $chartFilters->toArray()) {
            foreach ($chartFilters as $chartFilter) {
                $itemFilters = $chartFilter['chart_search_filter_types'] ? json_decode($chartFilter['chart_search_filter_types'], 1) : [];
                if ($itemFilters) {
                    $newFilter = [];
                    $change = false;
                    foreach($itemFilters as $itemFilter) {
                        if (!isset($filter[$itemFilter['itemValue']])) {
                            $change = true;
                        } else if ($itemFilter && !empty($filter[$itemFilter['itemValue']])) {
                            // 数据源字段类型变化不影响之前创建的
//                            if ($filter[$itemFilter['itemValue']]['show_name'] != $itemFilter['itemName'] || $filter[$itemFilter['itemValue']]['filter_type'] != $itemFilter['filter_type']) {
//                                $itemFilter['itemName'] = $filter[$itemFilter['itemValue']]['show_name'];
//                                $itemFilter['filter_type'] = $filter[$itemFilter['itemValue']]['filter_type'];
//                                $change = true;
//                            }
                            $newFilter[$itemFilter['itemValue']] = $itemFilter;
                        }
                    }
                    if ($change && $newFilter) {
                        app($this->reportFilterRepository)->updateData(['chart_search_filter_types' => json_encode($newFilter)], ['chart_id' =>$chartFilter['chart_id'] ]);
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }

    //保存自定义报表属性

    /**
     * 保存自定义报表数据
     * @param array $data 自定义数据
     * @param array $own 当前登录用户的各种信息
     * @return bool        是否保存成功
     */
    public function saveCustomData($data, $own)
    {
        $user_id = isset($own['user_id']) ? $own['user_id'] : '';
        // report_chart表的字段数据
        $report_data = [];
        $report_data['datasource_create_user'] = $user_id;
        $report_data['datasource_id'] = 0;
        $report_data['datasource_name'] = isset($data['datasource_name']) ? $data['datasource_name'] : '';
        $report_data['datasource_type'] = isset($data['datasource_type']) ? $data['datasource_type'] : '';
        $report_data['datasource_tag'] = isset($data['datasource_tag']) ? $data['datasource_tag'] : '';
        // $report_data['chart_permission_user'] = isset($data['chart_permission_user']) ? $data['chart_permission_user'] : '';
        // $report_data['chart_permission_role'] = isset($data['chart_permission_role']) ? $data['chart_permission_role'] : '';
        // $report_data['chart_permission_dept'] = isset($data['chart_permission_dept']) ? $data['chart_permission_dept'] : '';
        $report_data['datasource_filter'] = isset($data['datasource_filter']) ? $data['datasource_filter'] : '';
        if (isset($data['custom_report'])) {
            // $check = $this->testData($data['custom_report'], $report_data['datasource_filter']);
            // if (isset($check['code'])) {
            //     return $check;
            // }
            $report_data['custom_report'] = json_encode($data['custom_report']);
        }

        $result = app($this->reportDatasourceRepository)->insertData($report_data);
        return true;
    }

    public function testData($data, $filter)
    {
        if (isset($data['isSqlSearch']) && !empty($filter)) {
            if ($data['isSqlSearch']) {
                $sql = $data['content'];
            } else {
                $sql = "select * from " . $data['table_name'];
            }
            $param = [
                'database_id' => $data['database_id'],
                'sql' => $sql,
                'page' => 1,
                'limit' => 1,

            ];
            $result = app($this->externalDatabaseService)->getExternalDatabasesDataBySql($param);
            if (isset($result['list']) && isset($result['list'][0]) && $result['list']) {
                $res = $result['list'][0];
                $filter = json_decode($filter, true);
                $field_name = array_column($filter, 'field_name');
                foreach ($field_name as $key => $name) {
                    if (!key_exists($name, $res)) {
                        return ['code' => ['0x065009', 'report']];
                    }
                }

            }
        }

    }

    public function transferYield($data)
    {
        if (empty($data)) {
            return [];
        }
        foreach ($data as $index => $item) {
            yield $item;
        }
    }

    public function collectData($result, $statistics_type, $group_by, $field_name, $responseResult, $chart_type)
    {
        $result = json_decode(json_encode($result['list']), true);
        $ge = $this->transferYield($result);
        if ($statistics_type === '0') {
            foreach ($ge as $key => $item) {
                if (!array_key_exists($group_by, $item)) {
                    return ['code' => ['0x065007', 'report']];
                }
                $group = $item[$group_by];
                if (!isset($responseResult[$group])) {
                    $responseResult[$group] = 0;
                }
                ++$responseResult[$group];
            }
        } elseif ($statistics_type === '1') {
            foreach ($ge as $key => $item) {
                if (!array_key_exists($group_by, $item)) {
                    return ['code' => ['0x065007', 'report']];
                }
                $group = $item[$group_by];
                if (!isset($responseResult[$group])) {
                    $responseResult[$group] = 0;
                }
                if (!array_key_exists($field_name, $item)) {
                    return ['code' => ['0x065007', 'report']];
                }
                if (strstr($item[$field_name], ",")) {
                    $item[$field_name] = str_replace(",", "", $item[$field_name]);
                }
                $responseResult[$group] += (float)$item[$field_name];
            }
        } elseif ($statistics_type === '2') {
            if ($chart_type == "excel") {
                foreach ($ge as $key => $item) {
                    if (!array_key_exists($group_by, $item)) {
                        return ['code' => ['0x065007', 'report']];
                    }
                    $group = $item[$group_by];
                    if (!isset($responseResult[$group])) {
                        $responseResult[$group] = 0;
                    }
                    if (!array_key_exists($field_name, $item)) {
                        return ['code' => ['0x065007', 'report']];
                    }
                    $responseResult[$group] = $item[$field_name];
                }
            }

        }

        return $responseResult;
    }

    public function getUrlData($data)
    {
        $url = isset($data['url']) ? $data['url'] : '';
        $limit = isset($data['limit']) ? $data['limit'] : 10;
        $result = [];
        if (!empty($url)) {
            if (!is_int(strpos($url, '?'))) {
                $url .= '?';
            }
            if (!is_int(strpos($url, 'op'))) {
                $url .= "op=init";
            }
            try {
                // 限制仅支持http和https -- 相对路径不处理
                if (substr($url, 0, 1) === '/') {
                    $origin = getHttps($url);
                } else {
                    $options[] = [CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS];
                    $origin = getHttps($url, null, [], $options);
                }
                $originData = json_decode($origin, true);
                if (is_array($originData)) {
                    if (isset($originData['errcode'])) {
                        $message = $originData['errmsg'];
                        if ($message) {
                            if (preg_match('/^Protocol (.*) not supported or disabled in libcurl$/',$message)) {
                                $message = trans('report.only_support_http_and_https');
                            }
                            return ['code' => ['', $message], 'dynamic' => $message];
                        }
                    }
                    if (isset($originData['data'])) {
                        $result['list'] = array_slice($originData['data'], 0, $limit);
                    } else {
                        $result['list'] = array_slice($originData, 0, $limit);
                    }
                }
            } catch (Exception $e) {
                return false;
            }

        }
        return $result;
    }

    public function getImportData($data)
    {
        $result = [];
        $attachment_id = isset($data['attachment_id']) ? $data['attachment_id'] : '';
        $limit = isset($data['limit']) ? $data['limit'] : 10;
        if (!empty($attachment_id)) {
            $attchment = app($this->attachmentService)->getAttachmentInfo($attachment_id, []);
            if (!empty($attchment)) {
                $attchment_url = $attchment['url'];
                try {
                    $attchment_data = app($this->importExportService)->getExcelData($attchment_url, 1, 10000000);
                } catch (\Exception $exception) {
                    \Log::info($exception->getMessage());
                    return ['code' => ['load_file_error', 'report']];
                }
                $headers = $attchment_data[0];
                array_shift($attchment_data);
                $res = [];
                foreach ($attchment_data as $key => $value) {
                    $res[] = array_combine($headers, $value);
                }
                $result['list'] = array_slice($res, 0, $limit);
            }
        }
        return $result;
    }

    /**
     * 根据sql语句查询报表数据
     * @param array $data 自定义表单的数据
     * @return array       自定义报表图表数据
     */
    public function getCustomChart($data, $param)
    {
        $getType = isset($data['getType']) ? $data['getType'] : 0; //数据明细或数据统计
        $group_by = isset($data['group_by_field']) ? $data['group_by_field'] : ''; // 分组
        $searchFields = isset($data['searchFields']) ? $data['searchFields'] : ''; // 数据分析方式
        $groupByName = isset($data['group_by_name']) ? $data['group_by_name'] : ''; // 分组别名
        $chart_title = isset($data['chart_title']) ? $data['chart_title'] : ''; // 报表名称
        $chart_type = isset($data['chart_type']) ? $data['chart_type'] : 'line'; // 图表类型
        $datasource_id = isset($data['database_id']) ? $data['database_id'] : ""; //来自数据源
        $url = isset($data['url']) ? $data['url'] : ""; //来自文件
        $showData = isset($data['showData']) ? $data['showData'] : '';
        $attachment_id = isset($data['attachment_id']) ? $data['attachment_id'] : ''; // 来自附件
        $search = [];
        $chatSearch = isset($param['chart_search']) ? $param['chart_search'] : '';
        $byPage = isset($param['byPage']) ? $param['byPage'] : 0;
        if (!empty($chatSearch)) {
            $except = ['time_filter', 'time_year', 'time_month', 'dateType', 'dateValue'];
            foreach ($chatSearch as $key => $value) {
                if (!in_array($key, $except)) {
                    $search[$key] = [$value, 'like'];
                }
            }
        }
        if (!empty($datasource_id)) {
            if (isset($data['isSqlSearch']) && $data['isSqlSearch'] === true) {
                // 多表查询
                $sql = isset($data['content']) ? $data['content'] : ''; // sql语句
            } else {
                // 单表查询
                $tableName = isset($data['table_name']) ? $data['table_name'] : ''; // 表名
                $sql = "select * from $tableName where 1 = 1";
            }
            if ($byPage == 0) {
                $param['page'] = 0;
            }
            $own = !empty($param['user_info']) ? $param['user_info'] : own();
            $sql = $this->sqlReplace($sql, $own);
            // 数据库id和data语句参数
            $sqlParam = [
                'database_id' => $datasource_id,
                'sql' => $sql,
            ];
            $result = app($this->externalDatabaseService)->getExternalDatabasesDataBySql($sqlParam);
            if (!$result) {
                return ['code' => ['0x065010', 'report']];
            }
        } elseif (!empty($url)) {
            if (!is_int(strpos($url, '?'))) {
                $url .= '?';
            } else {
                $url .= '&';
            }
            if ($search) {
                $url .= "param=" . json_encode($search);
            }
            if (isset($param['project_id'])) {
                $url .= "&project_id=" . $param['project_id'];
            }
            $own = !empty($param['user_info']) ? $param['user_info'] : own();
            if ($own && isset($own['user_id'])) {
                $url .= "user_id=" . $own['user_id'];
            }
            $origin = getHttps($url);
            $res = json_decode($origin, true);
            $result['list'] = isset($res['data']) ? $res['data'] : $res;

        } elseif (!empty($attachment_id)) {
            $attchment = app($this->attachmentService)->getAttachmentInfo($attachment_id, []);
            if (!$attchment) {
                return ['code' => ['load_file_error', 'report']];
            }
            $attchment_url = $attchment['url'];
            $attchment_data = app($this->importExportService)->getExcelData($attchment_url, 1, 10000000);
            $res = [];
            if (isset($attchment_data[0])) {
                $headers = $attchment_data[0];
                array_shift($attchment_data);
                foreach ($attchment_data as $key => $value) {
                    $res[] = array_combine($headers, $value);
                }
            }
            $result['list'] = $res;

        }
        if (!$result || !isset($result['list'])) {
            return 'sql语句不合法';
        }
        if (!empty($search) && !isset($result['list']['filter'])) {
            foreach ($result['list'] as $key => $value) {
                $value = (array)$value;
                foreach ($search as $field => $condition) {
                    $filter = isset($condition[0]) ? $condition[0] : '';
                    if (!is_array($filter)) {
                        if (!isset($value[$field]) || strpos($value[$field], $filter) === false) {
                            unset($result['list'][$key]);
                        }
                    } else {
                        $type = $filter['type'] ?? '';
                        $conditionArray = explode(',', $filter['value']);
                        $begin = isset($conditionArray[0]) ? $conditionArray[0] : '';
                        $end = isset($conditionArray[1]) ? $conditionArray[1] : '';
                        if (isset($value[$field]) && ($value[$field] == date('Y-m-d', strtotime($value[$field])) || $value[$field] == date('Y-m-d H:i', strtotime($value[$field])) || $value[$field] == date('Y-m-d H:i:s', strtotime($value[$field])))) {
                            $fieldValue = $value[$field] ? Carbon::parse($value[$field])->toDateTimeString() : '';
                            $beginValue = $begin ? Carbon::parse($begin)->toDateTimeString() : '';
                            $endValue = $end ? Carbon::parse($end)->toDateTimeString() : '';
                            if (!$type || !isset($value[$field]) || !$value[$field] || ($beginValue && strtotime($beginValue) > strtotime($fieldValue)) || ($endValue && strtotime($endValue) < strtotime($fieldValue))) {
                                unset($result['list'][$key]);
                            }
                        } else {
                            unset($result['list'][$key]);
                        }
                    }
                }
            }
            $result['list'] = array_values($result['list']);
            $result['total'] = count($result['list']);
        }
        $copyResult = $result;
        $packageParam = [];
        if ($result) {
            // 输出数据格式统一,处理的结果，见群文件：报表接口开发文档
            $report_data = [];
            $template = [];
            $data = [];
            if (!empty($searchFields) && ($chart_type != 'excel' || ($chart_type == 'excel' && $getType == 0))) {
                foreach ($searchFields as $searchKey => $searchField) {
                    if (!isset($searchField['legend_name'])) {
                        return ['code' => ['0x065004', 'report']];
                    }
                    $legend_name = $searchField['legend_name'];
                    $field_name = isset($searchField['field_name']) ? $searchField['field_name'] : ''; // 查询字段
                    $statistics_type = isset($searchField['statistics_type']) ? $searchField['statistics_type'] : 0;
                    $responseResult = [];
                    //数据源收集数据
                    if (!empty($datasource_id)) {
                        $responseResult = $this->collectData($copyResult, $statistics_type, $group_by, $field_name, $responseResult, $chart_type);
                        // if($param['page'] == 0){
                        //     $param['page'] = 1;
                        //     while (!empty($result['list'])) {
                        //         $param['page']++;
                        //         $result = app($this->externalDatabaseService)->getExternalDatabasesDataBySql($param);
                        //         $responseResult = $this->collectData($result, $statistics_type, $group_by, $field_name, $responseResult, $chart_type);
                        //     }
                        // }
                        //来自文件收集数据
                    } elseif (!empty($url)) {
                        $responseResult = $this->collectData($result, $statistics_type, $group_by, $field_name, $responseResult, $chart_type);
                    } elseif (!empty($attachment_id)) {
                        $responseResult = $this->collectData($result, $statistics_type, $group_by, $field_name, $responseResult, $chart_type);
                    }
                    if (isset($responseResult['code'])) {
                        return $responseResult;
                    }
                    $result = $copyResult;
                    // ksort($responseResult);
                    $response = [];
                    foreach ($responseResult as $k => $value) {
                        $response[] = ['y' => $value, 'name' => $k];
                    }
                    $data[] = ['name' => $legend_name, 'group_by' => $groupByName, 'data' => $response];
                }
            } else if ($chart_type == 'excel' && $getType == 1) {
                $data = [];
                $address = [];
                $macth = [];
                foreach ($showData as $key => $value) {
                    if (isset($value['field_name']) && $value['field_name']) {
                        $field_name = $value['field_name'];
                        $legend_name = isset($value['legend_name']) ? $value['legend_name'] : $field_name;
                        if ($value['showType'] == 0) {
                            $res = ['name' => $legend_name, 'group_by' => $field_name, 'data' => array_column($result['list'], $field_name)];
                            array_push($data, $res);
                        } else if ($value['showType'] == 2) {
                            $currentData = array_column($result['list'], $field_name);
                            $res = ['name' => $legend_name, 'group_by' => $field_name, 'data' => $currentData];
                            $packageParam['aggregate'][$legend_name] = array_sum($currentData);
                            array_push($data, $res);
                        } else {
                            $template[] = $legend_name;
//                            $patten = '/\#(.+)\#/';  // 原正则中若拼接参数不换行，则参数为空
                            $patten = '/\#.*?\#/';
                            preg_match_all($patten, $value['field_name'], $variableName);
//                            if (!empty($variableName) && isset($variableName[0]) && isset($variableName[1])) {
//                                $macth = $variableName[1];
//                            }
                            if (!empty($variableName) && isset($variableName[0])) {
                                $macth = $variableName[0];
                            }
                            foreach ($result['list'] as $k => $v) {
                                $address[$k] = $value['field_name'];
                                if ($macth) {
                                    foreach ($macth as $p => $m) {
                                        $v = (array)$v;
                                        $m = trim($m, '#');
                                        $replace = isset($v[$m]) ? $v[$m] : '';
                                        $address[$k] = str_replace($variableName[0][$p], $replace, $address[$k]);
                                    }
                                } else {
                                    $address[$k] = $value['field_name'];
                                }
                            }
                            $res = ['name' => $legend_name, 'group_by' => $field_name, 'data' => $address];
                            array_push($data, $res);
                        }

                    }
                }
            }
            if ($byPage && $datasource_id) {
                //分页
                $param['page'] = isset($param['page']) ? $param['page'] : 0;
                $param['limit'] = isset($param['limit']) ? $param['limit'] : 10;
                $data[0]['total'] = isset($data[0]['total']) && $data[0]['total'] != null ? $data[0]['total'] : count($data[0]['data']);
                $page = ceil($data[0]['total'] / $param['limit']);
                if ($param['page'] > $page) {
                    $param['page'] = $page;
                }
                $start = $param['page'] * $param['limit'] - $param['limit'];
                foreach ($data as $key => $value) {
                    $data[$key]['data'] = array_slice($value['data'], $start, $param['limit']);
                }
            }
            $packageParam['total'] = $data[0]['total'] ?? 1;
            $packageParam['template'] = $template ? $template : [];
            $packageParam['showData'] = $showData ? $showData : [];
            // 获取echarts需要的报表数据
            $chart_data = [];
            //用来导出的数据结构
            $origin_chart_data = $data;
            try {
                $chart_data = app($this->reportDatasourceRepository)->packageReportData($chart_title, $data, $chart_type, $packageParam);
            } catch (\Exception $e) {
                $chart_data['err_msg'] = $e->getMessage() . ",  file:  " . $e->getFile() . ",  line: " . $e->getLine() . ",  code:  " . $e->getCode();
            }
            return ['chart_data' => $chart_data, 'chart_type' => strtolower($chart_type), 'origin_chart_data' => $origin_chart_data];

        }
        // try {

        // } catch(\Exception $e) {
        //     $data['err_msg'] = $e->getMessage() . ",  file:  " . $e->getFile() . ",  line: " . $e->getLine() . ",  code:  " . $e->getCode();
        // }
    }

    public function sqlReplace($sql, $own)
    {
        $systemConstants = [
            '#loginUserId#' => 'user_id',
            '#loginUserName#' => 'user_name',
            '#loginUserAccount#' => 'user_accounts',
            '#loginUserRoleId#' => 'role_id',
            '#loginUserDeptId#' => 'dept_id',
            '#loginUserDeptName#' => 'dept_name',
        ];

        $result = $sql;
        foreach ($systemConstants as $key => $item) {
            if (strpos($result, $key) !== false) {
                $value = $own[$item];
                if (is_array($value) && count($value) > 0) {
                    $value = $value[0];
                } /* else {
                $value = '';
                }*/
                $value = "'" . $value . "'";
                $result = str_replace($key, $value, $result);
            }
        }

        return $result;
    }
}
