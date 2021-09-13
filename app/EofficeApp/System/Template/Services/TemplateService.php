<?php
namespace App\EofficeApp\System\Template\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\System\Template\Repositories\TemplateRepository;
/**
 * @公告模板服务类
 *
 * @author 李志军
 */
class TemplateService extends BaseService
{
	private $templateRepository;

	public function __construct(TemplateRepository $templateRepository)
	{
		parent::__construct();
		$this->templateRepository = $templateRepository;
	}
	public function listTemplate($param)
	{
		return $this->response($this->templateRepository, 'getTemplateCount', 'listTemplate', $this->parseParams($param));
	}

	public function addTemplate($data, $uploadFile)
	{
		$templateData = [
			'template_subject'		=> $data['template_subject'],
			'template_description'	=> $this->defaultValue('template_description', $data, ''),
			'template_content'		=> $this->defaultValue('template_content', $data, '')
		];

		if ($result = $this->templateRepository->insertData($templateData)) {
			return ['template_id'  => $result->template_id];
		}

		return ['code' => ['0x000003', 'common']];
	}

	public function editTemplate($data, $uploadFile, $templateId)
	{
		if (!$templateId) {
			return ['code' => ['0x045002', 'template']];
		}
		$templateData = [
			'template_subject'		=> $data['template_subject'],
			'template_description'	=> $this->defaultValue('template_description', $data, ''),
			'template_content'		=> $this->defaultValue('template_content', $data, '')
		];

		if ($this->templateRepository->updateData($templateData, ['template_id' => $templateId])) {
			return true;
		}

		return ['code' => ['0x000003', 'common']];
	}

	public function deleteTemplate($templateId)
	{
		if(!$templateId) {
			return ['code' => ['0x045002', 'template']];
		}

		if ($this->templateRepository->deleteById(explode(',', $templateId))) {
			return true;
		}

		return ['code' => ['0x000003', 'common']];
	}

	private function defaultValue($key, $data, $default)
	{
		return isset($data[$key]) ? $data[$key] : $default;
	}
}
