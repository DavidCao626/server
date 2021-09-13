<?php
	$routeConfig = [
		//获取所有提醒方式(自定义提醒有用到)
		['remind/reminds', 'getReminds'],
		['remind/send', 'sendReminds', 'post'],
		['remind/get-reminds', 'getAllReminds', 'post'],
		//获取提醒设置中间列表()
		['remind/system-reminds', 'getRemindsMiddleList'],
		//获取提醒设置中间列表
		['remind/system-reminds/getRemindsType', 'getRemindsTypeMobile'],
		//选择器获取设置方式(选择器有用到, 不控制)
		['remind/system-reminds/getSystemRemindsList', 'getSystemRemindsList'],
		//选择器保存设置方式
		['remind/system-reminds/postSystemReminds', 'postSystemReminds','put', [500]],
		//获取提醒设置数据
		['remind/system-reminds/{remindsId}', 'getRemindsInfo', [500]],
		//获取提醒设置数据
		['remind/system-reminds/mark/{remindMark}', 'getRemindByMark'],
		//编辑提醒设置
		['remind/system-reminds/{remindsId}', 'modifyReminds', 'put', [500]],
	];