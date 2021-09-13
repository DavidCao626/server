<?php
namespace App\EofficeApp\System\SystemPhrase\Controllers;

use App\EofficeApp\Base\Controller;
use Illuminate\Http\Request;
use App\EofficeApp\System\SystemPhrase\Requests\SystemPhraseRequest;
use App\EofficeApp\System\SystemPhrase\Services\SystemPhraseService;
/**
 * 系统短语控制器
 *
 */
class SystemPhraseController extends Controller
{
	/** @var object  */
	private $systemPhraseService;

	/**
	 * 
	 *
	 * @param \App\EofficeApp\System\SystemPhrase\Services\SystemPhraseService $systemPhraseService
	 *
	 */
	public function __construct(
            SystemPhraseService $systemPhraseService,
            SystemPhraseRequest $systemPhraseRequest,
            Request $request
            )
	{
		parent::__construct();

		$this->SystemPhraseService = $systemPhraseService;

        $this->request = $request;
        $this->formFilter($request, $systemPhraseRequest);
	}
	
	/**
	 * 获取常用短语列表
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return json 常用短语列表
	 *
	 */
	public function listCommonPhrase()
	{	
		return $this->returnResult($this->SystemPhraseService->listCommonPhrase($this->request->all(),$this->own['user_id']));
	}
	/**
	 * 新建常用短语
	 *
	 * @param \App\Http\Requests\SystemPhraseRequest $request
	 *
	 * @return json id
	 *
	 */
	public function addSystemPhrase()
	{
		return $this->returnResult($this->SystemPhraseService->addSystemPhrase($this->request->all(),$this->own['user_id']));
	}
	/**
	 * 编辑常用短语
	 *
	 * @param \App\Http\Requests\SystemPhraseRequest $request
	 * @param int $phraseId
	 *
	 * @return json 编辑结果
	 *
	 */
	public function editSystemPhrase($phraseId)
	{
		return $this->returnResult($this->SystemPhraseService->editSystemPhrase($this->request->all(), $phraseId,$this->own['user_id']));
	}
	
	/**
	 * 删除历史常用短语
	 *
	 * @param int $phraseId
	 *
	 * @return json 删除结果
	 *
	 */
	public function deleteSystemPhrase($phraseId)
	{
		return $this->returnResult($this->SystemPhraseService->deleteSystemPhrase($phraseId, $this->own['user_id']));
	}
	/**
	 * 获取常用短语详情
	 *
	 * @param int $phraseId
	 *
	 * @return json 常用短语详情
	 *
	 */
	public function showCommonPhrase($phraseId)
	{
		return $this->returnResult($this->SystemPhraseService->showCommonPhrase($phraseId));
	}
	
}
