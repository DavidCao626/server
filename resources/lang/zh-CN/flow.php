<?php
return [
    // 数字类型的依次在这后面增加，请注意英文包里同步增加，保持中英文包里两边顺序一致，便于查询且不容易遗漏
    "0x030000"                                              => "年",
    "0x030001"                                              => "流程不存在",
    "0x030002"                                              => "流水号已经达到设置的上限，无法新建流程，请联系管理员修改设置或删除无用工作流！",
    "0x030003"                                              => "流水号跟历史流水号重复，请联系系统管理员修改设置！",
    "0x030004"                                              => "流程已经被查看过，无法修改签办反馈！",
    "0x030005"                                              => "流程已提交或委托",
    "0x030006"                                              => "此流程已经办理，建议刷新列表或页面",
    "0x030007"                                              => "签办反馈删除失败",
    "0x030008"                                              => "未找到相应的签办反馈数据",
    "0x030009"                                              => "固定流程",
    "0x030010"                                              => "自由流程",
    "0x030011"                                              => "第:process_id步",
    "0x030012"                                              => "请指定下一步的办理人员",
    "0x030013"                                              => "当前委托规则列表没有数据",
    "0x030014"                                              => "第，process_id，步",
    "0x030015"                                              => "主办人",
    "0x030016"                                              => "经办人",
    "0x030017"                                              => "未查看",
    "0x030018"                                              => "已查看",
    "0x030019"                                              => "已提交",
    "0x030020"                                              => "已办理",
    "0x030021"                                              => "没有抄送人员",
    "0x030022"                                              => "没有此流程的办理权限",
    "0x030023"                                              => "定义流程数据错误，请联系系统管理员检查！",
    "0x030024"                                              => "流程创建失败",
    "0x030025"                                              => "邮件发送失败，请联系系统管理员设置系统邮箱!",
    "0x030026"                                              => "正常",
    "0x030027"                                              => "重要",
    "0x030028"                                              => "紧急",
    "0x030029"                                              => "流水号",
    "0x030030"                                              => "流程标题",
    "0x030031"                                              => "表单不存在",
    "0x030032"                                              => "流程序号最大不能超过10000",
    "0x030033"                                              => "导入参数配置错误，请联系系统管理员或重新导出模板录入数据再导入",
    "0x030034"                                              => "首节点不能删除！",
    "0x030035"                                              => "等于",
    "0x030036"                                              => "大于",
    "0x030037"                                              => "小于",
    "0x030038"                                              => "大于等于",
    "0x030039"                                              => "小于等于",
    "0x030040"                                              => "不等于",
    "0x030041"                                              => "字符等于",
    "0x030042"                                              => "开始字符",
    "0x030043"                                              => "包含字符",
    "0x030044"                                              => "结束字符",
    "0x030045"                                              => "不包括字符",
    "0x030046"                                              => "包含于",
    "0x030047"                                              => "字符不等于",
    "0x030048"                                              => "明细字段值合计标识",
    "0x030049"                                              => "自增ID",
    "0x030050"                                              => "流程ID",
    "0x030051"                                              => "表单ID",
    "0x030052"                                              => "原表单控件ID",
    "0x030053"                                              => "更新后的表单控件ID",
    "0x030054"                                              => "操作类型",
    "0x030055"                                              => "操作时间",
    "0x030056"                                              => "操作人",
    "0x030057"                                              => "目标节点[:select_process_name]设置了智能获取办理人员：关联节点[:process_name]的主办人。根据智能获取条件和关联节点的办理方式设置，目标节点没有符合条件的办理人，请与系统管理员联系！",
    "0x030058"                                              => "目标节点[:select_process_name]设置了智能获取办理人员：关联节点[:process_name]的主办人。流程尚未从关联节点流转过，根据智能获取条件，目标节点没有符合条件的办理人，请与系统管理员联系！",
    "0x030059"                                              => "天",
    "0x030060"                                              => "小时",
    "0x030061"                                              => "分钟",
    "0x030062"                                              => "秒",
    "0x030063"                                              => "流程: :run_name run_id: :run_id 被: :user_name 删除。",
    "0x030064"                                              => "离超时时间还剩",
    "0x030065"                                              => "超时时间已过期",
    "0x030066"                                              => "流程状态",
    "0x030067"                                              => "紧急程度",
    "0x030068"                                              => "流程开始时间",
    "0x030069"                                              => "已完成",
    "0x030070"                                              => "执行中",
    "0x030071"                                              => "签名人",
    "0x030072"                                              => "动态信息控件不支持导出！",
    "0x030073"                                              => "流程名称",
    "0x030074"                                              => "创建人",
    "0x030075"                                              => "创建时间",
    "0x030076"                                              => "步骤名称",
    "0x030077"                                              => "步骤状态",
    "0x030078"                                              => "办理人员",
    "0x030079"                                              => "提交时间",
    "0x030080"                                              => "催促时间",
    "0x030081"                                              => "超时时间",
    "0x030082"                                              => "最新步骤",
    "0x030083"                                              => "最新步骤未办理人",
    "0x030084"                                              => "抄送人员",
    "0x030085"                                              => "抄送时间",
    "0x030086"                                              => "起始节点",
    "0x030087"                                              => "定义流程: :flow_name  flow_id: :flow_id 被: :user_name 删除",
    "0x030088"                                              => "已结束",
    "0x030089"                                              => "首节点",
    "0x030090"                                              => "定义流程ID",
    "0x030091"                                              => "运行流程ID",
    "0x030092"                                              => "流程表单ID",
    "0x030093"                                              => "流程节点ID",
    "0x030094"                                              => "运行步骤ID",
    "0x030095"                                              => "相关附件",
    "0x030096"                                              => "签办反馈",
    "0x030097"                                              => "相关文档",
    "0x030098"                                              => "流程创建人ID",
    "0x030099"                                              => "流程提交人ID",
    "0x030100"                                              => "创建子流程失败, 失败原因",
    "0x030101"                                              => "节点",
    "0x030102"                                              => "创建子流程【:run_name】成功",
    "0x030103"                                              => "数据外发失败, 失败原因",
    "0x030104"                                              => "数据外发成功",
    "0x030105"                                              => "由[:parent_run_name]触发的: :flow_run_name",
    "0x030106"                                              => "流程：:flow_name flow_id: :flow_id的角色: :role_name替换为: :role_replace_name",
    "0x030107"                                              => "流程：:flow_name flow_id: :flow_id的部门: :dept_name替换为: :dept_replace_name",
    "0x030108"                                              => "流程：:flow_name flow_id: :flow_id的人员: :user_name替换为: :user_replace_name",
    "0x030109"                                              => "流程开始日期",
    "0x030110"                                              => "流程结束日期",
    "0x030111"                                              => "所有",
    "0x030112"                                              => "流程创建人",
    "0x030113"                                              => "结束时间",
    "0x030114"                                              => "数量",
    "0x030115"                                              => "空",
    "0x030116"                                              => "其他",
    "0x030117"                                              => "第一季度",
    "0x030118"                                              => "第二季度",
    "0x030119"                                              => "第三季度",
    "0x030120"                                              => "第四季度",
    "0x030121"                                              => "成功",
    "0x030122"                                              => "失败",
    "0x030123"                                              => "表单中有必填字段未填写",
    "0x030124"                                              => "该流程未开启隐藏选择办理人页面",
    "0x030125"                                              => "该步骤向下有多个满足出口条件的流出节点",
    "0x030126"                                              => "下个节点有多个办理人，只能手动选择后提交",
    "0x030127"                                              => "不满足出口条件",
    "0x030128"                                              => "当前节点需要会签且有人员未办理",
    "0x030129"                                              => "当前节点需要触发子流程",
    "0x030130"                                              => "当前节点需要子流程办理完成才可向下流转",
    "0x030131"                                              => "满足直接提交条件，但提交过程失败",
    "0x030132"                                              => "当前节点未设置可结束流程",
    "0x030133"                                              => "当前用户不是此节点主办人",
    "0x030134"                                              => "满足直接结束条件，但结束过程失败",
    "0x030135"                                              => "未能找到正确的流程表单，请联系管理员检查表单模板规则设置！",
    "0x030136"                                              => "未能找到正确的流程表单，请联系管理员检查流程打印模板规则设置！",
    "0x030137"                                              => "未能找到正确的流程表单，请联系管理员检查流程归档模板规则设置！",
    "0x030138"                                              => "流程模板规则错误，流程表单展示失败，请联系管理员检查流程模板规则设置！",
    "0x030139"                                              => "根据当前人员的流程查看/参与权限，未能找到正确的流程表单，请联系管理员检查相关设置！",
    "0x030140"                                              => "未能找到正确的流程表单，请联系管理员打开模板规则设置里的其他人员规则！",
    "0x030141"                                              => "流程归档",
    "0x030142"                                              => "合计",
    "0x030143"                                              => "请至少选择一种监控权限",
    "0x030144"                                              => "未获取上一节点的主办人，不能收回",
    "0x030145"                                              => "该紧急程度选项已有流程使用，不可删除",
    "0x030146"                                              => "紧急程度选项不能为空",
    "0x030147"                                              => "紧急程度选项名称不能为空",
    "0x030148"                                              => "保存失败",
    "0x030149"                                              => "当前节点[:select_process_name]设置了智能获取抄送人员：关联节点[:process_name]的主办人。根据智能获取条件和关联节点的办理方式设置，当前节点没有符合条件的抄送人，请与系统管理员联系！",
    "0x030150"                                              => "当前节点[:select_process_name]设置了智能获取抄送人员：关联节点[:process_name]的主办人。流程尚未从关联节点流转过，根据智能获取条件，当前节点没有符合条件的抄送人，请与系统管理员联系！",
    "0x030151"                                              => "此流程已产生数据，流程节点不能删除",
    "0x030152"                                              => "被退回的流程再次提交时直接提交至退回节点可选择办理人",
    "0x030153"                                              => "办理时间",
    "0x030154"                                              => "保存失败，未获取到流程ID",
    "0x030155"                                              => "保存失败，未获取到流程节点ID",
    "0x030156"                                              => "监控规则不能为空",
    "0x030157"                                              => "参数异常，请刷新页面重试",
    "0x030158"                                              => "当前节点不允许委托",
    "0x030159"                                              => "委托您办理的流程不能再委托给其他人",
    "0x030160"                                              => "委托办理的流程不能再委托回去",
    "0x030161"                                              => "流程标题有误",
    "0x030162"                                              => "请填写流程标题",
    "0x030163"                                              => "明细控件【:control_title】有必填字段未填写，请填写相应字段",
    "0x030164"                                              => "必填字段【:control_title】未填写，请填写相应字段",
    "0x030165"                                              => "请填写签办反馈",
    "0x030166"                                              => "请上传相关附件",
    "0x030167"                                              => "请设置至少一个表单控件",
    "0x030168"                                              => "数据验证失败",
    "0x030169"                                              => "超时时间不能早于当前时间",
    "0x030170"                                              => "接收时间",
    "0x030171"                                              => "查看时间",
    "0x030172"                                              => "第:pieces条失败",
    "0x030173"                                              => "结束时间不得早于开始时间或当前时间",
    "0x030174"                                              => "详情",
    "0x030175"                                              => "数据外发完成",
    "0x030176"                                              => "电子签章控件不支持导出！",
    "0x030177"                                              => "超时日期",
    "0x030178"                                              => "当前流程办理状态已发生改变，请重新刷新页面后办理",
    "0x030179"                                              => "交办给您办理的流程不能再委托给其他人",
    // "0x030180"                                              => "并发分支之间节点不能交叉！",
    // "0x030181"                                              => "首节点不能设置非强制合并！",
    // "0x030182"                                              => "首节点不能设置强制合并！",
    "0x030183"                                              => "流程处于汇总节点，尚有其他节点未提交至此",
    "0x030184"                                              => "保存步骤信息失败，步骤ID建议大于等于【:step_id】",
    "0x030180"                                              => "存在与已有步骤ID重复的ID:【:step_id】，保存步骤信息失败",
    "0x030181"                                              => "保存步骤信息失败，步骤ID建议大于等于【:step_id】",
    "0x030182"                                              => "流程已被系统自动提交，建议刷新列表或页面",
    "0x030185"                                              => "根据抄送人设置，默认被抄送人只在:submit_type时生效",
    "0x030186"                                              => "流程办理状态发生变化，请尝试刷新页面",
    "0x030187"                                              => "排序ID",
    // 非数字类型的依次在这后面增加，请注意英文包里同步增加，保持中英文包里两边顺序一致，便于查询且不容易遗漏
    "datetime"                                              => "日期时间",
    "flow_define_name"                                      => "定义流程名称",
    "flow_id"                                               => "定义流程ID",
    "form_name"                                             => "表单名称",
    "form_id"                                               => "表单ID",
    "flow_creator"                                          => "流程创建人名称",
    "flow_create_time"                                      => "流程创建时间",
    "unclassified"                                          => "未分类",
    "textarea"                                              => "多行文本框",
    "input"                                                 => "单行文本框",
    "radio"                                                 => "单选框",
    "check_box"                                             => "复选框",
    "dropdown_box"                                          => "下拉框",
    "editor"                                                => "编辑器",
    "system_data"                                           => "系统数据",
    "signature_picture"                                     => "签名图片",
    "countersign_control"                                   => "会签控件",
    "dynamic_information"                                   => "动态信息",
    "attachments_upload"                                    => "附件上传",
    "detail_layout"                                         => "明细布局",
    "electronic_signature"                                  => "电子签章",
    "flow_set"                                              => "流程设置",
    "node_set"                                              => "节点设置",
    "flow"                                                  => "流程",
    "node"                                                  => "节点",
    "default_user"                                          => "流程办理人设置-默认主办人",
    "default_hander"                                        => "流程办理人设置-默认办理人",
    "hander_user"                                           => "流程办理人设置-办理人",
    "auto_rule_user"                                        => "流程办理人设置-智能获取办理人规则",
    "free_flow_user"                                        => "流程基本信息设置-自由流程办理人",
    "manage_user"                                           => "流程监控人设置-监控人",
    "manage_scope_user"                                     => "流程监控人设置-监控人范围",
    "cope_user"                                             => "流程抄送人设置-抄送人",
    "auto_cope_user"                                        => "流程抄送人设置-智能获取抄送人规则",
    "agency_user"                                           => "委托设置-被委托人",
    "anency_user_done"                                      => "已产生的流程委托-被委托人",
    "type_user"                                             => "流程分类-管理人员",
    "form_user"                                             => "表单分类-管理人员",
    "template_user"                                         => "模板设置-表单模板规则中的人员",
    "filing_template_user"                                  => "模板设置-归档模板规则中的人员",
    "print_template_user"                                   => "模板设置-打印模板规则中的人员",
    "run_user"                                              => "运行流程办理人",
    "run_user_done"                                         => "已完成流程办理人",
    "undefined"                                             => "未知",
    "flow_type"                                             => "流程分类",
    "flow_form_type"                                        => "表单分类",
    "run_flow"                                              => "运行流程",
    "run_flow_done"                                         => '已完成流程',
    "flow_seting"                                           => "流程设置",
    "flow_type_seting"                                      => "流程分类设置",
    "flow_form_type_seting"                                 => "表单分类设置",
    "flow_run_user"                                         => "运行流程办理人",
    "flow_run_user_done"                                    => "已完成流程办理人",
    "flow_batch_set"                                        => "流程批量设置",
    "node_batch_set"                                        => "节点批量设置",
    "flow_node_name"                                        => "节点名称",
    "logging_sources"                                       => "日志来源",
    "workflow_basic_information_modification"               => "流程基本信息修改",
    "workflow_monitoring_modification"                      => "流程监控修改",
    "process_settings_others_information_settings"          => "流程设置-其他信息设置",
    "workflow_settings_base_information_settings"           => "流程设置-基本设置",
    "workflow_settings_filing_settings"                     => "流程设置-归档设置",
    "workflow_settings_print_template_settings"             => "流程设置-打印模板设置",
    "workflow_settings_end_remind_settings"                 => "流程设置-流程结束提醒对象设置",
    "workflow_settings_form_data_template_settings"         => "流程设置-表单数据模板设置",
    "workflow_node_settings_node_base_information_settings" => "流程节点设置-节点信息设置",
    "workflow_node_settings_node_form_template_settings"    => "流程节点设置-节点表单模板设置",
    "workflow_node_settings_field_control_settings"         => "流程节点设置-字段控制设置",
    "workflow_node_settings_copy_user_settings"             => "流程节点设置-抄送人员设置",
    "workflow_node_settings_handle_user_settings"           => "流程节点设置-办理人员设置",
    "workflow_settings_free_workflow_required_settings"     => "流程设置-必填设置",
    "workflow_node_settings_sonflow_settings"               => "流程节点设置-子流程设置",
    "workflow_node_settings_outsend_settings"               => "流程节点设置-数据外发设置",
    "unified_set_press_time"                                => "统一设置催促时间",
    "workflow_node_settings"                                => "流程节点设置",
    "process_sort"                                          => "节点排序",
    "process_to_set"                                        => "出口设置",
    "flow_map_node_info_update"                             => "流程图节点信息更新",
    "flow_map_delete_node_link"                             => "流程图删除节点连线",
    "flow_map_delete_all_node_link"                         => "流程图删除所有节点连线",
    "undefined_flow"                                        => "未知流程",
    "define_worlflow_other_set_data_error"                  => "定义流程-其他设置数据错误，请联系系统管理员检查！",
    "workflow_node_settings_data_validation"                => "流程节点设置-数据验证",
    "copy"                                                  => " - 副本",
    "outsendlogone"                                         => "，数据外发失败，失败原因：节点信息不全",
    "outsendlogtwo"                                         => "，数据外发失败，失败原因：外发配对参数结构错误",
    "outsendlogthree"                                       => "，数据外发失败，失败原因：节点信息获取失败",
    "detail_layout_fields_0"                                => "控件名称",
    "detail_layout_fields_1"                                => "控件类型",
    "detail_layout_fields_2"                                => "应填入值的类型",
    "detail_layout_fields_3"                                => "明细字段",
    "detail_layout_fields_4"                                => "明细字段说明",
    "detail_layout_fields_5"                                => "控件数据",
    "detail_layout_fields_6"                                => "控件数据ID",
    "detail_layout_fields_7"                                => "不可导入",
    "detail_layout_fields_8"                                => "附件ID或插入图片",
    "detail_layout_fields_text"                             => "单行文本框",
    "detail_layout_fields_textarea"                         => "多行文本框",
    "detail_layout_fields_radio"                            => "单选",
    "detail_layout_fields_checkbox"                         => "复选",
    "detail_layout_fields_select"                           => "下拉框",
    "detail_layout_fields_editor"                           => "编辑器",
    "detail_layout_fields_data_selector"                    => "系统数据",
    "detail_layout_fields_upload"                           => "附件上传",
    "detail_layout_fields_dynamic_info"                     => "动态信息",
    "detail_layout_fields_title"                            => "未知",
    "deleted_user"                                          => "已删除用户",
    "flow_type_required"                                    => "流程分类不能为空",
    "flow_type_name"                                        => "流程分类名称",
    "free_process_can_not_submit"                           => "当前节点可以自定义流转步骤",
    'system_remind'                                         => '系统催办',
    "agency"                                                => '委托',
    "check"                                                 => '查看',
    "examine"                                               => '办理',
    "launch"                                                => '新建',
    "flow_overtime_to_next"                                 => '流程超时，已流转到下一个节点',
    "flow_will_overtime"                                    => '流程即将超时，请办理流程',
    "flow_overtimed"                                        => '流程已超时，请办理流程',
    "flow_overtime_day_must_be_positive"                    => '天数必须是正整数',
    "hours_are_positive_real_numbers"                       => '小时为正实数且只能保留一位小数',
    "reminder_cannot_greater_than_overtime"                 => '超时提醒时间不能比超时时间大',
    "flow_overtime_to_end"                                  => '流程超时，已结束流转',
    "retransmission"                                        => '转发',
    'cc'                                                    => '抄送',
    "edit_node_condition"                                   => "编辑出口条件",
    "create_flow_process"                                   => "新建节点",
    "delete_flow_process"                                   => "删除节点",
    "with_or_without_outsend"                               => '是否外发',
    "with_outsend"                                          => "是",
    "without_outsend"                                       => "否",
    "have_not_outsend"                                      => "尚未外发",
    "aggregate_field"                                       => "合计字段",
    "urge_time_cannot_earlier_than_present_time"            => "超时时间不能早于当前时间！",
    "the_prompt_cannot_be_empty"                            => "请配置数据验证:data_validate_key的提示文字",
	"flow_have_end"                                         => "此流程已提交",
	"flow_concurrent_error"                                 => "流程分支之间有交叉，暂不支持设置并发",
	"flow_multi_concurrent_error"                           => "并发线上的节点暂不支持再设置并发",
	"flow_multi_force_error"                                => "并发线上的节点暂不支持再设置合并",
	"flow_concurrent_merge_error"                           => "并发节点不支持直接连接合并节点",
	"flow_merge_concurrent_error"                           => "合并节点不支持退回并发节点",
	"flow_branch_node_merge_error"                          => "并发分支上的节点不支持直接连接合并节点",
	"flow_branch_node_beyond_merge_error"                   => "并发分支上的节点不支持连接合并节点之后的节点",
	"flow_beyond_merge_branch_node_error"                   => "合并节点之后的节点不支持与并发分支上的节点相连",
	"flow_have_takeback"                                    => "此流程已提交",
    "workflow_node_settings_overtime"                       => "流程节点设置-超时设置",
    "workflow_settings_overtime"                            => "流程设置-超时设置",
    "main_flow"                                             => "主流程",
    "sub_flow"                                              => "子流程",
    "please_handle_current_flow"                            => "请办理当前流程！",
    "flow_end_time"                                         => "流程结束时间",
    "has_conflict_entrust_rule"                             => "【:flow_name】流程已存在时间段冲突的委托规则",
    "dependent_field_wrong"                                 => "，数据外发失败，失败原因：依赖字段数据为空",
    "dependent_run_id_has_no_log"                           => "，数据外发失败，失败原因：指定流程没有外发相关的模块数据",
    "flow_material_version_too_high"                        => "【:flow_name】流程素材版本高于当前系统，请联系OA管理员升级系统到最新版后再导入！",
    "update"                                                => "更新",
    "delete"                                                => "删除",
    "data"                                                  => "数据",
    "reason"                                                => "原因是 ",
    "flow_stop"                                             => '已停用',
    "flow_material_version_too_high"                        => "【:flow_name】流程素材版本高于当前系统，请联系系统管理员升级系统到最新版后再导入！",
    "form_material_version_too_high"                        => "【:form_name】表单素材版本高于当前系统，请联系系统管理员升级系统到最新版后再导入！",
    "form_source_code_version_too_high"                     => "表单源代码版本高于当前系统，请联系系统管理员升级系统到最新版后再导入！",
	"merge_process_turn_back_error"                         => "合并节点不能退回到与本节点无连线的节点！",
	"process_can_not_relation"                              => "并发分支之间节点暂不支持交叉！",
	"can_not_concurrent_with_merge"                         => "暂不支持既是并发节点又是合并节点！",
    "dependent_field_data_wrong"                            => "依赖字段的数据必须为数字！",
    "submit"                                                => "提交",
    "back"                                                  => "退回",
    "end"                                                   => "结束",
    "already_overtime_submit"                               => "流程已被超时自动提交，建议刷新列表或页面",
	"please_choose_more_than_two_nodes"                     => "本节点是强制并发节点，请选择至少两个流出节点",
    "open_debug_mode"                                       => "开启了调试模式",
    "unique_id_has_no_data"                                 => "没有当前ID对应的模块数据",
    "outsend_failed_since_attachment_null"                  => "，数据外发失败，失败原因：流程表单附件不存在，请收回流程，处理后再提交",
    "flow_save_error_tip"                                   => "没有权限或此流程已办理，即将自动刷新或直接关闭当前页面。",
    'host_already_leave'                                    => '主办人已离职',
    "dependent_unique_id_has_no_log"                        => "，数据外发失败，失败原因：指定ID :id 没有模块数据",
    'host_already_leave'                                    => '目标节点主办人已离职或已删除',
    'process_cannot_find_the_sponsor'                       => '因修改了流程设置，导致流程找不到主办人无法继续流转，请联系管理员',
	'process_force_merge_set_error'                         => '强制并发节点应当有两个以上的流出节点，请注意流出节点的节点序号应该大于强制并发节点的序号',
    "a_node_is_on_two_concurrent_branches"                  => "将该节点设置为并发节点后，【:node_name】节点将会同时存在多条分支上，暂时不支持这种设置",
    "two_node_is_on_one_concurrent_branches"                => "将该节点设置为并发节点后，【:node_name】和【:node_name2】两个并发节点将会在一条分支上，暂时不支持这种设置",
    "a_concurrent_and_a_branche"                            => "同一个节点并发出来的分支节点最后只能流向同一个合并节点，当前节点只能连向合并节点【:node_name】",
    "a_concurrent_and_a_branche_save"                       => "将该节点设置为合并节点后，【:node_name】所在的分支和其他分支没有流向同一个合并节点【:node_name2】，暂时不支持这种设置",
    "concurrent_set_normal"                                 => "该节点由合并节点设置为普通节点后，会同时存在多条分支上，暂时不支持这种设置",
    "concurrent_node_connection"                            => "【:node_name】流向的其它节点只能在【:node_name1】-【:node_name2】-【:node_name3】组成的分支线上",
    "a_node_is_on_two_concurrent_branches_chart"            => "将节点连线后，【:node_name】节点将会同时存在多条分支上，暂时不支持这种设置",
    "concurrent_node_not_same_merge"                        => "将该节点设置为并发节点后，【:node_name】节点并发出去的多条分支没有流向同一个合并节点，暂时不支持这种设置",
    "connect_to_the_same_merge_node"                        => "该分支线上的最后一个节点才能流向合并节点",
    "merging_node_rollback"                                 => "合并节点暂时只支持退回到该分支上的最后一个节点",
    "last_node_can_set_to_merge"                            => "【:node_name】节点所在的分支上只有最后一个节点才能流向合并节点",
    "branch_not_support_multiple_exits"                     => "分支上的节点暂不支持设置多个出口",
    "to_merge_node_cannot_to_other_nodes"                   => "该节点已经流向合并节点，不能再流向其它节点",
    "concurrent_not_support_after_merge"                    => "并发节点不支持流向合并节点【:node_name】之后的节点",
    "leave_office"                                          => "离职",
    "more_unhandle_user_tip"                                => "更多请至流程查看页面办理状态中查看",
    "superior_approval"                                     => "上级审批",
    "merge_has_concurrent"                                  => "该合并节点已存在对应的并发节点【:node_name】",
    "to_normal_cannot_last_node"                            => "【:node_name】节点流向【:node_name1】节点后，将不再是该分支上的最后一个节点，合并节点暂时只支持退回到该分支上的最后一个节点",
    "to_branch_must_be_same_merge"                          => "分支外的节点【:node_name】暂不支持退回到【:node_name1】分支节点上",
    "only_one_set_of_concurrent_and_merge_nodes"            => "暂时只支持一组分支和合并节点，当前流程已存在:type【:node_name】",
    "concurrent_node"                                       => "并发节点",
    "merge_node"                                            => "合并节点",
    "data_creator_is_wrong"                                 => "，数据外发失败，失败原因：外发数据创建人内容有误",
    "branch_sort_must_smaller"                              => "【:node_name】分支节点的序号必须小于合并节点的序号",
    "resignation"                                           => "已离职",
    "deletion"                                              => "已删除",
    "still_running_processes_finish_before_setting"         => "当前流程还存在【:number】条正在运行中的流程，普通流程和并发流程之间相互切换，会导致运行中的流程无法正常流转，请等待流程都结束后再进行设置",
    "no_handle"                                             => "未办理",
    "branch_node_can_not_end"                               => "并发分支上的节点除合并节点外暂不支持结束",
    "main_data_dependent_field_is_wrong"                    => "，数据外发失败，失败原因：主数据依赖字段指定的控件数据为空",
    "detail_data_dependent_field_is_wrong"                  => "，数据外发失败，失败原因：明细数据依赖字段指定的控件数据为空",
    "main_data_dependent_field_config_is_wrong"             => "，数据外发失败，失败原因：主数据依赖字段配置错误",
    "main_form_is_necessary"                                => "请指定主表单！",
    "save_main_form_first"                                  => "请先保存主表单！",
    "comma"                                                 =>"，",
];
