<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\About;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
	/** @var array{component_version:string,joomla_version:string,profile_count:int} */
	public array $installation = [];

	public function display($tpl = null): void
	{
		$this->installation = $this->getModel()->getInstallation();
		$this->getDocument()->setTitle(Text::_('COM_JOOMLEAGUE_ABOUT_VIEW_TITLE'));
		parent::display($tpl);
	}
}
