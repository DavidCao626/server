<?php

namespace App\EofficeApp\Report\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Report\Entities\ReportDatasourceEntity;
use DB;

class ReportDatasourceRepository extends BaseRepository
{

    public function __construct(ReportDatasourceEntity $entity)
    {
        parent::__construct($entity);
    }

    //列表
    public function getAllList($data)
    {
        $default = [
            'fields' => ["*"],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['datasource_id' => 'asc'],
        ];
        $param = array_merge($default, array_filter($data));
        $query = $this->entity;
        return $query->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }
    //数量
    public function getTotalNum($data)
    {
        $default = ['search' => []];
        $param = array_merge($default, array_filter($data));
        $query = $this->entity;
        return $query->wheres($param['search'])->count();
    }

    //获得数据源数据
    public function findItem($id, $chart_id = '')
    {
        $result = array();
        if (!$chart_id) {
            $infoResult = $this->entity->where('datasource_id', $id)->get()->toArray();
        } else {
            $infoResult = $this->entity
                ->join('report_chart', 'report_chart.datasource_id', '=', 'report_datasource.datasource_id')
                ->where('report_datasource.datasource_id', $id)
                ->where('report_chart.chart_id', $chart_id)
                ->get()
                ->toArray();
        }
        if (isset($infoResult[0])) {
            $result = $infoResult[0];
            //from,to
            $group_by_filter = array();
            $group_by_filter['createTime'] = 'start_time';
            $group_by_filter['endTime'] = 'end_time';
            if (isset($result['datasource_group_by']) && is_array($group_by_filter)) {
                foreach ($group_by_filter as $k => $v) {
                    $result['datasource_group_by'] = str_replace($k, $v, $result['datasource_group_by']);
                }
            }
            $data_analysis_filter = array();
            //$data_analysis_filter['createTime'] = 'createTime';//from,to
            if (isset($result['datasource_data_analysis']) && is_array($data_analysis_filter)) {
                foreach ($data_analysis_filter as $k => $v) {
                    $result['datasource_data_analysis'] = str_replace($k, $v, $result['datasource_data_analysis']);
                }
            }
        }
        return $result;
    }

    //查找
    public function getItem($key, $value)
    {
        $infoResult = $this->entity->where($key, $value)
            ->get()
            ->toArray();
        return isset($infoResult[0]) ? $infoResult[0] : array();
    }
    /**
     * 获取数据源管理
     * @param  [type] &$tagRepository [description]
     * @param  [type] $data           [description]
     * @return [type]                 [description]
     */
    public function getDatasourceList(&$tagRepository, $data)
    {
        $resultList = [];
        $datasource = $this->getAllList($data);
        $tag_array = [];
        foreach ($datasource as $k => $v) {
            if (!empty($v['datasource_tag'])) {
                $tag_info = explode(",", $v['datasource_tag']);
                foreach ($tag_info as $tag) {
                    if (!isset($tag_array[$tag])) {
                        $tag_array[$tag] = array();
                        $tag_array[$tag]['datasource_list'] = array();
                    }
                    $tag_array[$tag]['datasource_list'][] = $v;
                }
            } else {
                $tag_array['else']['datasource_list'][] = $v;
            }
        }
        $tags = $tagRepository->getAllTag();
        foreach ($tags as $k => $v) {
            if (isset($tag_array[$v['tag_id']])) {
                $tag_array[$v['tag_id']] = array_merge($tag_array[$v['tag_id']], $v);
                $resultList[] = $tag_array[$v['tag_id']];
                unset($tag_array[$v['tag_id']]);
            }
        }
        if (!empty($tag_array)) {
            $array = [];
            $array['tag_title'] = '其他';
            $array['datasource_list'] = [];
            foreach ($tag_array as $v) {
                foreach ($v['datasource_list'] as $val) {
                    $array['datasource_list'][] = $val;
                }
            }
            $resultList[] = $array;
        }
        return $resultList;
    }

    //内部获得报表数据
    public function getOriginDate(&$origin, $datasource_type, $datasource_group_by, $datasource_data_analysis, $chart_search = "")
    {
        $data = [];
        $config = [];
        $report_config = config('report.datasource_types');

        if (count($report_config) > 0) {
            foreach ($report_config as $item) {
                if ($item['datasource_type'] == $datasource_type) {
                    $config = $item;
                    break;
                }
            }
        }
        $origin['show_time_filter'] = false;
        if (isset($config['time_filter']) && in_array($datasource_group_by, $config['time_filter'])) {
            $origin['show_time_filter'] = true;
        }
        if (empty($config)) {
            return $data;
        }
        $analysis = array();
        $tmp = explode(',', trim($datasource_data_analysis, ','));
        if (is_array($tmp)) {
            foreach ($tmp as $v) {
                if (!empty($v)) {
                    $analysis[$v] = $v;
                }

            }
        }
        if (empty($analysis)) {
            $analysis['count'] = "count";
        }
        //if(empty($chart_search['dateType']))  $chart_search['dateType'] = "year";
        if (is_array($chart_search)) {
            $systemDateSearchs = ['startDate', 'endDate', 'created_at', 'contractDate', 'remind_date', 'manager_time', 'date_range'];
            foreach ($systemDateSearchs as $systemDateSearch) {
                if (isset($chart_search[$systemDateSearch]) && !empty($chart_search[$systemDateSearch]) && isset($chart_search[$systemDateSearch]['type']) && !empty($chart_search[$systemDateSearch]['type'])) {
                    $chart_search[$systemDateSearch] = $chart_search[$systemDateSearch]['value'];
                }
            }
        }
        $data = app($config['datasource_from'][0])->{$config['datasource_from'][1]}($datasource_group_by, $analysis, $chart_search);
        if (!is_array($data)) {
            $data = [];
        }
        return $data;
    }

    /**
     * 获得报表数据，加载图表
     * @param  int     $datasource_id           数据源id
     * @param  array   &$datasource_info        数据源信息
     * @param  array   &$chart_info             数据库的搜索信息
     * @param  array   &$param                  页面传入的参数信息
     * @param  boolean $export                  是否是导出
     * @return array
     */
    public function getReportData($datasource_id, &$datasource_info, &$chart_info, &$param, $export = false, $datasource = '')
    {
        $data = [];
        if (empty($datasource_info)) {
            return $data;
        }
        $chart_id = isset($chart_info['chart_id']) ? $chart_info['chart_id'] : "";
        $title = isset($chart_info['chart_title']) ? $chart_info['chart_title'] : "";
        $chart_type = isset($chart_info['chart_type']) ? $chart_info['chart_type'] : "";
        if (isset($param['chart_type'])) {
            $chart_type = $param['chart_type'];
        }
        $chart_data = array();
        $datasource_type = isset($datasource_info['datasource_type']) ? $datasource_info['datasource_type'] : "";
        $datasource_group_by = isset($datasource_info['datasource_group_by']) ? $datasource_info['datasource_group_by'] : '';
        $datasource_data_analysis = isset($datasource_info['datasource_data_analysis']) ? $datasource_info['datasource_data_analysis'] : '';
        $chart_search = isset($chart_info['chart_search']) ? $chart_info['chart_search'] : '';
        $datasource_param = isset($datasource_info['datasource_param']) ? $datasource_info['datasource_param'] : "";
        //数据源表用来保存自定义URL、或者自定义数据源信息的字段
        $custom_report = isset($datasource_info['custom_report']) ? $datasource_info['custom_report'] : "";
        $chart_arr = array();
        $editMode = false;
        if (isset($param['editMode'])) {
            $editMode = json_decode($param['editMode']);
        }
        // 查看页面取这种方法。绑定的筛选条件和数据库保存的条件同时生效；以页面绑定的筛选条件为主
        if (!$editMode) {
            // 数据库中保存的搜索条件
            $chart_arr = isset($chart_search) && !empty($chart_search) ? json_decode($chart_search, true) : [];
            if (!is_array($chart_arr)) {
                $chart_arr = json_decode($chart_arr, true);
            }
            // 如果是初始化查看页面，则直接使用数据库中保存的搜索条件，不点击【重新加载报表】，获取不到页面的绑定值，因此默认直接读取数据库的筛选值值；
            if (isset($param['isInitExport'])) {

            } else {
                // 编辑或者查看时页面绑定的搜索条件
                $client = isset($param['chart_search']) ? json_decode($param['chart_search'], true) : [];
                // 获取保存的过滤字段
                $filters = DB::table('report_filter')->select('chart_search_filter')->where('chart_id', $chart_id)->pluck('chart_search_filter')->first();
                $filters = json_decode($filters, true);
                // 匹配保存的字段key和页面展示的key，如果设置了高级筛选，而传入的页面数据上没有这个key，则赋予此字段key为空
                if ($filters) {
                    foreach ($filters as $key => $value) {
                        if (!isset($client[$key])) {
                            $client[$key] = '';
                        }
                    }
                }
                // 最后搜索的参数为二者结合，没有匹配的按照页面的搜索条件为主
                // 保存入数据库的搜索条件key值添加入搜索条件中
                // dd($client);
                foreach ($client as $k => $v) {
                    $chart_arr[$k] = $client[$k];
                }
            }
        } else {
            // 适用于编辑和新建页面的情况
            //【重新加载按钮】请求时，只加载页面绑定的筛选条件,
            // 编辑或者新建初始化加载页面时，如果页面绑定的搜索条件为空，则传入数据库的搜索条件
            $chart_search = isset($chart_search) && !empty($chart_search) ? $chart_search : '{}';
            $chart_arr = isset($param['chart_search']) && json_decode($param['chart_search'], true) ? json_decode($param['chart_search'], true) : json_decode($chart_search, true);
            if (!is_array($chart_arr)) {
                $chart_arr = json_decode($chart_arr, true);
            }
        }
        if (empty($chart_type)) {
            $chart_type = "line";
        }

        if (isset($param['editMode'])) {
            unset($param['editMode']);
        }

        $data['chart_title'] = $title;
        $data['chart_data'] = $chart_data;
        $data['datasource_info'] = $datasource_info;
        $data['chart_info'] = $chart_info;
        $data['chart_type'] = $chart_type;
        $data['datasource_data_analysis'] = $datasource_data_analysis;

        //获取数据
        try {
            if (empty($datasource)) {
                $param_arr = $this->get_data_param($datasource_param);
                $chart_arr = array_merge($chart_arr, $param_arr);
                $chart_arr = $this->parse_chart_search($chart_arr);
                $chart_data = $this->getOriginDate($data, $datasource_type, $datasource_group_by, $datasource_data_analysis, $chart_arr);
            } else {
                $chart = [];
                foreach ($datasource as $key => $value) {
                    $param_arr = $value['idJson'];
                    $chart_arr = array_merge($chart_arr, $param_arr);
                    $chart_arr = $this->parse_chart_search($chart_arr);
                    $datasource_group_by = $value['group'];
                    $datasource_data_analysis = $value['analysis'];
                    if (!empty($datasource_group_by)) {
                        $chart_data = $this->getOriginDate($data, $datasource_type, $datasource_group_by, $datasource_data_analysis, $chart_arr);
                        if (!empty($chart_data)) {
                            foreach ($chart_data as $key => $value) {
                                $chart[] = $value;
                            }
                        }
                    }

                }
                $name_arr = [];
                foreach ($chart as $key => $value) {
                    $name = array_column($value['data'], 'name');
                    foreach ($name as $k => $v) {
                        if (!in_array($v, $name_arr)) {
                            $name_arr[] = $v;
                        }
                    }
                }
                if (!empty($name_arr)) {
                    foreach ($chart as $key => $value) {
                        $arr = array_column($value['data'], 'name');
                        $diff = array_diff($name_arr, $arr);
                        if (!empty($diff)) {
                            foreach ($diff as $k => $v) {
                                $last = ['name' => $v, 'y' => 0];
                                array_push($chart[$key]['data'], $last);
                            }
                        }
                    }
                }
                foreach ($chart as $key => $value) {
                    $chart[$key]['data'] = $this->second_array_unique_bykey($value['data'], 'name');
                    if (empty($chart[$key]['data'])) {
                        $chart[$key]['data'] = [];//[['name' => '', 'y' => '']];
                    }
                    sort($chart[$key]['data']);
                }

                $chart_data = $chart;
            }
        } catch (\Exception $e) {
            $data['err_msg'] = $e->getMessage() . ",  file:  " . $e->getFile() . ",  line: " . $e->getLine() . ",  code:  " . $e->getCode();
        }
        $data['chart_data'] = $chart_data;
        $data['origin_chart_data'] = $chart_data;
        $data['chart_search'] = $chart_arr;
        $data['chart_title'] = $title;
        $tmp_data = $chart_data;
        $key = current($tmp_data);
        if (!$export && isset($key['name'])) {
            if ($datasource_type != "custom") {
                $data['chart_data'] = $this->packageReportData($title, $data['chart_data'], $chart_type);
            }
        }
        return $data;
    }

    public function second_array_unique_bykey($arr, $key)
    {
        $tmp_arr = [];
        $tmp = [];
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmp_arr)) {
                $re = array_search($v[$key], $tmp_arr);
                unset($tmp_arr[$re]);
            }
            $tmp_arr[$k] = $v[$key];
        }

        foreach ($tmp_arr as $k => $v) {
            if (array_key_exists($k, $arr)) {
                $tmp[] = $arr[$k];
            }
        }
        return $tmp;
    }
    public function parse_chart_search($chart_arr)
    {
        foreach ($chart_arr as $k => $v) {
            if ($k == 'select') {
                unset($chart_arr[$k]);
            }
            // 排除未定义的值
            if (is_string($v) && strstr($v, 'undefined')) {
                unset($chart_arr[$k]);
            }
            if (isset($v['inputValue'])) {
                if ((trim($v['inputValue'])) === "") {
                    unset($chart_arr[$k]);
                }

            } else {
                if (is_string($v) && (trim($v)) === "") {
                    unset($chart_arr[$k]);

                }
            }
        }
        if (empty($chart_arr['time_filter'])) {
            $chart_arr['time_filter'] = "year";
        }

        if (empty($chart_arr['time_year'])) {
            $chart_arr['time_year'] = date("Y");
        }

        if (empty($chart_arr['time_month'])) {
            $chart_arr['time_month'] = 1;
        }

        if (isset($chart_arr['time_filter'])) {
            if ($chart_arr['time_filter'] == "undefined") {
                $chart_arr['time_filter'] = "year";
            }

            $chart_arr['dateType'] = $chart_arr['time_filter'];
            if ($chart_arr['time_filter'] == "year") {
                $year = date("Y");
                $chart_arr['dateValue'] = ($year - 4) . "-" . $year;
            }
            if ($chart_arr['time_filter'] == "quarter") {
                $chart_arr['dateValue'] = $chart_arr['time_year'];
            }
            if ($chart_arr['time_filter'] == "month") {
                $chart_arr['dateValue'] = $chart_arr['time_year'];
            }
            if ($chart_arr['time_filter'] == "day") {
                $chart_arr['dateValue'] = $chart_arr['time_year'] . "-" . $chart_arr['time_month'];
            }
        }
        return $chart_arr;

    }

    public function get_data_param($param)
    {
        $result = array();
        if (!empty($param)) {
            $str = json_decode($param, true);
            if (!empty($str)) {
                foreach ($str as $k => $v) {
                    $result[$k] = $v;
                }
            }
        }
        return $result;
    }
    //packageReportData
    public function packageReportData($title = "", &$data, $chart_type = "", $param = '')
    {
        if (empty($data)) {
            return [];
        }
        if (empty($chart_type)) {
            $chart_type = "line";
        }
        if ($chart_type == 'line') {
            $data = $this->packageLine($title, $data);
        }

        if ($chart_type == 'column') {
            $data = $this->packageColumn($title, $data);
        }

        if ($chart_type == 'pie') {
            $data = $this->packagePie($title, $data);
        }

        if ($chart_type == 'bar') {
            $data = $this->packageBar($title, $data);
        }

        if ($chart_type == 'area') {
            $data = $this->packageArea($title, $data);
        }

        if ($chart_type == 'scatter') {
            $data = $this->packageScatter($title, $data);
        }

        if ($chart_type == 'bubble') {
            $data = $this->packageBubble($title, $data);
        }

        if ($chart_type == 'line_polar') {
            $data = $this->packageLinePolar($title, $data);
        }

        if ($chart_type == 'excel') {
            $data = $this->packageExcel($title, $data, $param);
        }

        if ($chart_type == 'spline') {
            $data = $this->packageSpline($title, $data);
        }

        if ($chart_type == 'column_stack') {
            $data = $this->packageColumnStack($title, $data);
        }

        if ($chart_type == 'bar_stack') {
            $data = $this->packageBarStack($title, $data);
        }

        if ($chart_type == 'area_stack') {
            $data = $this->packageAreaStack($title, $data);
        }

        return $data;
    }
    private $dataZoomCount = 20;
    //line/折线图
    public function packageLine($title, &$data)
    {
        $result = [];
        //$result['tooltip'] = ['trigger'=>'item','formatter'=>"{b} <br/>{a} : {c}"];
        $result['tooltip'] = ['trigger' => 'axis'];
        $result['legend']['data'] = [];
        //$result['legend']['x']='right';
        //$result['legend']['y']='bottom';
        $result['legend']['orient'] = 'vertical';
        $result['legend']['align'] = 'right';
//        $result['tooltip']['position'] = ['20%', '30%'];
        $result['legend']['x'] = 'right';
        $result['legend']['y'] = 'top';
        $result['grid']['y2'] = '22%';
        // 图表距离顶部高度
        $result['grid']['y'] = '22%';
        $result['grid']['x'] = '22%';
        $result['titleName'] = $title;
        // $result['title']['text']                  = $title;
        // $result['title']['textStyle']             = [];
        // $result['title']['textStyle']['fontSize'] = '14';
        // $result['title']['textStyle']['color'] = '#373a3c';
        $result['yAxis']['type'] = 'value';
        $result['xAxis'] = ['type' => 'category', 'boundaryGap' => 'false', 'data' => []];
        $result['xAxis']['axisLabel']['interval'] = 0;
        $result['xAxis']['axisLabel']['rotate'] = "-45";
        $result['series'] = [];
        foreach ($data as $v) {
            $result['yAxis']['name'] = isset($v['title']) ? $v['title'] : '(个)';
            $result['legend']['data'][] = $v['name'];
            //$key = current($v['data']);
            //$result['yAxis']['name'] = isset($v['remark'])?$v['remark']:"";
            $row = [];
            $row['name'] = $v['name'];
            $row['type'] = 'line';
            $row['smooth'] = true;
            // $row['itemStyle']['normal']['label']['show'] = true;
            $row['data'] = [];
            // x轴的列名,属性xAxis下面的data
            foreach ($v['data'] as $val) {
                if (!in_array($val['name'], $result['xAxis']['data'])) {
                    $result['xAxis']['data'][] = $val['name'];
                }
                if (isset($val['tips'])) {
                    $row['itemStyle']['normal']['label']['formatter'] = "{b}";
                    $row['data'][] = ['value' => $val['y'], 'name' => $val['tips']];
                    $result['tooltip']['formatter'] = "{b}";
                } else {
                    $row['data'][] = $val['y'];
                }
            }
            $result['series'][] = $row;
        }
        if (isset($result['xAxis']['data']) && count($result['xAxis']['data']) > $this->dataZoomCount) {
            $result['dataZoom'][] = ['start' => 0, 'end' => 5];
        }
        return $result;
    }

    //column/柱状图
    public function packageColumn($title, &$data)
    {
        $result = [];
        $result['titleName'] = $title;
        // $result['title']['text']                = $title;
        // $result['title']['textStyle']             = [];
        // $result['title']['textStyle']['fontSize'] = '14';
        // $result['title']['textStyle']['color'] = '#373a3c';
        $result['legend']['data'] = [];
        $result['legend']['x'] = 'right';
        $result['legend']['y'] = 'bottom';
        $result['legend']['y'] = 20;
        $result['tooltip'] = ['trigger' => 'axis', 'axisPointer' => ['type' => 'shadow']];
        $result['yAxis']['splitLine'] = ['show' => true];
        $result['series'] = [];
        $result['grid']['y2'] = '22%';
        $result['grid']['x'] = '22%';
        $result['xAxis']['axisLabel']['rotate'] = "-45";
//        $result['tooltip']['position'] = ['20%', '30%'];
        $result['xAxis']['data'] = [];
        foreach ($data as $v) {
            $result['yAxis']['name'] = isset($v['title']) ? $v['title'] : '(个)';
            $result['legend']['data'][] = $v['name'];
            $row = [];
            $row['type'] = 'bar';
            $row['name'] = $v['name'];
            $row['data'] = [];
            // $row['itemStyle']['normal']['label']['show'] = true;
            $row['itemStyle']['normal']['label']['position'] = 'top';
            // $row['barWidth']            = 50;
            foreach ($v['data'] as $val) {
                if (!in_array($val['name'], $result['xAxis']['data'])) {
                    $result['xAxis']['data'][] = $val['name'];
                }
                if (isset($val['tips'])) {
                    $row['itemStyle']['normal']['label']['formatter'] = "{b}";
                    $row['data'][] = ['value' => $val['y'], 'name' => $val['tips']];
                    $result['tooltip']['formatter'] = "{b}";
                } else {
                    $row['data'][] = $val['y'];
                }
            }
            $result['series'][] = $row;
            $result['xAxis']['axisLabel']['interval'] = 0;
        }
        if (isset($result['xAxis']['data']) && count($result['xAxis']['data']) > $this->dataZoomCount) {
            $result['dataZoom'][] = ['start' => 0, 'end' => 5];
        }
        return $result;
    }

    //pie/饼状图
    public function packagePie($title, &$data)
    {
        $result = [];
        $result['tooltip'] = [
            'trigger' => 'item',
            'formatter' => "{b} <br/>{a} : {c}",
            'extraCssText' => 'width: 150px; word-break: break-all; white-space: normal',
        ];
        //$result['tooltip'] = ['trigger'=>'axis'];
        $result['series'] = [];
        $result['titleName'] = $title;
        // $result['title']['text']                  = $title;
        // $result['title']['textStyle']             = [];
        // $result['title']['textStyle']['fontSize'] = '14';
        // $result['title']['textStyle']['color'] = '#373a3c';
        $i = 0;
        foreach ($data as $v) {
            if ($i > 0) {
                continue;
            }
            $i++;
            $key = current($v['data']);
            $row = [];
            $row['name'] = isset($v['name']) ? $v['name'] : "";
            $row['type'] = 'pie';
            $row['center'] = array('50%', '50%');
            $row['radius'] = '55%';
            $row['data'] = [];
            $row['label']['normal'] = [
                'formatter' => '{b}: {@2012} ({d}%)',
            ];
            foreach ($v['data'] as $val) {
                $row['data'][] = array('value' => $val['y'], 'name' => $val['name']);
            }
            $result['series'][] = $row;
        }

        return $result;
    }

    //bar/条形图
    public function packageBar($title, &$data)
    {
        $result = [];
        $result['titleName'] = $title;
        // $result['title']['text'] = $title;
        // $result['title']['textStyle']             = [];
        // $result['title']['textStyle']['fontSize'] = '14';
        // $result['title']['textStyle']['color'] = '#373a3c';
        //$result['title']['subtext'] = "";
        $result['tooltip'] = ['trigger' => 'axis', 'axisPointer' => ['type' => 'shadow']];
        $result['legend']['data'] = [];
        $result['legend']['x'] = 'right';
        $result['legend']['y'] = 'bottom';
        $result['legend']['y'] = 20;
        $result['grid'] = ['left' => '3%', 'right' => '4%', 'bottom' => '3%', 'containLabel' => true];
        $result['xAxis'] = ['type' => 'value', 'boundaryGap' => [0, 0.01]];
        $result['yAxis'] = ['type' => 'category', 'data' => []];
        $result['grid']['x'] = '22%';
        $result['grid']['y2'] = '22%';
        $result['yAxis']['axisLabel']['interval'] = 0;
//        $result['tooltip']['position'] = ['20%', '30%'];
        //$result['yAxis']['axisLabel']['rotate'] = -45;
        $result['series'] = [];
        $result['yAxis']['data'] = [];
        foreach ($data as $v) {
            $result['xAxis']['name'] = isset($v['title']) ? $v['title'] : '(个)';
            $result['legend']['data'][] = $v['name'];
            $row = [];
            $row['type'] = 'bar';
            $row['name'] = $v['name'];
            $row['data'] = [];
            // $row['itemStyle']['normal']['label']['show'] = true;
            $row['itemStyle']['normal']['label']['position'] = 'right';
            foreach ($v['data'] as $val) {
                if (!in_array($val['name'], $result['yAxis']['data'])) {
                    $result['yAxis']['data'][] = $val['name'];
                }
                if (isset($val['tips'])) {
                    $row['itemStyle']['normal']['label']['formatter'] = "{b}";
                    $row['data'][] = ['value' => $val['y'], 'name' => $val['tips']];
                    $result['tooltip']['formatter'] = "{b}";
                } else {
                    $row['data'][] = $val['y'];
                }
            }
            $result['series'][] = $row;
        }
        if (isset($result['yAxis']['data']) && count($result['yAxis']['data']) > $this->dataZoomCount) {
            $result['dataZoom'][] = ['start' => 0, 'end' => 5, 'yAxisIndex' => 0];
        }
        return $result;
    }

    //area/面积图
    public function packageArea($title, &$data)
    {
        $result = [];
        $result['titleName'] = $title;
        // $result['title']['text'] = $title;
        // $result['title']['textStyle']             = [];
        // $result['title']['textStyle']['fontSize'] = '14';
        // $result['title']['textStyle']['color'] = '#373a3c';
        //$result['title']['subtext'] = "";
        $result['tooltip'] = ['trigger' => 'axis'];
//        $result['tooltip']['position'] = ['20%', '30%'];
        $result['legend']['data'] = [];
        $result['legend']['x'] = 'right';
        $result['legend']['y'] = 'bottom';
        $result['legend']['y'] = 20;
        $result['xAxis'] = ['type' => 'category', 'boundaryGap' => false, 'data' => []];
        $result['xAxis']['axisLabel']['interval'] = 0;
        $result['xAxis']['axisLabel']['rotate'] = '-45';
        $result['yAxis'] = ['type' => 'value'];
        $result['series'] = [];
        $result['grid']['y2'] = '22%';
        $result['grid']['x'] = '20%';
        foreach ($data as $v) {
            $result['yAxis']['name'] = isset($v['title']) ? $v['title'] : '(个)';
            $result['legend']['data'][] = $v['name'];
            $row = [];
            $row['type'] = 'line';
            $row['name'] = $v['name'];
            $row['smooth'] = true;
            $row['data'] = [];
            $row['itemStyle']['normal']['areaStyle'] = ['type' => 'default'];
            // $row['itemStyle']['normal']['label']['show'] = true;
            $row['itemStyle']['normal']['label']['position'] = 'top';
            foreach ($v['data'] as $val) {
                if (!in_array($val['name'], $result['xAxis']['data'])) {
                    $result['xAxis']['data'][] = $val['name'];
                }
                if (isset($val['tips'])) {
                    $row['itemStyle']['normal']['label']['formatter'] = "{b}";
                    $row['data'][] = ['value' => $val['y'], 'name' => $val['tips']];
                    $result['tooltip']['formatter'] = "{b}";
                } else {
                    $row['data'][] = $val['y'];
                }
            }
            $result['series'][] = $row;
        }
        if (isset($result['xAxis']['data']) && count($result['xAxis']['data']) > $this->dataZoomCount) {
            $result['dataZoom'][] = ['start' => 0, 'end' => 5];
        }
        return $result;
    }

    //scatter/散点图
    public function packageScatter($title, &$data)
    {
        $result = [];
        $result['titleName'] = $title;
        // $result['title']['text'] = $title;
        // $result['title']['textStyle']             = [];
        // $result['title']['textStyle']['fontSize'] = '14';
        // $result['title']['textStyle']['color'] = '#373a3c';
        //$result['title']['subtext'] = "";
        $result['tooltip'] = ['trigger' => 'axis', 'axisPointer' => ['type' => 'shadow', 'show' => true, 'lineStyle' => ['type' => 'dashed', 'width' => 1]]];
        $result['legend']['data'] = [];
        $result['legend']['x'] = 'right';
        $result['legend']['y'] = 'bottom';
        $result['grid']['y2'] = '22%';
        $result['grid']['x'] = '20%';
//        $result['tooltip']['position'] = ['20%', '30%'];
        $result['legend']['y'] = 20;
        $result['xAxis'] = ['type' => 'category'];
        $result['xAxis']['axisLabel']['interval'] = 0;
        $result['xAxis']['axisLabel']['rotate'] = "-45";
        $result['yAxis'] = ['type' => 'value', 'splitNumber' => 8, 'scale' => true];
        $result['series'] = [];
        $result['xAxis']['data'] = [];
        foreach ($data as $v) {
            $result['yAxis']['name'] = isset($v['title']) ? $v['title'] : '(个)';
            $result['legend']['data'][] = $v['name'];
            $row = [];
            $row['type'] = 'scatter';
            $row['name'] = $v['name'];
            //$row['symbolSize'] = 5;
            $row['data'] = [];
            // $row['itemStyle']['normal']['label']['show'] = true;
            $row['itemStyle']['normal']['label']['position'] = 'top';
            foreach ($v['data'] as $val) {
                if (!in_array($val['name'], $result['xAxis']['data'])) {
                    $result['xAxis']['data'][] = $val['name'];
                }
                if (isset($val['tips'])) {
                    $row['itemStyle']['normal']['label']['formatter'] = "{b}";
                    $row['data'][] = ['value' => $val['y'], 'name' => $val['tips']];
                    $result['tooltip']['formatter'] = "{b}";
                } else {
                    $row['data'][] = $val['y'];
                }
            }
            $result['series'][] = $row;
        }
        if (isset($result['xAxis']['data']) && count($result['xAxis']['data']) > $this->dataZoomCount) {
            $result['dataZoom'][] = ['start' => 0, 'end' => 5];
        }
        return $result;
    }

    //bubble/气泡图
    public function packageBubble($title, &$data)
    {
        $result = [];
        $result['titleName'] = $title;
        // $result['title']['text'] = $title;
        // $result['title']['textStyle']             = [];
        // $result['title']['textStyle']['fontSize'] = '14';
        // $result['title']['textStyle']['color'] = '#373a3c';
        //$result['title']['subtext'] = "";
        $result['tooltip'] = ['trigger' => 'axis', 'axisPointer' => ['type' => 'shadow', 'show' => true, 'lineStyle' => ['type' => 'dashed', 'width' => 1]]];
        $result['legend']['data'] = [];
        $result['legend']['x'] = 'right';
        $result['legend']['y'] = 'bottom';
        $result['legend']['y'] = 20;
        $result['xAxis'] = ['type' => 'category'];
        $result['xAxis']['axisLabel']['interval'] = 0;
        $result['xAxis']['axisLabel']['rotate'] = "-45";
        $result['grid']['y2'] = '22%';
        $result['grid']['x'] = '20%';
//        $result['tooltip']['position'] = ['20%', '30%'];
        $result['yAxis'] = ['type' => 'value', 'splitNumber' => 8, 'scale' => true];
        $result['series'] = [];
        $result['xAxis']['data'] = [];
        foreach ($data as $v) {
            $result['yAxis']['name'] = isset($v['title']) ? $v['title'] : '(个)';
            $result['legend']['data'][] = $v['name'];
            $row = [];
            $row['type'] = 'scatter';
            $row['name'] = $v['name'];
            $row['symbolSize'] = 39;
            $row['animationDelay'] = 200;
            $row['data'] = [];
            // $row['itemStyle']['normal']['label']['show'] = true;
            $row['itemStyle']['normal']['label']['position'] = 'top';
            foreach ($v['data'] as $val) {
                if (!in_array($val['name'], $result['xAxis']['data'])) {
                    $result['xAxis']['data'][] = $val['name'];
                }
                if (isset($val['tips'])) {
                    $row['itemStyle']['normal']['label']['formatter'] = "{b}";
                    $row['data'][] = ['value' => $val['y'], 'name' => $val['tips']];
                    $result['tooltip']['formatter'] = "{b}";
                } else {
                    $row['data'][] = $val['y'];
                }
            }
            $result['series'][] = $row;
        }
        if (isset($result['xAxis']['data']) && count($result['xAxis']['data']) > $this->dataZoomCount) {
            $result['dataZoom'][] = ['start' => 0, 'end' => 5];
        }
        return $result;
    }

    //line_polar/雷达图
    public function packageLinePolar($title, &$data)
    {
        $result = [];
        $result['titleName'] = $title;
        // $result['title']['text'] = $title;
        // $result['title']['textStyle']             = [];
        // $result['title']['textStyle']['fontSize'] = '14';
        // $result['title']['textStyle']['color'] = '#373a3c';
        //$result['tooltip'] = ['trigger'=>'axis','show'=>true];
        $result['tooltip'] = ['trigger' => 'item'];
        $result['legend'] = ['x' => 'right', 'y' => 'top'];
        $result['legend']['x'] = 'right';
        $result['legend']['y'] = 'bottom';
        $result['legend']['y'] = 20;
        $result['legend']['data'] = [];
        $result['tooltip']['position'] = ['50%', '10%'];
        $result['polar']['indicator'] = [];
        $result['series'] = [];
        $row = [];
        $row['type'] = 'radar';
        $row['name'] = "";
        $row['data'] = [];
        foreach ($data as $v) {
            $result['legend']['data'][] = $v['name'];
            $count = count($v['data']);
            $tmp = [];
            $tmp['name'] = $v['name'];
            // $row['itemStyle']['normal']['label']['show'] = true;
            $row['itemStyle']['normal']['label']['position'] = 'top';
            $row['data'] = [];
            if (!empty($v['data'])) {
            foreach ($v['data'] as $k => $val) {
                if (count($result['polar']['indicator']) < $count) {
                    $result['polar']['indicator'][] = ['text' => $val['name']];
                }
                $tmp['value'][] = $val['y'];
            }
            $row['data'][] = $tmp;
        }
        }
        $result['series'][] = $row;
        return $result;
    }

    //动态图
    public function packageSpline($title, &$chart_data)
    {
        $data = $this->packageLine($title, $chart_data);
        // if ($datasource_type == "custom") {
        //     if (!empty($chart_arr['url'])) {
        //         $data['data_url'] = $chart_arr['url'];
        //     }
        // }
        return $data;
    }
    public function array_merge_more($keys, $arrs)
    {
        $arrs = array_merge($arrs,[]);
        // 检查参数是否正确
        if (!$keys || !is_array($keys) || !$arrs || !is_array($arrs) || count($keys) != count($arrs)) {
            return array();
        } // 一维数组中最大长度
        $max_len = 0; //整理数据，把所有一维数组转重新索引
        for ($i = 0, $len = count($arrs); $i < $len; $i++) {
            $arrs[$i] = array_values($arrs[$i]);
            if (count($arrs[$i]) > $max_len) {
                $max_len = count($arrs[$i]);
            }
        }
        // 合拼数据
        $result = array();
        for ($i = 0; $i < $max_len; $i++) {
            $tmp = array();
            foreach ($keys as $k => $v) {
                $res = isset($arrs[$k][$i])?$arrs[$k][$i]:'';
                // if (isset($arrs[$k][$i])) {
                    $tmp[$v] = $res;
                // }
            }
            $result[] = $tmp;
        }
        return $result;
    }
    //EXCEL
    public function packageExcel($title, &$chart_data, $param)
    {
        $data = [];
        $header = [];
        $arr = [];
        $columns = [];
        $i = 0;
        if (isset($chart_data[0]['data'])) {
            $header = array_column($chart_data[0]['data'], 'name');
            $group = isset($chart_data[0]['group_by']) ? $chart_data[0]['group_by'] : "";
        }
        // array_unshift($header, $group);
        $data[$i] = $header;
        foreach ($chart_data as $v) {
            if (isset($v['data']) && $v['data']) {
                foreach ($v['data'] as $value) {
                    if (is_array($value) && array_key_exists('y',$value)) {
                        $flag = true;
                        break;
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
                // if (isset($v['data'][0]['y'])) {
                //     $arr[0] = $group;
                //     $i++;
                //     $data[$i] = array_column($v['data'], 'y');
                //     $arr[$i] = isset($v['name']) ? $v['name'] : '';
                //     // array_unshift($data[$i], isset($v['name']) ? $v['name'] : '');

                // } else {
                //     $data[$i] = $v['data'];
                //     $arr[$i] = isset($v['name']) ? $v['name'] : '';
                //     // array_unshift($data[$i], isset($v['name']) ? $v['name'] : '');
                //     $i++;
                // }
            }
        }
        $res = $this->array_merge_more($arr, $data);
        $template = isset($param['template']) ? $param['template'] : [];
        if(isset($param['showData']) && !empty($param['showData'])){
            foreach ($param['showData'] as $key => $value) {
                $title = isset($value['legend_name'])?$value['legend_name']:'';
                $value['title'] = $title;
                if (in_array($title, $template)) {
                    $value['directive'] =  true;
                }
                if($value['showType'] == '2'){
                    $value['aggregate'] = true;
                }
                $columns[$title] = $value;
            }
        }else{
            foreach ($arr as $key => $value) {
                if (in_array($value, $template)) {
                    $columns[$value] = ['title' => $value, 'directive' => true];
                } else {
                    $columns[$value] = ['title' => $value];
                }
            }
        }
        $return = ['total' => isset($param['total']) ? $param['total'] : count($chart_data[0]['data']), 'list' => $res, 'columns' => $columns, 'columnsArray' => $arr];
        if(isset($param['aggregate']) && !empty($param['aggregate'])){
            $return['aggregate'] = $param['aggregate'];
        }
        return $return;
        // foreach ($chart_data as $v) {
        //     $i++;
        //     if (isset($v['data'])) {
        //         if (!isset($header[0])) {
        //             $header[0] = isset($v['group_by']) ? $v['group_by'] : "";
        //         }

        //         $row   = [];
        //         $row[] = isset($v['name']) ? $v['name'] : "";
        //         foreach ($v['data'] as $k => $val) {
        //             // 获取第一列的类名，相当于x轴的数据
        //             $name = isset($val['name']) ? $val['name'] : "";
        //             if (!in_array($name, $header) && $name !== "") {
        //                 $header[] = $name;
        //             }
        //             // $value = isset($val['y']) ? $val['y'] : "";
        //             // if ($name === '') {
        //             //     $row[] = '';
        //             // } else {

        //             // 获取相当于y轴的数据
        //             $row[] = isset($val['y']) ? $val['y'] : "";

        //             // }
        //         }
        //         $data[$i] = $row;
        //     }
        // }
        // $data[0] = $header;
        // // 当有多个图例（3条独立的数据时），y值为空，也需要显示多条空数据

        // $yAxisCount = count($header) - 1; // y轴数据的数量
        // if ($yAxisCount >= 1) {
        //     foreach ($data as $k => &$v) {
        //         // 当y轴数据和header头数据（就是x轴的数据）数量不一样时，说明excel有些地方会显示空，空的地方也要显示出0
        //         if (count($v) - 1 != $yAxisCount) {
        //             for ($k = 1; $k <= $yAxisCount; $k++) {
        //                 if (!isset($v[$k]) || $v[$k] === '') {
        //                     $v[$k] = '0';
        //                 }
        //             }
        //             for ($k = $yAxisCount; $k <= count($v) - 1; $k++) {
        //                 unset($v[$k]);
        //             }
        //             $v = array_values($v);
        //         }
        //     }
        // };
        // return $data;
    }

    //column_stack/堆栈柱状图
    public function packageColumnStack($title, &$data)
    {
        $result = [];
        $result['titleName'] = $title;
        // $result['title']['text']                = $title;
        // $result['title']['textStyle']             = [];
        // $result['title']['textStyle']['fontSize'] = '14';
        // $result['title']['textStyle']['color'] = '#373a3c';
        $result['legend']['data'] = [];
        $result['legend']['x'] = 'right';
        $result['legend']['y'] = 'bottom';
        $result['legend']['y'] = 20;
        $result['tooltip'] = ['trigger' => 'axis', 'axisPointer' => ['type' => 'shadow']];
        $result['yAxis']['splitLine'] = ['show' => true];
        $result['series'] = [];
        $result['grid']['x'] = '22%';
        $result['grid']['y2'] = '22%';
//        $result['tooltip']['position'] = ['20%', '30%'];
        $result['xAxis']['axisLabel']['rotate'] = "-45";
        $result['xAxis']['data'] = [];
        foreach ($data as $v) {
            $result['yAxis']['name'] = isset($v['title']) ? $v['title'] : '(个)';
            $result['legend']['data'][] = $v['name'];
            $row = [];
            $row['type'] = 'bar';
            $row['name'] = $v['name'];
            $row['data'] = [];
            // $row['barWidth']            = 50;
            // $row['itemStyle']['normal']['label']['show'] = true;
            $row['itemStyle']['normal']['label']['position'] = 'top';
            $row['stack'] = 'stack';
            foreach ($v['data'] as $val) {
                if (!in_array($val['name'], $result['xAxis']['data'])) {
                    $result['xAxis']['data'][] = $val['name'];
                }
                if (isset($val['tips'])) {
                    $row['itemStyle']['normal']['label']['formatter'] = "{b}";
                    $row['data'][] = ['value' => $val['y'], 'name' => $val['tips']];
                    $result['tooltip']['formatter'] = "{b}";
                } else {
                    $row['data'][] = $val['y'];
                }
            }
            $result['series'][] = $row;
            $result['xAxis']['axisLabel']['interval'] = 0;
        }
        if (isset($result['xAxis']['data']) && count($result['xAxis']['data']) > $this->dataZoomCount) {
            $result['dataZoom'][] = ['start' => 0, 'end' => 5];
        }
        return $result;
    }

    //bar_stack/堆栈条形图
    public function packageBarStack($title, &$data)
    {
        $result = [];
        $result['titleName'] = $title;
        // $result['title']['text'] = $title;
        // $result['title']['textStyle']             = [];
        // $result['title']['textStyle']['fontSize'] = '14';
        // $result['title']['textStyle']['color'] = '#373a3c';
        //$result['title']['subtext'] = "";
        $result['tooltip'] = ['trigger' => 'axis', 'axisPointer' => ['type' => 'shadow']];
        $result['legend']['data'] = [];
//        $result['tooltip']['position'] = ['20%', '30%'];
        $result['legend']['x'] = 'right';
        $result['legend']['y'] = 'bottom';
        $result['legend']['y'] = 20;
        $result['grid'] = ['left' => '3%', 'right' => '4%', 'bottom' => '3%', 'containLabel' => true];
        $result['xAxis'] = ['type' => 'value', 'boundaryGap' => [0, 0.01]];
        $result['yAxis'] = ['type' => 'category', 'data' => []];
        $result['grid']['x'] = '22%';
        $result['grid']['y2'] = '22%';
        $result['yAxis']['axisLabel']['interval'] = 0;
        //$result['yAxis']['axisLabel']['rotate'] = -45;
        $result['series'] = [];
        foreach ($data as $v) {
            $result['xAxis']['name'] = isset($v['title']) ? $v['title'] : '(个)';
            $result['legend']['data'][] = $v['name'];
            $row = [];
            $row['type'] = 'bar';
            $row['name'] = $v['name'];
            $row['data'] = [];
            $row['stack'] = 'stack';
            // $row['itemStyle']['normal']['label']['show'] = true;
            $row['itemStyle']['normal']['label']['position'] = 'right';
            foreach ($v['data'] as $val) {
                $result['yAxis']['data'][] = $val['name'];
                if (isset($val['tips'])) {
                    $row['itemStyle']['normal']['label']['formatter'] = "{b}";
                    $row['data'][] = ['value' => $val['y'], 'name' => $val['tips']];
                    $result['tooltip']['formatter'] = "{b}";
                } else {
                    $row['data'][] = $val['y'];
                }
            }
            $result['series'][] = $row;
        }
        if (isset($result['yAxis']['data']) && count($result['yAxis']['data']) > $this->dataZoomCount) {
            $result['dataZoom'][] = ['start' => 0, 'end' => 5, 'yAxisIndex' => 0];
        }
        return $result;
    }

    //area_stack/堆栈面积图
    public function packageAreaStack($title, &$data)
    {
        $result = [];
        $result['titleName'] = $title;
        // $result['title']['text'] = $title;
        // $result['title']['textStyle']             = [];
        // $result['title']['textStyle']['fontSize'] = '14';
        // $result['title']['textStyle']['color'] = '#373a3c';
        //$result['title']['subtext'] = "";
        $result['tooltip'] = ['trigger' => 'axis'];
        $result['legend']['data'] = [];
//        $result['tooltip']['position'] = ['20%', '30%'];
        $result['legend']['x'] = 'right';
        $result['legend']['y'] = 'bottom';
        $result['legend']['y'] = 20;
        $result['xAxis'] = ['type' => 'category', 'boundaryGap' => false, 'data' => []];
        $result['xAxis']['axisLabel']['interval'] = 0;
        $result['xAxis']['axisLabel']['rotate'] = "-45";
        $result['grid']['y2'] = '22%';
        $result['grid']['x'] = '22%';
        $result['yAxis'] = ['type' => 'value'];
        $result['series'] = [];
        foreach ($data as $v) {
            $result['yAxis']['name'] = isset($v['title']) ? $v['title'] : '(个)';
            $result['legend']['data'][] = $v['name'];
            $row = [];
            $row['type'] = 'line';
            $row['name'] = $v['name'];
            $row['smooth'] = true;
            $row['data'] = [];
            // $row['itemStyle']['normal']['label']['show'] = true;
            $row['itemStyle']['normal']['label']['position'] = 'top';
            $row['itemStyle']['normal']['areaStyle'] = ['type' => 'default'];
            $row['stack'] = 'stack';
            foreach ($v['data'] as $val) {
                if (!in_array($val['name'], $result['xAxis']['data'])) {
                    $result['xAxis']['data'][] = $val['name'];
                }
                if (isset($val['tips'])) {
                    $row['itemStyle']['normal']['label']['formatter'] = "{b}";
                    $row['data'][] = ['value' => $val['y'], 'name' => $val['tips']];
                    $result['tooltip']['formatter'] = "{b}";
                } else {
                    $row['data'][] = $val['y'];
                }
            }
            $result['series'][] = $row;
        }
        if (isset($result['xAxis']['data']) && count($result['xAxis']['data']) > $this->dataZoomCount) {
            $result['dataZoom'][] = ['start' => 0, 'end' => 5];
        }
        return $result;
    }
}
