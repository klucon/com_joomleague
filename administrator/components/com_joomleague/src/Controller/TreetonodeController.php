<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;

final class TreetonodeController extends EntityFormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_TREETONODE';
	protected $view_list = 'treetonodes';

	protected function getRedirectToListAppend()
	{
		$append = parent::getRedirectToListAppend();
		$form = $this->input->post->get('jform', [], 'array');
		$treeId = (int) ($form['treeto_id'] ?? $this->input->getInt('treeto_id'));

		return $treeId > 0 ? $append . '&treeto_id=' . $treeId : $append;
	}
}
