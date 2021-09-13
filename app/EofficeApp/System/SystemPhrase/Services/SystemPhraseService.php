<?php
namespace App\EofficeApp\System\SystemPhrase\Services;

use App\EofficeApp\Base\BaseService;


class SystemPhraseService  extends BaseService
{
	/** @var object 常用短语资源库对象*/
	private $systemPhraseRepository;


	/**
	 * 注册相应的资源库对象
	 *
	 * @param \App\EofficeApp\PersonalSet\Repositories\SystemPhraseRepository $systemPhraseRepository
	 *
	 */
	public function __construct()
	{
		parent::__construct();

		$this->systemPhraseRepository	= 'App\EofficeApp\System\SystemPhrase\Repositories\SystemPhraseRepository';
	}


    // 个人常用短语设置
    /**
	 * 获取短语列表
	 *
	 * @param array $param
	 *
	 * @return array 获取短语列表
	 *
	 */
	public function listCommonPhrase($param, $currentUserId)
	{
        $param = $this->parseParams($param);
        $param['currentUserId'] = $currentUserId;
		return $this->response(app($this->systemPhraseRepository), 'getSystemPhraseCount', 'listCommonPhrase', $param);

	}
	/**
	 * 新建常用短语
	 *
	 * @param array $data
	 *
	 * @return array 短语id
	 *
	 */
	public function addSystemPhrase($data,$currentUserId)
	{
		$param = [];
		$dataphrase=app($this->systemPhraseRepository)->getsystemPhrase($param);
		$phraseData = [
			'user_id' => $currentUserId,
			'content' => $data['content'],
			'order_number' => $data['order_number'] ?? 0
		];
		if(!in_array($phraseData['content'],array_column($dataphrase,'content'))) {
			if($result = app($this->systemPhraseRepository)->insertData($phraseData)) {
				return ['phrase_id' => $result->phrase_id];
			}
		}else{
			return ['code' => ['0x015023', 'system']];
		}

		return ['code' => ['0x000003', 'common']];
	}
	/**
	 * 编辑常用短语
	 *
	 * @param array $data
	 * @param int $phraseId
	 *
	 * @return int 编辑结果
	 *
	 */
	public function editSystemPhrase($data, $phraseId,$currentUserId)
	{
		$phraseInfo = app($this->systemPhraseRepository)->getDetail($phraseId);
		$phraseData = [
			'content'		=> $data['content'],
			'order_number' => $data['order_number'] ?? 0
		];
		$result = app($this->systemPhraseRepository)->getUniqueCommonPhrase($data);
		if (empty($result) || ($result && $phraseId == $result['phrase_id'])) {
			if(app($this->systemPhraseRepository)->updateData($phraseData, ['phrase_id' => $phraseId])) {
				return true;
			}
			return ['code' => ['0x000003', 'common']];
		} else {
			return ['code' => ['0x015023', 'system']];
		}
	}
	/**
	 * 删除常用短语
	 *
	 * @param int $phraseId
	 *
	 * @return int 删除结果
	 *
	 */
	public function deleteSystemPhrase($phraseId,$currentUserId)
	{
		// 当为多条数据删除的时候要进行分割字符串（$phraseId传入的是一个数组）
		$phraseId = explode(',', $phraseId);
		foreach($phraseId as $key => $value) {
			$phraseInfo = app($this->systemPhraseRepository)->getDetail($value);
			if($phraseInfo) {
				// 	if($phraseInfo->user_id != $currentUserId) {
				// 	return ['code' => ['0x039012','personalset']];
				// }
				if(!app($this->systemPhraseRepository)->deleteById($value)) {
					return ['code' => ['0x000003', 'common']];
				}
			}
		}
		return true;
	}

}
