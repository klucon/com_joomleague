<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Resultmatrix;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string,mixed> */
	public array $matrix = [];

	public function display($tpl = null): void
	{
		$this->matrix = $this->getModel()->getMatrix();
		$this->getDocument()->setTitle(isset($this->matrix['project'])
			? Text::sprintf('COM_JOOMLEAGUE_RESULTMATRIX_PAGE_TITLE', $this->matrix['project']->name)
			: Text::_('COM_JOOMLEAGUE_RESULTMATRIX_VIEW_TITLE'));
		parent::display($tpl);
	}
}
