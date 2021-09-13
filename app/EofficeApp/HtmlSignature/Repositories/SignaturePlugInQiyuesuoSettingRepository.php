<?php

namespace App\EofficeApp\HtmlSignature\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\HtmlSignature\Entities\SignaturePlugInQiyuesuoSettingEntity;

/**
 * 签章插件契约锁签章设置
 *
 * @author yml
 *
 * @since  2020-07-22 创建
 */
class SignaturePlugInQiyuesuoSettingRepository extends BaseRepository
{
	public function __construct(SignaturePlugInQiyuesuoSettingEntity $entity)
	{
		parent::__construct($entity);
	}
}
