<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class ClubController extends EntityFormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_CLUB';
	protected $view_list = 'clubs';

	protected function preSaveHook(BaseDatabaseModel $model, array $validData = []): array
	{
		$postedData = $this->input->post->get('jform', [], 'array');
		$validData['create_team'] = empty($postedData['create_team']) ? 0 : 1;
		$validData['create_stadium'] = empty($postedData['create_stadium']) ? 0 : 1;

		return $validData;
	}
}
