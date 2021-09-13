<?php
$routeConfig = [


	// 短语列表
	['system-phrase/list', 'listCommonPhrase'],
	// 添加常用短语
	['system-phrase/common-phrase', 'addSystemPhrase', 'post', [117]],
	// 修改常用短语
	['system-phrase/common-phrase/{phraseId}', 'editSystemPhrase', 'post', [117]],
	// 删除短语
	['system-phrase/common-phrase/{phraseId}', 'deleteSystemPhrase', 'delete', [117]],
	// 每条短语详情
	['system-phrase/common-phrase/{phraseId}', 'showCommonPhrase'],

];